<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移执行
 */
class Execute extends Backend
{
    /**
     * 执行页面
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $action = $this->request->post('action');
            $params = $this->request->post('params/a', []);
            
            try {
                $result = $this->executeMigration($action, $params);
                $this->success('执行成功', null, $result);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        // 获取可执行的迁移任务
        $tasks = [
            ['name' => 'coin_log', 'title' => '金币流水迁移', 'description' => '迁移历史金币流水数据到归档表'],
            ['name' => 'watch_record', 'title' => '观看记录迁移', 'description' => '迁移视频观看记录到归档表'],
            ['name' => 'watch_session', 'title' => '观看会话迁移', 'description' => '迁移观看会话记录到归档表'],
            ['name' => 'risk_log', 'title' => '风控日志迁移', 'description' => '迁移风控日志到归档表'],
            ['name' => 'user_behavior', 'title' => '用户行为迁移', 'description' => '迁移用户行为数据到归档表'],
            ['name' => 'anticheat', 'title' => '防刷日志迁移', 'description' => '迁移防刷日志到归档表'],
            ['name' => 'red_packet', 'title' => '红包记录迁移', 'description' => '迁移红包领取记录到归档表'],
            ['name' => 'commission', 'title' => '分佣日志迁移', 'description' => '迁移邀请分佣日志到归档表'],
            ['name' => 'transfer', 'title' => '打款日志迁移', 'description' => '迁移微信打款日志到归档表'],
            ['name' => 'inactive', 'title' => '未活跃用户标记', 'description' => '标记长期未登录的用户'],
            ['name' => 'clean', 'title' => '清理过期统计', 'description' => '清理过期的统计数据'],
        ];

        $this->view->assign('tasks', $tasks);
        return $this->view->fetch();
    }

    /**
     * 执行迁移
     */
    protected function executeMigration($action, $params)
    {
        $prefix = config('database.prefix');
        $now = time();
        
        // 记录日志
        Db::execute("
            INSERT INTO {$prefix}data_migration_log 
            (table_name, action, params, createtime)
            VALUES (?, ?, ?, ?)
        ", [$action, $action, json_encode($params), $now]);
        
        $logId = Db::getLastInsID();

        try {
            // 调用数据迁移服务
            $service = new \app\common\library\DataMigrationService();
            
            $result = [];
            $migratedCount = 0;
            $failedCount = 0;
            $deletedCount = 0;
            
            switch ($action) {
                case 'coin_log':
                    $days = $params['days'] ?? 90;
                    $result = $service->migrateCoinLog($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'watch_record':
                    $days = $params['days'] ?? 180;
                    $result = $service->migrateVideoWatchRecord($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'watch_session':
                    $days = $params['days'] ?? 30;
                    $result = $service->migrateVideoWatchSession($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'risk_log':
                    $days = $params['days'] ?? 180;
                    $result = $service->migrateRiskLog($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'user_behavior':
                    $days = $params['days'] ?? 90;
                    $result = $service->migrateUserBehavior($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'anticheat':
                    $days = $params['days'] ?? 90;
                    $result = $service->migrateAnticheatLog($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'red_packet':
                    $days = $params['days'] ?? 365;
                    $result = $service->migrateRedPacketRecord($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'commission':
                    $days = $params['days'] ?? 365;
                    $result = $service->migrateInviteCommissionLog($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'transfer':
                    $days = $params['days'] ?? 365;
                    $result = $service->migrateWechatTransferLog($days, false);
                    $migratedCount = $result['migrated'] ?? 0;
                    break;
                    
                case 'inactive':
                    $days = $params['days'] ?? 90;
                    $result = $service->markInactiveUsers($days);
                    $migratedCount = $result['marked'] ?? 0;
                    break;
                    
                case 'clean':
                    $days = $params['days'] ?? 365;
                    $result = $service->cleanDailyRewardStats($days);
                    $deletedCount = $result['deleted'] ?? 0;
                    break;
                    
                default:
                    throw new \Exception("未知的迁移类型: {$action}");
            }

            // 更新日志
            Db::execute("
                UPDATE {$prefix}data_migration_log 
                SET migrated_count = ?, failed_count = ?, deleted_count = ?, result = ?
                WHERE id = ?
            ", [$migratedCount, $failedCount, $deletedCount, json_encode($result), $logId]);

            return [
                'migrated' => $migratedCount,
                'failed' => $failedCount,
                'deleted' => $deletedCount,
            ];

        } catch (\Exception $e) {
            // 更新错误日志
            Db::execute("
                UPDATE {$prefix}data_migration_log 
                SET error = ?
                WHERE id = ?
            ", [$e->getMessage(), $logId]);
            
            throw $e;
        }
    }
}
