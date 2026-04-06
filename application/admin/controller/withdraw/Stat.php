<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\model\WithdrawOrder;
use think\Db;

/**
 * 提现统计
 */
class Stat extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 统计页面
     */
    public function index()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        // 确保分表存在
        WithdrawOrder::ensureTablesExistByRange($startTimestamp, $endTimestamp);

        if ($this->request->isAjax()) {
            // 获取需要查询的分表
            $tables = WithdrawOrder::getTablesByRange($startTimestamp, $endTimestamp);
            $prefix = \think\Config::get('database.prefix');

            // 1. 汇总统计
            $stats = $this->getSummaryStats($tables, $startTimestamp, $endTimestamp);
            
            // 2. 每日趋势数据
            $dailyTrend = $this->getDailyTrend($tables, $startTimestamp, $endTimestamp);
            
            // 3. 状态分布
            $statusDistribution = $this->getStatusDistribution($tables, $startTimestamp, $endTimestamp);
            
            // 4. 用户提现排行
            $topUsers = $this->getTopUsers($tables, $startTimestamp, $endTimestamp);
            
            // 5. 每日统计明细
            $dailyStats = $this->getDailyStats($tables, $startTimestamp, $endTimestamp);

            $this->success('获取成功', null, [
                'summary' => $stats,
                'daily_trend' => $dailyTrend,
                'status_distribution' => $statusDistribution,
                'top_users' => $topUsers,
                'daily_stats' => $dailyStats,
            ]);
        }
        
        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        return $this->view->fetch();
    }
    
    /**
     * 获取汇总统计
     */
    protected function getSummaryStats($tables, $startTime, $endTime)
    {
        $stats = [
            'total_amount' => 0,
            'total_count' => 0,
            'pending_amount' => 0,
            'pending_count' => 0,
            'approved_amount' => 0,
            'approved_count' => 0,
            'success_amount' => 0,
            'success_count' => 0,
            'rejected_amount' => 0,
            'rejected_count' => 0,
            'approve_rate' => 0,
        ];

        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                // 总数（不含已取消）
                $stats['total_count'] += Db::name($table)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->where('status', '<>', WithdrawOrder::STATUS_CANCELED)
                    ->count();
                $stats['total_amount'] += Db::name($table)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->where('status', '<>', WithdrawOrder::STATUS_CANCELED)
                    ->sum('cash_amount');

                // 待审核
                $stats['pending_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_PENDING)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['pending_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_PENDING)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');

                // 审核通过（含打款中和已打款）
                $stats['approved_count'] += Db::name($table)
                    ->where('status', 'in', [WithdrawOrder::STATUS_APPROVED, WithdrawOrder::STATUS_TRANSFERING])
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['approved_amount'] += Db::name($table)
                    ->where('status', 'in', [WithdrawOrder::STATUS_APPROVED, WithdrawOrder::STATUS_TRANSFERING])
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');

                // 已完成
                $stats['success_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['success_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');

                // 已拒绝
                $stats['rejected_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_REJECTED)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['rejected_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_REJECTED)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');
            }
        }
        
        // 计算审核通过率
        $totalProcessed = $stats['success_count'] + $stats['rejected_count'];
        if ($totalProcessed > 0) {
            $stats['approve_rate'] = round($stats['success_count'] / $totalProcessed * 100, 1);
        }
        
        // 格式化金额
        $stats['total_amount'] = round($stats['total_amount'], 2);
        $stats['pending_amount'] = round($stats['pending_amount'], 2);
        $stats['approved_amount'] = round($stats['approved_amount'], 2);
        $stats['success_amount'] = round($stats['success_amount'], 2);
        $stats['rejected_amount'] = round($stats['rejected_amount'], 2);

        return $stats;
    }
    
    /**
     * 获取每日趋势数据
     */
    protected function getDailyTrend($tables, $startTime, $endTime)
    {
        $prefix = \think\Config::get('database.prefix');
        $trend = [];
        
        // 构建UNION查询
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return $trend;
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        // 按日期统计
        $sql = "SELECT 
                    FROM_UNIXTIME(wo.createtime, '%Y-%m-%d') as date,
                    COUNT(*) as total_count,
                    SUM(wo.cash_amount) as total_amount,
                    SUM(CASE WHEN wo.status = 3 THEN wo.cash_amount ELSE 0 END) as success_amount
                FROM {$unionSql}
                WHERE wo.createtime BETWEEN ? AND ?
                GROUP BY FROM_UNIXTIME(wo.createtime, '%Y-%m-%d')
                ORDER BY date ASC";
        
        $result = Db::query($sql, [$startTime, $endTime]);
        
        foreach ($result as $row) {
            $trend[] = [
                'date' => $row['date'],
                'total_count' => intval($row['total_count']),
                'total_amount' => round($row['total_amount'], 2),
                'success_amount' => round($row['success_amount'], 2),
            ];
        }
        
        return $trend;
    }
    
    /**
     * 获取状态分布
     */
    protected function getStatusDistribution($tables, $startTime, $endTime)
    {
        $prefix = \think\Config::get('database.prefix');
        $distribution = [];
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return $distribution;
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT 
                    wo.status,
                    COUNT(*) as count,
                    SUM(wo.cash_amount) as amount
                FROM {$unionSql}
                WHERE wo.createtime BETWEEN ? AND ?
                GROUP BY wo.status
                ORDER BY wo.status";
        
        $result = Db::query($sql, [$startTime, $endTime]);
        
        $statusNames = WithdrawOrder::$statusList;
        
        foreach ($result as $row) {
            $status = intval($row['status']);
            $distribution[] = [
                'status' => $status,
                'name' => $statusNames[$status] ?? '未知',
                'count' => intval($row['count']),
                'amount' => round($row['amount'], 2),
            ];
        }
        
        return $distribution;
    }
    
    /**
     * 获取用户提现排行
     */
    protected function getTopUsers($tables, $startTime, $endTime)
    {
        $prefix = \think\Config::get('database.prefix');
        $topUsers = [];
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return $topUsers;
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT 
                    wo.user_id,
                    COUNT(*) as withdraw_count,
                    SUM(wo.cash_amount) as total_amount,
                    SUM(CASE WHEN wo.status = 3 THEN wo.cash_amount ELSE 0 END) as success_amount
                FROM {$unionSql}
                WHERE wo.createtime BETWEEN ? AND ?
                GROUP BY wo.user_id
                ORDER BY total_amount DESC
                LIMIT 10";
        
        $result = Db::query($sql, [$startTime, $endTime]);
        
        foreach ($result as $row) {
            $topUsers[] = [
                'user_id' => intval($row['user_id']),
                'withdraw_count' => intval($row['withdraw_count']),
                'total_amount' => round($row['total_amount'], 2),
                'success_amount' => round($row['success_amount'], 2),
            ];
        }
        
        return $topUsers;
    }
    
    /**
     * 获取每日统计明细
     */
    protected function getDailyStats($tables, $startTime, $endTime)
    {
        $prefix = \think\Config::get('database.prefix');
        $dailyStats = [];
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return $dailyStats;
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT 
                    FROM_UNIXTIME(wo.createtime, '%Y-%m-%d') as date,
                    COUNT(*) as apply_count,
                    SUM(wo.cash_amount) as apply_amount,
                    SUM(CASE WHEN wo.status = 3 THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN wo.status = 3 THEN wo.cash_amount ELSE 0 END) as success_amount
                FROM {$unionSql}
                WHERE wo.createtime BETWEEN ? AND ?
                GROUP BY FROM_UNIXTIME(wo.createtime, '%Y-%m-%d')
                ORDER BY date DESC
                LIMIT 30";
        
        $result = Db::query($sql, [$startTime, $endTime]);
        
        foreach ($result as $row) {
            $dailyStats[] = [
                'date' => $row['date'],
                'apply_count' => intval($row['apply_count']),
                'apply_amount' => round($row['apply_amount'], 2),
                'success_count' => intval($row['success_count']),
                'success_amount' => round($row['success_amount'], 2),
            ];
        }
        
        return $dailyStats;
    }
}
