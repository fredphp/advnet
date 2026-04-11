<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\AdIncomeService;
use app\common\library\DataEncryptService;
use app\common\library\RiskControlService;
use think\Cache;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;

/**
 * 广告接口
 *
 * 处理广告联盟回调、广告收益概览、浏览记录、冻结金币领取、聊天资源
 * URL映射: /api/ad/serverNotify, /api/ad/callback, /api/ad/overview, /api/ad/recordView, /api/ad/claimFreezeBalance, /api/ad/chatResources
 */
class Ad extends Api
{
    // serverNotify 无需登录（DCloud广告联盟服务端回调），其余需要登录
    protected $noNeedLogin = ['serverNotify'];
    protected $noNeedRight = ['*'];

    /**
     * ★ DCloud 广告联盟服务端回调
     *
     * DCloud 广告联盟在用户完成广告观看后，会向开发者配置的回调URL发送服务端通知。
     * 此接口用于接收并验证 DCloud 的服务端回调，处理广告收益。
     *
     * DCloud 回调参数：
     *   - adpid: String, DCloud广告位id
     *   - provider: String, 广告服务商 (china/global)
     *   - platform: String, 平台 (iOS/Android/weixin-mp)
     *   - sign: String, 签名 = sha256(secret:trans_id)
     *   - trans_id: String, 交易id（唯一标识一次广告观看）
     *   - user_id: String, 用户id（调用SDK时透传的用户唯一标识）
     *   - extra: String, 自定义数据
     *   - cpm: int, 千次曝光收益（单位：分），cpm/1000 为本次收益（元）
     *
     * @api {post} /api/ad/serverNotify DCloud广告服务端回调
     * @apiParam {String} adpid DCloud广告位id
     * @apiParam {String} provider 广告服务商
     * @apiParam {String} platform 平台
     * @apiParam {String} sign 签名 sha256(secret:trans_id)
     * @apiParam {String} trans_id 交易id
     * @apiParam {String} user_id 用户id
     * @apiParam {String} [extra] 自定义数据
     * @apiParam {int} [cpm] 千次曝光收益（分）
     */
    public function serverNotify()
    {
        // ★ 获取原始请求体（DCloud 可能以 POST form 或 JSON 发送）
        $input = file_get_contents('php://input');
        $request = $this->request;

        // 收集 DCloud 标准回调参数
        $adpid = $request->post('adpid/s', '');
        $provider = $request->post('provider/s', '');
        $platform = $request->post('platform/s', '');
        $sign = $request->post('sign/s', '');
        $transId = $request->post('trans_id/s', '');
        $userIdStr = $request->post('user_id/s', '');
        $extra = $request->post('extra/s', '');
        $cpm = $request->post('cpm/d', 0);

        Log::info('[AdServerNotify] 收到DCloud回调: adpid=' . $adpid . ', provider=' . $provider . ', platform=' . $platform . ', trans_id=' . $transId . ', user_id=' . $userIdStr . ', cpm=' . $cpm . ', sign=' . substr($sign, 0, 8) . '..., raw_body=' . substr($input, 0, 500));

        // ==================== 参数校验 ====================

        if (empty($adpid) || empty($transId)) {
            Log::warning('[AdServerNotify] 缺少必要参数: adpid=' . $adpid . ', trans_id=' . $transId);
            $this->error('缺少必要参数');
        }

        // ==================== 签名验证 ====================

        $secret = $this->getCallbackSecret();
        if (empty($secret)) {
            Log::error('[AdServerNotify] 未配置回调密钥(callback_secret)');
            $this->error('系统配置错误');
        }

        // 签名算法: sign = sha256(secret:trans_id)
        $expectedSign = hash('sha256', $secret . ':' . $transId);

        if (empty($sign) || !hash_equals($expectedSign, $sign)) {
            Log::warning('[AdServerNotify] 签名验证失败: expected=' . $expectedSign . ', received=' . $sign . ', trans_id=' . $transId);
            $this->error('签名验证失败');
        }

        Log::info('[AdServerNotify] 签名验证通过, trans_id=' . $transId);

        // ==================== 用户识别 ====================

        $userId = (int)$userIdStr;
        if ($userId <= 0) {
            // 尝试从 extra 字段解析（前端可能在 extra 中传递 user_id）
            if (!empty($extra)) {
                $extraData = json_decode($extra, true);
                if (is_array($extraData) && isset($extraData['user_id'])) {
                    $userId = (int)$extraData['user_id'];
                }
            }

            if ($userId <= 0) {
                Log::warning('[AdServerNotify] 无效的用户ID: user_id=' . $userIdStr . ', extra=' . $extra);
                $this->error('无效的用户ID');
            }
        }

        // 验证用户是否存在
        $user = Db::name('user')->where('id', $userId)->field('id,status')->find();
        if (!$user) {
            Log::warning('[AdServerNotify] 用户不存在: user_id=' . $userId);
            $this->error('用户不存在');
        }

        // ==================== 判断广告类型 ====================

        // 通过 adpid 与配置的广告位对比来判断广告类型
        $service = new AdIncomeService();
        $feedAdpid = $service->getConfig('feed_adpid', '');
        $videoAdpid = $service->getConfig('rewarded_video_adpid', '');

        if ($adpid === $videoAdpid || strpos($transId, 'rv_') === 0 || strpos($transId, 'video') !== false) {
            $adType = 'reward';
        } else {
            $adType = 'feed';
        }

        // ==================== 计算收益金额 ====================

        // 如果有 cpm（千次曝光收益，单位：分），则实际收益 = cpm / 1000 元
        $amountYuan = 0;
        if ($cpm > 0) {
            $amountYuan = round($cpm / 1000, 4); // 分 → 元
        }

        // ==================== 构造 handleAdCallback 参数 ====================

        $params = [
            'ad_type'        => $adType,
            'adpid'          => $adpid,
            'ad_provider'    => 'uniad',  // DCloud 统一为 uniad
            'ad_source'      => 'dcloud_server_callback',
            'amount'         => $amountYuan,
            'transaction_id' => 'dcloud_' . $transId,  // 加前缀区分来源
            'ip'             => $request->ip(),
            'user_agent'     => $request->header('user-agent', ''),
            'device_id'      => '',
            'remark'         => 'DCloud服务端回调|provider=' . $provider . '|platform=' . $platform . '|cpm=' . $cpm . '|extra=' . $extra,
        ];

        // 如果 cpm > 0，按实际收益计算金币；否则使用配置的固定奖励
        if ($amountYuan <= 0) {
            unset($params['amount']); // 不传 amount，让 handleAdCallback 使用固定奖励
        }

        // ==================== 防重复处理 ====================

        $dedupeKey = 'dcloud_callback:' . $transId;
        try {
            $cache = \think\Cache::get($dedupeKey);
            if ($cache) {
                Log::info('[AdServerNotify] 重复回调已忽略: trans_id=' . $transId);
                // DCloud 回调返回 200 表示成功（幂等）
                echo json_encode(['code' => 0, 'msg' => '重复回调']);
                exit;
            }
            \think\Cache::set($dedupeKey, 1, 86400); // 24小时防重复
        } catch (\Throwable $e) {
            // 缓存异常不影响主流程（handleAdCallback 内部也有 transaction_id 防重复）
        }

        // ==================== 处理广告收益 ====================

        $result = $service->handleAdCallback($userId, $params);

        if ($result['success']) {
            Log::info('[AdServerNotify] 处理成功: userId=' . $userId . ', adType=' . $adType . ', amount=' . $amountYuan . '元, userCoin=' . ($result['user_amount_coin'] ?? 0) . ', platformCoin=' . ($result['platform_amount_coin'] ?? 0));

            // ★ 处理成功后检查是否需要生成通知红包
            try {
                $settleResult = $service->checkAndAutoSettle($userId);
                if ($settleResult['success']) {
                    Log::info('[AdServerNotify] 自动生成通知红包: userId=' . $userId . ', amount=' . ($settleResult['amount'] ?? 0));
                }
            } catch (\Throwable $e) {
                Log::warning('[AdServerNotify] 检查红包生成失败: ' . $e->getMessage());
            }

            // DCloud 回调返回 200 即可
            echo json_encode([
                'code' => 0,
                'msg'  => 'success',
                'data' => [
                    'log_id'              => $result['log_id'],
                    'user_amount_coin'    => $result['user_amount_coin'] ?? 0,
                    'platform_amount_coin' => $result['platform_amount_coin'] ?? 0,
                ],
            ]);
            exit;
        } else {
            Log::warning('[AdServerNotify] 处理失败: userId=' . $userId . ', msg=' . ($result['message'] ?? ''));
            // 即使处理失败也返回 200（避免 DCloud 重试导致问题）
            echo json_encode(['code' => 1, 'msg' => $result['message'] ?? '处理失败']);
            exit;
        }
    }

