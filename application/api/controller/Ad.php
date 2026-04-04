<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\AdIncomeService;
use app\common\library\RiskControlService;
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
                        Log::info('AutoRedPacket: 用户' . $userId . '自动生成红包' . ($settleResult['amount'] ?? 0) . '金币');
                    }
                } catch (\Throwable $e) {
                    Log::warning('AutoRedPacket检查异常: ' . $e->getMessage());
                    // 自动发红包失败不影响主流程
                }
            }

            $this->success('广告收益已记录', $responseData);
        } else {
            $this->error($result['message'] ?? '处理失败');
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

        // 附加红包基数额度配置
        $threshold = \app\common\library\SystemConfigService::get('ad.redpacket_threshold', null, 1000);
        $data['redpacket_threshold'] = (int)$threshold;

        $this->success('获取成功', $data);
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
}
