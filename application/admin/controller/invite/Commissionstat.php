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
        parent::_initialize();
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
            
            // 获取所有用户ID
            $userIds = [];
            foreach ($list as $item) {
                $userIds[] = $item->user_id;
            }
            
            // 批量获取邀请统计数据
            $inviteStats = [];
            if (!empty($userIds)) {
                $inviteStatsData = Db::name('user_invite_stat')
                    ->where('user_id', 'in', $userIds)
                    ->select();
                foreach ($inviteStatsData as $stat) {
                    $inviteStats[$stat['user_id']] = $stat;
                }
            }
            
            // 批量获取已提现佣金（从提现订单表统计）
            $withdrawnStats = [];
            if (!empty($userIds)) {
                $withdrawnData = Db::name('withdraw_order')
                    ->where('user_id', 'in', $userIds)
                    ->where('status', 'in', [3]) // 3=提现成功
                    ->field('user_id, SUM(actual_amount) as total_withdrawn')
                    ->group('user_id')
                    ->select();
                foreach ($withdrawnData as $wd) {
                    $withdrawnStats[$wd['user_id']] = $wd['total_withdrawn'];
                }
            }
            
            foreach ($list as $item) {
                $item->user_nickname = $item->user ? $item->user->nickname : '';
                $item->user_avatar = $item->user ? $item->user->avatar : '';
                $item->user_mobile = $item->user ? $item->user->mobile : '';
                
                // 邀请统计数据
                $inviteStat = isset($inviteStats[$item->user_id]) ? $inviteStats[$item->user_id] : null;
                $item->total_invite_count = $inviteStat ? $inviteStat['total_invite_count'] : 0;
                $item->level1_count = $inviteStat ? $inviteStat['level1_count'] : 0;
                $item->level2_count = $inviteStat ? $inviteStat['level2_count'] : 0;
                $item->valid_invite_count = $inviteStat ? $inviteStat['valid_invite_count'] : 0;
                
                // 已提现佣金
                $item->withdrawn_commission = isset($withdrawnStats[$item->user_id]) ? $withdrawnStats[$item->user_id] : 0;
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
        
        // 总邀请人数统计
        $totalInviteCount = Db::name('user_invite_stat')->sum('total_invite_count');
        $level1TotalCount = Db::name('user_invite_stat')->sum('level1_count');
        $level2TotalCount = Db::name('user_invite_stat')->sum('level2_count');
        
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
            'total_invite_count' => $totalInviteCount,
            'level1_total_count' => $level1TotalCount,
            'level2_total_count' => $level2TotalCount,
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
        
        $userId = $row->user_id;
        
        // 获取邀请统计
        $inviteStat = Db::name('user_invite_stat')
            ->where('user_id', $userId)
            ->find();
        
        // 获取一级邀请列表
        $level1Users = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'ir.user_id = u.id', 'LEFT')
            ->where('ir.parent_id', $userId)
            ->field('ir.user_id, ir.createtime as invite_time, u.nickname, u.avatar, u.mobile')
            ->order('ir.createtime', 'desc')
            ->limit(50)
            ->select();
        
        // 获取二级邀请列表
        $level2Users = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'ir.user_id = u.id', 'LEFT')
            ->where('ir.grandparent_id', $userId)
            ->field('ir.user_id, ir.createtime as invite_time, ir.parent_id, u.nickname, u.avatar, u.mobile')
            ->order('ir.createtime', 'desc')
            ->limit(50)
            ->select();
        
        // 为二级邀请获取直接上级信息
        foreach ($level2Users as &$user) {
            $parent = Db::name('user')->where('id', $user['parent_id'])->field('nickname')->find();
            $user['parent_nickname'] = $parent ? $parent['nickname'] : '';
        }
        
        // 获取近期分佣记录
        $recentLogs = Db::name('invite_commission_log')
            ->alias('cl')
            ->join('user u', 'cl.user_id = u.id', 'LEFT')
            ->where('cl.parent_id', $userId)
            ->field('cl.*, u.nickname as from_user_nickname')
            ->order('cl.id', 'desc')
            ->limit(30)
            ->select();
        
        // 统计数据
        $level1Count = count($level1Users);
        $level2Count = count($level2Users);
        $totalInviteCount = $level1Count + $level2Count;
        
        // 已提现佣金
        $withdrawnCommission = Db::name('withdraw_order')
            ->where('user_id', $userId)
            ->where('status', 3)
            ->sum('actual_amount');
        
        $this->view->assign('row', $row);
        $this->view->assign('invite_stat', $inviteStat);
        $this->view->assign('level1_users', $level1Users);
        $this->view->assign('level2_users', $level2Users);
        $this->view->assign('recent_logs', $recentLogs);
        $this->view->assign('level1_count', $level1Count);
        $this->view->assign('level2_count', $level2Count);
        $this->view->assign('total_invite_count', $totalInviteCount);
        $this->view->assign('withdrawn_commission', $withdrawnCommission);
        
        return $this->view->fetch();
    }
    
    /**
     * 获取一级邀请列表(AJAX)
     */
    public function getLevel1List()
    {
        $userId = $this->request->get('user_id');
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        
        if (!$userId) {
            $this->error('参数错误');
        }
        
        $offset = ($page - 1) * $limit;
        
        $total = Db::name('invite_relation')
            ->where('parent_id', $userId)
            ->count();
        
        $list = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'ir.user_id = u.id', 'LEFT')
            ->where('ir.parent_id', $userId)
            ->field('ir.user_id, ir.createtime as invite_time, u.nickname, u.avatar, u.mobile')
            ->order('ir.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        
        // 获取每个用户的贡献佣金
        foreach ($list as &$user) {
            $user['contribute_commission'] = Db::name('invite_commission_log')
                ->where('user_id', $user['user_id'])
                ->where('parent_id', $userId)
                ->where('level', 1)
                ->sum('commission_amount');
        }
        
        $this->success('', null, ['total' => $total, 'rows' => $list]);
    }
    
    /**
     * 获取二级邀请列表(AJAX)
     */
    public function getLevel2List()
    {
        $userId = $this->request->get('user_id');
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 20);
        
        if (!$userId) {
            $this->error('参数错误');
        }
        
        $offset = ($page - 1) * $limit;
        
        $total = Db::name('invite_relation')
            ->where('grandparent_id', $userId)
            ->count();
        
        $list = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'ir.user_id = u.id', 'LEFT')
            ->join('user pu', 'ir.parent_id = pu.id', 'LEFT')
            ->where('ir.grandparent_id', $userId)
            ->field('ir.user_id, ir.createtime as invite_time, ir.parent_id, u.nickname, u.avatar, u.mobile, pu.nickname as parent_nickname')
            ->order('ir.createtime', 'desc')
            ->limit($offset, $limit)
            ->select();
        
        // 获取每个用户的贡献佣金
        foreach ($list as &$user) {
            $user['contribute_commission'] = Db::name('invite_commission_log')
                ->where('user_id', $user['user_id'])
                ->where('parent_id', $userId)
                ->where('level', 2)
                ->sum('commission_amount');
        }
        
        $this->success('', null, ['total' => $total, 'rows' => $list]);
    }
}
