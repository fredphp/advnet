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
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 邀请排行
     */
    public function ranking()
    {
        $type = $this->request->get('type', 'total');
        $limit = $this->request->get('limit', 50);
        
        $list = \app\common\model\UserInviteStat::getRanking($type, $limit);
        
        $this->success('', null, ['list' => $list]);
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
    
    /**
     * 用户详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 获取下级列表
        $level1Ids = Db::name('invite_relation')
            ->where('parent_id', $row->user_id)
            ->column('user_id');
        
        $level2Ids = Db::name('invite_relation')
            ->where('grandparent_id', $row->user_id)
            ->column('user_id');
        
        // 获取用户贡献排行
        $topContributors = Db::name('invite_commission_log')
            ->where('parent_id', $row->user_id)
            ->field('user_id, SUM(commission_amount) as total')
            ->group('user_id')
            ->order('total', 'desc')
            ->limit(10)
            ->select();
        
        $this->view->assign('row', $row);
        $this->view->assign('level1_count', count($level1Ids));
        $this->view->assign('level2_count', count($level2Ids));
        $this->view->assign('top_contributors', $topContributors);
        
        return $this->view->fetch();
    }
}
