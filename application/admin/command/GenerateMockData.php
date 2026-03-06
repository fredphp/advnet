<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\Db;
use think\Log;

/**
 * 生成模拟数据命令
 * 
 * 使用方法：
 * php think generate:mock-data --type=withdraw --months=3
 * php think generate:mock-data --type=redpacket --months=3
 * php think generate:mock-data --type=all --months=3
 */
class GenerateMockData extends Command
{
    protected function configure()
    {
        $this->setName('generate:mock-data')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '数据类型: withdraw/redpacket/participation/all', 'all')
            ->addOption('months', 'm', Option::VALUE_OPTIONAL, '生成数据的月数', 3)
            ->addOption('count', 'c', Option::VALUE_OPTIONAL, '每月数据条数', 100)
            ->setDescription('生成模拟数据（提现订单、红包任务、领取记录）');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');
        $months = (int) $input->getOption('months');
        $count = (int) $input->getOption('count');

        $output->writeln("开始生成模拟数据...");
        $output->writeln("数据类型: {$type}");
        $output->writeln("月数: {$months}");
        $output->writeln("每月数据条数: {$count}");

        // 获取用户列表
        $users = Db::name('user')->field('id, username, nickname, mobile')->limit(50)->select();
        if (empty($users)) {
            $output->error('没有用户数据，请先创建用户');
            return;
        }
        $userIds = array_column($users->toArray(), 'id');

        if ($type === 'all' || $type === 'withdraw') {
            $this->generateWithdrawOrders($output, $months, $count, $userIds);
        }

        if ($type === 'all' || $type === 'redpacket') {
            $this->generateRedPacketTasks($output, $months, $count, $userIds);
        }

        if ($type === 'all' || $type === 'participation') {
            $this->generateParticipationRecords($output, $months, $count, $userIds);
        }

