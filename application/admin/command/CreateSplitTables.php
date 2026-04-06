<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\Db;
use think\Log;

/**
 * 自动创建分表命令
 * 
 * 功能：
 * 1. 创建当月分表
 * 2. 创建下月分表（提前创建，避免首次插入时延迟）
 * 3. 可配置提前创建的月数
 * 
 * 使用方法：
 * php think split:create-tables                    # 创建当月和下月分表
 * php think split:create-tables --months=3         # 创建未来3个月的分表
 * php think split:create-tables --type=withdraw    # 只创建提现订单分表
 * 
 * 定时任务配置（每月1号凌晨执行）：
 * 0 0 1 * * php /path/to/think split:create-tables --months=2
 */
class CreateSplitTables extends Command
{
    // 需要分表的模型配置
    protected $splitModels = [
        'withdraw' => [
            'class' => 'app\common\model\WithdrawOrderSplit',
            'base_table' => 'withdraw_order',
        ],
        'redpacket' => [
            'class' => 'app\common\model\RedPacketTaskSplit',
            'base_table' => 'red_packet_task',
        ],
        'participation' => [
            'class' => 'app\common\model\UserRedPacketAccumulateSplit',
            'base_table' => 'user_red_packet_accumulate',
        ],
        'adincome' => [
            'class' => 'app\common\model\AdIncomeLogSplit',
            'base_table' => 'ad_income_log',
        ],
        'adredpacket' => [
            'class' => 'app\common\model\AdRedPacketSplit',
            'base_table' => 'ad_red_packet',
        ],
    ];

    protected function configure()
    {
        $this->setName('split:create-tables')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '分表类型: withdraw/redpacket/participation/all', 'all')
            ->addOption('months', 'm', Option::VALUE_OPTIONAL, '创建未来N个月的分表', 2)
            ->addOption('dry-run', 'd', Option::VALUE_NONE, '仅显示将要创建的表，不实际创建')
            ->setDescription('自动创建分表（按月分表）');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');
        $months = (int) $input->getOption('months');
        $dryRun = $input->getOption('dry-run');

        $output->writeln("========== 分表自动创建 ==========");
        $output->writeln("时间: " . date('Y-m-d H:i:s'));
        $output->writeln("类型: {$type}");
        $output->writeln("月数: {$months}");
        $output->writeln("模式: " . ($dryRun ? '预览模式（不实际创建）' : '执行模式'));
        $output->writeln("");

        $types = $type === 'all' 
            ? array_keys($this->splitModels) 
            : [$type];

        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($types as $t) {
            if (!isset($this->splitModels[$t])) {
                $output->error("未知的分表类型: {$t}");
                continue;
            }

            $output->writeln("--- 创建 {$t} 分表 ---");
            
            $result = $this->createTablesForType($t, $months, $dryRun, $output);
            $totalCreated += $result['created'];
            $totalSkipped += $result['skipped'];
            
            $output->writeln("");
        }

        $output->writeln("========== 执行完成 ==========");
        $output->writeln("创建: {$totalCreated} 个分表");
        $output->writeln("跳过: {$totalSkipped} 个分表（已存在）");

        if ($dryRun) {
            $output->info("这是预览模式，请去掉 --dry-run 参数来实际创建分表");
        }
    }

    /**
     * 为指定类型创建分表
     */
    protected function createTablesForType($type, $months, $dryRun, $output)
    {
        $config = $this->splitModels[$type];
        $baseTable = $config['base_table'];
        $prefix = config('database.prefix');
        
        $created = 0;
        $skipped = 0;

        // 检查主表是否存在
        $mainTable = $prefix . $baseTable;
        $mainExists = Db::query("SHOW TABLES LIKE '{$mainTable}'");
        
        if (empty($mainExists)) {
            $output->error("主表 {$mainTable} 不存在，请先创建主表");
            return ['created' => 0, 'skipped' => 0];
        }

        // 创建分表
        for ($i = 0; $i < $months; $i++) {
            $timestamp = strtotime("+{$i} months");
            $suffix = '_' . date('Ym', $timestamp);
            $tableName = $baseTable . $suffix;
            $fullTableName = $prefix . $tableName;

            // 检查表是否已存在
            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");

            if (!empty($exists)) {
                $output->writeln("  [跳过] {$fullTableName} - 已存在");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $output->writeln("  [将创建] {$fullTableName}");
                $created++;
                continue;
            }

            // 实际创建表
            try {
                $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`";
                Db::execute($createSql);
                
                $output->writeln("  [创建成功] {$fullTableName}");
                Log::info("分表自动创建成功: {$fullTableName}");
                $created++;
            } catch (\Exception $e) {
                $output->error("  [创建失败] {$fullTableName} - " . $e->getMessage());
                Log::error("分表自动创建失败: {$fullTableName}, 错误: " . $e->getMessage());
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
