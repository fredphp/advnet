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
            
            // 获取邀请人列表（按parent_id分组）
            $query = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.parent_id', 'LEFT')
                ->where('ir.parent_id', '>', 0);
            
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
            
            // 获取所有邀请人ID
            $inviterIds = Db::name('invite_relation')
                ->where('parent_id', '>', 0)
                ->group('parent_id')
                ->column('parent_id');
            
            $total = count($inviterIds);
            
            if ($total == 0) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            // 分页处理
            $pageInviterIds = array_slice($inviterIds, $offset, $limit);
            
            // 获取邀请人信息
            $inviters = Db::name('user')
                ->whereIn('id', $pageInviterIds)
                ->field('id, username, nickname, mobile, avatar, level')
                ->select();
            
            $inviterMap = [];
            foreach ($inviters as $inv) {
                $inviterMap[$inv['id']] = $inv;
            }
            
            // 获取邀请统计
            $inviteStats = Db::name('user_invite_stat')
                ->whereIn('user_id', $pageInviterIds)
                ->select();
            
            $statMap = [];
            foreach ($inviteStats as $stat) {
                $statMap[$stat['user_id']] = $stat;
            }
            
            // 获取一级邀请人数
            $level1Counts = Db::name('invite_relation')
                ->whereIn('parent_id', $pageInviterIds)
                ->group('parent_id')
                ->field('parent_id, COUNT(*) as count')
                ->select();
            
            $level1Map = [];
            foreach ($level1Counts as $item) {
                $level1Map[$item['parent_id']] = $item['count'];
            }
            
            // 获取二级邀请人数（通过grandparent_id）
            $level2Counts = Db::name('invite_relation')
                ->whereIn('grandparent_id', $pageInviterIds)
                ->where('grandparent_id', '>', 0)
                ->group('grandparent_id')
                ->field('grandparent_id, COUNT(*) as count')
                ->select();
            
            $level2Map = [];
            foreach ($level2Counts as $item) {
                $level2Map[$item['grandparent_id']] = $item['count'];
            }
            
            // 获取被邀请人ID列表（用于统计）
            $allInviteeIds = [];
            $inviteeByParent = [];
            foreach ($pageInviterIds as $pid) {
                $inviteeIds = Db::name('invite_relation')
                    ->where('parent_id', $pid)
                    ->column('user_id');
                $inviteeByParent[$pid] = $inviteeIds;
                $allInviteeIds = array_merge($allInviteeIds, $inviteeIds);
            }
            
            // 获取佣金统计
            $commissionStats = Db::name('invite_commission_log')
                ->whereIn('parent_id', $pageInviterIds)
                ->where('status', 1)
                ->group('parent_id')
                ->field('parent_id, SUM(commission_amount) as total_commission, SUM(coin_amount) as total_coin')
                ->select();
            
            $commissionMap = [];
            foreach ($commissionStats as $item) {
                $commissionMap[$item['parent_id']] = $item;
            }
            
            // 获取待结算佣金
            $pendingStats = Db::name('invite_commission_log')
                ->whereIn('parent_id', $pageInviterIds)
                ->where('status', 0)
                ->group('parent_id')
                ->field('parent_id, SUM(commission_amount) as pending_commission')
                ->select();
            
            $pendingMap = [];
            foreach ($pendingStats as $item) {
                $pendingMap[$item['parent_id']] = $item['pending_commission'];
            }
            
            // 组装数据
            $list = [];
            foreach ($pageInviterIds as $parentId) {
                $inviter = $inviterMap[$parentId] ?? [];
                $stat = $statMap[$parentId] ?? [];
                
                $level1Count = $level1Map[$parentId] ?? 0;
                $level2Count = $level2Map[$parentId] ?? 0;
                
                // 获取被邀请人的消费和提现统计
                $inviteeIds = $inviteeByParent[$parentId] ?? [];
                $withdrawTotal = 0;
                $spendTotal = 0;
                
                if (!empty($inviteeIds)) {
                    // 提现总额 - coin_log中type=withdraw，amount为负数
                    $withdrawTotal = abs(Db::name('coin_log')
                        ->whereIn('user_id', $inviteeIds)
                        ->where('type', 'withdraw')
                        ->sum('amount'));
                    
                    // 消费总额
                    $spendTotal = abs(Db::name('coin_log')
                        ->whereIn('user_id', $inviteeIds)
                        ->where('type', 'spend')
                        ->sum('amount'));
                }
                
                $commissionData = $commissionMap[$parentId] ?? [];
                $pendingData = $pendingMap[$parentId] ?? [];
                
                $list[] = [
                    'parent_id' => $parentId,
                    'inviter_name' => $inviter['username'] ?? '-',
                    'inviter_nickname' => $inviter['nickname'] ?? '-',
                    'inviter_mobile' => $inviter['mobile'] ?? '-',
                    'inviter_avatar' => $inviter['avatar'] ?? '/assets/img/avatar.png',
                    'inviter_level' => $inviter['level'] ?? 0,
                    'level1_count' => $level1Count,
                    'level2_count' => $level2Count,
                    'total_invite_count' => $level1Count + $level2Count,
                    'valid_invite_count' => $stat['valid_invite_count'] ?? 0,
                    'withdraw_total' => $withdrawTotal,
                    'spend_total' => $spendTotal,
                    'commission_total' => $commissionData['total_commission'] ?? 0,
                    'coin_commission' => $commissionData['total_coin'] ?? 0,
                    'pending_commission' => $pendingData['pending_commission'] ?? 0,
                ];
            }
            
            // 排序
            usort($list, function($a, $b) use ($sort, $order) {
                if (!isset($a[$sort]) || !isset($b[$sort])) {
                    return 0;
                }
                if ($order === 'desc') {
                    return $b[$sort] <=> $a[$sort];
                }
                return $a[$sort] <=> $b[$sort];
            });

            return json(['total' => $total, 'rows' => $list]);
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
                ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level, 1 as level_num')
                ->select();
            
            // 获取二级被邀请人
            $level2List = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u', 'u.id = ir.user_id', 'LEFT')
                ->join('user u2', 'u2.id = ir.parent_id', 'LEFT')
                ->where('ir.grandparent_id', $parentId)
                ->where('ir.grandparent_id', '>', 0)
                ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level, 2 as level_num, u2.nickname as inviter_nickname')
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
                
                // 设置关系层级文字
                if ($item['level_num'] == 1) {
                    $item['relation_level'] = '一级';
                } else {
                    $item['relation_level'] = '二级(通过 ' . ($item['inviter_nickname'] ?? '未知') . ')';
                }
            }
            
            // 排序
            usort($list, function($a, $b) use ($sort, $order) {
                if (!isset($a[$sort]) || !isset($b[$sort])) {
                    return 0;
                }
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
        $level2Count = Db::name('invite_relation')->where('grandparent_id', $parentId)->where('grandparent_id', '>', 0)->count();
        
        // 被邀请人ID列表
        $inviteeIds = Db::name('invite_relation')
            ->where('parent_id', $parentId)
            ->column('user_id');
        
        // 被邀请人提现总额
        $withdrawTotal = 0;
        // 被邀请人消费总额
        $spendTotal = 0;
        
        if (!empty($inviteeIds)) {
            $withdrawTotal = abs(Db::name('coin_log')
                ->whereIn('user_id', $inviteeIds)
                ->where('type', 'withdraw')
                ->sum('amount'));
            
            $spendTotal = abs(Db::name('coin_log')
                ->whereIn('user_id', $inviteeIds)
                ->where('type', 'spend')
                ->sum('amount'));
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
