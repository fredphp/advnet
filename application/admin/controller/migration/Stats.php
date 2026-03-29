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
        
        // 初始化统计数据
        $stats = [
            'total_migrations' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'total_migrated' => 0,
            'total_deleted' => 0,
        ];
        
        $byAction = [];
        $recentLogs = [];
        $tableExists = false;
        
        try {
            // 检查表是否存在
            $tableCheck = Db::query("SHOW TABLES LIKE '{$prefix}data_migration_log'");
            $tableExists = !empty($tableCheck);
            
            if ($tableExists) {
                // 总记录数
                $countResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log");
                $stats['total_migrations'] = intval($countResult[0]['total'] ?? 0);
                
                // 成功数（没有错误信息）
                $successResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log WHERE error IS NULL OR error = ''");
                $stats['success_count'] = intval($successResult[0]['total'] ?? 0);
                
                // 失败数
                $failedResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log WHERE error IS NOT NULL AND error != ''");
                $stats['failed_count'] = intval($failedResult[0]['total'] ?? 0);
                
                // 总迁移数量
                $migratedResult = Db::query("SELECT COALESCE(SUM(migrated_count), 0) as total FROM {$prefix}data_migration_log");
                $stats['total_migrated'] = intval($migratedResult[0]['total'] ?? 0);
                
                // 总删除数量
                $deletedResult = Db::query("SELECT COALESCE(SUM(deleted_count), 0) as total FROM {$prefix}data_migration_log");
                $stats['total_deleted'] = intval($deletedResult[0]['total'] ?? 0);
                
                // 按类型统计
                $byAction = Db::query("
                    SELECT 
                        action,
                        COUNT(*) as count,
                        COALESCE(SUM(migrated_count), 0) as migrated,
                        COALESCE(SUM(failed_count), 0) as failed,
                        COALESCE(SUM(deleted_count), 0) as deleted,
                        SUM(CASE WHEN error IS NULL OR error = '' THEN 1 ELSE 0 END) as success
                    FROM {$prefix}data_migration_log
                    GROUP BY action
                    ORDER BY count DESC
                ");
                
                foreach ($byAction as &$item) {
                    $item['action_text'] = $this->getActionText($item['action']);
                    $item['count'] = intval($item['count']);
                    $item['migrated'] = intval($item['migrated']);
                    $item['failed'] = intval($item['failed']);
                    $item['deleted'] = intval($item['deleted']);
                    $item['success'] = intval($item['success']);
                }
                
                // 最近执行记录
                $recentLogs = Db::query("
                    SELECT id, action, table_name, migrated_count, deleted_count, error, createtime
                    FROM {$prefix}data_migration_log
                    ORDER BY createtime DESC
                    LIMIT 10
                ");
                
                foreach ($recentLogs as &$log) {
                    $log['action_text'] = $this->getActionText($log['action']);
                    $log['status_text'] = (!empty($log['error'])) ? '失败' : '成功';
                    $log['status_class'] = (!empty($log['error'])) ? 'danger' : 'success';
                    $log['createtime_text'] = $log['createtime'] ? date('Y-m-d H:i:s', $log['createtime']) : '-';
                    $log['migrated_count'] = intval($log['migrated_count'] ?? 0);
                    $log['deleted_count'] = intval($log['deleted_count'] ?? 0);
                }
            }
        } catch (\Exception $e) {
            // 记录错误但继续显示页面
            \think\Log::error('Migration stats error: ' . $e->getMessage());
        }

        $this->view->assign('stats', $stats);
        $this->view->assign('by_action', $byAction);
        $this->view->assign('recent_logs', $recentLogs);
        $this->view->assign('table_exists', $tableExists);
        
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
