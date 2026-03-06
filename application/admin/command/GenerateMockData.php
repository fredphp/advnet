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
 * php think generate:mock-data --type=withdraw --days=30 --count=50
 * php think generate:mock-data --type=all --days=30 --count=50
 */
class GenerateMockData extends Command
{
    protected function configure()
    {
        $this->setName('generate:mock-data')
            ->addOption('type', 't', Option::VALUE_OPTIONAL, '数据类型: withdraw/redpacket/participation/all', 'all')
            ->addOption('days', 'd', Option::VALUE_OPTIONAL, '生成数据的天数', 30)
            ->addOption('count', 'c', Option::VALUE_OPTIONAL, '每天数据条数', 20)
            ->setDescription('生成模拟数据（提现订单、红包任务、领取记录、统计数据）');
    }

    protected function execute(Input $input, Output $output)
    {
        $type = $input->getOption('type');
        $days = (int) $input->getOption('days');
        $count = (int) $input->getOption('count');

        $output->writeln("开始生成模拟数据...");
        $output->writeln("数据类型: {$type}");
        $output->writeln("天数: {$days}");
        $output->writeln("每天数据条数: {$count}");

        // 获取或创建测试用户
        $users = $this->getOrCreateUsers($output);
        if (empty($users)) {
            $output->error('无法创建用户数据');
            return;
        }
        $userIds = array_column($users, 'id');

        if ($type === 'all' || $type === 'withdraw') {
            $this->generateWithdrawOrders($output, $days, $count, $users);
        }

        if ($type === 'all' || $type === 'redpacket') {
            $this->generateRedPacketTasks($output, $days, $count, $userIds);
        }

        if ($type === 'all' || $type === 'participation') {
            $this->generateParticipationRecords($output, $days, $count, $users);
        }

        // 更新统计数据
        $this->updateWithdrawStats($output, $users);

        $output->info("模拟数据生成完成！");
    }

