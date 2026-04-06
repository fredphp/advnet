<?php

namespace app\common\library;

use think\Db;
use think\Log;
use think\Exception;
use think\Cache;
use app\common\model\CoinAccount;
use app\common\model\AdIncomeLog;
use app\common\model\AdIncomeLogSplit;
use app\common\model\AdRedPacket;
use app\common\model\AdRedPacketSplit;
use app\common\model\CoinLog;

/**
 * 广告收益服务类
 *
 * 核心流程：
 *   广告展示 → 回调接口 → 计算抽成 → 写入 ad_freeze_balance → 记录日志
 *   定时任务 → 查询 ad_freeze_balance → 生成红包 → 清空冻结余额
 *   用户领取红包 → 金币进入 balance
 *
 * 安全机制：
 *   - 乐观锁防止并发更新
 *   - 分布式锁防重复操作
 *   - transaction_id 防重复回调
 *   - 每日上限控制
 */
class AdIncomeService
{
    const CACHE_PREFIX = 'coin:';
    const LOCK_PREFIX = 'lock:coin:';
    const AD_LOCK_PREFIX = 'lock:ad:';

    /**
     * @var array 配置缓存
     */
    protected $config = null;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * 加载广告配置
     */
    protected function loadConfig()
    {
        $this->config = [
            'enabled' => SystemConfigService::get('ad.ad_income_enabled', null, 1),
            'platform_rate' => SystemConfigService::get('ad.platform_rate', null, 0.30),
            'settle_interval' => SystemConfigService::get('ad.settle_interval', null, 30),
            'min_redpacket_amount' => SystemConfigService::get('ad.min_redpacket_amount', null, 100),
            'redpacket_expire_hours' => SystemConfigService::get('ad.redpacket_expire_hours', null, 48),
            'daily_reward_limit' => SystemConfigService::get('ad.daily_reward_limit', null, 50000),
            'reward_per_feed' => SystemConfigService::get('ad.reward_per_feed', null, 50),
            'reward_per_video' => SystemConfigService::get('ad.reward_per_video', null, 200),
            'callback_secret' => SystemConfigService::get('ad.callback_secret', null, ''),
            'redpacket_threshold' => SystemConfigService::get('ad.redpacket_threshold', null, 1000),
            'feed_reward_threshold' => SystemConfigService::get('ad.feed_reward_threshold', null, 5),
            'video_reward_threshold' => SystemConfigService::get('ad.video_reward_threshold', null, 1),
        ];
    }