    /**
     * 广告回调 - 记录广告收益
     *
     * ★ 调用时机：广告组件展示成功后，前端主动调用
     * ★ 前端也可在 uni-ad 的 onLoad / onClose 事件中调用
     *
     * @api {post} /api/ad/callback 广告回调
     * @apiParam {String} [ad_type] 广告类型: feed=信息流, reward=激励视频
     * @apiParam {String} [adpid] 广告位ID
     * @apiParam {String} [ad_provider] 广告平台: uniad, csj, ylh
     * @apiParam {String} [ad_source] 广告来源页面
     * @apiParam {Number} [amount] 广告返回金额（元），不传则使用配置的固定奖励
     * @apiParam {String} [transaction_id] 交易ID（防重复）
     * @apiSuccess {Number} log_id 收益记录ID
     * @apiSuccess {Number} user_amount_coin 用户获得金币
     * @apiSuccess {Number} platform_amount_coin 平台抽成金币
     * @apiSuccess {Number} total_reward_coin 总奖励金币
     * @apiSuccess {Number} redpacket_created 是否自动生成了红包 (1=是)
     */
    public function callback()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        // 收集参数
        $params = [
            'ad_type' => $this->request->post('ad_type/s', 'feed'),
            'adpid' => $this->request->post('adpid/s', ''),
            'ad_provider' => $this->request->post('ad_provider/s', 'uniad'),
            'ad_source' => $this->request->post('ad_source/s', 'redbag_page'),
            // ★ 安全：不使用客户端提交的 amount（客户端可能伪造大金额）
            // 真实 CPM 金额仅通过 serverNotify（DCloud 签名验证）处理
            // 'amount' => $this->request->post('amount/f', 0),
            'transaction_id' => $this->request->post('transaction_id/s', ''),
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->header('user-agent', ''),
            'device_id' => $this->request->header('X-Device-Id', ''),
            'remark' => $this->request->post('remark/s', ''),
        ];