    /**
     * 获取或创建测试用户
     */
    protected function getOrCreateUsers($output)
    {
        $users = Db::name('user')->field('id, username, nickname, mobile')->limit(50)->select();
        
        if (count($users) < 10) {
            $output->writeln("创建测试用户...");
            
            for ($i = 1; $i <= 30; $i++) {
                $mobile = '138' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $userId = Db::name('user')->insertGetId([
                    'username' => 'test_' . $i,
                    'nickname' => '测试用户' . $i,
                    'mobile' => $mobile,
                    'password' => md5(md5('123456') . 'test'),
                    'salt' => 'test',
                    'status' => 'normal',
                    'createtime' => time() - mt_rand(86400, 86400 * 90),
                    'updatetime' => time(),
                ]);
                
                // 创建金币账户
                Db::name('coin_account')->insert([
                    'user_id' => $userId,
                    'balance' => mt_rand(10000, 500000),
                    'total_earn' => mt_rand(100000, 1000000),
                    'total_withdraw' => mt_rand(0, 100000),
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }
            
            $output->writeln("创建了 30 个测试用户");
            $users = Db::name('user')->field('id, username, nickname, mobile')->limit(50)->select();
        }
        
        // TP5.0 select() 返回数组，TP5.1+ 返回 Collection
        return is_array($users) ? $users : $users->toArray();
    }

    /**
     * 生成提现订单数据
     */
    protected function generateWithdrawOrders($output, $days, $count, $users)
    {
        $output->writeln("生成提现订单数据...");

        $prefix = config('database.prefix');
        // 状态权重：大部分成功
        $statusWeights = [
            0 => 10,   // 待审核 10%
            1 => 5,    // 审核通过 5%
            2 => 3,    // 打款中 3%
            3 => 70,   // 提现成功 70%
            4 => 8,    // 审核拒绝 8%
            5 => 2,    // 打款失败 2%
            6 => 2,    // 已取消 2%
        ];
        $typeList = ['alipay', 'wechat', 'bank'];
        $bankList = ['工商银行', '建设银行', '农业银行', '中国银行', '招商银行', '邮政储蓄银行'];

        $totalOrders = 0;
        $userOrders = []; // 记录每个用户的订单数据用于统计

        for ($d = $days - 1; $d >= 0; $d--) {
            $date = date('Y-m-d', strtotime("-{$d} days"));
            $month = date('Ym', strtotime($date));
            $tableName = 'withdraw_order_' . $month;
            $fullTableName = $prefix . $tableName;

            // 检查表是否存在，不存在则创建
            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}withdraw_order`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $dayStart = strtotime($date);
            $dayEnd = strtotime($date . ' 23:59:59');

            for ($i = 0; $i < $count; $i++) {
                $user = $users[array_rand($users)];
                $userId = $user['id'];
                $status = $this->getWeightedRandom($statusWeights);
                $withdrawType = $typeList[array_rand($typeList)];
                
                // 金额分布：小额多，大额少
                $amountLevel = mt_rand(1, 100);
                if ($amountLevel <= 50) {
                    $coinAmount = mt_rand(10000, 50000);  // 1-5元
                } elseif ($amountLevel <= 80) {
                    $coinAmount = mt_rand(50000, 200000); // 5-20元
                } elseif ($amountLevel <= 95) {
                    $coinAmount = mt_rand(200000, 500000); // 20-50元
                } else {
                    $coinAmount = mt_rand(500000, 1000000); // 50-100元
                }
                
                $cashAmount = round($coinAmount / 10000, 2);
                $createtime = mt_rand($dayStart, $dayEnd);

                $data = [
                    'order_no' => 'WD' . date('YmdHis', $createtime) . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'user_id' => $userId,
                    'coin_amount' => $coinAmount,
                    'exchange_rate' => 10000,
                    'cash_amount' => $cashAmount,
                    'fee_amount' => 0,
                    'actual_amount' => $cashAmount,
                    'withdraw_type' => $withdrawType,
                    'withdraw_account' => $this->generateAccount($withdrawType),
                    'withdraw_name' => $user['nickname'] ?? ('用户' . mt_rand(100, 999)),
                    'bank_name' => $withdrawType === 'bank' ? $bankList[array_rand($bankList)] : '',
                    'status' => $status,
                    'audit_type' => $status >= 1 ? 0 : null,
                    'ip' => '192.168.' . mt_rand(1, 255) . '.' . mt_rand(1, 255),
                    'createtime' => $createtime,
                    'updatetime' => time(),
                ];

                // 根据状态设置相关字段
                if ($status >= 1) {
                    $data['audit_time'] = $createtime + mt_rand(60, 3600);
                    $data['audit_admin_id'] = 1;
                    $data['audit_admin_name'] = '系统';
                }
                
                if ($status >= 3) {
                    $data['complete_time'] = $data['audit_time'] + mt_rand(300, 86400);
                    $data['transfer_time'] = $data['complete_time'];
                    $data['transfer_no'] = 'TF' . date('YmdHis', $data['complete_time']) . mt_rand(1000, 9999);
                }
                
                if ($status === 4) {
                    $data['reject_reason'] = '审核拒绝：信息不匹配';
                }
                
                if ($status === 5) {
                    $data['fail_reason'] = '打款失败：账户信息有误';
                }

                Db::name($tableName)->insert($data);
                $totalOrders++;

                // 记录用户订单统计
                if (!isset($userOrders[$userId])) {
                    $userOrders[$userId] = [
                        'total_count' => 0,
                        'total_amount' => 0,
                        'success_count' => 0,
                        'success_amount' => 0,
                        'first_time' => $createtime,
                        'last_time' => $createtime,
                    ];
                }
                $userOrders[$userId]['total_count']++;
                $userOrders[$userId]['total_amount'] += $cashAmount;
                if ($status === 3) {
                    $userOrders[$userId]['success_count']++;
                    $userOrders[$userId]['success_amount'] += $cashAmount;
                }
                if ($createtime < $userOrders[$userId]['first_time']) {
                    $userOrders[$userId]['first_time'] = $createtime;
                }
                if ($createtime > $userOrders[$userId]['last_time']) {
                    $userOrders[$userId]['last_time'] = $createtime;
                }

                // 随机生成风控日志
                if (mt_rand(1, 100) <= 30) {
                    $this->generateRiskLog($userId, $data['order_no'], $createtime);
                }
            }
        }

        // 更新用户提现统计表
        $this->updateUserWithdrawStats($userOrders);

        $output->writeln("生成提现订单 {$totalOrders} 条");
    }

    /**
     * 生成风控日志
     */
    protected function generateRiskLog($userId, $orderNo, $createtime)
    {
        $riskTypes = [
            'ip_check' => 'IP检测',
            'device_check' => '设备检测',
            'frequency_check' => '频率检测',
            'amount_check' => '金额检测',
        ];
        
        $riskType = array_rand($riskTypes);
        $riskLevel = mt_rand(1, 3);
        $riskScore = mt_rand(0, 60);
        
        Db::name('withdraw_risk_log')->insert([
            'user_id' => $userId,
            'order_no' => $orderNo,
            'risk_type' => $riskType,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_detail' => json_encode([
                'type' => $riskTypes[$riskType],
                'score' => $riskScore,
            ]),
            'handle_action' => $riskScore < 50 ? 'pass' : 'review',
            'createtime' => $createtime,
        ]);
    }

    /**
     * 更新用户提现统计表
     */
    protected function updateUserWithdrawStats($userOrders)
    {
        foreach ($userOrders as $userId => $stats) {
            $exists = Db::name('withdraw_stat')->where('user_id', $userId)->find();
            
            $data = [
                'user_id' => $userId,
                'total_withdraw_count' => $stats['total_count'],
                'total_withdraw_amount' => $stats['total_amount'],
                'total_withdraw_coin' => $stats['total_amount'] * 10000,
                'success_count' => $stats['success_count'],
                'fail_count' => $stats['total_count'] - $stats['success_count'],
                'first_withdraw_time' => $stats['first_time'],
                'last_withdraw_time' => $stats['last_time'],
                'updatetime' => time(),
            ];
            
            if ($exists) {
                Db::name('withdraw_stat')->where('user_id', $userId)->update($data);
            } else {
                $data['createtime'] = time();
                Db::name('withdraw_stat')->insert($data);
            }
        }
    }

    /**
     * 生成红包任务数据
     */
    protected function generateRedPacketTasks($output, $days, $count, $userIds)
    {
        $output->writeln("生成红包任务数据...");

        $prefix = config('database.prefix');
        $typeList = ['chat', 'download', 'miniapp', 'adv', 'video'];
        $statusList = ['pending', 'normal', 'normal', 'normal', 'finished', 'finished', 'expired'];

        for ($d = $days - 1; $d >= 0; $d--) {
            $date = date('Y-m-d', strtotime("-{$d} days"));
            $month = date('Ym', strtotime($date));
            $tableName = 'red_packet_task_' . $month;
            $fullTableName = $prefix . $tableName;

            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}red_packet_task`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $dayStart = strtotime($date);
            $dayEnd = strtotime($date . ' 23:59:59');

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
                    'push_time' => in_array($status, ['normal', 'finished']) ? mt_rand($dayStart, $dayEnd) : null,
                    'createtime' => mt_rand($dayStart, $dayEnd),
                    'updatetime' => time(),
                ];

                Db::name($tableName)->insert($data);
            }
        }

