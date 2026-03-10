<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use app\common\model\CoinLog;
use think\Db;

/**
 * 邀请关系管理
 */
class Relation extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InviteRelation();
    }

    /**
     * 邀请关系列表（按邀请人分组）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取参数
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 15);
            $filter = $this->request->get('filter', '{}');
            $filter = json_decode($filter, true);
            
            // 构建查询 - 按邀请人分组统计
            $query = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.parent_id', 'LEFT')
                ->where('ir.parent_id', '>', 0)
                ->group('ir.parent_id');
            
            // 应用筛选条件
            if (!empty($filter)) {
                if (isset($filter['parent_id']) && $filter['parent_id'] !== '') {
                    $query->where('ir.parent_id', $filter['parent_id']);
                }
                if (isset($filter['inviter_name']) && $filter['inviter_name'] !== '') {
                    $query->where('u.username|u.nickname', 'like', '%' . $filter['inviter_name'] . '%');
                }
                if (isset($filter['invite_channel']) && $filter['invite_channel'] !== '') {
                    $query->where('ir.invite_channel', $filter['invite_channel']);
                }
            }
            
            // 获取总数
            $totalQuery = Db::name('invite_relation')
                ->where('parent_id', '>', 0)
                ->group('parent_id');
            $total = count($totalQuery->select());
            
            // 获取列表数据
            $list = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.parent_id', 'LEFT')
                ->join('user_invite_stat uis', 'uis.user_id = ir.parent_id', 'LEFT')
                ->where('ir.parent_id', '>', 0)
                ->field('ir.parent_id, u.username as inviter_name, u.nickname as inviter_nickname, u.mobile as inviter_mobile, u.avatar as inviter_avatar,
                         u.level as inviter_level,
                         COUNT(ir.id) as level1_count,
                         SUM(CASE WHEN ir.grandparent_id > 0 THEN 1 ELSE 0 END) as has_grandparent_count,
                         COALESCE(uis.level2_count, 0) as level2_count,
                         COALESCE(uis.total_invite_count, 0) as total_invite_count,
                         COALESCE(uis.valid_invite_count, 0) as valid_invite_count')
                ->group('ir.parent_id')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            // 获取每个邀请人的佣金统计
            foreach ($list as &$item) {
                // 获取该邀请人的被邀请人ID列表
                $inviteeIds = Db::name('invite_relation')
                    ->where('parent_id', $item['parent_id'])
                    ->column('user_id');
                
                // 获取被邀请人的提现总额
                $withdrawTotal = Db::name('coin_log')
                    ->whereIn('user_id', $inviteeIds)
                    ->where('type', 'withdraw')
                    ->where('status', 1)
                    ->sum('amount');
                $item['withdraw_total'] = abs($withdrawTotal ?? 0);
                
                // 获取被邀请人的返现总额（获得的佣金）
                $commissionTotal = Db::name('invite_commission_log')
                    ->where('parent_id', $item['parent_id'])
                    ->where('status', 1)
                    ->sum('commission_amount');
                $item['commission_total'] = $commissionTotal ?? 0;
                
                // 获取待结算佣金
                $pendingCommission = Db::name('invite_commission_log')
                    ->where('parent_id', $item['parent_id'])
                    ->where('status', 0)
                    ->sum('commission_amount');
                $item['pending_commission'] = $pendingCommission ?? 0;
                
                // 获取金币佣金
                $coinCommission = Db::name('invite_commission_log')
                    ->where('parent_id', $item['parent_id'])
                    ->where('status', 1)
                    ->sum('coin_amount');
                $item['coin_commission'] = $coinCommission ?? 0;
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 获取邀请人的被邀请人列表
     */
    public function invitees()
    {
        $parentId = $this->request->get('parent_id', 0);
        
        if (!$parentId) {
            $this->error('参数错误');
        }
        
        if ($this->request->isAjax()) {
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            
            // 获取一级被邀请人
            $level1List = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.user_id', 'LEFT')
                ->where('ir.parent_id', $parentId)
                ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level,
                         "一级" as relation_level, 1 as level_num')
                ->order("ir.{$sort}", $order)
                ->select();
            
            // 获取二级被邀请人
            $level2List = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.user_id', 'LEFT')
                ->join('user u2', 'u2.id = ir.parent_id', 'LEFT')
                ->where('ir.grandparent_id', $parentId)
                ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level,
                         CONCAT("二级(通过", u2.nickname, ")") as relation_level, 2 as level_num,
                         u2.nickname as inviter_nickname')
                ->order("ir.{$sort}", $order)
                ->select();
            
            // 合并列表
            $list = array_merge($level1List, $level2List);
            $total = count($list);
            
            // 为每个被邀请人添加统计信息
            foreach ($list as &$item) {
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
                    ->where('status', 1)
                    ->sum('amount');
                $item['withdraw_total'] = abs($withdrawTotal ?? 0);
                
                // 返现总额（该用户产生的佣金）
                $commissionTotal = Db::name('invite_commission_log')
                    ->where('user_id', $item['user_id'])
                    ->where('status', 1)
                    ->sum('commission_amount');
                $item['commission_total'] = $commissionTotal ?? 0;
                
                // 账户余额
                $account = Db::name('coin_account')
                    ->where('user_id', $item['user_id'])
                    ->find();
                $item['balance'] = $account ? $account['balance'] : 0;
            }
            
            // 排序
            usort($list, function($a, $b) use ($sort, $order) {
                if ($order === 'desc') {
                    return $b[$sort] <=> $a[$sort];
                }
                return $a[$sort] <=> $b[$sort];
            });
            
            // 分页
            $list = array_slice($list, $offset, $limit);
            
            return json(['total' => $total, 'rows' => $list]);
        }
        
        $this->error('非法请求');
    }

    /**
     * 邀请人详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u1', 'u1.id = ir.parent_id', 'LEFT')
            ->join('user u2', 'u2.id = ir.user_id', 'LEFT')
            ->field('ir.*, u1.username as inviter_name, u1.nickname as inviter_nickname, u1.mobile as inviter_mobile,
                     u2.username as invitee_name, u2.nickname as invitee_nickname, u2.mobile as invitee_mobile')
            ->where('ir.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        // 获取邀请人的累计邀请人数和佣金
        $inviterStats = Db::name('user_invite_stat')
            ->where('user_id', $row['parent_id'])
            ->find();

        $this->view->assign('row', $row);
        $this->view->assign('inviterStats', $inviterStats);
        return $this->view->fetch();
    }
    
    /**
     * 统计概览
     */
    public function stat()
    {
        $parentId = $this->request->get('parent_id', 0);
        
        if (!$parentId) {
            $this->error('参数错误');
        }
        
        // 邀请人信息
        $inviter = Db::name('user')->where('id', $parentId)->find();
        
        // 邀请统计
        $stat = Db::name('user_invite_stat')->where('user_id', $parentId)->find();
        
        // 一级被邀请人数
        $level1Count = Db::name('invite_relation')->where('parent_id', $parentId)->count();
        
        // 二级被邀请人数
        $level2Count = Db::name('invite_relation')->where('grandparent_id', $parentId)->count();
        
        // 被邀请人ID列表
        $inviteeIds = Db::name('invite_relation')
            ->where('parent_id', $parentId)
            ->column('user_id');
        
        // 被邀请人提现总额
        $withdrawTotal = 0;
        // 被邀请人消费总额
        $spendTotal = 0;
        
        if (!empty($inviteeIds)) {
            $withdrawTotal = Db::name('coin_log')
                ->whereIn('user_id', $inviteeIds)
                ->where('type', 'withdraw')
                ->where('status', 1)
                ->sum('amount');
            $withdrawTotal = abs($withdrawTotal ?? 0);
            
            $spendTotal = Db::name('coin_log')
                ->whereIn('user_id', $inviteeIds)
                ->where('type', 'spend')
                ->sum('amount');
            $spendTotal = abs($spendTotal ?? 0);
        }
        
        // 佣金统计
        $commissionTotal = Db::name('invite_commission_log')
            ->where('parent_id', $parentId)
            ->where('status', 1)
            ->sum('commission_amount');
        $commissionTotal = $commissionTotal ?? 0;
        
        $pendingCommission = Db::name('invite_commission_log')
            ->where('parent_id', $parentId)
            ->where('status', 0)
            ->sum('commission_amount');
        $pendingCommission = $pendingCommission ?? 0;
        
        // 今日新增
        $todayStart = strtotime(date('Y-m-d'));
        $todayNew = Db::name('invite_relation')
            ->where('parent_id', $parentId)
            ->where('createtime', '>=', $todayStart)
            ->count();
        
        // 本周新增
        $weekStart = strtotime('this week monday');
        $weekNew = Db::name('invite_relation')
            ->where('parent_id', $parentId)
            ->where('createtime', '>=', $weekStart)
            ->count();
        
        // 本月新增
        $monthStart = strtotime(date('Y-m-01'));
        $monthNew = Db::name('invite_relation')
            ->where('parent_id', $parentId)
            ->where('createtime', '>=', $monthStart)
            ->count();
        
        $this->success('', null, [
            'inviter' => $inviter,
            'stat' => $stat,
            'level1_count' => $level1Count,
            'level2_count' => $level2Count,
            'total_count' => $level1Count + $level2Count,
            'withdraw_total' => $withdrawTotal,
            'spend_total' => $spendTotal,
            'commission_total' => $commissionTotal,
            'pending_commission' => $pendingCommission,
            'today_new' => $todayNew,
            'week_new' => $weekNew,
            'month_new' => $monthNew,
        ]);
    }
}
