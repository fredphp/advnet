<?php

namespace app\admin\controller\videoreward;

use app\common\controller\Backend;
use think\Db;

/**
 * 收益统计
 */
class RewardStat extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 统计概览
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            // 每日统计
            $dailyStats = Db::name('user_daily_reward_stat')
                ->where('date_key', '>=', $startDate)
                ->where('date_key', '<=', $endDate)
                ->field('date_key, SUM(video_reward_count) as total_count, SUM(video_reward_coin) as total_coin')
                ->group('date_key')
                ->order('date_key', 'asc')
                ->select();
            
            // 总体统计
            $totalStats = Db::name('user_daily_reward_stat')
                ->where('date_key', '>=', $startDate)
                ->where('date_key', '<=', $endDate)
                ->field('SUM(video_reward_count) as total_count, SUM(video_reward_coin) as total_coin')
                ->find();
            
            // 视频排行
            $videoRank = Db::name('video')
                ->where('reward_count', '>', 0)
                ->field('id, title, reward_count, reward_coin_total')
                ->order('reward_coin_total', 'desc')
                ->limit(10)
                ->select();
            
            // 用户排行
            $userRank = Db::name('coin_account')
                ->alias('ca')
                ->join('user u', 'u.id = ca.user_id')
                ->field('ca.user_id, u.nickname, ca.total_earn, ca.balance')
                ->order('ca.total_earn', 'desc')
                ->limit(10)
                ->select();
            
            $this->success('', null, [
                'daily' => $dailyStats,
                'total' => $totalStats,
                'video_rank' => $videoRank,
                'user_rank' => $userRank,
            ]);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 用户收益明细
     */
    public function userstat()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = Db::name('user_daily_reward_stat')
                ->alias('s')
                ->join('user u', 'u.id = s.user_id')
                ->field('s.*, u.nickname, u.mobile')
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }
        
        return $this->view->fetch();
    }
}
