<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移统计
 */
class Stats extends Backend
{
    /**
     * 统计页面
     */
    public function index()
    {
        $prefix = config('database.prefix');
        
        // 统计数据
        $stats = [
            'total_migrations' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'total_migrated' => 0,
            'total_deleted' => 0,
        ];
        
        // 检查表是否存在
        $tableExists = Db::query("SHOW TABLES LIKE '{$prefix}data_migration_log'");
        
        if ($tableExists) {
            // 总记录数
            $countResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log");
            $stats['total_migrations'] = $countResult[0]['total'] ?? 0;
            
            // 成功/失败数
            $successResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log WHERE (error IS NULL OR error = '')");
            $stats['success_count'] = $successResult[0]['total'] ?? 0;
            
            $failedResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log WHERE error IS NOT NULL AND error != ''");
            $stats['failed_count'] = $failedResult[0]['total'] ?? 0;
            
            // 总迁移数量
            $migratedResult = Db::query("SELECT SUM(migrated_count) as total FROM {$prefix}data_migration_log");
            $stats['total_migrated'] = $migratedResult[0]['total'] ?? 0;
            
            // 总删除数量
            $deletedResult = Db::query("SELECT SUM(deleted_count) as total FROM {$prefix}data_migration_log");
            $stats['total_deleted'] = $deletedResult[0]['total'] ?? 0;
        }

        // 按类型统计
        $byAction = [];
        if ($tableExists) {
            $byAction = Db::query("
                SELECT 
                    action,
                    COUNT(*) as count,
                    SUM(migrated_count) as migrated,
                    SUM(failed_count) as failed,
                    SUM(deleted_count) as deleted,
                    SUM(CASE WHEN error IS NULL OR error = '' THEN 1 ELSE 0 END) as success
                FROM {$prefix}data_migration_log
                GROUP BY action
                ORDER BY count DESC
            ");
            
            foreach ($byAction as &$item) {
                $item['action_text'] = $this->getActionText($item['action']);
            }
        }

        // 最近执行记录
        $recentLogs = [];
        if ($tableExists) {
            $recentLogs = Db::query("
                SELECT * FROM {$prefix}data_migration_log
                ORDER BY createtime DESC
                LIMIT 10
            ");
            
            foreach ($recentLogs as &$log) {
                $log['action_text'] = $this->getActionText($log['action']);
                $log['status_text'] = isset($log['error']) && $log['error'] ? '失败' : '成功';
                $log['createtime_text'] = $log['createtime'] ? date('Y-m-d H:i:s', $log['createtime']) : '-';
            }
        }

        $this->view->assign('stats', $stats);
        $this->view->assign('by_action', $byAction);
        $this->view->assign('recent_logs', $recentLogs);
        $this->view->assign('table_exists', !empty($tableExists));
        return $this->view->fetch();
    }
    
    /**
     * 获取操作类型文本
     */
    private function getActionText($action)
    {
        $actions = [
            'migrate' => '数据迁移',
            'clean' => '数据清理',
            'stats' => '统计计算',
            'coin_log' => '金币流水迁移',
            'watch_record' => '观看记录迁移',
            'watch_session' => '观看会话迁移',
            'risk_log' => '风控日志迁移',
            'user_behavior' => '用户行为迁移',
            'anticheat' => '防刷日志迁移',
            'red_packet' => '红包记录迁移',
            'commission' => '分佣日志迁移',
            'transfer' => '打款日志迁移',
            'inactive' => '未活跃用户标记',
        ];
        return $actions[$action] ?? $action;
    }
}
