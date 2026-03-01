<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\input\Argument;
use think\console\Output;
use app\common\library\DataMigrationService;

/**
 * 数据迁移命令
 * 
 * 使用方法：
 * php think data:migrate --action=stats              # 查看数据统计
 * php think data:migrate --action=coin_log           # 迁移金币流水
 * php think data:migrate --action=watch_record       # 迁移观看记录
 * php think data:migrate --action=watch_session      # 迁移观看会话
 * php think data:migrate --action=risk_log           # 迁移风控日志
 * php think data:migrate --action=user_behavior      # 迁移用户行为
 * php think data:migrate --action=anticheat          # 迁移防刷日志
 * php think data:migrate --action=red_packet         # 迁移红包记录
 * php think data:migrate --action=commission         # 迁移分佣日志
 * php think data:migrate --action=transfer           # 迁移打款日志
 * php think data:migrate --action=inactive           # 标记未活跃用户
 * php think data:migrate --action=clean              # 清理过期统计
 * php think data:migrate --action=all                # 执行所有迁移
 * 
 * 选项：
 * --days=90          迁移多少天前的数据（默认90天）
 * --batch=1000       批量处理数量（默认1000）
 * --delete           迁移后删除源数据
 * --table=coin_log   指定单个表操作
 */
class DataMigration extends Command
{
    protected function configure()
    {
        $this->setName('data:migrate')
            ->setDescription('数据迁移工具 - 迁移历史冷数据到归档表')
            ->addOption('action', 'a', Option::VALUE_OPTIONAL, '执行动作: stats/coin_log/watch_record/watch_session/risk_log/user_behavior/anticheat/red_packet/commission/transfer/inactive/clean/all/file:sync/file:status/file:run/file:pending/file:reset', 'stats')
            ->addOption('days', 'd', Option::VALUE_OPTIONAL, '迁移多少天前的数据', 90)
            ->addOption('batch', 'b', Option::VALUE_OPTIONAL, '批量处理数量', 1000)
            ->addOption('delete', null, Option::VALUE_NONE, '迁移后删除源数据')
            ->addOption('table', 't', Option::VALUE_OPTIONAL, '指定单个表（用于stats操作）', null)
            ->addOption('file', 'f', Option::VALUE_OPTIONAL, '指定迁移文件名', null)
            ->addOption('rollback', 'r', Option::VALUE_OPTIONAL, '回滚指定批次', null);
    }
    
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $days = (int) $input->getOption('days');
        $batch = (int) $input->getOption('batch');
        $delete = $input->getOption('delete');
        $table = $input->getOption('table');
        $file = $input->getOption('file');
        $rollback = $input->getOption('rollback');
        
        $service = new DataMigrationService();
        $service->setBatchSize($batch);
        
        $output->writeln("========================================");
        $output->writeln("数据迁移工具");
        $output->writeln("时间: " . date('Y-m-d H:i:s'));
        $output->writeln("========================================\n");
        
        $startTime = microtime(true);
        
