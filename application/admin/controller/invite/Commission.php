<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;

/**
 * 分佣记录
 */
class Commission extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InviteCommissionLog();
    }

    /**
     * 分佣记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('invite_commission_log')->where($where)->count();
            $list = Db::name('invite_commission_log')
                ->alias('icl')
                ->join('user u', 'u.id = icl.user_id', 'LEFT')
                ->field('icl.*, u.username, u.nickname')
                ->where($where)
                ->order("icl.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 分佣统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        if ($this->request->isAjax()) {
            // 总体统计
            $totalStats = Db::name('invite_commission_log')
                ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
                ->field('COUNT(*) as total_count, SUM(commission_amount) as total_amount,
                         AVG(commission_amount) as avg_amount')
                ->find();

            // 按来源统计
            $sourceStats = Db::name('invite_commission_log')
                ->field('source, COUNT(*) as count, SUM(commission_amount) as amount')
                ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('source')
                ->select();

            // 每日趋势
            $dailyStats = Db::name('invite_commission_log')
                ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                         COUNT(*) as count, SUM(commission_amount) as amount')
                ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('date')
                ->order('date', 'asc')
                ->select();

            // 用户分佣排行
            $topUsers = Db::name('invite_commission_log')
                ->alias('icl')
                ->join('user u', 'u.id = icl.user_id', 'LEFT')
                ->field('u.id, u.username, u.nickname, COUNT(*) as commission_count, SUM(icl.commission_amount) as total_amount')
                ->where('icl.createtime', 'between', [$startTimestamp, $endTimestamp])
                ->group('icl.user_id')
                ->order('total_amount', 'desc')
                ->limit(20)
                ->select();

            $this->success('', [
                'total_stats' => $totalStats,
                'source_stats' => $sourceStats,
                'daily_stats' => $dailyStats,
                'top_users' => $topUsers,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('invite_commission_log')
            ->alias('icl')
            ->join('user u', 'u.id = icl.user_id', 'LEFT')
            ->field('icl.*, u.username, u.nickname, u.mobile')
            ->where('icl.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        // 如果有关联用户，获取被邀请人信息
        if ($row['invitee_id']) {
            $invitee = Db::name('user')->where('id', $row['invitee_id'])->field('username, nickname, mobile')->find();
            $this->view->assign('invitee', $invitee);
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
