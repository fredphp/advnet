<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\WithdrawService;
use think\facade\Db;

/**
 * 提现订单管理
 */
class Order extends Backend
{
    protected $model = null;
    
    public function initialize()
    {
        parent::initialize();
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
            $list = $this->model->where($where)
                ->with(['user'])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            foreach ($list as $item) {
                $item->user_nickname = $item->user ? $item->user->nickname : '';
                $item->status_text = $item->status_text;
                $item->withdraw_type_text = $item->withdraw_type_text;
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 订单详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 获取用户提现统计
        $stat = Db::name('withdraw_stat')->where('user_id', $row->user_id)->find();
        
        // 获取风控记录
        $riskLogs = Db::name('withdraw_risk_log')
            ->where('order_no', $row->order_no)
            ->order('id', 'desc')
            ->select();
        
        // 获取打款日志
        $transferLogs = Db::name('wechat_transfer_log')
            ->where('order_no', $row->order_no)
            ->order('id', 'desc')
            ->select();
        
        $this->view->assign('row', $row);
        $this->view->assign('stat', $stat);
        $this->view->assign('risk_logs', $riskLogs);
        $this->view->assign('transfer_logs', $transferLogs);
        
        return $this->view->fetch();
    }
    
    /**
     * 审核通过
     */
    public function approve($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        if ($row->status != 0) {
            $this->error('订单状态异常');
        }
        
        $remark = $this->request->post('remark', '');
        
        $service = new WithdrawService();
        $result = $service->approveOrder($ids, $this->auth->id, $this->auth->nickname, $remark);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 审核拒绝
     */
    public function reject($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        if (!in_array($row->status, [0, 1])) {
            $this->error('订单状态异常');
        }
        
        $reason = $this->request->post('reason', '');
        if (empty($reason)) {
            $this->error('请填写拒绝原因');
        }
        
        $service = new WithdrawService();
        $result = $service->rejectOrder($ids, $reason, $this->auth->id, $this->auth->nickname);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 发起打款
     */
    public function transfer($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        if ($row->status != 1) {
            $this->error('订单状态异常，请先审核通过');
        }
        
        $service = new WithdrawService();
        $result = $service->transfer($ids);
        
        if ($result['success']) {
            $this->success($result['message'], null, $result['data']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 重试打款
     */
    public function retry($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('订单不存在');
        }
        
        if ($row->status != 5) {
            $this->error('订单状态异常');
        }
        
        $service = new WithdrawService();
        $result = $service->retryTransfer($ids);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量审核通过
     */
    public function batchApprove()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error('请选择要审核的订单');
        }
        
        $ids = explode(',', $ids);
        $remark = $this->request->post('remark', '');
        
        $service = new WithdrawService();
        $success = 0;
        $failed = 0;
        
        foreach ($ids as $id) {
            $result = $service->approveOrder($id, $this->auth->id, $this->auth->nickname, $remark);
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        $this->success("审核完成: 成功{$success}条, 失败{$failed}条");
    }
    
    /**
     * 统计数据
     */
    public function stat()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        // 总体统计
        $totalStat = [
            'total_count' => $this->model->count(),
            'total_amount' => $this->model->sum('cash_amount'),
            'total_coin' => $this->model->sum('coin_amount'),
            'total_fee' => $this->model->sum('fee_amount'),
            'pending_count' => $this->model->where('status', 0)->count(),
            'pending_amount' => $this->model->where('status', 0)->sum('cash_amount'),
            'success_count' => $this->model->where('status', 3)->count(),
            'success_amount' => $this->model->where('status', 3)->sum('cash_amount'),
            'failed_count' => $this->model->where('status', 4)->count() + $this->model->where('status', 5)->count(),
        ];
        
        // 每日趋势
        $dailyStat = $this->model->where('status', 3)
            ->where('complete_time', '>=', strtotime($startDate . ' 00:00:00'))
            ->where('complete_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->field([
                'DATE(FROM_UNIXTIME(complete_time)) as date',
                'COUNT(*) as count',
                'SUM(cash_amount) as amount',
            ])
            ->group('date')
            ->order('date', 'asc')
            ->select();
        
        // 状态分布
        $statusStat = $this->model->field([
            'status',
            'COUNT(*) as count',
            'SUM(cash_amount) as amount',
        ])->group('status')->select();
        
        // 提现方式分布
        $typeStat = $this->model->field([
            'withdraw_type',
            'COUNT(*) as count',
            'SUM(cash_amount) as amount',
        ])->group('withdraw_type')->select();
        
        $this->success('', null, [
            'total' => $totalStat,
            'daily' => $dailyStat,
            'status' => $statusStat,
            'type' => $typeStat,
        ]);
    }
    
    /**
     * 导出
     */
    public function export()
    {
        $ids = $this->request->get('ids');
        $status = $this->request->get('status');
        
        $query = $this->model->with(['user']);
        
        if ($ids) {
            $query->whereIn('id', explode(',', $ids));
        }
        
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        
        $list = $query->order('id', 'desc')->limit(10000)->select();
        
        // TODO: 实现导出逻辑
        
        $this->success('导出成功');
    }
}
