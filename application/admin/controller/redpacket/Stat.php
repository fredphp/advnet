<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据统计
 */
class Stat extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 统计概览
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            // 任务统计
            $taskStats = Db::name('red_packet_task')
                ->field('COUNT(*) as total, 
                    SUM(total_amount) as total_amount,
                    SUM(receive_amount) as receive_amount,
                    SUM(complete_count) as complete_count')
                ->find();
            
            // 参与统计
            $participationStats = Db::name('task_participation')
                ->field('COUNT(*) as total,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as wait_audit,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as pass,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as rewarded,
                    SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as rejected')
                ->find();
            
            // 每日统计
            $dailyStats = Db::name('task_participation')
                ->whereTime('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                    COUNT(*) as count,
                    SUM(reward_coin) as reward_coin')
                ->group('date')
                ->order('date', 'asc')
                ->select();
            
            // 任务类型分布
            $typeStats = Db::name('red_packet_task')
                ->field('type, COUNT(*) as count, SUM(receive_amount) as amount')
                ->group('type')
                ->select();
            
            $this->success('', null, [
                'task' => $taskStats,
                'participation' => $participationStats,
                'daily' => $dailyStats,
                'type' => $typeStats
            ]);
        }
        
        return $this->view->fetch();
    }
}
