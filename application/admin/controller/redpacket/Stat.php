<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 红包数据统计
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
            $taskStats = [
                'total' => 0,
                'pending' => 0,
                'normal' => 0,
                'finished' => 0,
                'expired' => 0,
            ];

            $taskCount = Db::name('red_packet_task')
                ->field('status, COUNT(*) as count')
                ->group('status')
                ->select();

            foreach ($taskCount as $item) {
                $taskStats['total'] += $item['count'];
                if (isset($taskStats[$item['status']])) {
                    $taskStats[$item['status']] = $item['count'];
                }
            }

            // 领取统计（基于 user_red_packet_accumulate 表）
            $collectStats = [
                'total_grabs' => 0,          // 抢红包次数
                'total_collected' => 0,       // 已领取次数
                'total_amount' => 0,          // 总发放金额
                'total_base_amount' => 0,     // 基础金额
                'total_accumulate_amount' => 0, // 累加金额
                'total_clicks' => 0,          // 总点击次数
            ];

            // 检查表是否存在
            $prefix = \think\Config::get('database.prefix');
            $tableExists = Db::query("SHOW TABLES LIKE '{$prefix}user_red_packet_accumulate'");

            if (!empty($tableExists)) {
                $grabStats = Db::name('user_red_packet_accumulate')
                    ->field('COUNT(*) as total_grabs,
                        SUM(CASE WHEN is_collected = 1 THEN 1 ELSE 0 END) as total_collected,
                        SUM(total_amount) as total_amount,
                        SUM(base_amount) as total_base_amount,
                        SUM(accumulate_amount) as total_accumulate_amount,
                        SUM(click_count) as total_clicks')
                    ->find();

                if ($grabStats) {
                    $collectStats = [
                        'total_grabs' => $grabStats['total_grabs'] ?? 0,
                        'total_collected' => $grabStats['total_collected'] ?? 0,
                        'total_amount' => $grabStats['total_amount'] ?? 0,
                        'total_base_amount' => $grabStats['total_base_amount'] ?? 0,
                        'total_accumulate_amount' => $grabStats['total_accumulate_amount'] ?? 0,
                        'total_clicks' => $grabStats['total_clicks'] ?? 0,
                    ];
                }
            }

            // 每日统计
            $dailyStats = [];
            if (!empty($tableExists)) {
                $dailyStats = Db::name('user_red_packet_accumulate')
                    ->whereTime('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                    ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                        COUNT(*) as count,
                        SUM(total_amount) as amount,
                        SUM(CASE WHEN is_collected = 1 THEN 1 ELSE 0 END) as collected')
                    ->group('date')
                    ->order('date', 'asc')
                    ->select();
            }

            // 任务类型分布
            $typeStats = Db::name('red_packet_task')
                ->field('type, COUNT(*) as count')
                ->group('type')
                ->select();

            $this->success('', null, [
                'task' => $taskStats,
                'collect' => $collectStats,
                'daily' => $dailyStats ?: [],
                'type' => $typeStats ?: []
            ]);
        }

        return $this->view->fetch();
    }
}
