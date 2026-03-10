<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;

/**
 * 佣金统计管理
 */
class Commissionstat extends Backend
{
    /**
     * CommissionStat模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\UserCommissionStat();
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
                ->with(['user'])
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            foreach ($list as $item) {
                $item->user_nickname = $item->user ? $item->user->nickname : '';
                $item->user_avatar = $item->user ? $item->user->avatar : '';
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 佣金排行
     */
    public function ranking()
    {
        $type = $this->request->get('type', 'total');
        $limit = $this->request->get('limit', 50);
        
        $list = \app\common\model\UserCommissionStat::getRanking($type, $limit);
        
        $this->success('', null, ['list' => $list]);
    }
    
    /**
     * 平台统计
     */
    public function platform()
    {
        // 总佣金
        $totalCommission = $this->model->sum('total_commission');
        
        // 总金币
        $totalCoin = $this->model->sum('total_coin');
        
        // 一级佣金
        $level1Commission = $this->model->sum('level1_commission');
        
        // 二级佣金
        $level2Commission = $this->model->sum('level2_commission');
        
        // 各来源佣金
        $withdrawCommission = $this->model->sum('withdraw_commission');
        $videoCommission = $this->model->sum('video_commission');
        $redPacketCommission = $this->model->sum('red_packet_commission');
        $gameCommission = $this->model->sum('game_commission');
        
        // 今日佣金
        $todayCommission = $this->model->sum('today_commission');
        
        // 昨日佣金
        $yesterdayCommission = $this->model->sum('yesterday_commission');
        
        // 本周佣金
        $weekCommission = $this->model->sum('week_commission');
        
        // 本月佣金
        $monthCommission = $this->model->sum('month_commission');
        
        // 待结算佣金
        $pendingCommission = $this->model->sum('pending_commission');
        
        // 冻结佣金
        $frozenCommission = $this->model->sum('frozen_commission');
        
        // 获得佣金用户数
        $userCount = $this->model->where('total_commission', '>', 0)->count();
        
        $this->success('', null, [
            'total_commission' => $totalCommission,
            'total_coin' => $totalCoin,
            'level1_commission' => $level1Commission,
            'level2_commission' => $level2Commission,
            'withdraw_commission' => $withdrawCommission,
            'video_commission' => $videoCommission,
            'red_packet_commission' => $redPacketCommission,
            'game_commission' => $gameCommission,
            'today_commission' => $todayCommission,
            'yesterday_commission' => $yesterdayCommission,
            'week_commission' => $weekCommission,
            'month_commission' => $monthCommission,
            'pending_commission' => $pendingCommission,
            'frozen_commission' => $frozenCommission,
            'user_count' => $userCount,
        ]);
    }
    
    /**
     * 趋势图表
     */
    public function trend()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $list = Db::name('daily_commission_stat')
            ->where('date_key', '>=', $startDate)
            ->where('date_key', '<=', $endDate)
            ->order('date_key', 'asc')
            ->select();
        
        $dates = [];
        $commissions = [];
        $counts = [];
        
        foreach ($list as $item) {
            $dates[] = $item['date_key'];
            $commissions[] = $item['total_commission'];
            $counts[] = $item['total_count'];
        }
        
        $this->success('', null, [
            'dates' => $dates,
            'commissions' => $commissions,
            'counts' => $counts,
        ]);
    }
    
    /**
     * 来源分布
     */
    public function source()
    {
        $startDate = $this->request->get('start_date', date('Y-m-01'));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $list = Db::name('daily_commission_stat')
            ->where('date_key', '>=', $startDate)
            ->where('date_key', '<=', $endDate)
            ->field([
                'SUM(withdraw_commission) as withdraw',
                'SUM(video_commission) as video',
                'SUM(red_packet_commission) as red_packet',
                'SUM(game_commission) as game',
            ])
            ->find();
        
        $this->success('', null, $list);
    }
    
    /**
     * 用户详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 获取近期分佣记录
        $recentLogs = Db::name('invite_commission_log')
            ->where('parent_id', $row->user_id)
            ->order('id', 'desc')
            ->limit(20)
            ->select();
        
        $this->view->assign('row', $row);
        $this->view->assign('recent_logs', $recentLogs);
        
        return $this->view->fetch();
    }
}
