<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;

/**
 * 邀请统计管理
 */
class Statistic extends Backend
{
    /**
     * 邀请统计概览
     */
    public function index()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 总体统计
        $totalStats = Db::name('invite_relation')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_invites, COUNT(DISTINCT inviter_id) as unique_inviters')
            ->find();

        // 每日邀请趋势
        $dailyStats = Db::name('invite_relation')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 邀请排行榜
        $topInviters = Db::name('user_invite_stat uis')
            ->join('user u', 'u.id = uis.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, uis.total_invite_count, uis.total_commission')
            ->order('uis.total_invite_count', 'desc')
            ->limit(20)
            ->select();

        // 分佣统计
        $commissionStats = Db::name('invite_commission_log')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('SUM(commission_amount) as total_commission, COUNT(*) as total_count')
            ->find();

        // 分佣来源分布
        $commissionBySource = Db::name('invite_commission_log')
            ->field('source, COUNT(*) as count, SUM(commission_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('source')
            ->select();

        if ($this->request->isAjax()) {
            $this->success('', [
                'total_stats' => $totalStats,
                'daily_stats' => $dailyStats,
                'top_inviters' => $topInviters,
                'commission_stats' => $commissionStats,
                'commission_by_source' => $commissionBySource,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('daily_stats', $dailyStats);
        $this->view->assign('top_inviters', $topInviters);
        $this->view->assign('commission_stats', $commissionStats);
        $this->view->assign('commission_by_source', $commissionBySource);
        return $this->view->fetch();
    }
}
