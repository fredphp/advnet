<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;
use app\common\model\InviteCommissionLog;

/**
 * 分佣记录管理
 */
class Commissionlog extends Backend
{
    /**
     * Commissionlog模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
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
                // 来源用户（产生分佣的用户，如提现的用户）
                $item->user_nickname = $item->user ? $item->user->nickname : '';
                $item->user_id_val = $item->user_id;
                // 获益用户（获得佣金的上级）
                $item->parent_nickname = $item->parent ? $item->parent->nickname : '';
                $item->parent_id_val = $item->parent_id;
                // 来源类型中文
                $item->source_type_text = $this->getSourceTypeText($item->source_type);
                $item->status_text = $item->status_text;
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 获取来源类型文本
     */
    protected function getSourceTypeText($type)
    {
        $types = [
            'ad_feed'   => '信息流广告',
            'ad_reward' => '激励视频广告',
        ];
        return $types[$type] ?? $type;
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
     * 取消分佣
     */
    public function cancel($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('记录不存在');
        }

        if ($row->status == InviteCommissionLog::STATUS_SETTLED) {
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
