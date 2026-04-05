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
 * 处理广告联盟回调、获取广告收益概览
 * URL映射: /api/ad/callback, /api/ad/overview
 */
class Ad extends Api
{
    // 所有接口都需要登录
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

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
            'amount' => $this->request->post('amount/f', 0),
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
        $cacheKey = 'ad_overview:' . $userId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $this->success('获取成功', $cached);
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
        $data['platform_rate'] = (float)($adConfig['platform_rate'] ?? 0.30);
        $data['reward_per_video'] = (int)($adConfig['reward_per_video'] ?? 200);
        $data['rewarded_video_interval'] = (int)($adConfig['rewarded_video_interval'] ?? 120);
        $data['settle_interval'] = (int)($adConfig['settle_interval'] ?? 30);

        // ★ 广告浏览进度（阈值奖励机制）
        try {
            $viewProgress = $service->getAdViewProgress($userId);
            $data['feed_view_progress'] = $viewProgress['feed'];
            $data['reward_view_progress'] = $viewProgress['reward'];
            $data['feed_reward_threshold'] = (int)($adConfig['feed_reward_threshold'] ?? 5);
            $data['video_reward_threshold'] = (int)($adConfig['video_reward_threshold'] ?? 3);
        } catch (\Throwable $e) {
            $data['feed_view_progress'] = ['view_count' => 0, 'threshold' => 5, 'remaining' => 5, 'reward_count' => 0, 'reward_coin' => 50, 'progress_percent' => 0];
            $data['reward_view_progress'] = ['view_count' => 0, 'threshold' => 3, 'remaining' => 3, 'reward_count' => 0, 'reward_coin' => 200, 'progress_percent' => 0];
            $data['feed_reward_threshold'] = 5;
            $data['video_reward_threshold'] = 3;
        }

        // ★ 加密 data 字段（复用已加载的配置判断，不再额外查库）
        $encryptEnabled = isset($adConfig['data_encrypt']) ? (int)$adConfig['data_encrypt'] : 1;
        if ($encryptEnabled) {
            $data = DataEncryptService::encrypt($data);
        }

        // 缓存结果 30 秒
        try {
            Cache::set($cacheKey, $data, 30);
        } catch (\Throwable $e) {
            // 缓存写入失败不影响响应
        }

        $this->success('获取成功', $data);
    }

    /**
     * 清除 overview 接口缓存（在数据变更时调用）
     * @param int|array $userId 用户ID或ID数组
     */
    public static function clearOverviewCache($userId)
    {
        $ids = is_array($userId) ? $userId : [$userId];
        foreach ($ids as $id) {
            try {
                Cache::delete('ad_overview:' . (int)$id);
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
     * 在 catch(\Throwable) 中重新抛出 HttpResponseException
     */
    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
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
     * 领取待释放金币
     *
     * 用户观看激励视频后调用，将 ad_freeze_balance 转入 balance
     *
     * @api {post} /api/ad/claimFreezeBalance 领取待释放金币
     * @apiParam {String} [transaction_id] 交易ID(防重复)
     * @apiSuccess {Number} amount 领取金额（金币）
     * @apiSuccess {Number} balance 当前余额
     */
    public function claimFreezeBalance()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $service = new AdIncomeService();
        $result = $service->claimFreezeBalance($userId);

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
}
