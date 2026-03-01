<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\WithdrawService;
use think\Db;
use think\Exception;

/**
 * 提现审核管理
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
     * 提现订单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('wo')
                ->join('user u', 'u.id = wo.user_id', 'LEFT')
                ->field('wo.*, u.username, u.nickname, u.mobile, u.avatar')
                ->where($where)
                ->order("wo.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
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
            ->field('COUNT(*) as total_count, SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount')
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

        if ($order['status'] != 'pending') {
            $this->error('该订单已处理');
        }

        $remark = $this->request->post('remark', '');

        Db::startTrans();
        try {
            $order->status = 'approved';
            $order->admin_id = $this->auth->id;
            $order->admin_name = $this->auth->username;
            $order->approve_time = time();
            $order->admin_remark = $remark;
            $order->updatetime = time();
            $order->save();

            // 可以在这里触发自动打款
            // 或者手动打款后调用 complete 方法

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

        if ($order['status'] != 'pending') {
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

        if (!in_array($order['status'], ['pending', 'approved'])) {
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
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'admin_id' => $this->auth->id,
                'admin_name' => $this->auth->username,
                'approve_time' => time(),
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
            ->where('status', 'approved')
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

        // 总体统计
        $totalStats = $this->model->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_count, SUM(amount) as total_amount,
                     SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed_amount,
                     SUM(CASE WHEN status = "rejected" THEN amount ELSE 0 END) as rejected_amount')
            ->find();

        // 状态分布
        $statusDistribution = $this->model->field('status, COUNT(*) as count, SUM(amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('status')
            ->select();

        // 每日趋势
        $dailyStats = $this->model
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as count, SUM(amount) as amount,
                     SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 提现金额分布
        $amountDistribution = [
            ['range' => '0-10', 'count' => $this->model->where('amount', 'between', [0, 10])->where('createtime', 'between', [$startTimestamp, $endTimestamp])->count()],
            ['range' => '10-50', 'count' => $this->model->where('amount', 'between', [10, 50])->where('createtime', 'between', [$startTimestamp, $endTimestamp])->count()],
            ['range' => '50-100', 'count' => $this->model->where('amount', 'between', [50, 100])->where('createtime', 'between', [$startTimestamp, $endTimestamp])->count()],
            ['range' => '100-500', 'count' => $this->model->where('amount', 'between', [100, 500])->where('createtime', 'between', [$startTimestamp, $endTimestamp])->count()],
            ['range' => '500+', 'count' => $this->model->where('amount', '>', 500)->where('createtime', 'between', [$startTimestamp, $endTimestamp])->count()],
        ];

        // 用户提现排行
        $topUsers = $this->model->alias('wo')
            ->join('user u', 'u.id = wo.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, COUNT(*) as withdraw_count, SUM(wo.amount) as total_amount')
            ->where('wo.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->where('wo.status', 'completed')
            ->group('wo.user_id')
            ->order('total_amount', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'status_distribution' => $statusDistribution,
            'daily_stats' => $dailyStats,
            'amount_distribution' => $amountDistribution,
            'top_users' => $topUsers,
        ]);
    }

    /**
     * 待审核列表（快捷入口）
     */
    public function pending()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            $total = $this->model->where('status', 'pending')->count();
            $list = $this->model->alias('wo')
                ->join('user u', 'u.id = wo.user_id', 'LEFT')
                ->field('wo.*, u.username, u.nickname, u.mobile')
                ->where('wo.status', 'pending')
                ->order('wo.createtime', 'asc')
                ->limit($offset, $limit)
                ->select();

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

        $query = $this->model->alias('wo')
            ->join('user u', 'u.id = wo.user_id', 'LEFT')
            ->field('wo.*, u.username, u.nickname, u.mobile');

        if ($status) {
            $query->where('wo.status', $status);
        }

        if ($startDate) {
            $query->where('wo.createtime', '>=', strtotime($startDate));
        }

        if ($endDate) {
            $query->where('wo.createtime', '<=', strtotime($endDate . ' 23:59:59'));
        }

        $list = $query->order('wo.createtime', 'desc')->select();

        // 导出CSV
        $filename = 'withdraw_orders_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['订单号', '用户ID', '用户名', '手机号', '提现金额', '提现方式', '收款账号', '状态', '申请时间', '处理时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['order_no'],
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['amount'],
                $row['withdraw_type'],
                $row['account_no'],
                $row['status'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['complete_time'] ? date('Y-m-d H:i:s', $row['complete_time']) : '',
            ]);
        }

        fclose($output);
        exit;
    }
}
