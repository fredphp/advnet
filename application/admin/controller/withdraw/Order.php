<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\WithdrawService;
use app\common\model\WithdrawOrder;
use app\common\model\CoinLog;
use think\Db;
use think\Exception;

/**
 * 提现审核管理
 * 支持按年分表查询
 */
class Order extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\WithdrawOrder();
    }

    /**
     * 获取列表统计数据（今日提现金额、待审核、待打款、已拒绝等）
     * 只统计人民币金额，不统计金币
     */
    public function getStats()
    {
        // 今日时间范围
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        // 当年时间范围
        $yearStart = strtotime(date('Y-01-01'));
        $yearEnd = strtotime(date('Y-12-31 23:59:59'));
        
        // 获取当年分表
        $tables = WithdrawOrder::ensureTablesExistByRange($yearStart, $yearEnd);
        $prefix = \think\Config::get('database.prefix');
        
        $stats = [
            'today_total' => 0,        // 今日提现总金额（人民币）
            'today_count' => 0,        // 今日提现笔数
            'pending_amount' => 0,     // 待审核金额（人民币）
            'pending_count' => 0,      // 待审核笔数
            'approved_amount' => 0,    // 待打款金额（人民币）
            'approved_count' => 0,     // 待打款笔数
            'rejected_amount' => 0,    // 已拒绝金额（人民币）
            'rejected_count' => 0,     // 已拒绝笔数
            'success_amount' => 0,     // 已成功金额（人民币）
            'success_count' => 0,      // 已成功笔数
        ];
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                // 今日统计（只算人民币cash_amount）
                $stats['today_count'] += Db::name($table)
                    ->where('createtime', '>=', $todayStart)
                    ->where('createtime', '<=', $todayEnd)
                    ->count();
                $stats['today_total'] += Db::name($table)
                    ->where('createtime', '>=', $todayStart)
                    ->where('createtime', '<=', $todayEnd)
                    ->sum('cash_amount');
                
                // 待审核统计（状态0）
                $stats['pending_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_PENDING)
                    ->count();
                $stats['pending_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_PENDING)
                    ->sum('cash_amount');
                
                // 待打款统计（状态1：审核通过）
                $stats['approved_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_APPROVED)
                    ->count();
                $stats['approved_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_APPROVED)
                    ->sum('cash_amount');
                
                // 已拒绝统计（状态4）
                $stats['rejected_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_REJECTED)
                    ->count();
                $stats['rejected_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_REJECTED)
                    ->sum('cash_amount');
                
                // 已成功统计（状态3）
                $stats['success_count'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->count();
                $stats['success_amount'] += Db::name($table)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->sum('cash_amount');
            }
        }
        
        // 格式化金额
        $stats['today_total'] = round($stats['today_total'], 2);
        $stats['pending_amount'] = round($stats['pending_amount'], 2);
        $stats['approved_amount'] = round($stats['approved_amount'], 2);
        $stats['rejected_amount'] = round($stats['rejected_amount'], 2);
        $stats['success_amount'] = round($stats['success_amount'], 2);

        $this->success('获取成功', null, $stats);
    }

    /**
     * 提现订单列表
     * 支持分表查询，默认查询当年数据
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 排序参数
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            
            // 解析 FastAdmin 标准筛选参数
            $filter = json_decode($this->request->get('filter', '{}'), true);
            $op = json_decode($this->request->get('op', '{}'), true);
            
            // 构建时间范围
            $startTime = null;
            $endTime = null;
            
            // 处理 createtime 筛选（FastAdmin 时间范围格式）
            if (isset($filter['createtime']) && isset($op['createtime']) && $op['createtime'] == 'RANGE') {
                $timeRange = $filter['createtime'];
                if (strpos($timeRange, ' - ') !== false) {
                    list($startStr, $endStr) = explode(' - ', $timeRange);
                    $startTime = strtotime(trim($startStr));
                    $endTime = strtotime(trim($endStr));
                }
            }
            
            // 如果没有时间筛选，默认查询当年数据
            if ($startTime === null) {
                $startTime = strtotime(date('Y-01-01')); // 当年第一天
            }
            if ($endTime === null) {
                $endTime = strtotime(date('Y-12-31 23:59:59')); // 当年最后一天
            }
            
            // 获取需要查询的分表
            $tables = WithdrawOrder::ensureTablesExistByRange($startTime, $endTime);
            $prefix = \think\Config::get('database.prefix');
            
            // 构建UNION ALL查询
            $unionQueries = [];
            foreach ($tables as $table) {
                if (WithdrawOrder::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }
            
            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
            
            // 构建WHERE条件
            $whereParts = ['1=1'];
            $bindParams = [];
            
            // 时间范围筛选
            $whereParts[] = "wo.createtime BETWEEN ? AND ?";
            $bindParams[] = $startTime;
            $bindParams[] = $endTime;
            
            // 处理其他筛选条件
            foreach ($filter as $field => $value) {
                if ($field == 'createtime') {
                    continue; // 已经处理过
                }
                
                $fieldOp = $op[$field] ?? '=';
                $dbField = 'wo.' . $field;
                
                if ($fieldOp == 'RANGE' && strpos($value, ' - ') !== false) {
                    list($start, $end) = explode(' - ', $value);
                    $whereParts[] = "{$dbField} BETWEEN ? AND ?";
                    $bindParams[] = trim($start);
                    $bindParams[] = trim($end);
                } elseif ($fieldOp == 'LIKE') {
                    $whereParts[] = "{$dbField} LIKE ?";
                    $bindParams[] = '%' . $value . '%';
                } elseif ($fieldOp == 'IN') {
                    $values = is_array($value) ? $value : explode(',', $value);
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $whereParts[] = "{$dbField} IN ({$placeholders})";
                    $bindParams = array_merge($bindParams, $values);
                } elseif ($fieldOp == '=') {
                    $whereParts[] = "{$dbField} = ?";
                    $bindParams[] = $value;
                } elseif ($fieldOp == '!=') {
                    $whereParts[] = "{$dbField} != ?";
                    $bindParams[] = $value;
                } elseif ($fieldOp == '>') {
                    $whereParts[] = "{$dbField} > ?";
                    $bindParams[] = $value;
                } elseif ($fieldOp == '<') {
                    $whereParts[] = "{$dbField} < ?";
                    $bindParams[] = $value;
                } elseif ($fieldOp == '>=') {
                    $whereParts[] = "{$dbField} >= ?";
                    $bindParams[] = $value;
                } elseif ($fieldOp == '<=') {
                    $whereParts[] = "{$dbField} <= ?";
                    $bindParams[] = $value;
                } else {
                    $whereParts[] = "{$dbField} = ?";
                    $bindParams[] = $value;
                }
            }
            
            $whereStr = implode(' AND ', $whereParts);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE {$whereStr}";
            $totalResult = Db::query($countSql, $bindParams);
            $total = $totalResult[0]['total'] ?? 0;
            
            // 查询列表（关联用户信息）
            $listSql = "SELECT wo.*, u.username, u.nickname, u.mobile, u.avatar 
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                        WHERE {$whereStr} 
                        ORDER BY wo.{$sort} {$order} 
                        LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, $bindParams);
            
            // 格式化数据（确保包含 FastAdmin 需要的字段）
            foreach ($list as &$row) {
                $row['status_text'] = WithdrawOrder::$statusList[$row['status']] ?? '';
                $row['withdraw_type_text'] = WithdrawOrder::$typeList[$row['withdraw_type']] ?? '';
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
                // 确保 ID 是整数
                $row['id'] = intval($row['id']);
                $row['user_id'] = intval($row['user_id']);
            }
            
            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch();
    }

    /**
     * 提现详情
     */
    public function detail($ids = null)
    {
        // 支持通过订单号查询
        $orderNo = $this->request->get('order_no');
        $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($ids);
        
        if (!$row) {
            $this->error('订单不存在');
        }

        // 用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();

        // 用户提现统计
        $userStats = $this->getUserWithdrawStats($row['user_id']);

        // 风险信息
        $riskInfo = Db::name('user_risk_score')
            ->where('user_id', $row['user_id'])
            ->find();

        // 最近提现记录
        $recentOrders = $this->getRecentOrders($row['user_id'], $row['id']);

        $this->view->assign('order', $row);
        $this->view->assign('user', $user);
        $this->view->assign('user_stats', $userStats);
        $this->view->assign('risk_info', $riskInfo);
        $this->view->assign('recent_orders', $recentOrders);
        $this->view->assign('withdrawTypeText', WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 审核通过弹窗
     */
    public function approve($ids = null)
    {
        // POST 请求 - 执行审核
        if ($this->request->isPost()) {
            // 支持通过订单号或ID查询
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $remark = $this->request->post('remark', '');

            if (empty($orderNo) && empty($id)) {
                $this->error('参数错误，缺少订单号或订单ID');
            }

            // 优先使用订单号查询
            $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($id);
            
            if (!$row) {
                $this->error('订单不存在');
            }

            if ($row['status'] != WithdrawOrder::STATUS_PENDING) {
                $statusText = WithdrawOrder::$statusList[$row['status']] ?? '未知状态';
                $this->error("该订单已处理，当前状态: {$statusText}");
            }

            Db::startTrans();
            try {
                // 获取订单所在表名
                $tableName = $row['_table'] ?? 'withdraw_order';
                
                // 使用订单号作为条件更新
                Db::name($tableName)->where('order_no', $row['order_no'])->update([
                    'status' => WithdrawOrder::STATUS_APPROVED,
                    'audit_type' => 1, // 人工审核
                    'audit_admin_id' => $this->auth->id,
                    'audit_admin_name' => $this->auth->username,
                    'audit_time' => time(),
                    'audit_remark' => $remark,
                    'updatetime' => time(),
                ]);

                Db::commit();
                $this->success('审核通过，订单进入待打款状态');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        // GET 请求 - 显示弹窗
        $orderNo = $this->request->get('order_no');
        $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($ids);
        
        if (!$row) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
        // 获取用户账户信息
        $account = Db::name('coin_account')->where('user_id', $row['user_id'])->find();
        if (!$account) {
            $account = ['balance' => 0, 'frozen' => 0, 'total_earn' => 0];
        }
        
        // 今日提现统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        $todayStats = $this->getUserTodayStats($row['user_id'], $todayStart, $todayEnd);
        
        // 累计提现统计
        $totalStats = $this->getUserTotalStats($row['user_id']);
        
        // 风险信息
        $riskInfo = Db::name('user_risk_score')->where('user_id', $row['user_id'])->find();
        $riskInfoTags = [];
        if ($riskInfo && $riskInfo['risk_tags']) {
            $riskInfoTags = json_decode($riskInfo['risk_tags'], true) ?: [];
        }
        
        // 订单风控标签
        $orderRiskTags = [];
        if (!empty($row['risk_tags'])) {
            $orderRiskTags = json_decode($row['risk_tags'], true) ?: [];
        }
        
        // 最近提现记录
        $recentOrders = $this->getRecentOrders($row['user_id'], $row['id']);
        
        $this->view->assign('order', $row);
        $this->view->assign('user', $user);
        $this->view->assign('account', $account);
        $this->view->assign('todayStats', $todayStats);
        $this->view->assign('totalStats', $totalStats);
        $this->view->assign('riskInfo', $riskInfo);
        $this->view->assign('riskInfoTags', $riskInfoTags);
        $this->view->assign('orderRiskTags', $orderRiskTags);
        $this->view->assign('recentOrders', $recentOrders);
        $this->view->assign('withdrawTypeText', WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 审核拒绝弹窗
     */
    public function reject($ids = null)
    {
        // POST 请求 - 执行拒绝
        if ($this->request->isPost()) {
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $reason = $this->request->post('reason', '');
            $customReason = $this->request->post('custom_reason', '');

            $rejectReason = $reason === 'custom' ? $customReason : $reason;

            if (empty($rejectReason)) {
                $this->error('请选择或填写拒绝原因');
            }

            // 优先使用订单号查询
            $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($id);
            if (!$row) {
                $this->error('订单不存在');
            }

            if ($row['status'] != WithdrawOrder::STATUS_PENDING) {
                $this->error('该订单已处理');
            }

            Db::startTrans();
            try {
                // 获取订单所在表名
                $tableName = $row['_table'] ?? 'withdraw_order';

                // 获取用户账户
                $account = Db::name('coin_account')
                    ->where('user_id', $row['user_id'])
                    ->lock(true)
                    ->find();

                if (!$account) {
                    throw new Exception('用户账户不存在');
                }

                // 检查冻结金币是否足够
                if ($account['frozen'] < $row['coin_amount']) {
                    // 如果冻结不足，直接加到余额（兼容历史数据）
                    Db::name('coin_account')
                        ->where('user_id', $row['user_id'])
                        ->update([
                            'balance' => $account['balance'] + $row['coin_amount'],
                            'updatetime' => time(),
                        ]);
                } else {
                    // 解冻金币（从冻结转回余额）
                    Db::name('coin_account')
                        ->where('user_id', $row['user_id'])
                        ->update([
                            'balance' => $account['balance'] + $row['coin_amount'],
                            'frozen' => $account['frozen'] - $row['coin_amount'],
                            'updatetime' => time(),
                        ]);
                }

                // 记录金币流水（写入当月分表，自动创建）
                $logTableName = CoinLog::getOrCreateTable();
                
                Db::name($logTableName)->insert([
                    'user_id' => $row['user_id'],
                    'type' => 'withdraw_refund',
                    'amount' => $row['coin_amount'],
                    'balance_before' => $account['balance'],
                    'balance_after' => $account['balance'] + $row['coin_amount'],
                    'relation_type' => 'withdraw',
                    'relation_id' => $row['id'],
                    'title' => '提现拒绝退还',
                    'description' => "提现拒绝退还，订单号: {$row['order_no']}，原因: {$rejectReason}",
                    'createtime' => time(),
                    'create_date' => date('Y-m-d'),
                ]);

                // 更新订单状态
                Db::name($tableName)->where('order_no', $row['order_no'])->update([
                    'status' => WithdrawOrder::STATUS_REJECTED,
                    'audit_type' => 1, // 人工审核
                    'audit_admin_id' => $this->auth->id,
                    'audit_admin_name' => $this->auth->username,
                    'audit_time' => time(),
                    'reject_reason' => $rejectReason,
                    'updatetime' => time(),
                ]);

                Db::commit();
                $this->success('已拒绝并退还金币');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        // GET 请求 - 显示弹窗
        $orderNo = $this->request->get('order_no');
        $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
        // 获取用户账户信息
        $account = Db::name('coin_account')->where('user_id', $row['user_id'])->find();
        if (!$account) {
            $account = ['balance' => 0];
        }
        
        $this->view->assign('order', $row);
        $this->view->assign('user', $user);
        $this->view->assign('account', $account);
        $this->view->assign('withdrawTypeText', WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 确认打款弹窗
     */
    public function complete($ids = null)
    {
        // POST 请求 - 执行打款
        if ($this->request->isPost()) {
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $transferNo = $this->request->post('transfer_no', '');
            $remark = $this->request->post('remark', '');

            // 优先使用订单号查询
            $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($id);
            if (!$row) {
                $this->error('订单不存在');
            }

            if (!in_array($row['status'], [WithdrawOrder::STATUS_PENDING, WithdrawOrder::STATUS_APPROVED])) {
                $statusText = WithdrawOrder::$statusList[$row['status']] ?? '未知状态';
                $this->error("该订单状态不允许打款，当前状态: {$statusText}");
            }

            Db::startTrans();
            try {
                // 获取订单所在表名
                $tableName = $row['_table'] ?? 'withdraw_order';
                
                // 如果是待审核状态，先自动审核通过
                if ($row['status'] == WithdrawOrder::STATUS_PENDING) {
                    Db::name($tableName)->where('order_no', $row['order_no'])->update([
                        'status' => WithdrawOrder::STATUS_APPROVED,
                        'audit_type' => 1, // 人工审核
                        'audit_admin_id' => $this->auth->id,
                        'audit_admin_name' => $this->auth->username,
                        'audit_time' => time(),
                        'updatetime' => time(),
                    ]);
                }
                
                // 更新为打款成功状态
                Db::name($tableName)->where('order_no', $row['order_no'])->update([
                    'status' => WithdrawOrder::STATUS_SUCCESS,
                    'transfer_no' => $transferNo,
                    'complete_time' => time(),
                    'audit_remark' => $remark,
                    'updatetime' => time(),
                ]);

                Db::commit();
                $this->success('打款成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        // GET 请求 - 显示弹窗
        $orderNo = $this->request->get('order_no');
        $row = $orderNo ? $this->getOrderByNo($orderNo) : $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
        // 今日提现统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        $todayStats = $this->getUserTodayStats($row['user_id'], $todayStart, $todayEnd);
        
        // 累计提现统计
        $totalStats = $this->getUserTotalStats($row['user_id']);
        
        $this->view->assign('order', $row);
        $this->view->assign('user', $user);
        $this->view->assign('todayStats', $todayStats);
        $this->view->assign('totalStats', $totalStats);
        $this->view->assign('withdrawTypeText', WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 创建金币流水表（如果不存在）
     */
    protected function createCoinLogTableIfNotExists($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTableName = $prefix . $tableName;
        
        $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
        if (empty($exists)) {
            $sql = "CREATE TABLE `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL DEFAULT '0',
                `type` varchar(50) NOT NULL DEFAULT '' COMMENT '类型',
                `amount` int(11) NOT NULL DEFAULT '0' COMMENT '金额',
                `balance_before` int(11) NOT NULL DEFAULT '0' COMMENT '变动前余额',
                `balance_after` int(11) NOT NULL DEFAULT '0' COMMENT '变动后余额',
                `relation_type` varchar(50) DEFAULT NULL COMMENT '关联类型',
                `relation_id` int(11) DEFAULT NULL COMMENT '关联ID',
                `title` varchar(255) DEFAULT NULL COMMENT '标题',
                `description` varchar(500) DEFAULT NULL COMMENT '描述',
                `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
                `create_date` date DEFAULT NULL COMMENT '创建日期',
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_type` (`type`),
                KEY `idx_createtime` (`createtime`),
                KEY `idx_create_date` (`create_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币流水表'";
            Db::execute($sql);
        }
    }

    /**
     * 批量审核通过
     */
    public function batchApprove()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $ids = is_array($ids) ? $ids : explode(',', $ids);
        
        $count = 0;
        foreach ($ids as $id) {
            $row = $this->getOrderById($id);
            if ($row && $row['status'] == WithdrawOrder::STATUS_PENDING) {
                $tableName = $row['_table'] ?? 'withdraw_order';
                Db::name($tableName)->where('order_no', $row['order_no'])->update([
                    'status' => WithdrawOrder::STATUS_APPROVED,
                    'audit_type' => 1, // 人工审核
                    'audit_admin_id' => $this->auth->id,
                    'audit_admin_name' => $this->auth->username,
                    'audit_time' => time(),
                    'updatetime' => time()
                ]);
                $count++;
            }
        }

        $this->success("成功审核{$count}条记录");
    }

    /**
     * 批量打款
     */
    public function batchPay()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $ids = is_array($ids) ? $ids : explode(',', $ids);

        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $row = $this->getOrderById($id);
            if ($row && in_array($row['status'], [WithdrawOrder::STATUS_PENDING, WithdrawOrder::STATUS_APPROVED])) {
                try {
                    $tableName = $row['_table'] ?? 'withdraw_order';
                    
                    // 如果是待审核状态，先审核通过
                    if ($row['status'] == WithdrawOrder::STATUS_PENDING) {
                        Db::name($tableName)->where('order_no', $row['order_no'])->update([
                            'status' => WithdrawOrder::STATUS_APPROVED,
                            'audit_type' => 1, // 人工审核
                            'audit_admin_id' => $this->auth->id,
                            'audit_admin_name' => $this->auth->username,
                            'audit_time' => time(),
                            'updatetime' => time(),
                        ]);
                    }
                    
                    // 更新为打款成功
                    Db::name($tableName)->where('order_no', $row['order_no'])->update([
                        'status' => WithdrawOrder::STATUS_SUCCESS,
                        'complete_time' => time(),
                        'updatetime' => time(),
                    ]);
                    $success++;
                } catch (Exception $e) {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        $this->success("成功打款{$success}笔，失败{$failed}笔");
    }

    /**
     * 导出提现记录
     */
    public function export()
    {
        $filter = json_decode($this->request->get('filter', '{}'), true);
        $op = json_decode($this->request->get('op', '{}'), true);

        $startTime = null;
        $endTime = null;
        
        if (isset($filter['createtime']) && isset($op['createtime']) && $op['createtime'] == 'RANGE') {
            $timeRange = $filter['createtime'];
            if (strpos($timeRange, ' - ') !== false) {
                list($startStr, $endStr) = explode(' - ', $timeRange);
                $startTime = strtotime(trim($startStr));
                $endTime = strtotime(trim($endStr));
            }
        }
        
        // 默认导出当年数据
        if ($startTime === null) {
            $startTime = strtotime(date('Y-01-01'));
        }
        if ($endTime === null) {
            $endTime = strtotime(date('Y-12-31 23:59:59'));
        }

        $tables = WithdrawOrder::ensureTablesExistByRange($startTime, $endTime);
        $prefix = \think\Config::get('database.prefix');

        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }

        if (empty($unionQueries)) {
            $this->error('没有可导出的数据');
        }

        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';

        $whereParts = ['wo.createtime BETWEEN ? AND ?'];
        $bindParams = [$startTime, $endTime];
        
        if (isset($filter['status'])) {
            $whereParts[] = "wo.status = ?";
            $bindParams[] = $filter['status'];
        }

        $whereStr = implode(' AND ', $whereParts);

        $sql = "SELECT wo.*, u.username, u.nickname, u.mobile 
                FROM {$unionSql} 
                LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                WHERE {$whereStr} 
                ORDER BY wo.createtime DESC";
        $list = Db::query($sql, $bindParams);

        $filename = 'withdraw_orders_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['订单号', '用户ID', '用户名', '手机号', '提现金额', '提现方式', '收款账号', '收款人', '状态', '申请时间', '处理时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['order_no'],
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['cash_amount'],
                WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type'],
                $row['withdraw_account'],
                $row['withdraw_name'],
                WithdrawOrder::$statusList[$row['status']] ?? $row['status'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['complete_time'] ? date('Y-m-d H:i:s', $row['complete_time']) : '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 根据ID获取订单（跨分表查询）
     */
    protected function getOrderById($id)
    {
        // 查询最近6年的数据
        $tables = WithdrawOrder::ensureTablesExistByRange(strtotime('-6 years'), time());
        $prefix = \think\Config::get('database.prefix');
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $row = Db::name($table)->where('id', $id)->find();
                if ($row) {
                    $row['_table'] = $table;
                    return $row;
                }
            }
        }
        
        return null;
    }

    /**
     * 根据订单号获取订单（跨分表查询）
     */
    protected function getOrderByNo($orderNo)
    {
        // 首先尝试从订单号获取对应年份的分表
        $tableByOrderNo = WithdrawOrder::getTableByOrderNo($orderNo);
        if ($tableByOrderNo && WithdrawOrder::tableExists($tableByOrderNo)) {
            $row = Db::name($tableByOrderNo)->where('order_no', $orderNo)->find();
            if ($row) {
                $row['_table'] = $tableByOrderNo;
                return $row;
            }
        }
        
        // 如果订单号中没有年份信息或未找到，则查询最近6年的数据
        $tables = WithdrawOrder::ensureTablesExistByRange(strtotime('-6 years'), time());
        $prefix = \think\Config::get('database.prefix');
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $row = Db::name($table)->where('order_no', $orderNo)->find();
                if ($row) {
                    $row['_table'] = $table;
                    return $row;
                }
            }
        }
        
        return null;
    }

    /**
     * 更新订单状态（跨分表）
     */
    protected function updateOrderStatus($id, $data)
    {
        $tables = WithdrawOrder::ensureTablesExistByRange(strtotime('-6 years'), time());
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $affected = Db::name($table)->where('id', $id)->update($data);
                if ($affected > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 获取用户今日提现统计
     */
    protected function getUserTodayStats($userId, $startTime, $endTime)
    {
        $tables = WithdrawOrder::ensureTablesExistByRange($startTime, $endTime);
        $prefix = \think\Config::get('database.prefix');
        
        $stats = [
            'count' => 0,
            'coin_amount' => 0,
            'cash_amount' => 0,
            'success_count' => 0,
            'success_amount' => 0
        ];
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $stats['count'] += Db::name($table)
                    ->where('user_id', $userId)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['coin_amount'] += Db::name($table)
                    ->where('user_id', $userId)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('coin_amount');
                $stats['cash_amount'] += Db::name($table)
                    ->where('user_id', $userId)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');
                $stats['success_count'] += Db::name($table)
                    ->where('user_id', $userId)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->count();
                $stats['success_amount'] += Db::name($table)
                    ->where('user_id', $userId)
                    ->where('status', WithdrawOrder::STATUS_SUCCESS)
                    ->where('createtime', '>=', $startTime)
                    ->where('createtime', '<=', $endTime)
                    ->sum('cash_amount');
            }
        }
        
        return $stats;
    }

    /**
     * 获取用户累计提现统计
     */
    protected function getUserTotalStats($userId)
    {
        // 查询最近12年的数据
        $tables = WithdrawOrder::ensureTablesExistByRange(strtotime('-12 years'), time());
        $prefix = \think\Config::get('database.prefix');
        
        $stats = [
            'total_count' => 0,
            'total_coin' => 0,
            'total_amount' => 0,
            'success_count' => 0,
            'success_amount' => 0
        ];
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $stats['total_count'] += Db::name($table)->where('user_id', $userId)->count();
                $stats['total_coin'] += Db::name($table)->where('user_id', $userId)->sum('coin_amount');
                $stats['total_amount'] += Db::name($table)->where('user_id', $userId)->sum('cash_amount');
                $stats['success_count'] += Db::name($table)->where('user_id', $userId)->where('status', WithdrawOrder::STATUS_SUCCESS)->count();
                $stats['success_amount'] += Db::name($table)->where('user_id', $userId)->where('status', WithdrawOrder::STATUS_SUCCESS)->sum('cash_amount');
            }
        }
        
        return $stats;
    }

    /**
     * 获取用户提现统计（跨分表）
     */
    protected function getUserWithdrawStats($userId)
    {
        return $this->getUserTotalStats($userId);
    }

    /**
     * 获取用户最近提现记录（跨分表）
     */
    protected function getRecentOrders($userId, $excludeId)
    {
        // 查询最近3年的数据
        $tables = WithdrawOrder::ensureTablesExistByRange(strtotime('-3 years'), time());
        $prefix = \think\Config::get('database.prefix');
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return [];
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT * FROM {$unionSql} 
                WHERE user_id = ? AND id != ? 
                ORDER BY createtime DESC 
                LIMIT 5";
        
        $list = Db::query($sql, [$userId, $excludeId]);
        
        foreach ($list as &$row) {
            $row['status_text'] = WithdrawOrder::$statusList[$row['status']] ?? '未知';
        }
        
        return $list;
    }

    /**
     * 待审核列表
     */
    public function pending()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            // 查询当年的数据
            $tables = WithdrawOrder::ensureTablesExistByRange(strtotime(date('Y-01-01')), time());
            $prefix = \think\Config::get('database.prefix');

            $unionQueries = [];
            foreach ($tables as $table) {
                if (WithdrawOrder::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }

            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }

            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';

            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE status = ?";
            $totalResult = Db::query($countSql, [WithdrawOrder::STATUS_PENDING]);
            $total = $totalResult[0]['total'] ?? 0;

            $listSql = "SELECT wo.*, u.username, u.nickname, u.mobile 
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                        WHERE wo.status = ? 
                        ORDER BY wo.createtime ASC 
                        LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, [WithdrawOrder::STATUS_PENDING]);

            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch('index');
    }
}