        // 处理迁移文件相关操作
        if (strpos($action, 'file:') === 0) {
            $this->handleFileMigration($action, $output, $file, $rollback);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime), 2);
            $output->writeln("\n========================================");
            $output->writeln("执行完成，总耗时: {$duration} 秒");
            $output->writeln("========================================");
            return;
        }
        
        switch ($action) {
            case 'stats':
                // 查看数据统计
                $this->showStats($service, $output, $table, $days);
                break;
                
            case 'check':
                // 检查表是否存在
                $this->checkTables($service, $output);
                break;
                
            case 'coin_log':
                // 迁移金币流水
                $output->writeln("迁移金币流水数据 ({$days}天前)...\n");
                $result = $service->migrateCoinLog($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'watch_record':
                // 迁移观看记录
                $output->writeln("迁移视频观看记录 ({$days}天前)...\n");
                $result = $service->migrateVideoWatchRecord($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'watch_session':
                // 迁移观看会话
                $output->writeln("迁移观看会话记录 ({$days}天前)...\n");
                $result = $service->migrateVideoWatchSession($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'risk_log':
                // 迁移风控日志
                $output->writeln("迁移风控日志 ({$days}天前)...\n");
                $result = $service->migrateRiskLog($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'user_behavior':
                // 迁移用户行为
                $output->writeln("迁移用户行为记录 ({$days}天前)...\n");
                $result = $service->migrateUserBehavior($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'anticheat':
                // 迁移防刷日志
                $output->writeln("迁移防刷日志 ({$days}天前)...\n");
                $result = $service->migrateAnticheatLog($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'red_packet':
                // 迁移红包记录
                $output->writeln("迁移红包领取记录 ({$days}天前)...\n");
                $result = $service->migrateRedPacketRecord($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'commission':
                // 迁移分佣日志
                $output->writeln("迁移邀请分佣日志 ({$days}天前)...\n");
                $result = $service->migrateInviteCommissionLog($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'transfer':
                // 迁移打款日志
                $output->writeln("迁移微信打款日志 ({$days}天前)...\n");
                $result = $service->migrateWechatTransferLog($days, $delete);
                $this->showResult($result, $output);
                break;
                
            case 'inactive':
                // 标记未活跃用户
                $output->writeln("标记未活跃用户 ({$days}天未登录)...\n");
                $result = $service->markInactiveUsers($days);
                $this->showResult($result, $output);
                break;
                
            case 'clean':
                // 清理过期统计
                $output->writeln("清理过期统计数据 (保留{$days}天)...\n");
                $result1 = $service->cleanDailyRewardStats($days);
                $result2 = $service->cleanBehaviorStats($days);
                $this->showResult($result1, $output);
                $this->showResult($result2, $output);
                break;
                
            case 'all':
                // 执行所有迁移
                $output->writeln("执行所有迁移任务...\n");
                $options = [
                    'coin_log_days' => $days,
                    'watch_record_days' => max($days, 180),
                    'watch_session_days' => min($days, 30),
                    'risk_log_days' => max($days, 180),
                    'behavior_days' => $days,
                    'anticheat_days' => $days,
                    'red_packet_days' => max($days, 365),
                    'commission_days' => max($days, 365),
                    'transfer_days' => max($days, 365),
                    'delete_source' => $delete,
                    'inactive_days' => max($days, 90),
                    'stats_keep_days' => max($days, 365),
                ];
                $results = $service->migrateAll($options);
                
                foreach ($results as $key => $result) {
                    $this->showResult($result, $output, $key);
                }
                break;
                
            default:
                $output->writeln("<error>未知操作: {$action}</error>");
                $output->writeln("\n可用操作:");
                $output->writeln("  check         - 检查表是否存在");
                $output->writeln("  stats         - 查看数据统计");
                $output->writeln("  coin_log      - 迁移金币流水");
                $output->writeln("  watch_record  - 迁移观看记录");
                $output->writeln("  watch_session - 迁移观看会话");
                $output->writeln("  risk_log      - 迁移风控日志");
                $output->writeln("  user_behavior - 迁移用户行为");
                $output->writeln("  anticheat     - 迁移防刷日志");
                $output->writeln("  red_packet    - 迁移红包记录");
                $output->writeln("  commission    - 迁移分佣日志");
                $output->writeln("  transfer      - 迁移打款日志");
                $output->writeln("  inactive      - 标记未活跃用户");
                $output->writeln("  clean         - 清理过期统计");
                $output->writeln("  all           - 执行所有迁移");
                return;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime), 2);
        
        $output->writeln("\n========================================");
        $output->writeln("执行完成，总耗时: {$duration} 秒");
        $output->writeln("========================================");
    }
    
    /**
     * 显示数据统计
     */
    protected function showStats($service, $output, $table = null, $days = 30)
    {
        // 先检查表是否存在
        $requiredTables = $table ? [$table] : [
            'coin_log',
            'video_watch_record',
            'video_watch_session',
            'risk_log',
            'user_behavior',
            'anticheat_log',
            'red_packet_record',
            'invite_commission_log',
            'wechat_transfer_log',
        ];
        
        $missingTables = $service->getMissingTables($requiredTables);
        
        if (!empty($missingTables)) {
            $output->writeln("<error>警告: 以下表不存在:</error>");
            foreach ($missingTables as $missingTable) {
                $output->writeln("  - {$missingTable}");
            }
            $output->writeln("\n请先执行SQL创建表结构:");
            $output->writeln("  mysql -u root advnet < sql/video_coin.sql");
            $output->writeln("  mysql -u root advnet < sql/red_packet_task.sql");
            $output->writeln("  mysql -u root advnet < sql/risk_control_system.sql");
            $output->writeln("  mysql -u root advnet < sql/withdraw_system.sql");
            $output->writeln("  mysql -u root advnet < sql/invite_commission.sql");
            $output->writeln("\n使用 --action=check 查看详细的表检查结果\n");
        }
        
        $output->writeln("数据表统计信息 (统计{$days}天前的数据)");
        $output->writeln(str_repeat("-", 80));
        $output->writeln(sprintf("%-25s | %12s | %12s | %12s | %10s", 
            '表名', '总数据量', '待归档', '近期数据', '归档表'));
        $output->writeln(str_repeat("-", 80));
        
        if ($table) {
            // 单个表统计
            $stats = $service->getTableStats($table, $days);
            $this->printTableStats($table, $stats, $output);
        } else {
            // 所有表统计
            $allStats = $service->getAllTableStats();
            foreach ($allStats as $tableName => $stats) {
                $this->printTableStats($tableName, $stats, $output, $days);
            }
        }
        
        $output->writeln(str_repeat("-", 80));
        $output->writeln("\n提示: 使用 --action=migrate --table=表名 进行迁移");
    }
    
    /**
     * 检查表是否存在
     */
    protected function checkTables($service, $output)
    {
        $output->writeln("检查数据表是否存在...\n");
        
        $tables = [
            'coin_log' => '金币流水表',
            'video_watch_record' => '视频观看记录表',
            'video_watch_session' => '观看会话表',
            'risk_log' => '风控日志表',
            'user_behavior' => '用户行为记录表',
            'anticheat_log' => '防刷日志表',
            'red_packet_record' => '红包领取记录表',
            'invite_commission_log' => '邀请分佣日志表',
            'wechat_transfer_log' => '微信打款日志表',
            'user_daily_reward_stat' => '用户每日收益统计表',
            'user_behavior_stat' => '用户行为统计表',
            'user' => '用户表',
            'coin_account' => '金币账户表',
        ];
        
        $output->writeln(str_repeat("-", 60));
        $output->writeln(sprintf("%-30s | %-12s | %s", '表名', '状态', '说明'));
        $output->writeln(str_repeat("-", 60));
        
        $missing = [];
        foreach ($tables as $table => $desc) {
            $exists = $service->tableExists($table);
            $status = $exists ? '<info>存在</info>' : '<error>不存在</error>';
            $output->writeln(sprintf("%-30s | %-20s | %s", $table, $status, $desc));
            if (!$exists) {
                $missing[] = $table;
            }
        }
        
        $output->writeln(str_repeat("-", 60));
        
        if (!empty($missing)) {
            $output->writeln("\n<error>缺少 {$missing} 个表，请执行以下SQL创建表结构:</error>\n");
            $output->writeln("  # 主表结构");
            $output->writeln("  mysql -u root advnet < sql/video_coin.sql");
            $output->writeln("  mysql -u root advnet < sql/red_packet_task.sql");
            $output->writeln("  mysql -u root advnet < sql/risk_control_system.sql");
            $output->writeln("  mysql -u root advnet < sql/withdraw_system.sql");
            $output->writeln("  mysql -u root advnet < sql/invite_commission.sql");
            $output->writeln("\n  # 配置表");
            $output->writeln("  mysql -u root advnet < sql/migration_config.sql");
        } else {
            $output->writeln("\n<info>所有必要的表都存在，可以执行数据迁移</info>");
        }
    }
    
    /**
     * 打印单个表统计
     */
    protected function printTableStats($table, $stats, $output, $days = 30)
    {
        if (isset($stats['error'])) {
            $output->writeln(sprintf("%-25s | 错误: %s", $table, $stats['error']));
            return;
        }
        
        $archiveStatus = isset($stats['archive_exists']) && $stats['archive_exists'] 
            ? "存在({$stats['archive_count']})" 
            : "不存在";
        
        // 重新计算待归档数量
        $beforeTime = time() - ($days * 86400);
        $oldCount = \think\Db::name($table)->where('createtime', '<', $beforeTime)->count();
        
        $output->writeln(sprintf("%-25s | %12s | %12s | %12s | %10s",
            $table,
            number_format($stats['total_count']),
            number_format($oldCount),
            number_format($stats['total_count'] - $oldCount),
            $archiveStatus
        ));
    }
    
    /**
     * 显示迁移结果
     */
    protected function showResult($result, $output, $name = null)
    {
        $title = isset($result['table']) ? $result['table'] : ($name ?: '未知');
        
        $output->writeln("\n--- {$title} ---");
        
        if (isset($result['error'])) {
            $output->writeln("<error>错误: {$result['error']}</error>");
            return;
        }
        
        if (isset($result['total'])) {
            $output->writeln("总数据量: " . number_format($result['total']));
        }
        
        if (isset($result['migrated'])) {
            $output->writeln("已迁移: " . number_format($result['migrated']));
        }
        
        if (isset($result['failed'])) {
            $output->writeln("失败: " . number_format($result['failed']));
        }
        
        if (isset($result['deleted'])) {
            $output->writeln("已删除: " . number_format($result['deleted']));
        }
        
        if (isset($result['marked'])) {
            $output->writeln("已标记: " . number_format($result['marked']));
        }
        
        if (isset($result['duration'])) {
            $output->writeln("耗时: {$result['duration']} 秒");
        }
    }
    
    /**
     * 处理迁移文件操作
     */
    protected function handleFileMigration($action, $output, $file = null, $rollback = null)
    {
        $fileService = new \app\common\library\MigrationFileService();
        
        switch ($action) {
            case 'file:sync':
                // 同步迁移文件到数据库
                $output->writeln("扫描并同步迁移文件...\n");
                $result = $fileService->syncMigrationFiles();
                $output->writeln("同步完成:");
                $output->writeln("  - 扫描文件数: {$result['total_files']}");
                $output->writeln("  - 新增记录: {$result['added']}");
                $output->writeln("  - 更新记录: {$result['updated']}");
                $output->writeln("  - 跳过记录: {$result['skipped']}");
                break;
                
            case 'file:status':
                // 查看迁移状态
                $output->writeln("迁移文件状态:\n");
                $stats = $fileService->getMigrationStats();
                $output->writeln("总文件数: {$stats['total_files']}");
                $output->writeln("记录总数: {$stats['total_records']}");
                $output->writeln("待执行: {$stats['pending']}");
                $output->writeln("执行中: {$stats['running']}");
                $output->writeln("已完成: {$stats['completed']}");
                $output->writeln("失败: {$stats['failed']}");
                
                $output->writeln("\n最近的迁移记录:");
                $output->writeln(str_repeat("-", 80));
                $records = $fileService->getAllMigrationRecords();
                foreach (array_slice($records, 0, 10) as $record) {
                    $status = $record['status'];
                    $statusText = $status == 'completed' ? '<info>完成</info>' : 
                                 ($status == 'failed' ? '<error>失败</error>' : 
                                 ($status == 'running' ? '<comment>运行中</comment>' : '待执行'));
                    $executedAt = $record['executed_at'] ? date('Y-m-d H:i:s', $record['executed_at']) : '-';
                    $output->writeln(sprintf("%-40s | %-8s | %s | %s", 
                        $record['migration_name'], $statusText, $record['batch'] ?: '-', $executedAt));
                }
                break;
                
            case 'file:pending':
                // 查看待执行的迁移
                $output->writeln("待执行的迁移文件:\n");
                $pending = $fileService->getPendingMigrations();
                
                if (empty($pending)) {
                    $output->writeln("<info>没有待执行的迁移文件</info>");
                    break;
                }
                
                $output->writeln(str_repeat("-", 80));
                $output->writeln(sprintf("%-40s | %-10s | %s", '文件名', '状态', '校验和'));
                $output->writeln(str_repeat("-", 80));
                
                foreach ($pending as $migration) {
                    $status = $migration['status'];
                    $statusText = $status == 'new' ? '<comment>新文件</comment>' : 
                                 ($status == 'modified' ? '<comment>已修改</comment>' : 
                                 ($status == 'failed' ? '<error>失败</error>' : '待执行'));
                    $output->writeln(sprintf("%-40s | %-20s | %s", 
                        $migration['name'], $statusText, substr($migration['checksum'], 0, 8)));
                }
                
                $output->writeln("\n提示: 执行 php think data:migrate --action=file:run 来执行所有待执行的迁移");
                break;
                
            case 'file:run':
                // 执行迁移文件
                if ($file) {
                    // 执行单个文件
                    $output->writeln("执行迁移文件: {$file}\n");
                    $result = $fileService->executeMigration($file);
                    
                    if ($result['success']) {
                        if (isset($result['skipped']) && $result['skipped']) {
                            $output->writeln("<comment>{$result['message']}</comment>");
                        } else {
                            $output->writeln("<info>执行成功</info>");
                            $output->writeln("批次: {$result['batch']}");
                            $output->writeln("耗时: {$result['execution_time']} 秒");
                        }
                    } else {
                        $output->writeln("<error>执行失败: {$result['error']}</error>");
                    }
                } else {
                    // 执行所有待执行的迁移
                    $output->writeln("执行所有待执行的迁移文件...\n");
                    $result = $fileService->executeAllPending();
                    
                    $output->writeln("\n执行结果:");
                    $output->writeln("  - 总数: {$result['total']}");
                    $output->writeln("  - 成功: {$result['executed']}");
                    $output->writeln("  - 失败: {$result['failed']}");
                    
                    if ($result['failed'] > 0) {
                        $output->writeln("\n失败的迁移:");
                        foreach ($result['results'] as $r) {
                            if (!$r['success']) {
                                $output->writeln("  - {$r['name']}: {$r['error']}");
                            }
                        }
                    }
                }
                break;
                
            case 'file:reset':
                // 重置失败的迁移
                $output->writeln("重置失败的迁移状态...\n");
                $count = $fileService->resetFailedMigrations();
                $output->writeln("<info>已重置 {$count} 个失败的迁移</info>");
                break;
                
            case 'file:rollback':
                // 回滚指定批次
                if (!$rollback) {
                    $output->writeln("<error>请指定回滚批次号: --rollback=N</error>");
                    break;
                }
                
                $output->writeln("回滚批次 {$rollback} 的迁移...\n");
                $result = $fileService->rollbackBatch($rollback);
                $output->writeln("<info>已回滚 {$result['rolled_back']} 个迁移</info>");
                
                foreach ($result['migrations'] as $m) {
                    $output->writeln("  - {$m['name']}");
                }
                break;
                
            default:
                $output->writeln("<error>未知操作: {$action}</error>");
                $output->writeln("\n可用的迁移文件操作:");
                $output->writeln("  file:sync     - 扫描并同步迁移文件");
                $output->writeln("  file:status   - 查看迁移状态");
                $output->writeln("  file:pending  - 查看待执行的迁移");
                $output->writeln("  file:run      - 执行迁移 (使用 --file=xxx.sql 指定单个文件)");
                $output->writeln("  file:reset    - 重置失败的迁移");
                $output->writeln("  file:rollback - 回滚指定批次 (使用 --rollback=N)");
                break;
        }
    }
}