        $output->writeln("生成红包任务 {$days} 天数据完成");
    }

    /**
     * 生成领取记录数据
     */
    protected function generateParticipationRecords($output, $days, $count, $users)
    {
        $output->writeln("生成领取记录数据...");

        $prefix = config('database.prefix');
        $totalRecords = 0;
        
        // 用于跟踪已生成的 user_id + task_id 组合
        $userTaskMap = [];

        for ($d = $days - 1; $d >= 0; $d--) {
            $date = date('Y-m-d', strtotime("-{$d} days"));
            $month = date('Ym', strtotime($date));
            $tableName = 'user_red_packet_accumulate_' . $month;
            $fullTableName = $prefix . $tableName;

            $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
            if (empty($exists)) {
                Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$prefix}user_red_packet_accumulate`");
                $output->writeln("创建分表: {$fullTableName}");
            }

            $dayStart = strtotime($date);
            $dayEnd = strtotime($date . ' 23:59:59');
            
            $dayRecords = 0;
            $attempts = 0;
            $maxAttempts = $count * 3; // 最大尝试次数，避免无限循环
            
            while ($dayRecords < $count && $attempts < $maxAttempts) {
                $attempts++;
                
                $user = $users[array_rand($users)];
                $userId = $user['id'];
                $taskId = mt_rand(1, 100);
                
                // 检查是否已存在该组合
                $key = "{$userId}-{$taskId}";
                if (isset($userTaskMap[$key])) {
                    continue; // 跳过重复组合
                }
                
                // 标记该组合已使用
                $userTaskMap[$key] = true;
                
                $baseAmount = mt_rand(500, 5000);
                $clickCount = mt_rand(1, 20);
                $accumulateAmount = mt_rand(0, 5000);
                $isCollected = mt_rand(0, 100) <= 70 ? 1 : 0;

                $data = [
                    'user_id' => $userId,
                    'task_id' => $taskId,
                    'is_new_user' => mt_rand(0, 1),
                    'click_count' => $clickCount,
                    'base_amount' => $baseAmount,
                    'accumulate_amount' => $accumulateAmount,
                    'total_amount' => $baseAmount + $accumulateAmount,
                    'is_collected' => $isCollected,
                    'collect_time' => $isCollected ? mt_rand($dayStart, $dayEnd) : null,
                    'createtime' => mt_rand($dayStart, $dayEnd),
                    'updatetime' => time(),
                ];

                Db::name($tableName)->insert($data);
                $dayRecords++;
                $totalRecords++;
            }
        }

        $output->writeln("生成领取记录 {$totalRecords} 条");
    }

    /**
     * 更新统计数据
     */
    protected function updateWithdrawStats($output, $users)
    {
        $output->writeln("更新统计数据...");
        
        // 更新金币日志
        $userIds = array_column($users, 'id');
        foreach ($userIds as $userId) {
            // 检查金币账户是否存在
            $account = Db::name('coin_account')->where('user_id', $userId)->find();
            if (!$account) {
                Db::name('coin_account')->insert([
                    'user_id' => $userId,
                    'balance' => mt_rand(10000, 500000),
                    'total_earn' => mt_rand(100000, 1000000),
                    'total_withdraw' => mt_rand(0, 100000),
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }
        }
        
        $output->writeln("统计数据更新完成");
    }

    /**
     * 根据权重获取随机值
     */
    protected function getWeightedRandom($weights)
    {
        $total = array_sum($weights);
        $rand = mt_rand(1, $total);
        
        foreach ($weights as $value => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $value;
            }
        }
        
        return array_key_first($weights);
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