        $output->info("模拟数据生成完成！");
    }

    /**
     * 生成提现订单数据
     */
    protected function generateWithdrawOrders($output, $months, $count, $userIds)
    {
        $output->writeln("生成提现订单数据...");

        $prefix = config('database.prefix');
        $statusList = [0, 0, 0, 1, 3, 3, 3, 3, 3, 4]; // 权重：大部分成功
        $typeList = ['alipay', 'wechat', 'bank'];
        $bankList = ['工商银行', '建设银行', '农业银行', '中国银行', '招商银行'];

        for ($m = $months - 1; $m >= 0; $m--) {
            $month = date('Ym', strtotime("-{$m} months"));
            $tableName = 'withdraw_order_' . $month;
            $fullTableName = $prefix . $tableName;

            // 检查表是否存在
            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                // 创建分表
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}withdraw_order`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $monthStart = strtotime(date('Y-m-01', strtotime("-{$m} months")));
            $monthEnd = strtotime(date('Y-m-t', strtotime("-{$m} months")) . ' 23:59:59');

            for ($i = 0; $i < $count; $i++) {
                $userId = $userIds[array_rand($userIds)];
                $status = $statusList[array_rand($statusList)];
                $withdrawType = $typeList[array_rand($typeList)];
                $coinAmount = mt_rand(1000, 100000);
                $cashAmount = round($coinAmount / 10000, 2);

                $data = [
                    'order_no' => 'WD' . date('YmdHis', mt_rand($monthStart, $monthEnd)) . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'user_id' => $userId,
                    'coin_amount' => $coinAmount,
                    'exchange_rate' => 10000,
                    'cash_amount' => $cashAmount,
                    'fee_amount' => 0,
                    'actual_amount' => $cashAmount,
                    'withdraw_type' => $withdrawType,
                    'withdraw_account' => $this->generateAccount($withdrawType),
                    'withdraw_name' => '用户' . mt_rand(100, 999),
                    'bank_name' => $withdrawType === 'bank' ? $bankList[array_rand($bankList)] : '',
                    'status' => $status,
                    'audit_type' => 0,
                    'createtime' => mt_rand($monthStart, $monthEnd),
                    'updatetime' => time(),
                ];

                if ($status >= 3) {
                    $data['complete_time'] = $data['createtime'] + mt_rand(300, 86400);
                }

                Db::name($tableName)->insert($data);
            }

            $output->writeln("生成 {$month} 月提现订单 {$count} 条");
        }
    }

    /**
     * 生成红包任务数据
     */
    protected function generateRedPacketTasks($output, $months, $count, $userIds)
    {
        $output->writeln("生成红包任务数据...");

        $prefix = config('database.prefix');
        $typeList = ['chat', 'download', 'miniapp', 'adv', 'video'];
        $statusList = ['pending', 'normal', 'normal', 'normal', 'finished', 'finished', 'expired'];

        for ($m = $months - 1; $m >= 0; $m--) {
            $month = date('Ym', strtotime("-{$m} months"));
            $tableName = 'red_packet_task_' . $month;
            $fullTableName = $prefix . $tableName;

            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}red_packet_task`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $monthStart = strtotime(date('Y-m-01', strtotime("-{$m} months")));
            $monthEnd = strtotime(date('Y-m-t', strtotime("-{$m} months")) . ' 23:59:59');

            for ($i = 0; $i < $count; $i++) {
                $type = $typeList[array_rand($typeList)];
                $status = $statusList[array_rand($statusList)];
                $totalAmount = mt_rand(1000, 100000);

                $data = [
                    'name' => $this->getTaskName($type),
                    'description' => '完成任务获取金币奖励',
                    'type' => $type,
                    'status' => $status,
                    'sender_id' => 1,
                    'sender_name' => '系统管理员',
                    'push_status' => in_array($status, ['normal', 'finished']) ? 1 : 0,
                    'push_time' => in_array($status, ['normal', 'finished']) ? mt_rand($monthStart, $monthEnd) : null,
                    'createtime' => mt_rand($monthStart, $monthEnd),
                    'updatetime' => time(),
                ];

                Db::name($tableName)->insert($data);
            }

            $output->writeln("生成 {$month} 月红包任务 {$count} 条");
        }
    }

    /**
     * 生成领取记录数据
     */
    protected function generateParticipationRecords($output, $months, $count, $userIds)
    {
        $output->writeln("生成领取记录数据...");

        $prefix = config('database.prefix');

        for ($m = $months - 1; $m >= 0; $m--) {
            $month = date('Ym', strtotime("-{$m} months"));
            $tableName = 'user_red_packet_accumulate_' . $month;
            $fullTableName = $prefix . $tableName;

            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}user_red_packet_accumulate`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $monthStart = strtotime(date('Y-m-01', strtotime("-{$m} months")));
            $monthEnd = strtotime(date('Y-m-t', strtotime("-{$m} months")) . ' 23:59:59');

            for ($i = 0; $i < $count; $i++) {
                $userId = $userIds[array_rand($userIds)];
                $baseAmount = mt_rand(500, 5000);
                $clickCount = mt_rand(1, 20);
                $accumulateAmount = mt_rand(0, 5000);
                $isCollected = mt_rand(0, 1);

                $data = [
                    'user_id' => $userId,
                    'task_id' => mt_rand(1, 100),
                    'is_new_user' => mt_rand(0, 1),
                    'click_count' => $clickCount,
                    'base_amount' => $baseAmount,
                    'accumulate_amount' => $accumulateAmount,
                    'total_amount' => $baseAmount + $accumulateAmount,
                    'is_collected' => $isCollected,
                    'collect_time' => $isCollected ? mt_rand($monthStart, $monthEnd) : null,
                    'createtime' => mt_rand($monthStart, $monthEnd),
                    'updatetime' => time(),
                ];

                Db::name($tableName)->insert($data);
            }

            $output->writeln("生成 {$month} 月领取记录 {$count} 条");
        }
    }

    /**
     * 生成账号
     */
    protected function generateAccount($type)
    {
        switch ($type) {
            case 'alipay':
                return 'alipay_' . mt_rand(100000, 999999) . '@163.com';
            case 'wechat':
                return 'wx_' . substr(md5(mt_rand()), 0, 16);
            case 'bank':
                return '6222' . str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
            default:
                return '';
        }
    }

    /**
     * 获取任务名称
     */
    protected function getTaskName($type)
    {
        $names = [
            'chat' => ['聊天任务', '互动聊天', '每日聊天', '群聊任务'],
            'download' => ['下载任务', 'App下载', '应用下载', '软件下载'],
            'miniapp' => ['小程序游戏', '微信小游戏', '小游戏任务', '玩游戏'],
            'adv' => ['广告任务', '观看广告', '视频广告', '激励视频'],
            'video' => ['视频任务', '看视频', '短视频任务', '视频观看'],
        ];

        $list = $names[$type] ?? ['未知任务'];
        return $list[array_rand($list)];
    }
}
