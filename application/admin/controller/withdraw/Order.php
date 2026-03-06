<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\model\WithdrawOrderSplit;
use think\Db;
use think\Exception;

/**
 * 提现审核管理
 */
class Order extends Backend
{
    protected $model = null;
    protected $splitModel = null;
    
    // 状态列表
    protected $statusList = [
        0 => '待审核',
        1 => '待打款',
        2 => '打款中',
        3 => '提现成功',
        4 => '审核拒绝',
        5 => '打款失败',
        6 => '已取消'
    ];
    
    // 提现方式
    protected $withdrawTypes = [
        'alipay' => '支付宝',
        'wechat' => '微信',
        'bank' => '银行卡'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\WithdrawOrder();
        $this->splitModel = new WithdrawOrderSplit();
    }

    /**
     * 提现订单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取统计面板数据
            if ($this->request->get('stats') === '1') {
                $stats = WithdrawOrderSplit::getDashboardStats();
                $this->success('', $stats);
                return;
            }
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // 获取日期范围，用于选择分表
            $startDate = $this->request->get('start_date', date('Y-m-01'));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            // 获取所有相关分表
            $tables = $this->splitModel->getTableList($startDate, $endDate);
            
            $total = 0;
            $list = [];
            
            foreach ($tables as $table) {
                $query = Db::name($table)
                    ->alias('wo')
                    ->join('user u', 'u.id = wo.user_id', 'LEFT')
                    ->field('wo.*, u.username, u.nickname, u.mobile, u.avatar')
                    ->where($where)
                    ->where('wo.createtime', '>=', strtotime($startDate))
                    ->where('wo.createtime', '<=', strtotime($endDate . ' 23:59:59'));
                
                $tableTotal = $query->count();
                $total += $tableTotal;
                
                if ($tableTotal > 0 && count($list) < $limit) {
                    $tableList = Db::name($table)
                        ->alias('wo')
                        ->join('user u', 'u.id = wo.user_id', 'LEFT')
                        ->field('wo.*, u.username, u.nickname, u.mobile, u.avatar')
                        ->where($where)
                        ->where('wo.createtime', '>=', strtotime($startDate))
                        ->where('wo.createtime', '<=', strtotime($endDate . ' 23:59:59'))
                        ->order("wo.{$sort}", $order)
                        ->limit($offset, $limit - count($list))
                        ->select();
                    
                    foreach ($tableList as $row) {
                        $row['_table'] = $table;
                        $row['status_text'] = $this->statusList[$row['status']] ?? '未知';
                        $list[] = $row;
                    }
                }
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        // 获取统计数据
        $stats = WithdrawOrderSplit::getDashboardStats();
        $this->view->assign('stats', $stats);
        
        return $this->view->fetch();
    }

    /**
     * 审核通过弹窗
     */
    public function approve($ids = null)
    {
        if ($this->request->isPost()) {
            // 支持通过订单号或ID查询
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $remark = $this->request->post('remark', '');

            if (empty($orderNo) && empty($id)) {
                $this->error('参数错误，缺少订单号或订单ID');
            }

            // 优先使用订单号查询（避免分表ID重复问题）
            $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($id);
            
            // 检查是否找到订单
            if (isset($order['_not_found']) && $order['_not_found']) {
                $this->error('订单不存在');
            }
            
            if (!$order) {
                $this->error('订单不存在');
            }

            $currentStatus = isset($order['status']) ? intval($order['status']) : -1;
            $tableName = $order['_table'] ?? '未知表';
            $orderId = $order['id'] ?? '未知';
            $orderNo = $order['order_no'] ?? '未知';
            
            if ($currentStatus != 0) {
                $statusText = $this->statusList[$currentStatus] ?? '未知状态';
                $this->error("该订单已处理，当前状态: {$statusText}");
            }

            Db::startTrans();
            try {
                // 获取订单所在表名
                $tableName = $order['_table'] ?? 'withdraw_order';

                // 更新订单状态 - 使用订单号作为条件
                Db::name($tableName)->where('order_no', $order['order_no'])->update([
                    'status' => 1, // 待打款
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
        
        // GET请求 - 显示弹窗
        // 优先使用订单号查询
        $orderNo = $this->request->get('order_no');
        $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($ids);
        
        if (!$order || (isset($order['_not_found']) && $order['_not_found'])) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $order['user_id'])->find();
        
        // 获取用户账户信息
        $account = Db::name('coin_account')->where('user_id', $order['user_id'])->find();
        if (!$account) {
            $account = ['balance' => 0, 'frozen' => 0, 'total_earn' => 0];
        }
        
        // 今日提现统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        $tables = $this->splitModel->getTableList(date('Y-m-d'), date('Y-m-d'));
        $todayStats = ['count' => 0, 'coin_amount' => 0, 'cash_amount' => 0, 'success_count' => 0, 'success_amount' => 0];
        
        foreach ($tables as $table) {
            $todayStats['count'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->count();
            $todayStats['coin_amount'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('coin_amount');
            $todayStats['cash_amount'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('cash_amount');
            $todayStats['success_count'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('status', 3)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->count();
            $todayStats['success_amount'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('status', 3)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('cash_amount');
        }
        
        // 累计提现统计
        $allTables = $this->splitModel->getTableList();
        $totalStats = ['total_count' => 0, 'total_coin' => 0, 'total_amount' => 0, 'success_count' => 0, 'success_amount' => 0];
        
        foreach ($allTables as $table) {
            $totalStats['total_count'] += Db::name($table)->where('user_id', $order['user_id'])->count();
            $totalStats['total_coin'] += Db::name($table)->where('user_id', $order['user_id'])->sum('coin_amount');
            $totalStats['total_amount'] += Db::name($table)->where('user_id', $order['user_id'])->sum('cash_amount');
            $totalStats['success_count'] += Db::name($table)->where('user_id', $order['user_id'])->where('status', 3)->count();
            $totalStats['success_amount'] += Db::name($table)->where('user_id', $order['user_id'])->where('status', 3)->sum('cash_amount');
        }
        
        // 风险信息
        $riskInfo = Db::name('user_risk_score')->where('user_id', $order['user_id'])->find();
        $riskInfoTags = [];
        if ($riskInfo && $riskInfo['risk_tags']) {
            $riskInfoTags = json_decode($riskInfo['risk_tags'], true) ?: [];
        }
        
        // 订单风控标签
        $orderRiskTags = [];
        if ($order['risk_tags']) {
            $orderRiskTags = json_decode($order['risk_tags'], true) ?: [];
        }
        
        // 最近提现记录
        $recentOrders = [];
        foreach ($allTables as $table) {
            $records = Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('id', '<>', $ids)
                ->order('createtime', 'desc')
                ->limit(5 - count($recentOrders))
                ->select();
            foreach ($records as $record) {
                $record['status_text'] = $this->statusList[$record['status']] ?? '未知';
                $recentOrders[] = $record;
            }
            if (count($recentOrders) >= 5) break;
        }
        
        $this->view->assign('order', $order);
        $this->view->assign('user', $user);
        $this->view->assign('account', $account);
        $this->view->assign('todayStats', $todayStats);
        $this->view->assign('totalStats', $totalStats);
        $this->view->assign('riskInfo', $riskInfo);
        $this->view->assign('riskInfoTags', $riskInfoTags);
        $this->view->assign('orderRiskTags', $orderRiskTags);
        $this->view->assign('recentOrders', $recentOrders);
        $this->view->assign('withdrawTypeText', $this->withdrawTypes[$order['withdraw_type']] ?? $order['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 审核拒绝弹窗
     */
    public function reject($ids = null)
    {
        if ($this->request->isPost()) {
            // 支持通过订单号或ID查询
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $reason = $this->request->post('reason', '');
            $customReason = $this->request->post('custom_reason', '');

            $rejectReason = $reason === 'custom' ? $customReason : $reason;

            if (empty($rejectReason)) {
                $this->error('请选择或填写拒绝原因');
            }

            // 优先使用订单号查询
            $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($id);
            if (!$order || (isset($order['_not_found']) && $order['_not_found'])) {
                $this->error('订单不存在');
            }

            if ($order['status'] != 0) {
                $this->error('该订单已处理');
            }

            Db::startTrans();
            try {
                // 获取订单所在表名
                $tableName = $order['_table'] ?? 'withdraw_order';

                // 获取用户账户
                $account = Db::name('coin_account')
                    ->where('user_id', $order['user_id'])
                    ->lock(true)
                    ->find();

                if (!$account) {
                    throw new Exception('用户账户不存在');
                }

                // 检查冻结金币是否足够
                if ($account['frozen'] < $order['coin_amount']) {
                    // 如果冻结不足，直接加到余额（兼容历史数据）
                    Db::name('coin_account')
                        ->where('user_id', $order['user_id'])
                        ->update([
                            'balance' => $account['balance'] + $order['coin_amount'],
                            'updatetime' => time(),
                        ]);
                } else {
                    // 解冻金币（从冻结转回余额）
                    Db::name('coin_account')
                        ->where('user_id', $order['user_id'])
                        ->update([
                            'balance' => $account['balance'] + $order['coin_amount'],
                            'frozen' => $account['frozen'] - $order['coin_amount'],
                            'updatetime' => time(),
                        ]);
                }

                // 记录金币流水
                $logTableName = 'coin_log_' . date('Ym');
                Db::name($logTableName)->insert([
                    'user_id' => $order['user_id'],
                    'type' => 'withdraw_refund',
                    'amount' => $order['coin_amount'],
                    'balance_before' => $account['balance'],
                    'balance_after' => $account['balance'] + $order['coin_amount'],
                    'relation_type' => 'withdraw',
                    'relation_id' => $order['id'],
                    'title' => '提现拒绝退还',
                    'description' => "提现拒绝退还，订单号: {$order['order_no']}，原因: {$rejectReason}",
                    'createtime' => time(),
                    'create_date' => date('Y-m-d'),
                ]);

                // 更新订单状态 - 使用订单号作为条件
                Db::name($tableName)->where('order_no', $order['order_no'])->update([
                    'status' => 4, // 审核拒绝
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
        
        // GET请求 - 显示弹窗
        $orderNo = $this->request->get('order_no');
        $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($ids);
        if (!$order || (isset($order['_not_found']) && $order['_not_found'])) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $order['user_id'])->find();
        
        // 获取用户账户信息
        $account = Db::name('coin_account')->where('user_id', $order['user_id'])->find();
        if (!$account) {
            $account = ['balance' => 0];
        }
        
        $this->view->assign('order', $order);
        $this->view->assign('user', $user);
        $this->view->assign('account', $account);
        $this->view->assign('withdrawTypeText', $this->withdrawTypes[$order['withdraw_type']] ?? $order['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 确认打款弹窗
     */
    public function complete($ids = null)
    {
        if ($this->request->isPost()) {
            // 支持通过订单号或ID查询
            $orderNo = $this->request->post('order_no');
            $id = $this->request->post('id');
            $transferNo = $this->request->post('transfer_no', '');
            $remark = $this->request->post('remark', '');
            
            // 优先使用订单号查询
            $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($id);
            if (!$order || (isset($order['_not_found']) && $order['_not_found'])) {
                $this->error('订单不存在');
            }

            // 只有审核通过/待打款状态(status=1)才能打款
            if ($order['status'] != 1) {
                $this->error('只有待打款状态的订单才能确认打款');
            }

            Db::startTrans();
            try {
                // 确认打款成功
                $this->completeOrder($order, $transferNo, $this->auth->id, $remark);

                Db::commit();
                $this->success('打款成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        // GET请求 - 显示弹窗
        $orderNo = $this->request->get('order_no');
        $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($ids);
        if (!$order || (isset($order['_not_found']) && $order['_not_found'])) {
            $this->error('订单不存在');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $order['user_id'])->find();
        if (!$user) {
            $user = ['id' => $order['user_id'], 'nickname' => '', 'mobile' => ''];
        }
        
        // 今日提现统计
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        $tables = $this->splitModel->getTableList(date('Y-m-d'), date('Y-m-d'));
        $todayStats = ['count' => 0, 'cash_amount' => 0, 'success_count' => 0, 'success_amount' => 0];
        
        foreach ($tables as $table) {
            $todayStats['count'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->count();
            $todayStats['cash_amount'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('cash_amount');
            $todayStats['success_count'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('status', 3)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->count();
            $todayStats['success_amount'] += Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('status', 3)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('cash_amount');
        }
        
        // 累计提现统计
        $allTables = $this->splitModel->getTableList();
        $totalStats = ['total_count' => 0, 'total_amount' => 0, 'success_count' => 0, 'success_amount' => 0];
        
        foreach ($allTables as $table) {
            $totalStats['total_count'] += Db::name($table)->where('user_id', $order['user_id'])->count();
            $totalStats['total_amount'] += Db::name($table)->where('user_id', $order['user_id'])->sum('cash_amount');
            $totalStats['success_count'] += Db::name($table)->where('user_id', $order['user_id'])->where('status', 3)->count();
            $totalStats['success_amount'] += Db::name($table)->where('user_id', $order['user_id'])->where('status', 3)->sum('cash_amount');
        }
        
        $this->view->assign('order', $order);
        $this->view->assign('user', $user);
        $this->view->assign('todayStats', $todayStats);
        $this->view->assign('totalStats', $totalStats);
        $this->view->assign('withdrawTypeText', $this->withdrawTypes[$order['withdraw_type']] ?? $order['withdraw_type']);
        
        return $this->view->fetch();
    }

    /**
     * 查找订单（可能在分表中）- 通过ID查询
     * 注意：分表中不同表可能有相同ID，建议使用 findOrderByNo 方法
     */
    protected function findOrder($id)
    {
        // 先查主表
        $order = Db::name('withdraw_order')->where('id', $id)->find();
        
        if ($order) {
            $order['_table'] = 'withdraw_order';
            return $order;
        }

        // 查所有分表
        $tables = $this->splitModel->getTableList();
        
        foreach ($tables as $table) {
            $order = Db::name($table)->where('id', $id)->find();
            
            if ($order) {
                $order['_table'] = $table;
                return $order;
            }
        }

        return null;
    }

    /**
     * 通过订单号查找订单（推荐方式）
     * 订单号是全局唯一的，不会出现分表ID重复问题
     */
    protected function findOrderByNo($orderNo)
    {
        // 先查主表
        $order = Db::name('withdraw_order')->where('order_no', $orderNo)->find();
        
        if ($order) {
            $order['_table'] = 'withdraw_order';
            return $order;
        }

        // 查所有分表
        $tables = $this->splitModel->getTableList();
        
        foreach ($tables as $table) {
            $order = Db::name($table)->where('order_no', $orderNo)->find();
            
            if ($order) {
                $order['_table'] = $table;
                return $order;
            }
        }

        return null;
    }

    /**
     * 完成订单打款
     */
    protected function completeOrder($order, $transferNo, $adminId, $remark = '')
    {
        if (!$order) {
            throw new Exception('订单不存在');
        }
        
        // 更新订单状态 - 只更新表中存在的字段
        $updateData = [
            'status' => 3, // 提现成功
            'transfer_no' => $transferNo,
            'transfer_time' => time(),
            'complete_time' => time(),
            'updatetime' => time(),
        ];
        
        // 将打款管理员信息和备注存入 transfer_result JSON
        $transferResult = json_decode($order['transfer_result'] ?? '', true) ?: [];
        $transferResult['transfer_admin_id'] = $adminId;
        $transferResult['transfer_admin_name'] = $this->auth->username;
        $transferResult['transfer_remark'] = $remark;
        $transferResult['transfer_time'] = date('Y-m-d H:i:s');
        $updateData['transfer_result'] = json_encode($transferResult, JSON_UNESCAPED_UNICODE);
        
        // 扣减冻结金币
        $account = Db::name('coin_account')
            ->where('user_id', $order['user_id'])
            ->lock(true)
            ->find();
        
        if (!$account || $account['frozen'] < $order['coin_amount']) {
            throw new Exception('冻结金币不足');
        }
        
        Db::name('coin_account')
            ->where('user_id', $order['user_id'])
            ->update([
                'frozen' => $account['frozen'] - $order['coin_amount'],
                'total_withdraw' => $account['total_withdraw'] + $order['coin_amount'],
                'updatetime' => time(),
            ]);
        
        // 更新订单 - 使用订单号作为条件
        $tableName = $order['_table'] ?? 'withdraw_order';
        Db::name($tableName)->where('order_no', $order['order_no'])->update($updateData);
        
        // 记录金币流水
        $logTableName = 'coin_log_' . date('Ym');
        Db::name($logTableName)->insert([
            'user_id' => $order['user_id'],
            'type' => 'withdraw_success',
            'amount' => -$order['coin_amount'],
            'balance_before' => $account['balance'],
            'balance_after' => $account['balance'],
            'relation_type' => 'withdraw',
            'relation_id' => $order['id'],
            'title' => '提现成功',
            'description' => "提现成功，订单号: {$order['order_no']}，金额: ¥{$order['cash_amount']}",
            'createtime' => time(),
            'create_date' => date('Y-m-d'),
        ]);
        
        // 更新用户提现统计
        $this->updateUserWithdrawStat($order);
        
        return true;
    }

    /**
     * 更新用户提现统计
     */
    protected function updateUserWithdrawStat($order)
    {
        $stat = Db::name('withdraw_stat')->where('user_id', $order['user_id'])->find();
        
        $today = date('Y-m-d');
        
        if (!$stat) {
            Db::name('withdraw_stat')->insert([
                'user_id' => $order['user_id'],
                'total_withdraw_count' => 1,
                'total_withdraw_amount' => $order['cash_amount'],
                'total_withdraw_coin' => $order['coin_amount'],
                'success_count' => 1,
                'today_withdraw_count' => 1,
                'today_withdraw_amount' => $order['cash_amount'],
                'today_withdraw_date' => $today,
                'first_withdraw_time' => time(),
                'last_withdraw_time' => time(),
                'createtime' => time(),
                'updatetime' => time(),
            ]);
        } else {
            $todayCount = $stat['today_withdraw_date'] == $today ? $stat['today_withdraw_count'] + 1 : 1;
            $todayAmount = $stat['today_withdraw_date'] == $today ? $stat['today_withdraw_amount'] + $order['cash_amount'] : $order['cash_amount'];
            
            Db::name('withdraw_stat')->where('user_id', $order['user_id'])->update([
                'total_withdraw_count' => $stat['total_withdraw_count'] + 1,
                'total_withdraw_amount' => $stat['total_withdraw_amount'] + $order['cash_amount'],
                'total_withdraw_coin' => $stat['total_withdraw_coin'] + $order['coin_amount'],
                'success_count' => $stat['success_count'] + 1,
                'today_withdraw_count' => $todayCount,
                'today_withdraw_amount' => $todayAmount,
                'today_withdraw_date' => $today,
                'last_withdraw_time' => time(),
                'updatetime' => time(),
            ]);
        }
    }

    /**
     * 提现详情
     */
    public function detail($ids = null)
    {
        // 支持通过订单号或ID查询
        $orderNo = $this->request->get('order_no');
        $order = $orderNo ? $this->findOrderByNo($orderNo) : $this->findOrder($ids);
        
        if (!$order) {
            $this->error('订单不存在');
        }

        // 用户信息
        $user = Db::name('user')->where('id', $order['user_id'])->find();

        // 用户提现统计
        $userStats = Db::name('withdraw_stat')
            ->where('user_id', $order['user_id'])
            ->find();

        // 风险信息
        $riskInfo = Db::name('user_risk_score')
            ->where('user_id', $order['user_id'])
            ->find();

        // 最近提现记录
        $recentOrders = [];
        $tables = $this->splitModel->getTableList();
        foreach ($tables as $table) {
            $records = Db::name($table)
                ->where('user_id', $order['user_id'])
                ->where('id', '<>', $ids)
                ->order('createtime', 'desc')
                ->limit(5 - count($recentOrders))
                ->select();
            foreach ($records as $record) {
                $record['status_text'] = $this->statusList[$record['status']] ?? '未知';
                $recentOrders[] = $record;
            }
            if (count($recentOrders) >= 5) break;
        }

        $this->success('', [
            'order' => $order,
            'user' => $user,
            'user_stats' => $userStats,
            'risk_info' => $riskInfo,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * 提现统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 使用分表模型获取统计数据
        $dailyStats = WithdrawOrderSplit::getDailyStats($startDate, $endDate);
        
        // 总体统计
        $totalStats = [
            'total_count' => 0,
            'total_amount' => 0,
            'completed_amount' => 0,
            'rejected_amount' => 0,
        ];
        
        foreach ($dailyStats as $day) {
            $totalStats['total_count'] += $day['count'];
            $totalStats['total_amount'] += $day['cash_amount'];
            $totalStats['completed_amount'] += $day['success_amount'];
        }
        
        // 状态分布
        $statusDistribution = WithdrawOrderSplit::getStatusDistribution();

        if ($this->request->isAjax()) {
            $this->success('', [
                'total_stats' => $totalStats,
                'status_distribution' => $statusDistribution,
                'daily_stats' => $dailyStats,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('status_distribution', $statusDistribution);
        $this->view->assign('daily_stats', $dailyStats);
        return $this->view->fetch();
    }

    /**
     * 待审核列表（快捷入口）
     */
    public function pending()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            // 需要扫描所有分表
            $tables = $this->splitModel->getTableList();
            $list = [];
            $total = 0;
            
            foreach ($tables as $table) {
                $tableTotal = Db::name($table)->where('status', 0)->count();
                $total += $tableTotal;
                
                if ($tableTotal > 0 && count($list) < $limit) {
                    $tableList = Db::name($table)
                        ->alias('wo')
                        ->join('user u', 'u.id = wo.user_id', 'LEFT')
                        ->field('wo.*, u.username, u.nickname, u.mobile')
                        ->where('wo.status', 0)
                        ->order('wo.createtime', 'asc')
                        ->limit($offset, $limit - count($list))
                        ->select();
                    
                    foreach ($tableList as $row) {
                        $row['_table'] = $table;
                        $row['status_text'] = $this->statusList[$row['status']] ?? '未知';
                        $list[] = $row;
                    }
                }
            }

            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch('index');
    }

    /**
     * 导出提现记录
     */
    public function export()
    {
        $status = $this->request->get('status');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');

        $tables = $this->splitModel->getTableList($startDate, $endDate);
        $list = [];

        foreach ($tables as $table) {
            $query = Db::name($table)
                ->alias('wo')
                ->join('user u', 'u.id = wo.user_id', 'LEFT')
                ->field('wo.*, u.username, u.nickname, u.mobile');

            if ($status !== '' && $status !== null) {
                $query->where('wo.status', $status);
            }

            if ($startDate) {
                $query->where('wo.createtime', '>=', strtotime($startDate));
            }

            if ($endDate) {
                $query->where('wo.createtime', '<=', strtotime($endDate . ' 23:59:59'));
            }

            $tableList = $query->order('wo.createtime', 'desc')->select();
            
            foreach ($tableList as $row) {
                $row['status_text'] = $this->statusList[$row['status']] ?? $row['status'];
                $list[] = $row;
            }
        }

        // 导出CSV
        $filename = 'withdraw_orders_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['订单号', '用户ID', '用户名', '手机号', '提现金币', '提现金额', '提现方式', '收款账号', '状态', '申请时间', '处理时间', '审核人', '打款人']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['order_no'],
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['coin_amount'],
                $row['cash_amount'],
                $this->withdrawTypes[$row['withdraw_type']] ?? $row['withdraw_type'],
                $row['withdraw_account'] ?? $row['account_no'] ?? '',
                $row['status_text'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['complete_time'] ? date('Y-m-d H:i:s', $row['complete_time']) : '',
                $row['audit_admin_name'] ?? '',
                $row['transfer_admin_name'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }
}