        // ★ 写入 adback.log 日志
        $this->writeAdBackLog('[Callback请求] userId=' . $userId . ' ad_type=' . $params['ad_type'] . ' adpid=' . $params['adpid'] . ' transaction_id=' . $params['transaction_id'] . ' ip=' . $params['ip']);

        // 参数校验
        if (!in_array($params['ad_type'], ['feed', 'reward'])) {
            $this->error('广告类型无效');
        }

        // 调用风控（非阻塞）
        try {
            $riskService = new RiskControlService();
            $riskService->init(
                $userId,
                $this->request->header('X-Device-Id', ''),
                $this->request->ip(),
                $this->request->header('user-agent', '')
            );
            $riskResult = $riskService->check('ad', 'callback', $params);
            if (!$riskResult['passed']) {
                $this->error($riskResult['message'] ?: '风控检测未通过');
            }
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::warning('广告回调风控检查异常: ' . $e->getMessage());
            // 风控异常不影响主流程
        }

        // 频率限制：同一用户每10秒最多一次回调
        $rateLimitKey = 'ad_callback_rate:' . $userId;
        try {
            $cache = think\Cache::get($rateLimitKey);
            if ($cache) {
                $this->error('操作太频繁，请稍后再试');
            }
            think\Cache::set($rateLimitKey, 1, 10);
        } catch (\Throwable $e) {
            // 缓存异常不影响主流程
        }

        // 处理广告收益
        $service = new AdIncomeService();
        $result = $service->handleAdCallback($userId, $params);