    protected function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 处理广告回调
     *
     * @param int $userId 用户ID
     * @param array $params 广告回调参数
     * @return array
     */
    public function handleAdCallback($userId, array $params)
    {
        $result = [
            'success' => false,
            'message' => '',
            'log_id' => 0,
            'user_amount_coin' => 0,
        ];

        $userId = (int)$userId;

        if ($userId <= 0) {
            $result['message'] = '用户ID无效';
            return $result;
        }

        // 检查是否启用广告收益
        if (!$this->getConfig('enabled', 1)) {
            $result['message'] = '广告收益功能未启用';
            return $result;
        }

        $adType = $params['ad_type'] ?? 'feed';
        $adpid = $params['adpid'] ?? '';
        $adProvider = $params['ad_provider'] ?? 'uniad';
        $adSource = $params['ad_source'] ?? 'redbag_page';
        $transactionId = $params['transaction_id'] ?? '';

        // 防重复回调：同一 transaction_id 只处理一次（跨分表查找）
        if (!empty($transactionId)) {
            $existing = AdIncomeLogSplit::findByTransactionId($transactionId);
            if ($existing) {
                $result['message'] = '重复回调';
                $result['log_id'] = $existing['id'];
                $result['success'] = true; // 重复回调也算成功（幂等）
                return $result;
            }
        }

        // 计算奖励金币数（根据广告类型）
        $rewardCoin = 0;
        // ★ 优先使用直接指定的金币数（阈值批量奖励场景）
        if (isset($params['reward_coin']) && (int)$params['reward_coin'] > 0) {
            $rewardCoin = (int)$params['reward_coin'];
        } elseif (isset($params['amount']) && floatval($params['amount']) > 0) {
            // 如果广告联盟返回了真实金额，按汇率转换
            $amountYuan = floatval($params['amount']);
            $rewardCoin = SystemConfigService::cashToCoin($amountYuan);
        } else {
            // 否则使用配置的固定奖励
            $rewardCoin = $adType === 'reward'
                ? (int)$this->getConfig('reward_per_video', 200)
                : (int)$this->getConfig('reward_per_feed', 50);
        }

        if ($rewardCoin <= 0) {
            $result['message'] = '奖励金额为0';
            return $result;
        }

        // 检查每日上限（跨分表统计）
        $todayIncome = AdIncomeLogSplit::getTodayIncome($userId);
        $dailyLimit = (int)$this->getConfig('daily_reward_limit', 50000);
        if ($todayIncome + $rewardCoin > $dailyLimit) {
            $rewardCoin = max(0, $dailyLimit - $todayIncome);
            if ($rewardCoin <= 0) {
                $result['message'] = '今日广告收益已达上限';
                return $result;
            }
        }

        // 获取分布式锁
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);

        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }

        try {
            Db::startTrans();

            // 1. 计算抽成
            // ★ 只有真实广告（有CPM收益，来自DCloud等服务端回调）才扣除平台分成
            // H5模拟广告没有真实广告成本，不扣分成，用户获得完整奖励
            $hasRealAmount = isset($params['amount']) && floatval($params['amount']) > 0;
            $platformRate = $hasRealAmount ? floatval($this->getConfig('platform_rate', 0.30)) : 0;
            $platformCoin = (int)round($rewardCoin * $platformRate);
            $userCoin = $rewardCoin - $platformCoin;

            if ($userCoin <= 0) {
                Db::rollback();
                $result['message'] = '用户获得金币为0';
                return $result;
            }

            // 2. 计算对应金额（元）
            $coinRate = SystemConfigService::getCoinRate();
            $amountYuan = $coinRate > 0 ? round($rewardCoin / $coinRate, 4) : 0;
            $platformAmountYuan = $coinRate > 0 ? round($platformCoin / $coinRate, 4) : 0;
            $userAmountYuan = $coinRate > 0 ? round($userCoin / $coinRate, 4) : 0;

            // 3. 写入广告收益日志
            $logData = [
                'user_id' => $userId,
                'ad_type' => $adType,
                'adpid' => $adpid,
                'ad_provider' => $adProvider,
                'ad_source' => $adSource,
                'amount' => $amountYuan,
                'amount_coin' => $rewardCoin,
                'platform_rate' => $platformRate,
                'platform_amount' => $platformAmountYuan,
                'platform_amount_coin' => $platformCoin,
                'user_amount' => $userAmountYuan,
                'user_amount_coin' => $userCoin,
                'status' => AdIncomeLog::STATUS_CONFIRMED,  // 直接确认
                'transaction_id' => $transactionId,
                'ip' => $params['ip'] ?? '',
                'user_agent' => $params['user_agent'] ?? '',
                'device_id' => $params['device_id'] ?? '',
                'remark' => $params['remark'] ?? '',
            ];

            // ★ 使用分表模型插入收益日志
            $splitResult = AdIncomeLogSplit::createLog($logData);
            if (!$splitResult['success']) {
                throw new Exception($splitResult['message'] ?? '收益日志写入失败');
            }
            $logId = $splitResult['id'];

            // 4. 更新 advn_coin_account：增加 ad_freeze_balance 和 total_ad_income
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();

            if (!$account) {
                // 自动创建账户
                $account = $this->createCoinAccount($userId);
            }

            // 使用乐观锁更新
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'ad_freeze_balance' => Db::raw('ad_freeze_balance + ' . $userCoin),
                    'total_ad_income' => Db::raw('total_ad_income + ' . $rewardCoin),
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);

            if ($affected === 0) {
                throw new Exception('账户更新失败，请重试');
            }

            // 5. 更新 today_earn（当日收益）
            $today = date('Y-m-d');
            if ($account['today_earn_date'] != $today) {
                Db::name('coin_account')
                    ->where('user_id', $userId)
                    ->update([
                        'today_earn' => $userCoin,
                        'today_earn_date' => $today,
                    ]);
            } else {
                Db::name('coin_account')
                    ->where('user_id', $userId)
                    ->update([
                        'today_earn' => Db::raw('today_earn + ' . $userCoin),
                    ]);
            }

            Db::commit();

            $result['success'] = true;
            $result['message'] = '广告收益已记录';
            $result['log_id'] = $logId;
            $result['user_amount_coin'] = $userCoin;
            $result['platform_amount_coin'] = $platformCoin;
            $result['total_reward_coin'] = $rewardCoin;

        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('AdCallback error: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }

        return $result;
    }

    /**
     * ★ 已废弃：定时任务旧版结算（直接消费 freeze_balance 创建真实红包）
     *
     * 新流程使用 checkAndAutoSettle() 创建通知红包（不消费 freeze_balance）
     * 用户通过 claimFreezeBalance() 主动领取时才消费 freeze_balance
     *
     * 此方法保留仅为兼容旧 cron 任务，实际不再执行任何操作。
     *
     * @param int $batchSize 每批处理数量
     * @return array 处理结果（始终返回空结果）
     * @deprecated 请使用 checkAndAutoSettle() + claimFreezeBalance() 新流程
     */
    public function settleToRedPacket($batchSize = 100)
    {
        Log::info('settleToRedPacket: 旧版批量结算已废弃，新流程使用 checkAndAutoSettle + claimFreezeBalance');
        return [
            'total_users' => 0,
            'packets_created' => 0,
            'total_coin' => 0,
            'errors' => ['旧版批量结算已废弃，不再执行'],
        ];
    }

    /**
     * 领取广告红包
     *
     * ★ 新逻辑：区分通知红包和真实红包
     * - 通知红包（source=freeze_notify, amount=0）→ 转发到 claimFreezeBalance
     * - 真实红包（source=ad_income, amount>0）→ 直接发放金币到 balance
     *
     * @param int $userId 用户ID
     * @param int $packetId 红包ID
     * @return array
     */
    public function claimRedPacket($userId, $packetId)
    {
        $result = [
            'success' => false,
            'message' => '',
            'amount' => 0,
            'balance' => 0,
        ];

        $userId = (int)$userId;
        $packetId = (int)$packetId;

        if ($userId <= 0 || $packetId <= 0) {
            $result['message'] = '参数无效';
            return $result;
        }

        // 获取分布式锁
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);

        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }

        try {
            Db::startTrans();

            // ★ 查找红包（跨分表查找）
            $packet = AdRedPacketSplit::findById($packetId);

            if (!$packet) {
                $this->error('红包不存在');
            }

            if ($packet['status'] == AdRedPacket::STATUS_CLAIMED) {
                $result['message'] = '红包已被领取';
                return $result;
            }

            if ($packet['status'] == AdRedPacket::STATUS_EXPIRED) {
                $result['message'] = '红包已过期';
                return $result;
            }

            if ($packet['status'] != AdRedPacket::STATUS_UNCLAIMED) {
                $result['message'] = '红包状态异常';
                return $result;
            }

            // 检查过期
            if ($packet['expire_time'] > 0 && time() > $packet['expire_time']) {
                if (isset($packet['_from_table'])) {
                    Db::name($packet['_from_table'])->where('id', $packetId)
                        ->update(['status' => AdRedPacket::STATUS_EXPIRED, 'updatetime' => time()]);
                }
                $result['message'] = '红包已过期';
                return $result;
            }

            // ★ 判断红包类型：通知红包 → 走 claimFreezeBalance 流程
            $source = $packet['source'] ?? '';
            $amount = (int)round($packet['amount']);

            if ($source === 'freeze_notify' || $amount <= 0) {
                Db::rollback();
                $this->releaseLock($lockKey);

                // ★ 通知红包：走 claimFreezeBalance 流程（ad_freeze_balance → balance）
                Log::info("ClaimRedPacket: 用户{$userId}红包{$packetId}为通知红包，转发到claimFreezeBalance");
                $freezeResult = $this->claimFreezeBalance($userId);
                return $freezeResult;
            }

            // ★ 真实红包：直接发放金币到 balance
            // 更新红包状态（跨分表更新）
            if (isset($packet['_from_table'])) {
                Db::name($packet['_from_table'])->where('id', $packetId)
                    ->update([
                        'status' => AdRedPacket::STATUS_CLAIMED,
                        'claim_time' => time(),
                        'updatetime' => time(),
                    ]);
            }

            // 通过 CoinService 发放金币到 balance
            Db::commit();

            $this->releaseLock($lockKey);

            // 使用 CoinService 添加金币（独立事务）
            $coinService = new CoinService();
            $coinResult = $coinService->addCoin(
                $userId,
                $amount,
                'ad_red_packet',
                'ad_red_packet',
                $packetId,
                '领取广告红包'
            );

            if ($coinResult['success']) {
                $result['success'] = true;
                $result['amount'] = $amount;
                $result['balance'] = $coinResult['balance'];
                $result['message'] = '领取成功';
            } else {
                // 金币发放失败，回滚红包状态
                if (isset($packet['_from_table'])) {
                    Db::name($packet['_from_table'])->where('id', $packetId)
                        ->update(['status' => AdRedPacket::STATUS_UNCLAIMED, 'claim_time' => null, 'updatetime' => time()]);
                }
                $result['message'] = $coinResult['message'] ?? '发放失败';
            }

        } catch (\Throwable $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('ClaimRedPacket error: ' . $e->getMessage());
        } finally {
            if ($lock) {
                $this->releaseLock($lockKey);
            }
        }

        return $result;
    }

    /**
     * 一键领取所有未领取的广告红包
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function claimAllRedPackets($userId)
    {
        $result = [
            'success' => true,
            'message' => '',
            'total_amount' => 0,
            'claim_count' => 0,
            'balance' => 0,
        ];

        $userId = (int)$userId;

        // 查找所有未领取红包
        $packets = AdRedPacket::where('user_id', $userId)
            ->where('status', AdRedPacket::STATUS_UNCLAIMED)
            ->where('expire_time', '>', time())
            ->order('id', 'asc')
            ->select();

        if (empty($packets)) {
            $result['message'] = '没有可领取的红包';
            return $result;
        }

        $totalAmount = 0;
        $claimCount = 0;

        foreach ($packets as $packet) {
            $claimResult = $this->claimRedPacket($userId, $packet['id']);
            if ($claimResult['success']) {
                $totalAmount += $claimResult['amount'];
                $claimCount++;
                $result['balance'] = $claimResult['balance'];
            }
        }

        $result['total_amount'] = $totalAmount;
        $result['claim_count'] = $claimCount;
        $result['message'] = $claimCount > 0 ? "成功领取 {$claimCount} 个红包，共 {$totalAmount} 金币" : '没有可领取的红包';

        return $result;
    }

    /**
     * 获取用户的广告收益概览
     *
     * @param int $userId
     * @return array
     */
    public function getAdIncomeOverview($userId)
    {
        $userId = (int)$userId;

        // 获取账户信息
        $account = Db::name('coin_account')->where('user_id', $userId)->find();

        // ★ 获取未领取红包（跨分表统计）
        $unclaimedSummary = AdRedPacketSplit::getUnclaimedSummary($userId);

        // 获取今日广告收益（跨分表统计）
        $todayIncome = AdIncomeLogSplit::getTodayIncome($userId);

        // 获取累计广告收益
        $totalAdIncome = $account ? (int)round($account['total_ad_income']) : 0;

        // 获取冻结余额
        $freezeBalance = $account ? (int)round($account['ad_freeze_balance']) : 0;

        return [
            'today_income' => $todayIncome,
            'total_ad_income' => $totalAdIncome,
            'ad_freeze_balance' => $freezeBalance,
            'unclaimed_packet_count' => $unclaimedSummary['count'],
            'unclaimed_packet_amount' => $unclaimedSummary['total_amount'],
        ];
    }

    /**
     * 检查用户冻结余额是否达到红包基数额度，达到则自动结算为红包
     *
     * ★ 核心逻辑：广告回调成功后 / 前端定时调用时触发
     * ★ 当 ad_freeze_balance >= redpacket_threshold 时生成红包
     * ★ 兜底：即使没有 CONFIRMED 的收益记录，也直接用 freeze_balance 生成红包
     *
     * @param int $userId 用户ID
     * @return array ['success' => bool, 'amount' => int, 'message' => string]
     */
    public function checkAndAutoSettle($userId)
    {
        $result = [
            'success' => false,
            'amount' => 0,
            'message' => '',
        ];

        $userId = (int)$userId;
        if ($userId <= 0) {
            return $result;
        }

        try {
            // 获取红包基数额度配置
            $threshold = (int)$this->getConfig('redpacket_threshold', 1000);
            if ($threshold <= 0) {
                return $result;
            }

            // 获取最小红包金额和过期时间配置
            $expireHours = (int)$this->getConfig('redpacket_expire_hours', 48);

            // 查询用户当前冻结余额
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();

            if (!$account) {
                return $result;
            }

            $freezeBalance = (int)round($account['ad_freeze_balance']);

            // 未达到基数额度 → 不自动发红包
            if ($freezeBalance < $threshold) {
                return $result;
            }

            // ★ 去重检查：如果该用户已有未领取的广告红包通知，不重复创建
            $unclaimed = AdRedPacketSplit::getUnclaimedSummary($userId);
            if ($unclaimed['count'] > 0) {
                return $result;
            }

            // ★ 新流程：只创建通知红包，不消费 ad_freeze_balance
            // 红包 amount 设为 0（仅作通知），实际金币仍在 ad_freeze_balance 中
            // 用户点击红包 → 查看当前 ad_freeze_balance → 观看激励视频 → claimFreezeBalance()
            $lockKey = self::LOCK_PREFIX . 'auto:' . $userId;
            $lock = $this->getLock($lockKey, 10);

            if (!$lock) {
                Log::warning("AutoSettle: 用户{$userId}获取锁失败");
                return $result;
            }

            try {
                Db::startTrans();

                // 再次确认没有未领取红包（防并发）
                $freshUnclaimed = AdRedPacketSplit::getUnclaimedSummary($userId);
                if ($freshUnclaimed['count'] > 0) {
                    Db::rollback();
                    return $result;
                }

                $expireTime = time() + $expireHours * 3600;

                // ★ 创建通知红包（amount=0 仅作通知标识）
                $packetData = [
                    'user_id' => $userId,
                    'amount' => 0,  // ★ 通知红包不携带金额，实际金币在 ad_freeze_balance
                    'source' => 'freeze_notify',  // ★ 新来源标识
                    'source_ids' => '',
                    'status' => AdRedPacket::STATUS_UNCLAIMED,
                    'expire_time' => $expireTime,
                ];
                $packetResult = AdRedPacketSplit::createPacket($packetData);
                if (!$packetResult['success']) {
                    throw new Exception($packetResult['message'] ?? '红包通知创建失败');
                }

                Db::commit();

                $result['success'] = true;
                $result['amount'] = $freezeBalance;  // 返回当前 freeze 余额供前端提示
                $result['message'] = '待释放金币已达到' . $freezeBalance . '，请领取';

                Log::info("AutoSettle: 用户{$userId}冻结余额{$freezeBalance}达到阈值{$threshold}，发送通知红包(不消费freeze)");

            } catch (Exception $e) {
                Db::rollback();
                Log::error("AutoSettle 用户{$userId}失败: " . $e->getMessage());
            } finally {
                $this->releaseLock($lockKey);
            }

        } catch (\Throwable $e) {
            Log::error("AutoSettle 检查用户{$userId}异常: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * 确保 ad_view_counter 表存在（自动建表）
     *
     * 首次使用阈值奖励功能时，若表不存在则自动创建。
     * 同时检查并插入 advn_config 中缺失的阈值配置项。
     *
     * @return bool 表是否可用
     */
    protected function ensureAdViewCounterTable()
    {
        static $ensured = false;
        if ($ensured) {
            return true;
        }

        try {
            // 检查表是否存在
            $prefix = \think\Config::get('database.prefix');
            $fullTable = $prefix . 'ad_view_counter';
            $rows = Db::query("SHOW TABLES LIKE '{$fullTable}'");

            if (empty($rows)) {
                // 表不存在 → 自动创建
                $sql = "CREATE TABLE IF NOT EXISTS `{$fullTable}` (
                    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
                    `ad_type` VARCHAR(20) NOT NULL DEFAULT 'feed' COMMENT '广告类型: feed=信息流, reward=激励视频',
                    `view_date` DATE NOT NULL COMMENT '浏览日期',
                    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前轮已浏览次数',
                    `reward_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日已领奖次数',
                    `createtime` INT UNSIGNED DEFAULT 0,
                    `updatetime` INT UNSIGNED DEFAULT 0,
                    UNIQUE KEY `uk_user_type_date` (`user_id`, `ad_type`, `view_date`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_view_date` (`view_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告浏览计数器（按天自动重置）'";
                Db::execute($sql);
                Log::info('ensureAdViewCounterTable: 自动创建 advn_ad_view_counter 表成功');
            }

            // 确保配置项存在
            $this->ensureConfigExists('feed_reward_threshold', 'ad', '信息流奖励阈值（次）', 'number', '5', '用户浏览多少次信息流广告后发放一次奖励，0=每次都发', 310);
            $this->ensureConfigExists('video_reward_threshold', 'ad', '激励视频奖励阈值（次）', 'number', '1', '用户观看多少次激励视频后发放一次奖励，0=每次都发，1=每次都发', 311);

            // ★ 修正旧默认值：如果 video_reward_threshold 仍为旧默认值 3，自动更新为 1
            try {
                Db::name('config')->where('name', 'video_reward_threshold')->where('`group`', 'ad')->where('value', '3')->update(['value' => '1', 'updatetime' => time()]);
            } catch (\Throwable $e) {}

            $ensured = true;
            return true;
        } catch (\Throwable $e) {
            Log::error('ensureAdViewCounterTable 失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 确保单条配置存在（不存在则插入，存在则跳过）
     */
    protected function ensureConfigExists($name, $group, $title, $type, $value, $remark, $weigh)
    {
        try {
            $count = Db::name('config')->where('name', $name)->where('`group`', $group)->count();
            if ($count == 0) {
                Db::name('config')->insert([
                    'name'     => $name,
                    '`group`' => $group,
                    'title'    => $title,
                    'type'     => $type,
                    'value'    => $value,
                    'remark'   => $remark,
                    'weigh'    => $weigh,
                    'status'   => 'normal',
                    'updatetime'=> time(),
                    'createtime'=> time(),
                ]);
            }
        } catch (\Throwable $e) {
            // 配置插入失败不影响主流程
        }
    }

    /**
     * 记录广告浏览并检查阈值奖励
     *
     * 流程：浏览+1 → 检查是否达到阈值 → 达到则触发奖励写入 ad_freeze_balance
     *
     * @param int $userId 用户ID
     * @param string $adType 广告类型: feed=信息流, reward=激励视频
     * @param array $params 广告回调参数（触发奖励时传递给 handleAdCallback）
     * @return array ['view_count'=>int, 'threshold'=>int, 'reward_given'=>bool, 'amount'=>int, 'message'=>string]
     */
    public function recordAdViewAndCheckReward($userId, $adType, array $params = [])
    {
        $result = [
            'view_count'     => 0,
            'threshold'      => 0,
            'reward_given'   => false,
            'amount'         => 0,
            'message'        => '',
            'total_today_views'   => 0,
            'total_today_rewards' => 0,
        ];

        $userId = (int)$userId;
        if ($userId <= 0) {
            $result['message'] = '用户ID无效';
            return $result;
        }

        // ★ 自动建表 + 自动修正旧配置值
        if (!$this->ensureAdViewCounterTable()) {
            $result['message'] = '系统初始化中，请稍后重试';
            return $result;
        }

        // ★ 刷新可能被 auto-migration 更新的配置（ensureAdViewCounterTable 会将旧的 3 更新为 1）
        $this->config['video_reward_threshold'] = (int)SystemConfigService::get('ad.video_reward_threshold', null, 1);

        // 获取阈值配置
        $threshold = $adType === 'reward'
            ? (int)$this->getConfig('video_reward_threshold', 1)
            : (int)$this->getConfig('feed_reward_threshold', 5);

        $result['threshold'] = $threshold;

        // 阈值为0 → 每次都发（兼容旧逻辑，直接走回调）
        if ($threshold <= 0) {
            // ★ 即时模式也显式设置 reward_coin，确保 handleAdCallback 使用正确金额
            $rewardPerView = $adType === 'reward'
                ? (int)$this->getConfig('reward_per_video', 200)
                : (int)$this->getConfig('reward_per_feed', 50);
            if ($rewardPerView > 0) {
                $params['reward_coin'] = $rewardPerView;
            }

            $callbackResult = $this->handleAdCallback($userId, $params);
            if ($callbackResult['success']) {
                $result['reward_given'] = true;
                $result['amount'] = $callbackResult['user_amount_coin'];
                $result['view_count'] = 0;
                $result['message'] = '即时奖励模式';
            } else {
                $result['message'] = $callbackResult['message'] ?? '处理失败';
            }
            return $result;
        }

        $today = date('Y-m-d');
        $now = time();

        // 获取分布式锁
        $lockKey = self::AD_LOCK_PREFIX . 'view:' . $userId . ':' . $adType;
        $lock = $this->getLock($lockKey, 5);
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }

        try {
            // 查找或创建今日计数记录
            $exists = Db::name('ad_view_counter')
                ->where('user_id', $userId)
                ->where('ad_type', $adType)
                ->where('view_date', $today)
                ->find();

            if ($exists) {
                $newCount = (int)$exists['view_count'] + 1;
                Db::name('ad_view_counter')
                    ->where('id', $exists['id'])
                    ->update(['view_count' => $newCount, 'updatetime' => $now]);
                $result['view_count'] = $newCount;
                $result['total_today_views'] = $newCount;
                $result['total_today_rewards'] = (int)$exists['reward_count'];
            } else {
                Db::name('ad_view_counter')->insert([
                    'user_id'     => $userId,
                    'ad_type'     => $adType,
                    'view_date'   => $today,
                    'view_count'  => 1,
                    'reward_count' => 0,
                    'createtime'  => $now,
                    'updatetime'  => $now,
                ]);
                $result['view_count'] = 1;
                $result['total_today_views'] = 1;
                $result['total_today_rewards'] = 0;
            }

            // ★ 检查是否达到阈值
            if ($result['view_count'] >= $threshold) {
                // ★ 计算批量奖励金额 = 阈值次数 × 单次奖励
                // 例：threshold=5, reward_per_feed=50 → 批量奖励=250
                $rewardPerView = $adType === 'reward'
                    ? (int)$this->getConfig('reward_per_video', 200)
                    : (int)$this->getConfig('reward_per_feed', 50);
                $batchReward = $threshold * $rewardPerView;
                $params['reward_coin'] = $batchReward;

                // 达到阈值 → 触发广告回调（写入 ad_income_log + ad_freeze_balance）
                $callbackResult = $this->handleAdCallback($userId, $params);

                if ($callbackResult['success']) {
                    $result['reward_given'] = true;
                    $result['amount'] = $callbackResult['user_amount_coin'];
                    $result['message'] = '恭喜获得 ' . $callbackResult['user_amount_coin'] . ' 金币，已存入待释放余额';

                    // 重置浏览计数，累加领奖次数
                    Db::name('ad_view_counter')
                        ->where('user_id', $userId)
                        ->where('ad_type', $adType)
                        ->where('view_date', $today)
                        ->update([
                            'view_count'   => 0,
                            'reward_count' => Db::raw('reward_count + 1'),
                            'updatetime'   => $now,
                        ]);

                    $result['view_count'] = 0; // 已重置
                    $result['total_today_rewards']++;
                } else {
                    $result['message'] = $callbackResult['message'] ?? '奖励发放失败';
                }
            } else {
                $remaining = $threshold - $result['view_count'];
                $result['message'] = '已记录浏览，再浏览 ' . $remaining . ' 次可获得奖励';
            }

        } catch (\Throwable $e) {
            $result['message'] = '系统异常';
            Log::error('RecordAdView error userId=' . $userId . ': ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }

        return $result;
    }

    /**
     * 获取用户当日广告浏览进度
     *
     * @param int $userId
     * @return array ['feed' => [...], 'reward' => [...]]
     */
    public function getAdViewProgress($userId)
    {
        $userId = (int)$userId;
        $today = date('Y-m-d');

        $feedThreshold  = (int)$this->getConfig('feed_reward_threshold', 5);
        $videoThreshold = (int)$this->getConfig('video_reward_threshold', 1);
        $feedReward     = (int)$this->getConfig('reward_per_feed', 50);
        $videoReward    = (int)$this->getConfig('reward_per_video', 200);

        // ★ 确保表存在（防止首次调用时表未创建导致 overview 接口异常）
        $counters = [];
        try {
            $this->ensureAdViewCounterTable();
            // 批量查询（一条SQL）
            $counters = Db::name('ad_view_counter')
                ->where('user_id', $userId)
                ->where('view_date', $today)
                ->select();
        } catch (\Throwable $e) {
            Log::warning('getAdViewProgress 查询失败: ' . $e->getMessage());
        }

        $feedData  = ['view_count' => 0, 'reward_count' => 0];
        $videoData = ['view_count' => 0, 'reward_count' => 0];

        foreach ($counters as $c) {
            if ($c['ad_type'] === 'feed') {
                $feedData['view_count']   = (int)$c['view_count'];
                $feedData['reward_count']  = (int)$c['reward_count'];
            } elseif ($c['ad_type'] === 'reward') {
                $videoData['view_count']  = (int)$c['view_count'];
                $videoData['reward_count'] = (int)$c['reward_count'];
            }
        }

        return [
            'feed' => [
                'view_count'       => $feedData['view_count'],
                'threshold'        => $feedThreshold,
                'remaining'        => max(0, $feedThreshold - $feedData['view_count']),
                'reward_count'     => $feedData['reward_count'],
                'reward_coin'      => $feedReward,
                'progress_percent' => $feedThreshold > 0 ? min(100, round(($feedData['view_count'] / $feedThreshold) * 100)) : 0,
            ],
            'reward' => [
                'view_count'       => $videoData['view_count'],
                'threshold'        => $videoThreshold,
                'remaining'        => max(0, $videoThreshold - $videoData['view_count']),
                'reward_count'     => $videoData['reward_count'],
                'reward_coin'      => $videoReward,
                'progress_percent' => $videoThreshold > 0 ? min(100, round(($videoData['view_count'] / $videoThreshold) * 100)) : 0,
            ],
        ];
    }

    /**
     * 领取待释放金币（空闲钱包）
     *
     * 将 ad_freeze_balance 转移到 balance
     * 前端调用时机：用户观看激励视频后
     *
     * @param int $userId
     * @return array
     */
    public function claimFreezeBalance($userId)
    {
        $result = [
            'success' => false,
            'message' => '',
            'amount' => 0,
            'balance' => 0,
        ];

        $userId = (int)$userId;
        if ($userId <= 0) {
            $result['message'] = '用户ID无效';
            return $result;
        }

        // 获取分布式锁
        $lockKey = self::AD_LOCK_PREFIX . 'freeze_claim:' . $userId;
        $lock = $this->getLock($lockKey, 10);

        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }

        try {
            Db::startTrans();

            // 锁定用户账户行
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();

            if (!$account) {
                Db::rollback();
                $result['message'] = '账户不存在';
                return $result;
            }

            $amount = (int)round($account['ad_freeze_balance']);

            if ($amount <= 0) {
                Db::rollback();
                $result['message'] = '暂无可领取的待释放金币';
                return $result;
            }

            // 清空 ad_freeze_balance，乐观锁更新
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'ad_freeze_balance' => 0,
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);

            if ($affected === 0) {
                throw new Exception('账户更新失败，请重试');
            }

            Db::commit();

            // 通过 CoinService 将金币加入 balance（独立事务）
            $coinService = new CoinService();
            $coinResult = $coinService->addCoin(
                $userId,
                $amount,
                'freeze_balance_claim',
                'freeze_balance_claim',
                0,
                '领取待释放金币(空闲钱包→可提现金币)'
            );

            if ($coinResult['success']) {
                $result['success'] = true;
                $result['amount'] = $amount;
                $result['balance'] = $coinResult['balance'];
                $result['message'] = '领取成功';

                // ★ 同步标记所有未领取的通知红包为已领取
                try {
                    $markedCount = AdRedPacketSplit::markAllClaimed($userId);
                    if ($markedCount > 0) {
                        Log::info("ClaimFreezeBalance: 用户{$userId}同步标记{$markedCount}个通知红包为已领取");
                    }
                } catch (\Throwable $e) {
                    Log::warning("ClaimFreezeBalance: 标记通知红包失败: " . $e->getMessage());
                }

                // ★ 完整记录链：标记相关 ad_income_log 为已释放状态
                // 保持 ad_income_log 的状态流转：PENDING → CONFIRMED → RELEASED
                try {
                    $releasedCount = AdIncomeLogSplit::batchUpdateStatus(
                        $userId,
                        [AdIncomeLog::STATUS_CONFIRMED],
                        AdIncomeLog::STATUS_RELEASED
                    );
                    if ($releasedCount > 0) {
                        Log::info("ClaimFreezeBalance: 用户{$userId}标记{$releasedCount}条广告收益记录为RELEASED");
                    }
                } catch (\Throwable $e) {
                    Log::warning("ClaimFreezeBalance: 标记收益记录RELEASED失败: " . $e->getMessage());
                }
            } else {
                // 金币发放失败，回滚 ad_freeze_balance
                Db::name('coin_account')
                    ->where('user_id', $userId)
                    ->update([
                        'ad_freeze_balance' => Db::raw('ad_freeze_balance + ' . $amount),
                    ]);
                $result['message'] = $coinResult['message'] ?? '发放失败';
            }

        } catch (\Throwable $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('ClaimFreezeBalance error userId=' . $userId . ': ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }

        return $result;
    }

    /**
     * 创建金币账户
     */
    protected function createCoinAccount($userId)
    {
        $account = [
            'user_id' => $userId,
            'balance' => 0,
            'frozen' => 0,
            'total_earn' => 0,
            'total_spend' => 0,
            'ad_freeze_balance' => 0,
            'total_ad_income' => 0,
            'version' => 1,
            'createtime' => time(),
            'updatetime' => time(),
        ];
        Db::name('coin_account')->insert($account);
        return Db::name('coin_account')->where('user_id', $userId)->find();
    }

    /**
     * 获取分布式锁（复用 CoinService 的锁机制）
     */
    protected function getLock($key, $expire = 5)
    {
        try {
            $cacheConfig = config('cache');
            if (isset($cacheConfig['type']) && strtolower($cacheConfig['type']) === 'redis') {
                $redis = Cache::store('redis')->handler();
                if ($redis) {
                    return $redis->set($key, 1, ['NX', 'EX' => $expire]);
                }
            }
            return $this->getFileLock($key, $expire);
        } catch (\Exception $e) {
            return $this->getFileLock($key, $expire);
        }
    }

    protected function getFileLock($key, $expire = 5)
    {
        $lockDir = RUNTIME_PATH . 'lock' . DS;
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        $lockFile = $lockDir . md5($key) . '.lock';
        $lockHandle = fopen($lockFile, 'c+');
        if (!$lockHandle) return false;
        if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
            ftruncate($lockHandle, 0);
            rewind($lockHandle);
            fwrite($lockHandle, time() + $expire);
            fflush($lockHandle);
            self::$lockHandles[$key] = $lockHandle;
            return true;
        }
        rewind($lockHandle);
        $expireTime = (int)fread($lockHandle, 20);
        if (time() > $expireTime && $expireTime > 0) {
            flock($lockHandle, LOCK_EX);
            ftruncate($lockHandle, 0);
            rewind($lockHandle);
            fwrite($lockHandle, time() + $expire);
            fflush($lockHandle);
            self::$lockHandles[$key] = $lockHandle;
            return true;
        }
        fclose($lockHandle);
        return false;
    }

    protected function releaseLock($key)
    {
        try {
            $cacheConfig = config('cache');
            if (isset($cacheConfig['type']) && strtolower($cacheConfig['type']) === 'redis') {
                $redis = Cache::store('redis')->handler();
                if ($redis) {
                    $redis->del($key);
                    return;
                }
            }
            $this->releaseFileLock($key);
        } catch (\Exception $e) {
            $this->releaseFileLock($key);
        }
    }

    protected function releaseFileLock($key)
    {
        if (isset(self::$lockHandles[$key])) {
            $lockHandle = self::$lockHandles[$key];
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            unset(self::$lockHandles[$key]);
        }
    }

    private static $lockHandles = [];
}
