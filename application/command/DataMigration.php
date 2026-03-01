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
            ->addOption('action', 'a', Option::VALUE_OPTIONAL, '执行动作: stats/coin_log/watch_record/watch_session/risk_log/user_behavior/anticheat/red_packet/commission/transfer/inactive/clean/all', 'stats')
            ->addOption('days', 'd', Option::VALUE_OPTIONAL, '迁移多少天前的数据', 90)
            ->addOption('batch', 'b', Option::VALUE_OPTIONAL, '批量处理数量', 1000)
            ->addOption('delete', null, Option::VALUE_NONE, '迁移后删除源数据')
            ->addOption('table', 't', Option::VALUE_OPTIONAL, '指定单个表（用于stats操作）', null);
    }
    
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $days = (int) $input->getOption('days');
        $batch = (int) $input->getOption('batch');
        $delete = $input->getOption('delete');
        $table = $input->getOption('table');
        
        $service = new DataMigrationService();
        $service->setBatchSize($batch);
        
        $output->writeln("========================================");
        $output->writeln("数据迁移工具");
        $output->writeln("时间: " . date('Y-m-d H:i:s'));
        $output->writeln("========================================\n");
        
        $startTime = microtime(true);
        
        switch ($action) {
            case 'stats':
                // 查看数据统计
                $this->showStats($service, $output, $table, $days);
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
}
