<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;

/**
 * 邀请统计管理
 */
class Invitestat extends Backend
{
    /**
     * InviteStat模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\UserInviteStat();
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
                $item->username = $item->user ? $item->user->username : '';
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 查看用户的一级邀请列表
     */
    public function invitees()
    {
        $userId = $this->request->get('user_id', 0);
        
        if (!$userId) {
            $this->error('参数错误');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $userId)->find();
        
        // 非AJAX请求，返回视图
        if (!$this->request->isAjax()) {
            $this->view->assign('user', $user);
            $this->view->assign('user_id', $userId);
            return $this->view->fetch();
        }
        
        // AJAX请求，返回数据
        $parentId = $this->request->get('parent_id', $userId);
        $sort = $this->request->get('sort', 'createtime');
        $order = $this->request->get('order', 'desc');
        $offset = $this->request->get('offset', 0);
        $limit = $this->request->get('limit', 15);
        
        // 获取一级被邀请人（只显示直接下级）
        $list = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'u.id = ir.user_id', 'LEFT')
            ->where('ir.parent_id', $parentId)
            ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level, 1 as level_num')
            ->order('ir.' . $sort, $order)
            ->select();
        
        $total = count($list);
        
        // 获取所有被邀请人ID
        $inviteeIds = array_column($list, 'user_id');
        
        // 获取每个被邀请人的下级数量
        $subCounts = [];
        if (!empty($inviteeIds)) {
            $subCountsQuery = Db::name('invite_relation')
                ->whereIn('parent_id', $inviteeIds)
                ->group('parent_id')
                ->field('parent_id, COUNT(*) as count')
                ->select();
            foreach ($subCountsQuery as $item) {
                $subCounts[$item['parent_id']] = $item['count'];
            }
        }
        
        // 获取金币账户
        $accounts = [];
        if (!empty($inviteeIds)) {
            $accountQuery = Db::name('coin_account')
                ->whereIn('user_id', $inviteeIds)
                ->select();
            foreach ($accountQuery as $item) {
                $accounts[$item['user_id']] = $item;
            }
        }
        
        // 为每个被邀请人添加统计信息
        foreach ($list as &$item) {
            $item['sub_count'] = $subCounts[$item['user_id']] ?? 0;
            
            // 消费总额
            $spendTotal = Db::name('coin_log')
                ->where('user_id', $item['user_id'])
                ->where('type', 'spend')
                ->sum('amount');
            $item['spend_total'] = abs($spendTotal ?? 0);
            
            // 提现总额
            $withdrawTotal = Db::name('coin_log')
                ->where('user_id', $item['user_id'])
                ->where('type', 'withdraw')
                ->sum('amount');
            $item['withdraw_total'] = abs($withdrawTotal ?? 0);
            
            // 产生佣金
            $commissionTotal = Db::name('invite_commission_log')
                ->where('user_id', $item['user_id'])
                ->where('status', 1)
                ->sum('commission_amount');
            $item['commission_total'] = $commissionTotal ?? 0;
            
            // 账户余额
            $account = $accounts[$item['user_id']] ?? null;
            $item['balance'] = $account ? $account['balance'] : 0;
        }
        
        // 分页
        $list = array_slice($list, $offset, $limit);
        
        // 统计数据
        $level1Count = Db::name('invite_relation')->where('parent_id', $userId)->count();
        $level2Count = Db::name('invite_relation')->where('grandparent_id', $userId)->where('grandparent_id', '>', 0)->count();
        
        return json([
            'total' => $total, 
            'rows' => $list,
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
        ]);
    }
    
    /**
     * 平台统计
     */
    public function platform()
    {
        // 总邀请人数
        $totalInvite = $this->model->sum('total_invite_count');
        
        // 一级邀请人数
        $level1Count = $this->model->sum('level1_count');
        
        // 二级邀请人数
        $level2Count = $this->model->sum('level2_count');
        
        // 有效邀请人数
        $validCount = $this->model->sum('valid_invite_count');
        
        // 今日新增
        $todayNew = $this->model->sum('new_invite_today');
        
        // 昨日新增
        $yesterdayNew = $this->model->sum('new_invite_yesterday');
        
        // 本周新增
        $weekNew = $this->model->sum('new_invite_week');
        
        // 本月新增
        $monthNew = $this->model->sum('new_invite_month');
        
        // 有邀请人的用户数
        $invitedUserCount = Db::name('invite_relation')->where('parent_id', '>', 0)->count();
        
        // 绑定率
        $totalUser = Db::name('user')->count();
        $bindRate = $totalUser > 0 ? round($invitedUserCount / $totalUser * 100, 2) : 0;
        
        $this->success('', null, [
            'total_invite' => $totalInvite,
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
            'valid_count' => $validCount,
            'today_new' => $todayNew,
            'yesterday_new' => $yesterdayNew,
            'week_new' => $weekNew,
            'month_new' => $monthNew,
            'invited_user_count' => $invitedUserCount,
            'bind_rate' => $bindRate,
        ]);
    }
}
