<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\AdIncomeService;
use app\common\library\RiskControlService;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;

/**
 * 广告回调接口
 *
 * 处理广告联盟回调，计算平台抽成，将用户收益写入 ad_freeze_balance
 */
class AdCallback extends Api
{
    // 所有接口都需要登录（serverNotify 除外，通过 noNeedLogin 单独声明）
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
     * @api {post} /api/ad_callback/serverNotify DCloud广告服务端回调
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
     * 广告回调 - 记录广告收益（前端主动调用）
     *
     * ★ 调用时机：广告组件展示成功后，前端主动调用
     * ★ 前端也可在 uni-ad 的 onLoad / onClose 事件中调用
     *
     * @api {post} /api/ad_callback/callback 广告回调
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
            $this->success('广告收益已记录', [
                'log_id' => $result['log_id'],
                'user_amount_coin' => $result['user_amount_coin'],
                'platform_amount_coin' => $result['platform_amount_coin'] ?? 0,
                'total_reward_coin' => $result['total_reward_coin'] ?? $result['user_amount_coin'],
                'message' => $result['message'],
            ]);
        } else {
            $this->error($result['message'] ?? '处理失败');
        }
    }

    /**
     * 获取广告收益概览
     *
     * @api {get} /api/ad_callback/overview 广告收益概览
     * @apiSuccess {Number} today_income 今日广告收益（金币）
     * @apiSuccess {Number} total_ad_income 累计广告收益（金币）
     * @apiSuccess {Number} ad_freeze_balance 待释放金币
     * @apiSuccess {Number} unclaimed_packet_count 未领取红包数
     * @apiSuccess {Number} unclaimed_packet_amount 未领取红包总金额
     */
    public function overview()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $service = new AdIncomeService();
        $data = $service->getAdIncomeOverview($userId);

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
     * 在 catch(\Throwable) 中重新抛出 HttpResponseException
     */
    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
    }
}
