<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\library\AdIncomeService;
use app\common\model\AdRedPacket;
use think\Log;

/**
 * 广告收益定时结算命令
 *
 * 功能：
 * 1. 将 ad_freeze_balance 转换为红包
 * 2. 过期未领取的红包标记为已过期
 * 3. 查看统计信息
 *
 * 使用方法：
 * php think ad:settle                           # 默认执行结算（处理100条）
 * php think ad:settle --action=settle           # 结算待释放广告收益为红包
 * php think ad:settle --action=expire            # 过期未领取红包处理
 * php think ad:settle --action=stats             # 查看统计信息
 * php think ad:settle --action=all              # 执行所有任务
 * php think ad:settle --limit=500              # 每批处理500条
 *
 * Cron 配置（建议每30分钟执行一次结算）：
 * 每30分钟: cd /path/to/advnet && php think ad:settle --action=settle >> /dev/null 2>&1
 *
 * 过期红包处理（建议每小时执行一次）：
 * 每小时: cd /path/to/advnet && php think ad:settle --action=expire >> /dev/null 2>&1
 */
class AdSettle extends Command
{
    protected function configure()
    {
        $this->setName('ad:settle')
            ->setDescription('广告收益定时结算')
            ->addOption('action', 'a', Option::VALUE_OPTIONAL, '执行动作: settle/expire/stats/all', 'settle')
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, '每批处理数量', 100);
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $limit = (int)$input->getOption('limit');

        $output->writeln("=== 广告收益结算任务开始 ===");
        $output->writeln("时间: " . date('Y-m-d H:i:s'));
        $output->writeln("动作: {$action}");
        $startTime = microtime(true);

        switch ($action) {
            case 'settle':
                $this->doSettle($output, $limit);
                break;

            case 'expire':
                $this->doExpire($output);
                break;

            case 'stats':
                $this->doStats($output);
                break;

            case 'all':
                $output->writeln("\n--- 1. 结算待释放收益 ---");
                $this->doSettle($output, $limit);

                $output->writeln("\n--- 2. 处理过期红包 ---");
                $this->doExpire($output);

                $output->writeln("\n--- 3. 统计信息 ---");
                $this->doStats($output);
                break;

            default:
                $output->writeln("未知操作: {$action}");
                $output->writeln("可用操作: settle, expire, stats, all");
                return;
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $output->writeln("\n=== 任务完成，耗时: {$duration}ms ===");
    }

    /**
     * 结算待释放广告收益为红包
     */
    protected function doSettle(Output $output, $limit)
    {
        $service = new AdIncomeService();
        $result = $service->settleToRedPacket($limit);

        $output->writeln("处理用户数: {$result['total_users']}");
        $output->writeln("生成红包数: {$result['packets_created']}");
        $output->writeln("释放金币数: {$result['total_coin']}");

        if (!empty($result['errors'])) {
            $output->writeln("错误数: " . count($result['errors']));
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $output->writeln("  - {$error}");
            }
            if (count($result['errors']) > 10) {
                $output->writeln("  ... 还有 " . (count($result['errors']) - 10) . " 个错误");
            }
        }

        if ($result['packets_created'] > 0) {
            Log::info('AdSettle: 生成' . $result['packets_created'] . '个红包，共' . $result['total_coin'] . '金币');
        }
    }

    /**
     * 处理过期红包
     */
    protected function doExpire(Output $output)
    {
        $count = AdRedPacket::expirePackets();
        $output->writeln("过期红包数: {$count}");

        if ($count > 0) {
            Log::info('AdSettle: 过期' . $count . '个红包');
        }
    }

    /**
     * 显示统计信息
     */
    protected function doStats(Output $output)
    {
        // 待结算用户数
        $pendingUsers = think\Db::name('coin_account')
            ->where('ad_freeze_balance', '>', 0)
            ->count();

        // 总冻结金额
        $totalFreeze = think\Db::name('coin_account')
            ->where('ad_freeze_balance', '>', 0)
            ->sum('ad_freeze_balance');

        // 未领取红包数
        $unclaimedPackets = AdRedPacket::where('status', AdRedPacket::STATUS_UNCLAIMED)->count();
        $unclaimedAmount = AdRedPacket::where('status', AdRedPacket::STATUS_UNCLAIMED)->sum('amount');

        // 今日广告收益
        $todayStart = strtotime(date('Y-m-d'));
        $todayIncome = \app\common\model\AdIncomeLog::where('createtime', '>=', $todayStart)
            ->whereIn('status', [\app\common\model\AdIncomeLog::STATUS_CONFIRMED, \app\common\model\AdIncomeLog::STATUS_RELEASED])
            ->sum('user_amount_coin');

        $output->writeln("--- 广告系统统计 ---");
        $output->writeln("待结算用户数: {$pendingUsers}");
        $output->writeln("总冻结金额: " . (int)$totalFreeze . " 金币");
        $output->writeln("未领取红包数: {$unclaimedPackets}");
        $output->writeln("未领取红包金额: " . (float)$unclaimedAmount . " 金币");
        $output->writeln("今日广告收益: " . (int)$todayIncome . " 金币");
    }
}
