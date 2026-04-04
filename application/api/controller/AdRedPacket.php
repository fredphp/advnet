<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\AdIncomeService;
use app\common\model\AdRedPacket;
use app\common\model\AdIncomeLog;
use think\Db;
use think\Log;
use think\exception\HttpResponseException;

/**
 * 广告红包接口
 *
 * 用户查看和领取由广告收益自动生成的红包
 * 区别于 RedPacket 控制器的任务红包（人工任务）
 */
class AdRedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 获取广告红包列表
     *
     * @api {get} /api/adredpacket/list 广告红包列表
     * @apiParam {Number} [page] 页码
     * @apiParam {Number} [limit] 每页数量
     * @apiSuccess {Array} list 红包列表
     * @apiSuccess {Number} total 总数
     * @apiSuccess {Number} unclaimed_count 未领取数量
     * @apiSuccess {Number} unclaimed_total 未领取总金额
     */
    public function list()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);

        if ($limit > 50) {
            $limit = 50;
        }

        $data = AdRedPacket::getUserPackets($userId, $page, $limit);

        $this->success('获取成功', $data);
    }

    /**
     * 领取单个广告红包
     *
     * @api {post} /api/adredpacket/claim 领取广告红包
     * @apiParam {Number} packet_id 红包ID
     * @apiSuccess {Number} amount 领取金额
     * @apiSuccess {Number} balance 当前余额
     */
    public function claim()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $packetId = $this->request->post('packet_id/d', 0);

        if (!$packetId) {
            $this->error('红包ID不能为空');
        }

        $service = new AdIncomeService();
        $result = $service->claimRedPacket($userId, $packetId);

        if ($result['success']) {
            $this->success('领取成功', [
                'amount' => $result['amount'],
                'balance' => $result['balance'],
            ]);
        } else {
            $this->error($result['message'] ?? '领取失败');
        }
    }

    /**
     * 一键领取所有广告红包
     *
     * @api {post} /api/adredpacket/claimAll 一键领取所有广告红包
     * @apiSuccess {Number} total_amount 总领取金额
     * @apiSuccess {Number} claim_count 领取数量
     * @apiSuccess {Number} balance 当前余额
     */
    public function claimAll()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $service = new AdIncomeService();
        $result = $service->claimAllRedPackets($userId);

        if ($result['success']) {
            $this->success($result['message'], [
                'total_amount' => $result['total_amount'],
                'claim_count' => $result['claim_count'],
                'balance' => $result['balance'],
            ]);
        } else {
            $this->error($result['message'] ?? '领取失败');
        }
    }

    /**
     * 观看广告后领取红包
     *
     * @api {post} /api/adredpacket/claimWithAd 观看广告后领取红包
     * @apiParam {Number} packet_id 红包ID
     * @apiParam {String} [transaction_id] 广告交易ID(防重复)
     * @apiSuccess {Number} amount 领取金额(金币)
     * @apiSuccess {Number} balance 当前余额
     * @apiSuccess {Number} ad_reward_coin 广告奖励金币
     */
    public function claimWithAd()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $packetId = $this->request->post('packet_id/d', 0);
        $transactionId = $this->request->post('transaction_id/s', '');

        if (!$packetId) {
            $this->error('红包ID不能为空');
        }

        $service = new AdIncomeService();

        // Step 1: 记录广告回调，将奖励写入 ad_freeze_balance
        $adResult = $service->handleAdCallback($userId, [
            'ad_type' => 'reward',
            'adpid' => \app\common\library\SystemConfigService::get('ad.rewarded_video_adpid', null, ''),
            'ad_provider' => 'uniad',
            'ad_source' => 'redbag_claim',
            'transaction_id' => $transactionId,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->header('user-agent', ''),
            'device_id' => $this->request->header('X-Device-Id', ''),
            'remark' => '领取广告红包-观看激励视频',
        ]);

        if (!$adResult['success']) {
            $msg = $adResult['message'];
            if ($msg !== '重复回调') {
                $this->error('广告奖励记录失败: ' . $msg);
            }
        }

        // Step 2: 领取红包 (将 ad_freeze_balance 转为 balance)
        $claimResult = $service->claimRedPacket($userId, $packetId);

        if ($claimResult['success']) {
            $this->success('领取成功', [
                'amount' => $claimResult['amount'],
                'balance' => $claimResult['balance'],
                'ad_reward_coin' => $adResult['user_amount_coin'] ?? 0,
            ]);
        } else {
            $this->error($claimResult['message'] ?? '领取失败');
        }
    }

    /**
     * 获取广告收益统计
     *
     * @api {get} /api/adredpacket/stats 广告收益统计
     * @apiParam {Number} [page] 页码
     * @apiParam {Number} [limit] 每页数量
     * @apiSuccess {Array} list 收益记录列表
     * @apiSuccess {Number} total 总记录数
     * @apiSuccess {Number} total_coin 总金币
     */
    public function stats()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }

        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);

        if ($limit > 50) {
            $limit = 50;
        }

        $list = AdIncomeLog::where('user_id', $userId)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = AdIncomeLog::where('user_id', $userId)->count();
        $totalCoin = (int)AdIncomeLog::where('user_id', $userId)
            ->whereIn('status', [AdIncomeLog::STATUS_CONFIRMED, AdIncomeLog::STATUS_RELEASED])
            ->sum('user_amount_coin');

        // 转换为前端友好的格式
        $formatList = [];
        foreach ($list as $item) {
            $formatList[] = [
                'id' => $item['id'],
                'ad_type' => $item['ad_type'],
                'ad_type_text' => AdIncomeLog::$typeList[$item['ad_type']] ?? '未知',
                'ad_provider' => $item['ad_provider'],
                'ad_provider_text' => AdIncomeLog::$providerList[$item['ad_provider']] ?? '未知',
                'user_amount_coin' => (int)$item['user_amount_coin'],
                'platform_amount_coin' => (int)$item['platform_amount_coin'],
                'status' => $item['status'],
                'status_text' => AdIncomeLog::$statusList[$item['status']] ?? '未知',
                'createtime' => $item['createtime'],
                'createtime_text' => date('Y-m-d H:i:s', $item['createtime']),
            ];
        }

        $this->success('获取成功', [
            'list' => $formatList,
            'total' => $total,
            'total_coin' => $totalCoin,
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
}
