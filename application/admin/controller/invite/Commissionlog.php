<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;
use app\common\library\InviteCommissionService;

/**
 * 分佣记录管理
 */
class Commissionlog extends Backend
{
    /**
     * Commissionlog模型对象
     */
    protected $model = null;
    
    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\InviteCommissionLog();
    }
    
    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->with(['user', 'parent'])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            // 添加额外信息
            foreach ($list as $item) {
                $item->user_nickname = $item->user ? $item->user->nickname : '';
                $item->parent_nickname = $item->parent ? $item->parent->nickname : '';
                $item->status_text = $item->status_text;
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user', 'parent'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        if ($this->request->isAjax()) {
            $this->success('', null, $row);
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 手动结算
     */
    public function settle($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('记录不存在');
        }
        
        if ($row->status != 0) {
            $this->error('该记录已处理');
        }
        
        $service = new InviteCommissionService();
        $result = $service->settleCommission($ids);
        
        if ($result['success']) {
            $this->success('结算成功');
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量结算
     */
    public function batchSettle()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error('请选择要结算的记录');
        }
        
        $ids = explode(',', $ids);
        
        $service = new InviteCommissionService();
        $result = $service->batchSettleCommission($ids);
        
        $this->success("结算完成: 成功{$result['success']}条, 失败{$result['failed']}条");
    }
    
    /**
     * 取消分佣
     */
    public function cancel($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('记录不存在');
        }
        
        if ($row->status == 1) {
            $this->error('已结算的记录不能取消');
        }
        
        $reason = $this->request->post('reason', '管理员取消');
        
        if ($row->cancel($reason)) {
            $this->success('取消成功');
        } else {
            $this->error('取消失败');
        }
    }
    
    /**
     * 冻结分佣
     */
    public function freeze($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('记录不存在');
        }
        
        $reason = $this->request->post('reason', '管理员冻结');
        
        if ($row->freeze($reason)) {
            $this->success('冻结成功');
        } else {
            $this->error('冻结失败');
        }
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
            'total_amount' => $this->model->sum('commission_amount'),
            'total_coin' => $this->model->sum('coin_amount'),
            'pending_count' => $this->model->where('status', 0)->count(),
            'pending_amount' => $this->model->where('status', 0)->sum('commission_amount'),
            'settled_count' => $this->model->where('status', 1)->count(),
            'settled_amount' => $this->model->where('status', 1)->sum('commission_amount'),
        ];
        
        // 来源统计
        $sourceStat = $this->model->field([
            'source_type',
            'COUNT(*) as count',
            'SUM(commission_amount) as amount',
        ])->group('source_type')->select();
        
        // 层级统计
        $levelStat = $this->model->field([
            'level',
            'COUNT(*) as count',
            'SUM(commission_amount) as amount',
        ])->group('level')->select();
        
        // 每日趋势
        $dailyStat = $this->model->where('status', 1)
            ->where('settle_time', '>=', strtotime($startDate . ' 00:00:00'))
            ->where('settle_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->field([
                'DATE(FROM_UNIXTIME(settle_time)) as date',
                'COUNT(*) as count',
                'SUM(commission_amount) as amount',
            ])
            ->group('date')
            ->order('date', 'asc')
            ->select();
        
        $this->success('', null, [
            'total' => $totalStat,
            'source' => $sourceStat,
            'level' => $levelStat,
            'daily' => $dailyStat,
        ]);
    }
}
