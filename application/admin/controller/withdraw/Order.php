<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\WithdrawService;
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
     * 提现详情
     */
    public function detail($ids = null)
    {
        $order = $this->model->get($ids);
        if (!$order) {
            $this->error('订单不存在');
        }

        // 用户信息
        $user = Db::name('user')->where('id', $order['user_id'])->find();

        // 用户提现统计
        $userStats = Db::name('withdraw_order')
            ->where('user_id', $order['user_id'])
            ->field('COUNT(*) as total_count, SUM(CASE WHEN status = 3 THEN cash_amount ELSE 0 END) as total_amount')
            ->find();

        // 风险信息
        $riskInfo = Db::name('user_risk_score')
            ->where('user_id', $order['user_id'])
            ->find();

        // 最近提现记录
        $recentOrders = $this->model->where('user_id', $order['user_id'])
            ->where('id', '<>', $ids)
            ->order('createtime', 'desc')
            ->limit(5)
            ->select();

        $this->success('', [
            'order' => $order,
            'user' => $user,
            'user_stats' => $userStats,
            'risk_info' => $riskInfo,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * 审核通过
     */
    public function approve($ids = null)
    {
        $order = $this->model->get($ids);
        if (!$order) {
            $this->error('订单不存在');
        }

        if ($order['status'] != 0) {
            $this->error('该订单已处理');
        }

        $remark = $this->request->post('remark', '');

        Db::startTrans();
        try {
            $order->status = 1;
            $order->audit_admin_id = $this->auth->id;
            $order->audit_admin_name = $this->auth->username;
            $order->audit_time = time();
            $order->audit_remark = $remark;
            $order->updatetime = time();
            $order->save();

            Db::commit();
            $this->success('审核通过');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 审核拒绝
     */
    public function reject($ids = null)
    {
        $order = $this->model->get($ids);
        if (!$order) {
            $this->error('订单不存在');
        }

        if ($order['status'] != 0) {
            $this->error('该订单已处理');
        }

        $reason = $this->request->post('reason', '');

        if (!$reason) {
            $this->error('请填写拒绝原因');
        }

        Db::startTrans();
        try {
            $withdrawService = new WithdrawService();
            $result = $withdrawService->reject($ids, $reason, $this->auth->id);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            Db::commit();
            $this->success('已拒绝并退还金币');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 确认打款
     */
    public function complete($ids = null)
    {
        $order = $this->model->get($ids);
        if (!$order) {
            $this->error('订单不存在');
        }

        if (!in_array($order['status'], [0, 1, 2])) {
            $this->error('该订单状态不允许打款');
        }

        $transferNo = $this->request->post('transfer_no', '');
        $remark = $this->request->post('remark', '');

        Db::startTrans();
        try {
            $withdrawService = new WithdrawService();
            $result = $withdrawService->complete($ids, $transferNo, $this->auth->id, $remark);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            Db::commit();
            $this->success('打款成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
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

        $count = $this->model->where('id', 'in', $ids)
            ->where('status', 0)
            ->update([
                'status' => 1,
                'audit_admin_id' => $this->auth->id,
                'audit_admin_name' => $this->auth->username,
                'audit_time' => time(),
                'updatetime' => time()
            ]);

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

        $orders = $this->model->where('id', 'in', $ids)
            ->where('status', 1)
            ->select();

        $success = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                $withdrawService = new WithdrawService();
                $result = $withdrawService->complete($order['id'], '', $this->auth->id);
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
            }
        }

        $this->success("成功打款{$success}笔，失败{$failed}笔");
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
                $list[] = $row;
            }
        }

        // 导出CSV
        $filename = 'withdraw_orders_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['订单号', '用户ID', '用户名', '手机号', '提现金币', '提现金额', '提现方式', '收款账号', '状态', '申请时间', '处理时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['order_no'],
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['coin_amount'],
                $row['cash_amount'],
                $row['withdraw_type'],
                $row['withdraw_account'] ?? $row['account_no'] ?? '',
                \app\common\model\WithdrawOrder::$statusList[$row['status']] ?? $row['status'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['complete_time'] ? date('Y-m-d H:i:s', $row['complete_time']) : '',
            ]);
        }

        fclose($output);
        exit;
    }
}
