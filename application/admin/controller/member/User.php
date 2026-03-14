<?php

namespace app\admin\controller\member;

use app\common\controller\Backend;
use app\common\library\CoinService;
use think\Db;
use think\Exception;

/**
 * 用户管理
 */
class User extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\User();
    }

    /**
     * 用户列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$item) {
                $account = Db::name('coin_account')
                    ->where('user_id', $item['id'])
                    ->find();
                $item['coin_balance'] = $account ? $account['balance'] : 0;
                $item['frozen_coin'] = $account ? $account['frozen'] : 0;
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 用户详情
     */
    public function detail($ids = null)
    {
        $user = $this->model->get($ids);
        if (!$user) {
            $this->error('用户不存在');
        }

        $prefix = config('database.prefix');

        // 金币账户信息
        $coinAccount = null;
        try {
            $coinAccount = Db::name('coin_account')
                ->where('user_id', $ids)
                ->find();
        } catch (\Exception $e) {
            $coinAccount = null;
        }

        // 邀请统计
        $inviteStats = null;
        try {
            $inviteStats = Db::name('user_invite_stat')
                ->where('user_id', $ids)
                ->find();
        } catch (\Exception $e) {
            $inviteStats = null;
        }

        // 今日统计
        $todayStats = null;
        try {
            $todayStats = Db::name('user_behavior_stat')
                ->where('user_id', $ids)
                ->where('stat_date', date('Y-m-d'))
                ->find();
        } catch (\Exception $e) {
            $todayStats = null;
        }

        // 提现统计 - 使用原生SQL避免字段问题
        $withdrawStats = ['total_count' => 0, 'total_amount' => 0];
        try {
            // 先检查表是否存在
            $tableExists = Db::query("SHOW TABLES LIKE '{$prefix}withdraw_order'");
            if (!empty($tableExists)) {
                // 检查是否有 amount 字段
                $columns = Db::query("SHOW COLUMNS FROM {$prefix}withdraw_order LIKE 'amount'");
                if (!empty($columns)) {
                    $result = Db::query("
                        SELECT COUNT(*) as total_count, 
                               SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount 
                        FROM {$prefix}withdraw_order 
                        WHERE user_id = ?
                    ", [$ids]);
                    if (!empty($result)) {
                        $withdrawStats = $result[0];
                    }
                } else {
                    // 没有 amount 字段，只统计数量
                    $result = Db::query("SELECT COUNT(*) as total_count FROM {$prefix}withdraw_order WHERE user_id = ?", [$ids]);
                    if (!empty($result)) {
                        $withdrawStats['total_count'] = $result[0]['total_count'];
                    }
                }
            }
        } catch (\Exception $e) {
            $withdrawStats = ['total_count' => 0, 'total_amount' => 0];
        }

        // 风险信息
        $riskInfo = null;
        try {
            $riskInfo = Db::name('user_risk_score')
                ->where('user_id', $ids)
                ->find();
        } catch (\Exception $e) {
            $riskInfo = null;
        }

        // 最近登录记录
        $recentLogins = [];
        try {
            $recentLogins = Db::name('user_behavior')
                ->where('user_id', $ids)
                ->where('behavior_type', 'login')
                ->order('createtime', 'desc')
                ->limit(10)
                ->select();
        } catch (\Exception $e) {
            $recentLogins = [];
        }

        // 设备信息
        $devices = [];
        try {
            $devices = Db::name('device_fingerprint')
                ->where('user_id', $ids)
                ->select();
        } catch (\Exception $e) {
            $devices = [];
        }

        $this->success('', null, [
            'user' => $user,
            'coin_account' => $coinAccount,
            'invite_stats' => $inviteStats,
            'today_stats' => $todayStats,
            'withdraw_stats' => $withdrawStats,
            'risk_info' => $riskInfo,
            'recent_logins' => $recentLogins,
            'devices' => $devices,
        ]);
    }

    /**
     * 编辑用户
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            $params['updatetime'] = time();
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 修改用户状态
     */
    public function status($ids = '')
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $status = $this->request->post('status', 'normal');

        if (!in_array($status, ['normal', 'frozen', 'banned'])) {
            $this->error('状态值无效');
        }

        $row->status = $status;
        $row->updatetime = time();
        $row->save();

        $this->success();
    }

    /**
     * 手动充值金币
     */
    public function recharge()
    {
        $userId = $this->request->post('user_id');
        $amount = $this->request->post('amount');
        $remark = $this->request->post('remark', '后台充值');

        if (!$userId || !$amount || $amount <= 0) {
            $this->error('参数错误');
        }

        Db::startTrans();
        try {
            $coinService = new CoinService();
            $result = $coinService->addCoin($userId, $amount, 'admin_recharge', 0, $remark);

            if (!$result['success']) {
                throw new Exception($result['message'] ?: '充值失败');
            }

            // 记录后台操作日志（表不存在时自动创建）
            try {
                $this->createAdminCoinLogTableIfNotExists();
                Db::name('admin_coin_log')->insert([
                    'admin_id' => $this->auth->id,
                    'admin_name' => $this->auth->username,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => 'recharge',
                    'remark' => $remark,
                    'createtime' => time(),
                ]);
            } catch (\Exception $e) {
                // 日志记录失败不影响充值结果
            }

            Db::commit();
            $this->success('充值成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 扣除金币
     */
    public function deduct()
    {
        $userId = $this->request->post('user_id');
        $amount = $this->request->post('amount');
        $remark = $this->request->post('remark', '后台扣除');

        if (!$userId || !$amount || $amount <= 0) {
            $this->error('参数错误');
        }

        Db::startTrans();
        try {
            $coinService = new CoinService();
            $result = $coinService->deductCoin($userId, $amount, 'admin_deduct', 0, $remark);

            if (!$result['success']) {
                throw new Exception($result['message'] ?: '扣除失败');
            }

            // 记录后台操作日志（表不存在时自动创建）
            try {
                $this->createAdminCoinLogTableIfNotExists();
                Db::name('admin_coin_log')->insert([
                    'admin_id' => $this->auth->id,
                    'admin_name' => $this->auth->username,
                    'user_id' => $userId,
                    'amount' => -$amount,
                    'type' => 'deduct',
                    'remark' => $remark,
                    'createtime' => time(),
                ]);
            } catch (\Exception $e) {
                // 日志记录失败不影响扣除结果
            }

            Db::commit();
            $this->success('扣除成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 用户统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        $totalStats = $this->model->field('COUNT(*) as total, 
                     SUM(CASE WHEN status = "normal" THEN 1 ELSE 0 END) as normal_count,
                     SUM(CASE WHEN status = "frozen" THEN 1 ELSE 0 END) as frozen_count,
                     SUM(CASE WHEN status = "banned" THEN 1 ELSE 0 END) as banned_count')
            ->find();

        $dailyNew = $this->model
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 尝试获取每日活跃用户，如果表不存在则返回空数组
        $dailyActive = [];
        try {
            $dailyActive = Db::name('user_behavior_stat')
                ->field('stat_date as date, COUNT(DISTINCT user_id) as count')
                ->where('stat_date', 'between', [$startDate, $endDate])
                ->group('stat_date')
                ->order('stat_date', 'asc')
                ->select();
        } catch (\Exception $e) {
            $dailyActive = [];
        }

        // 获取用户来源分布
        $sourceDistribution = $this->model
            ->field('source, COUNT(*) as count')
            ->group('source')
            ->select();

        // 尝试获取设备分布，如果表不存在则返回空数组
        $deviceDistribution = [];
        try {
            $deviceDistribution = Db::name('device_fingerprint')
                ->field('device_type, COUNT(DISTINCT user_id) as count')
                ->group('device_type')
                ->select();
        } catch (\Exception $e) {
            $deviceDistribution = [];
        }

        if ($this->request->isAjax()) {
            $this->success('', null, [
                'total_stats' => $totalStats,
                'daily_new' => $dailyNew,
                'daily_active' => $dailyActive,
                'source_distribution' => $sourceDistribution,
                'device_distribution' => $deviceDistribution,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('daily_new', $dailyNew);
        $this->view->assign('daily_active', $dailyActive);
        $this->view->assign('source_distribution', $sourceDistribution);
        $this->view->assign('device_distribution', $deviceDistribution);
        return $this->view->fetch();
    }

    /**
     * 用户行为记录
     */
    public function behaviors($ids = null)
    {
        $userId = $ids ?: $this->request->get('user_id');

        if (!$userId) {
            $this->error('请指定用户ID');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 20);
            $type = $this->request->get('type');

            $query = Db::name('user_behavior')->where('user_id', $userId);

            if ($type) {
                $query->where('behavior_type', $type);
            }

            $total = $query->count();
            $list = $query->order('createtime', 'desc')
                ->limit($offset, $limit)
                ->select();

            return json(['total' => $total, 'rows' => $list]);
        }

        $this->view->assign('user_id', $userId);
        return $this->view->fetch();
    }

    /**
     * 导出用户
     */
    public function export()
    {
        $ids = $this->request->get('ids');
        $status = $this->request->get('status');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');

        $query = $this->model;

        // 导出选中用户
        if ($ids) {
            $idsArr = explode(',', $ids);
            $query->whereIn('id', $idsArr);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('createtime', '>=', strtotime($startDate));
        }

        if ($endDate) {
            $query->where('createtime', '<=', strtotime($endDate . ' 23:59:59'));
        }

        $list = $query->order('createtime', 'desc')->select();

        $filename = 'users_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['用户ID', '用户名', '昵称', '手机号', '状态', '金币余额', '注册时间', '最后登录']);

        foreach ($list as $row) {
            $account = Db::name('coin_account')->where('user_id', $row['id'])->find();
            fputcsv($output, [
                $row['id'],
                $row['username'],
                $row['nickname'],
                $row['mobile'],
                $row['status'],
                $account ? $account['balance'] : 0,
                date('Y-m-d H:i:s', $row['createtime']),
                $row['logintime'] ? date('Y-m-d H:i:s', $row['logintime']) : '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 封禁用户
     */
    public function ban()
    {
        $userId = $this->request->post('user_id');
        $banType = $this->request->post('ban_type', 'temporary');
        $duration = $this->request->post('duration', 24);
        $reason = $this->request->post('reason', '管理员封禁');

        if (!$userId) {
            $this->error('请选择要封禁的用户');
        }

        $prefix = config('database.prefix');

        // 检查用户是否存在
        $user = Db::query("SELECT * FROM {$prefix}user WHERE id = ? LIMIT 1", [$userId]);
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $user = $user[0];

        // 检查是否已有封禁记录
        $existingBan = Db::query("SELECT * FROM {$prefix}ban_record WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);
        if (!empty($existingBan)) {
            $this->error('该用户已被封禁');
        }

        Db::startTrans();
        try {
            $now = time();
            $durationSeconds = $duration * 3600;
            $endTime = $banType === 'permanent' ? null : $now + $durationSeconds;

            // 创建封禁记录（使用正确的字段名和完整字段）
            Db::execute("
                INSERT INTO {$prefix}ban_record 
                (user_id, ban_type, ban_reason, ban_source, risk_score, admin_id, admin_name, start_time, end_time, duration, status, createtime, updatetime)
                VALUES (?, ?, ?, 'manual', 0, ?, ?, ?, ?, ?, 'active', ?, ?)
            ", [$userId, $banType, $reason, $this->auth->id, $this->auth->username, $now, $endTime, $banType === 'permanent' ? 0 : $durationSeconds, $now, $now]);

            // 更新用户状态
            Db::execute("UPDATE {$prefix}user SET status = 'banned', updatetime = ? WHERE id = ?", [$now, $userId]);

            // 更新风险评分（如果表存在）
            try {
                Db::execute("
                    INSERT INTO {$prefix}user_risk_score (user_id, total_score, violation_count, status, updatetime)
                    VALUES (?, 100, 1, 'banned', ?)
                    ON DUPLICATE KEY UPDATE 
                        total_score = LEAST(total_score + 20, 1000),
                        violation_count = violation_count + 1,
                        status = 'banned',
                        last_violation_time = ?,
                        updatetime = ?
                ", [$userId, $now, $now, $now]);
            } catch (\Exception $e) {
                // 风险评分表可能不存在或字段不匹配，忽略错误
            }

            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            // 重新抛出 HttpResponseException，这是 success/error 方法抛出的
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('封禁失败：' . $e->getMessage());
        }
        
        $this->success('封禁成功');
    }

    /**
     * 冻结用户
     */
    public function freeze()
    {
        $userId = $this->request->post('user_id');
        $duration = $this->request->post('duration', 24);
        $reason = $this->request->post('reason', '管理员冻结');

        if (!$userId) {
            $this->error('请选择要冻结的用户');
        }

        $prefix = config('database.prefix');

        $user = Db::query("SELECT * FROM {$prefix}user WHERE id = ? LIMIT 1", [$userId]);
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $user = $user[0];

        if ($user['status'] === 'banned') {
            $this->error('该用户已被封禁，无法冻结');
        }

        Db::startTrans();
        try {
            $now = time();
            $expireTime = $duration > 0 ? $now + ($duration * 3600) : 0;

            // 创建冻结记录（表不存在时自动创建）
            $this->createUserFreezeLogTableIfNotExists();
            Db::execute("
                INSERT INTO {$prefix}user_freeze_log 
                (user_id, reason, admin_id, createtime, expire_time, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ", [$userId, $reason, $this->auth->id, $now, $expireTime]);

            // 更新用户状态
            Db::execute("UPDATE {$prefix}user SET status = 'frozen', updatetime = ? WHERE id = ?", [$now, $userId]);

            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            // 重新抛出 HttpResponseException，这是 success/error 方法抛出的
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('冻结失败：' . $e->getMessage());
        }
        
        $this->success('冻结成功');
    }

    /**
     * 解冻用户
     */
    public function unfreeze()
    {
        $userId = $this->request->post('user_id');

        if (!$userId) {
            $this->error('请选择要解冻的用户');
        }

        $prefix = config('database.prefix');

        $user = Db::query("SELECT * FROM {$prefix}user WHERE id = ? LIMIT 1", [$userId]);
        if (empty($user)) {
            $this->error('用户不存在');
        }
        $user = $user[0];

        if ($user['status'] !== 'frozen') {
            $this->error('该用户状态不是冻结状态');
        }

        Db::startTrans();
        try {
            $now = time();

            // 更新冻结记录
            $this->createUserFreezeLogTableIfNotExists();
            Db::execute("
                UPDATE {$prefix}user_freeze_log 
                SET status = 'released', release_time = ?, release_reason = '管理员手动解冻'
                WHERE user_id = ? AND status = 'active'
            ", [$now, $userId]);

            // 更新用户状态
            Db::execute("UPDATE {$prefix}user SET status = 'normal', updatetime = ? WHERE id = ?", [$now, $userId]);

            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            // 重新抛出 HttpResponseException，这是 success/error 方法抛出的
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('解冻失败：' . $e->getMessage());
        }
        
        $this->success('解冻成功');
    }

    /**
     * 批量操作
     */
    public function batch()
    {
        $ids = $this->request->post('ids/a');
        $action = $this->request->post('action');
        $amount = $this->request->post('amount', 0);
        $remark = $this->request->post('remark', '');

        if (!$ids || !is_array($ids)) {
            $this->error('请选择要操作的用户');
        }

        if (!$action) {
            $this->error('请选择操作类型');
        }

        $prefix = config('database.prefix');
        $success = 0;
        $failed = 0;

        foreach ($ids as $userId) {
            try {
                switch ($action) {
                    case 'batch_normal':
                        Db::execute("UPDATE {$prefix}user SET status = 'normal', updatetime = ? WHERE id = ?", [time(), $userId]);
                        break;
                    case 'batch_freeze':
                        Db::execute("UPDATE {$prefix}user SET status = 'frozen', updatetime = ? WHERE id = ?", [time(), $userId]);
                        break;
                    case 'batch_ban':
                        Db::execute("UPDATE {$prefix}user SET status = 'banned', updatetime = ? WHERE id = ?", [time(), $userId]);
                        Db::execute("
                            INSERT INTO {$prefix}ban_record (user_id, ban_type, ban_reason, ban_source, admin_id, admin_name, start_time, duration, status, createtime, updatetime)
                            VALUES (?, 'temporary', ?, 'manual', ?, ?, ?, 0, 'active', ?, ?)
                        ", [$userId, $remark ?: '批量封禁', $this->auth->id, $this->auth->username, time(), time(), time()]);
                        break;
                    case 'batch_recharge':
                        if ($amount > 0) {
                            $coinService = new CoinService();
                            $coinService->addCoin($userId, $amount, 'admin_batch_recharge', 0, $remark ?: '批量充值');
                        }
                        break;
                    case 'batch_deduct':
                        if ($amount > 0) {
                            $coinService = new CoinService();
                            $coinService->deductCoin($userId, $amount, 'admin_batch_deduct', 0, $remark ?: '批量扣除');
                        }
                        break;
                    case 'batch_blacklist':
                        Db::execute("
                            INSERT INTO {$prefix}blacklist (type, value, reason, source, admin_id, createtime, enabled)
                            VALUES ('user', ?, ?, 'manual', ?, ?, 1)
                            ON DUPLICATE KEY UPDATE enabled = 1
                        ", [$userId, $remark ?: '批量加入黑名单', $this->auth->id, time()]);
                        break;
                    case 'batch_whitelist':
                        Db::execute("
                            INSERT INTO {$prefix}whitelist (type, value, reason, admin_id, createtime, status)
                            VALUES ('user', ?, ?, ?, ?, 1)
                            ON DUPLICATE KEY UPDATE status = 1
                        ", [$userId, $remark ?: '批量加入白名单', $this->auth->id, time()]);
                        break;
                }
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $this->success("操作完成：成功 {$success} 个，失败 {$failed} 个");
    }

    /**
     * 设备信息
     */
    public function devices()
    {
        $userId = $this->request->get('user_id');
        if (!$userId) {
            $this->error('请指定用户ID');
        }

        if ($this->request->isAjax()) {
            $prefix = config('database.prefix');
            $devices = Db::query("
                SELECT * FROM {$prefix}device_fingerprint 
                WHERE user_id = ? 
                ORDER BY last_seen DESC
            ", [$userId]);

            return json(['total' => count($devices), 'rows' => $devices]);
        }

        $this->view->assign('user_id', $userId);
        return $this->view->fetch();
    }

    /**
     * 创建用户冻结日志表（如果不存在）
     */
    protected function createUserFreezeLogTableIfNotExists()
    {
        $prefix = config('database.prefix');
        $tableName = $prefix . 'user_freeze_log';
        
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$tableName}'");
        if (!empty($exists)) {
            return;
        }
        
        // 创建表
        $sql = "CREATE TABLE `{$tableName}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
            `reason` varchar(500) NOT NULL DEFAULT '' COMMENT '冻结原因',
            `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
            `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
            `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '状态:active-生效中,released-已释放',
            `release_time` int(10) unsigned DEFAULT NULL COMMENT '解冻时间',
            `release_reason` varchar(500) DEFAULT '' COMMENT '解冻原因',
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_status` (`status`),
            KEY `idx_createtime` (`createtime`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户冻结日志表'";
        
        Db::execute($sql);
    }

    /**
     * 创建后台金币日志表（如果不存在）
     */
    protected function createAdminCoinLogTableIfNotExists()
    {
        $prefix = config('database.prefix');
        $tableName = $prefix . 'admin_coin_log';
        
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$tableName}'");
        if (!empty($exists)) {
            return;
        }
        
        // 创建表
        $sql = "CREATE TABLE `{$tableName}` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
            `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员用户名',
            `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
            `amount` int(11) NOT NULL DEFAULT '0' COMMENT '金币数量',
            `type` varchar(20) NOT NULL DEFAULT '' COMMENT '操作类型',
            `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
            `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_admin_id` (`admin_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_createtime` (`createtime`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台金币操作日志表'";
        
        Db::execute($sql);
    }
}
