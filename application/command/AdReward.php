<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\library\AdIncomeService;

/**
 * 广告奖励异步结算定时任务
 *
 * 使用方法：
 * php think ad:reward --action=settle_feed    # 结算信息流广告待发放奖励（建议每5秒）
 * php think ad:reward --action=queue_status   # 查看队列积压状态
 * php think ad:reward --action=recover        # 恢复 processing 队列中超时的记录
 */
class AdReward extends Command
{
    protected function configure()
    {
        $this->setName('ad:reward')
            ->setDescription('广告奖励异步结算（信息流广告 Redis 队列 → handleAdCallback）')
            ->addOption('action', 'a', Option::VALUE_OPTIONAL, '执行动作', 'settle_feed')
            ->addOption('limit', 'l', Option::VALUE_OPTIONAL, '每批处理数量', 50);
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $limit = (int)$input->getOption('limit');

        $service = new AdIncomeService();
        $startTime = microtime(true);

        $output->writeln("开始执行: ad:reward {$action}");

        switch ($action) {
            case 'settle_feed':
                // 结算信息流广告待发放奖励
                $result = $service->settlePendingFeedRewards($limit);
                $output->writeln("处理结果: 总数={$result['total']}, 成功={$result['success']}, 失败={$result['failed']}, 跳过={$result['skipped']}");
                if (!empty($result['details'])) {
                    foreach (array_slice($result['details'], 0, 10) as $detail) {
                        $output->writeln("  - {$detail}");
                    }
                    if (count($result['details']) > 10) {
                        $output->writeln("  ... 还有 " . (count($result['details']) - 10) . " 条");
                    }
                }
                break;

            case 'queue_status':
                // 查看队列状态
                $this->showQueueStatus($service, $output);
                break;

            case 'recover':
                // 恢复 processing 队列（超时未被处理的记录移回 queue）
                $count = $this->recoverProcessingQueue($service);
                $output->writeln("恢复处理中队列: {$count} 条记录已移回待处理队列");
                break;

            default:
                $output->writeln("未知操作: {$action}");
                $output->writeln("可用操作: settle_feed, queue_status, recover");
                return;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $output->writeln("\n执行完成, 耗时: {$duration}ms");
    }

    /**
     * 查看队列状态
     */
    protected function showQueueStatus(AdIncomeService $service, Output $output)
    {
        // 通过反射调用 getRedis（protected）
        $redis = null;
        try {
            $reflection = new \ReflectionMethod($service, 'getRedis');
            $reflection->setAccessible(true);
            $redis = $reflection->invoke($service);
        } catch (\Throwable $e) {}

        if (!$redis) {
            $output->writeln("Redis 不可用");
            return;
        }

        $queueLen = $redis->lLen(AdIncomeService::FEED_REWARD_QUEUE);
        $processingLen = $redis->lLen(AdIncomeService::FEED_REWARD_PROCESSING);

        $output->writeln("队列状态:");
        $output->writeln("  待处理(queue):      {$queueLen} 条");
        $output->writeln("  处理中(processing): {$processingLen} 条");
        $output->writeln("  合计:               " . ($queueLen + $processingLen) . " 条");

        if ($processingLen > 0) {
            $output->writeln("\n⚠️  有 {$processingLen} 条记录在处理队列中，如长时间未减少可能需要执行 recover");
        }
    }

    /**
     * 恢复 processing 队列中超时的记录
     */
    protected function recoverProcessingQueue(AdIncomeService $service)
    {
        $redis = null;
        try {
            $reflection = new \ReflectionMethod($service, 'getRedis');
            $reflection->setAccessible(true);
            $redis = $reflection->invoke($service);
        } catch (\Throwable $e) {}

        if (!$redis) {
            return 0;
        }

        $count = 0;
        $processingKey = AdIncomeService::FEED_REWARD_PROCESSING;
        $queueKey = AdIncomeService::FEED_REWARD_QUEUE;

        // 将 processing 中所有记录移回 queue
        while (true) {
            $item = $redis->rPopLPush($processingKey, $queueKey);
            if (!$item) break;
            $count++;
        }

        return $count;
    }
}
