<?php

namespace app\api\controller;

use app\api\controller\Ad;
use app\common\controller\Api;
use app\common\library\AdIncomeService;
use app\common\model\AdRedPacket as AdRedPacketModel;
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

        $data = AdRedPacketModel::getUserPackets($userId, $page, $limit);

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
            Ad::clearOverviewCache($userId);
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
            Ad::clearOverviewCache($userId);
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
     * ★ 已废弃：观看广告后领取红包
     *
     * 旧流程：先调用 handleAdCallback 写入 freeze，再领取红包
     * 新流程：所有红包领取走纯 claim（claim 或 claimFreezeBalance），不在领取时额外上报广告
     * 保留此方法仅为向前兼容，实际不再执行任何操作。
     *
     * @deprecated 请使用 claim() 或 claimAll() 代替
     * @api {post} /api/adredpacket/claimWithAd 观看广告后领取红包（已废弃）
     */
    public function claimWithAd()
    {
        // ★ 此方法已废弃，统一使用 claim() 纯领取流程
        Log::info('[AdRedPacket] claimWithAd已废弃，拒绝请求');
        $this->error('此接口已废弃，请使用普通领取');
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
