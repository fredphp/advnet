<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\library\InviteCommissionTask;

/**
 * 邀请分佣定时任务命令
 * 
 * 使用方法：
 * php think invite:commission --action=settle       # 结算待处理分佣
 * php think invite:commission --action=daily        # 每日统计重置
 * php think invite:commission --action=weekly       # 每周统计重置
 * php think invite:commission --action=monthly      # 每月统计重置
 * php think invite:commission --action=clean        # 清理过期记录
 * php think invite:commission --action=summary      # 汇总每日统计
 */
class InviteCommission extends Command
{
    protected function configure()
    {
        $this->setName('invite:commission')
            ->setDescription('邀请分佣定时任务')
            ->addOption('action', 'a', Option::VALUE_OPTIONAL, '执行动作', 'settle')
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, '处理数量限制', 100)
            ->addOption('date', 'd', Option::VALUE_OPTIONAL, '指定日期', null);
    }
    
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $limit = (int) $input->getOption('limit');
        $date = $input->getOption('date');
        
        $task = new InviteCommissionTask();
        
        $output->writeln("开始执行: {$action}");
        $startTime = microtime(true);
        
        switch ($action) {
            case 'settle':
                // 结算待处理分佣
                $result = $task->settlePendingCommission($limit);
                $output->writeln("处理结果: 总数={$result['total']}, 成功={$result['success']}, 失败={$result['failed']}");
                break;
                
            case 'daily':
                // 每日统计重置
                $result = $task->resetDailyStats();
                $output->writeln("每日统计重置: 邀请统计={$result['invite_stat']}, 佣金统计={$result['commission_stat']}");
                break;
                
            case 'weekly':
                // 每周统计重置
                $result = $task->resetWeeklyStats();
                $output->writeln("每周统计重置: 邀请统计={$result['invite_stat']}, 佣金统计={$result['commission_stat']}");
                break;
                
            case 'monthly':
                // 每月统计重置
                $result = $task->resetMonthlyStats();
                $output->writeln("每月统计重置: 邀请统计={$result['invite_stat']}, 佣金统计={$result['commission_stat']}");
                break;
                
            case 'clean':
                // 清理过期记录
                $count = $task->cleanExpiredLogs(90);
                $output->writeln("清理过期记录: {$count}条");
                break;
                
            case 'summary':
                // 汇总每日统计
                $result = $task->summaryDailyCommission($date);
                $output->writeln("每日统计汇总: 日期={$result['date']}, 总额={$result['total']}, 数量={$result['count']}");
                break;
                
            case 'period':
                // 更新周期统计
                $result = $task->updatePeriodStats();
                $output->writeln("周期统计更新: 周={$result['week']}, 月={$result['month']}");
                break;
                
            case 'frozen':
                // 处理冻结分佣
                $count = $task->processFrozenCommission(30);
                $output->writeln("处理冻结分佣: {$count}条");
                break;
                
            case 'all':
                // 执行所有任务
                $output->writeln("执行所有任务...");
                
                $output->writeln("\n1. 结算待处理分佣");
                $result = $task->settlePendingCommission($limit);
                $output->writeln("   总数={$result['total']}, 成功={$result['success']}, 失败={$result['failed']}");
                
                $output->writeln("\n2. 汇总每日统计");
                $result = $task->summaryDailyCommission($date);
                $output->writeln("   总额={$result['total']}, 数量={$result['count']}");
                
                $output->writeln("\n3. 更新周期统计");
                $result = $task->updatePeriodStats();
                $output->writeln("   周={$result['week']}, 月={$result['month']}");
                
                break;
                
            default:
                $output->writeln("未知操作: {$action}");
                $output->writeln("可用操作: settle, daily, weekly, monthly, clean, summary, period, frozen, all");
                return;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        $output->writeln("\n执行完成, 耗时: {$duration}ms");
    }
}