        if ($result['success']) {
            $responseData = [
                'log_id' => $result['log_id'],
                'user_amount_coin' => $result['user_amount_coin'],
                'platform_amount_coin' => $result['platform_amount_coin'] ?? 0,
                'total_reward_coin' => $result['total_reward_coin'] ?? $result['user_amount_coin'],
                'message' => $result['message'],
                'redpacket_created' => 0,
            ];

            // ★ 检查冻结余额是否达到红包基数额度，达到则自动发红包
            if (!empty($result['log_id'])) {
                try {
                    $settleResult = $service->checkAndAutoSettle($userId);
                    if ($settleResult['success']) {
                        $responseData['redpacket_created'] = 1;
                        $responseData['redpacket_amount'] = $settleResult['amount'] ?? 0;
                        $this->writeAdBackLog('[自动红包] userId=' . $userId . ' 生成红包金额=' . ($settleResult['amount'] ?? 0) . '金币');
                    }
                } catch (\Throwable $e) {
                    $this->writeAdBackLog('[自动红包异常] userId=' . $userId . ' error=' . $e->getMessage());
                }
            }

            $this->writeAdBackLog('[Callback成功] userId=' . $userId . ' ad_type=' . $params['ad_type'] . ' adpid=' . $params['adpid'] . ' log_id=' . $result['log_id'] . ' user_coin=' . $result['user_amount_coin'] . ' platform_coin=' . ($result['platform_amount_coin'] ?? 0));
            $this->success('广告收益已记录', $responseData);
        } else {
            $this->writeAdBackLog('[Callback失败] userId=' . $userId . ' ad_type=' . $params['ad_type'] . ' adpid=' . $params['adpid'] . ' message=' . ($result['message'] ?? '未知错误'));
            $this->error($result['message'] ?? '处理失败');
        }
    }

    /**
     * ★ 写入广告回调日志到 adback.log
     * @param string $message
     */
    private function writeAdBackLog($message)
    {
        try {
            $logFile = ROOT_PATH . 'adback.log';
            $time = date('Y-m-d H:i:s');
            $logLine = '[' . $time . '] ' . $message . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // 日志写入失败不影响主流程
        }
    }

    /**
     * 在 catch(\Throwable) 中重新抛出 HttpResponseException
     */
    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
    }

    /**
     * 获取广告收益概览
     *
     * @api {get} /api/ad/overview 广告收益概览
     * @apiSuccess {Number} today_income 今日广告收益（金币）
     * @apiSuccess {Number} total_ad_income 累计广告收益（金币）
     * @apiSuccess {Number} ad_freeze_balance 待释放金币
     * @apiSuccess {Number} unclaimed_packet_count 未领取红包数
     * @apiSuccess {Number} unclaimed_packet_amount 未领取红包总金额
     * @apiSuccess {Number} redpacket_threshold 红包基数额度（达到此额度自动发红包）
     */
    public function overview()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        // ★ 接口级缓存：同一用户 30 秒内重复请求直接返回缓存（前端频繁轮询场景）
        // ★ 使用版本化缓存 key，避免 clearOverviewCache 后并发 overview 请求用旧数据重新覆盖缓存
        $version = (int)(Cache::get('ad_overview_version:' . $userId) ?: 0);
        $cacheKey = 'ad_overview:' . $userId . ':v' . $version;
        $cached = Cache::get($cacheKey);
        // ★ 验证缓存数据必须是数组（旧的加密时代可能存入了 string/false 等无效值）
        if (is_array($cached)) {
            $this->success('获取成功', $cached);
        } elseif ($cached !== null) {
            // 缓存数据格式异常（非数组）→ 删除脏缓存，重新生成
            try { Cache::delete($cacheKey); } catch (\Throwable $e) {}
            Log::warning('AdOverview: 缓存数据格式异常，已清除, type=' . gettype($cached) . ', key=' . $cacheKey);
        }

        // ★ 性能优化：一次性批量加载全部 ad 分组配置（1次调用替代12+次）
        $adConfig = \app\common\library\SystemConfigService::get('ad');

        $service = new AdIncomeService();

        try {
            $data = $service->getAdIncomeOverview($userId);
        } catch (\Throwable $e) {
            Log::error('AdOverview error: ' . get_class($e) . ' - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            // 即使查询失败也返回基础结构
            $data = [
                'today_income' => 0,
                'total_ad_income' => 0,
                'ad_freeze_balance' => 0,
                'coin_balance' => 0,
                'unclaimed_packet_count' => 0,
                'unclaimed_packet_amount' => 0,
            ];
        }

        // 从已加载的配置中直接取值（零额外 I/O）
        $data['redpacket_threshold'] = (int)($adConfig['redpacket_threshold'] ?? 1000);
        $data['feed_adpid'] = $adConfig['feed_adpid'] ?? '';
        $data['rewarded_video_adpid'] = $adConfig['rewarded_video_adpid'] ?? '';
        $data['feed_ad_count'] = (int)($adConfig['feed_ad_count'] ?? 3);
        $data['reward_per_feed'] = (int)($adConfig['reward_per_feed'] ?? 50);
        $data['ad_income_enabled'] = (int)($adConfig['ad_income_enabled'] ?? 1);
        $data['enabled_providers'] = $adConfig['enabled_providers'] ?? 'uniad';
        // ★ platform_rate 不再暴露给前端（属于平台内部信息）
        $data['reward_per_video'] = (int)($adConfig['reward_per_video'] ?? 200);
        $data['rewarded_video_interval'] = (int)($adConfig['rewarded_video_interval'] ?? 120);
        $data['settle_interval'] = (int)($adConfig['settle_interval'] ?? 30);
        $data['ad_idle_timeout'] = (int)($adConfig['ad_idle_timeout'] ?? 30);

        // ★ 广告浏览进度（阈值奖励机制）
        try {
            $viewProgress = $service->getAdViewProgress($userId);
            $data['feed_view_progress'] = $viewProgress['feed'];
            $data['reward_view_progress'] = $viewProgress['reward'];
            $data['feed_reward_threshold'] = (int)($adConfig['feed_reward_threshold'] ?? 1);
            $data['video_reward_threshold'] = (int)($adConfig['video_reward_threshold'] ?? 1);
        } catch (\Throwable $e) {
            $data['feed_view_progress'] = ['view_count' => 0, 'threshold' => 1, 'remaining' => 1, 'reward_count' => 0, 'reward_coin' => 50, 'progress_percent' => 0];
            $data['reward_view_progress'] = ['view_count' => 0, 'threshold' => 1, 'remaining' => 1, 'reward_count' => 0, 'reward_coin' => 200, 'progress_percent' => 0];
            $data['feed_reward_threshold'] = 1;
            $data['video_reward_threshold'] = 1;
        }

        // ★ 广告配置数据（adpid、金币奖励等）非敏感信息，不做加密
        // 保证前端各平台都能正确解析数据

        // 缓存结果 30 秒（使用版本化 key，避免并发请求覆盖新版本缓存）
        try {
            // ★ 重新读取版本号，确保写入的是最新版本的 key
            // 防止本请求执行期间版本被递增（如 claimFreezeBalance 清除缓存）
            $currentVersion = (int)(Cache::get('ad_overview_version:' . $userId) ?: 0);
            $writeKey = 'ad_overview:' . $userId . ':v' . $currentVersion;
            if ($writeKey === $cacheKey) {
                Cache::set($cacheKey, $data, 30);
            } else {
                // 版本已变化（说明执行期间有数据变更），不写入旧版本缓存
                Log::info('AdOverview: 版本已变化(' . $version . '->' . $currentVersion . ')，跳过缓存写入');
            }
        } catch (\Throwable $e) {
            // 缓存写入失败不影响响应
        }

        $this->success('获取成功', $data);
    }

    /**
     * 清除 overview 接口缓存（在数据变更时调用）
     * ★ 使用版本递增机制：递增版本号后，旧的缓存 key 自然失效
     * 比直接 Cache::delete 更安全：并发 overview 请求在 delete 后可能用旧数据重新写入缓存
     * 而版本递增后，并发请求写入的是旧版本 key，不影响新版本的缓存读取
     * 
     * @param int|array $userId 用户ID或ID数组
     */
    public static function clearOverviewCache($userId)
    {
        $ids = is_array($userId) ? $userId : [$userId];
        foreach ($ids as $id) {
            try {
                // ★ 递增版本号，使旧版本缓存 key 失效
                $version = (int)(Cache::get('ad_overview_version:' . (int)$id) ?: 0) + 1;
                Cache::set('ad_overview_version:' . (int)$id, $version, 300);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * 红包结算检查接口
     * 前端按 settle_interval 间隔定时调用，检查冻结余额是否达到红包基数额度
     * 达到则自动生成红包，前端随后通过 adredpacket/list 轮询获取新红包
     *
     * @api {get} /api/ad/checkSettle 红包结算检查
     * @apiSuccess {Number} redpacket_created 是否生成了新红包 (0/1)
     * @apiSuccess {Number} redpacket_amount 红包金额（金币）
     * @apiSuccess {Number} ad_freeze_balance 当前冻结余额
     */
    public function checkSettle()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $service = new AdIncomeService();

        try {
            $result = $service->checkAndAutoSettle($userId);
        } catch (\Throwable $e) {
            Log::error('CheckSettle error: ' . $e->getMessage());
            $result = ['success' => false];
        }

        if ($result['success']) {
            // 生成红包后清除 overview 缓存
            self::clearOverviewCache($userId);
        }

        $this->success('检查完成', [
            'redpacket_created' => $result['success'] ? 1 : 0,
            'redpacket_amount' => (int)($result['amount'] ?? 0),
            'message' => $result['message'] ?? '',
        ]);
    }

    /**
     * ★ 记录广告浏览并检查阈值奖励
     *
     * 调用时机：用户在 watch.vue 完成倒计时后点击领取
     * 核心逻辑：浏览+1 → 达到阈值 → 触发 handleAdCallback 写入 ad_freeze_balance
     *
     * @api {post} /api/ad/recordView 记录广告浏览
     * @apiParam {String} ad_type 广告类型: feed=信息流, reward=激励视频
     * @apiParam {String} [adpid] 广告位ID
     * @apiParam {String} [ad_provider] 广告平台
     * @apiParam {String} [ad_source] 广告来源页面
     * @apiParam {String} [transaction_id] 交易ID（防重复）
     * @apiSuccess {Number} view_count 当前浏览次数
     * @apiSuccess {Number} threshold 奖励阈值
     * @apiSuccess {Boolean} reward_given 是否发放了奖励
     * @apiSuccess {Number} amount 奖励金额（金币）
     * @apiSuccess {String} message 提示信息
     */
    public function recordView()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $adType = $this->request->post('ad_type/s', 'feed');
        if (!in_array($adType, ['feed', 'reward'])) {
            $this->error('广告类型无效');
        }

        // 收集参数（达到阈值时传递给 handleAdCallback）
        $params = [
            'ad_type'        => $adType,
            'adpid'          => $this->request->post('adpid/s', ''),
            'ad_provider'    => $this->request->post('ad_provider/s', 'uniad'),
            'ad_source'      => $this->request->post('ad_source/s', 'redbag_page'),
            'transaction_id' => $this->request->post('transaction_id/s', ''),
            'ip'             => $this->request->ip(),
            'user_agent'     => $this->request->header('user-agent', ''),
            'device_id'      => $this->request->header('X-Device-Id', ''),
            'remark'         => 'threshold_reward',
        ];

        $this->writeAdBackLog('[RecordView] userId=' . $userId . ' ad_type=' . $adType . ' adpid=' . $params['adpid']);

        // 频率限制：同一用户每5秒最多一次
        $rateLimitKey = 'ad_record_rate:' . $userId;
        try {
            $cache = think\Cache::get($rateLimitKey);
            if ($cache) {
                $this->error('操作太频繁，请稍后再试');
            }
            think\Cache::set($rateLimitKey, 1, 5);
        } catch (\Throwable $e) {}

        $service = new AdIncomeService();
        $result = $service->recordAdViewAndCheckReward($userId, $adType, $params);

        if ($result['reward_given']) {
            // 奖励已发放 → 清除 overview 缓存
            self::clearOverviewCache($userId);

            $responseData = [
                'view_count'          => $result['view_count'],
                'threshold'           => $result['threshold'],
                'reward_given'        => true,
                'amount'              => $result['amount'],
                'message'             => $result['message'],
                'total_today_views'   => $result['total_today_views'] ?? 0,
                'total_today_rewards' => $result['total_today_rewards'] ?? 0,
                'redpacket_created'   => 0,
            ];

            // 检查是否自动生成红包
            try {
                $settleResult = $service->checkAndAutoSettle($userId);
                if ($settleResult['success']) {
                    $responseData['redpacket_created'] = 1;
                    $responseData['redpacket_amount'] = $settleResult['amount'] ?? 0;
                }
            } catch (\Throwable $e) {}

            $this->writeAdBackLog('[RecordView-奖励] userId=' . $userId . ' ad_type=' . $adType . ' amount=' . $result['amount']);
            $this->success('奖励已发放', $responseData);
        } else {
            $this->success('浏览已记录', [
                'view_count'          => $result['view_count'],
                'threshold'           => $result['threshold'],
                'reward_given'        => false,
                'amount'              => 0,
                'message'             => $result['message'],
                'total_today_views'   => $result['total_today_views'] ?? 0,
                'total_today_rewards' => $result['total_today_rewards'] ?? 0,
            ]);
        }
    }

    /**
     * ★ 轻量级记录信息流广告浏览（Redis 计数 + 异步结算）
     *
     * 与 recordView 的区别：
     * - recordView: 每次浏览写数据库（ad_view_counter），达到阈值同步调用 handleAdCallback（~50ms）
     * - recordFeedView: 只做 Redis INCR 计数，达到阈值推入队列由 cron 异步结算（~2ms）
     *
     * @api {post} /api/ad/recordFeedView 轻量记录信息流广告浏览
     * @apiParam {String} [adpid] 广告位ID
     * @apiSuccess {Number} view_count 当前浏览次数
     * @apiSuccess {Number} threshold 奖励阈值
     * @apiSuccess {Boolean} reward_pending 是否已触发奖励（异步发放中）
     * @apiSuccess {Number} estimated_coin 预估奖励金币（达到阈值时可获得的金币）
     * @apiSuccess {String} message 提示信息
     */
    public function recordFeedView()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $service = new AdIncomeService();
        $result = $service->recordFeedAdView($userId);

        if (!$result['success']) {
            $this->error($result['message']);
        }

        $this->writeAdBackLog('[RecordFeedView] userId=' . $userId . ' reward_pending=' . ($result['reward_pending'] ? '1' : '0'));

        if ($result['reward_pending']) {
            // 奖励已入队列，异步发放中
            $this->success($result['message'], [
                'view_count'     => $result['view_count'],
                'threshold'      => $result['threshold'],
                'reward_pending' => true,
                'estimated_coin' => $result['estimated_coin'],
                'message'        => $result['message'],
            ]);
        } else {
            $this->success($result['message'], [
                'view_count'     => $result['view_count'],
                'threshold'      => $result['threshold'],
                'reward_pending' => false,
                'estimated_coin' => $result['estimated_coin'],
                'message'        => $result['message'],
            ]);
        }
    }

    /**
     * 领取待释放金币
     *
     * 用户观看激励视频后调用，将 ad_freeze_balance 转入 balance
     *
     * @api {post} /api/ad/claimFreezeBalance 领取待释放金币
     * @apiParam {String} [transaction_id] 交易ID(防重复)
     * @apiParam {Number} [max_amount] 最大领取金额（0或不传表示领取全部，传入则只领取指定金额）
     * @apiSuccess {Number} amount 领取金额（金币）
     * @apiSuccess {Number} balance 当前余额
     */
    public function claimFreezeBalance()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        // ★ 频率限制：同一用户每10秒最多一次领取（防止并发/重复请求）
        $rateLimitKey = 'ad_freeze_claim_rate:' . $userId;
        try {
            $cache = think\Cache::get($rateLimitKey);
            if ($cache) {
                $this->error('操作太频繁，请稍后再试');
            }
            think\Cache::set($rateLimitKey, 1, 10);
        } catch (\Throwable $e) {}

        $maxAmount = (int)$this->request->post('max_amount', 0);
        $service = new AdIncomeService();
        $result = $service->claimFreezeBalance($userId, $maxAmount);

        if ($result['success']) {
            self::clearOverviewCache($userId);
            $this->writeAdBackLog('[ClaimFreezeBalance] userId=' . $userId . ' amount=' . $result['amount'] . ' balance=' . $result['balance']);
            $this->success('领取成功', [
                'amount' => $result['amount'],
                'balance' => $result['balance'],
            ]);
        } else {
            $this->error($result['message'] ?? '领取失败');
        }
    }

    /**
     * ★ 获取聊天资源（系统用户昵称头像 + 消息模板）
     *
     * 前端页面加载时调用，带版本号实现增量更新缓存。
     * - 系统用户：member_type=1 或 user_type=1 的用户，取 nickname + avatar
     * - 消息模板：advn_red_packet_resource 中 type='chat' 的资源
     *
     * 缓存策略：
     * - 服务端 ThinkPHP Cache 存储版本号和数据（10分钟TTL）
     * - 前端 uni.setStorageSync 本地缓存
     * - 后台管理增删改资源/系统用户时调用 refreshChatResourcesCache() 递增版本号
     *
     * @api {get} /api/ad/chatResources 获取聊天资源
     * @apiParam {Number} [version] 客户端本地缓存的版本号
     * @apiSuccess {Number} version 当前数据版本号
     * @apiSuccess {Boolean} updated 数据是否有更新
     * @apiSuccess {Array} users 系统用户列表 [{id, nickname, avatar}]
     * @apiSuccess {Array} messages 消息模板列表 [{id, description}]
     */
    public function chatResources()
    {
        // 1. 获取当前版本号
        $version = (int)Cache::get('chat_resources:version', 1);
        $clientVersion = (int)$this->request->get('version/d', 0);

        // 2. 客户端版本一致 → 无需更新，直接返回
        if ($clientVersion > 0 && $clientVersion === $version) {
            $this->success('已是最新', ['version' => $version, 'updated' => false]);
        }

        // 3. 尝试读取服务端数据缓存（key 绑定版本号，版本变化自动失效）
        $cacheKey = 'chat_resources:data:v' . $version;
        $data = Cache::get($cacheKey);

        if (!$data) {
            // 4. 查询系统用户（member_type=1 或 user_type=1，状态正常）
            $users = Db::name('user')
                ->where('status', 'normal')
                ->where(function ($query) {
                    $query->where('member_type', 1)->whereOr('user_type', 1);
                })
                ->field('id,nickname,avatar')
                ->order('id', 'desc')
                ->select();

            // 系统用户不足 5 人时，补充普通用户填充（确保聊天群看起来热闹）
            if (count($users) < 5) {
                $systemIds = array_column($users, 'id');
                $extraUsers = Db::name('user')
                    ->where('status', 'normal')
                    ->where('id', 'not in', empty($systemIds) ? [0] : $systemIds)
                    ->field('id,nickname,avatar')
                    ->orderRaw('RAND()')
                    ->limit(10)
                    ->select();
                $users = array_merge($users, $extraUsers);
            }

            // ★ 对 avatar 路径应用 cdnurl()，确保返回完整可访问的 URL
            foreach ($users as &$u) {
                $u['avatar'] = !empty($u['avatar']) ? cdnurl($u['avatar']) : '';
            }
            unset($u);

            // 5. 查询聊天消息模板（type='chat', status='normal'）
            $messages = Db::name('red_packet_resource')
                ->where('type', 'chat')
                ->where('status', 'normal')
                ->field('id,description')
                ->order('weigh', 'desc')
                ->order('id', 'desc')
                ->select();

            $data = [
                'users' => $users,
                'messages' => $messages,
            ];

            // 缓存 10 分钟
            Cache::set($cacheKey, $data, 600);
        }

        $data['version'] = $version;
        $data['updated'] = true;

        $this->success('获取成功', $data);
    }

    /**
     * 获取回调密钥
     */
    private function getCallbackSecret()
    {
        return \app\common\library\SystemConfigService::get('ad.callback_secret', null, '');
    }

    /**
     * ★ 刷新聊天资源缓存版本号
     *
     * 在后台管理增删改「红包资源」或「系统用户」时调用，
     * 递增版本号使前端下次请求时获取最新数据。
     *
     * 使用方式（在后台控制器中）:
     *   \app\api\controller\Ad::refreshChatResourcesCache();
     */
    public static function refreshChatResourcesCache()
    {
        try {
            $version = (int)Cache::get('chat_resources:version', 1);
            Cache::set('chat_resources:version', $version + 1);
            // 清除旧版本数据缓存
            Cache::delete('chat_resources:data:v' . $version);
        } catch (\Throwable $e) {
            Log::warning('刷新聊天资源缓存失败: ' . $e->getMessage());
        }
    }
}
