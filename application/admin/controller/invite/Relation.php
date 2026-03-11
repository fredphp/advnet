<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
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
     * 用户邀请列表（显示所有用户）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $sort = $this->request->get('sort', 'total_invite_count');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 15);
            $filter = $this->request->get('filter', '{}');
            $filter = json_decode($filter, true);
            
            // 构建用户查询
            $query = Db::name('user');
            
            // 应用筛选条件
            if (!empty($filter)) {
                if (isset($filter['id']) && $filter['id'] !== '') {
                    $query->where('id', $filter['id']);
                }
                if (isset($filter['username']) && $filter['username'] !== '') {
                    $query->where('username|nickname', 'like', '%' . $filter['username'] . '%');
                }
                if (isset($filter['level']) && $filter['level'] !== '') {
                    $query->where('level', $filter['level']);
                }
            }
            
            // 获取总数
            $total = $query->count();
            
            // 获取所有用户（不分页，先获取全部再排序）
            $users = Db::name('user')
                ->field('id, username, nickname, mobile, avatar, level, invite_code, parent_id, grandparent_id, createtime')
                ->select();
            
            if (empty($users)) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            // 获取所有用户ID
            $userIds = array_column($users, 'id');
            
            // 获取一级邀请人数（该用户邀请了多少人）
            $level1Counts = Db::name('invite_relation')
                ->whereIn('parent_id', $userIds)
                ->group('parent_id')
                ->field('parent_id, COUNT(*) as count')
                ->select();
            $level1Map = [];
            foreach ($level1Counts as $item) {
                $level1Map[$item['parent_id']] = $item['count'];
            }
            
            // 获取二级邀请人数（该用户的下级又邀请了多少人）
            $level2Counts = Db::name('invite_relation')
                ->whereIn('grandparent_id', $userIds)
                ->where('grandparent_id', '>', 0)
                ->group('grandparent_id')
                ->field('grandparent_id, COUNT(*) as count')
                ->select();
            $level2Map = [];
            foreach ($level2Counts as $item) {
                $level2Map[$item['grandparent_id']] = $item['count'];
            }
            
            // 获取邀请统计
            $stats = Db::name('user_invite_stat')
                ->whereIn('user_id', $userIds)
                ->select();
            $statMap = [];
            foreach ($stats as $stat) {
                $statMap[$stat['user_id']] = $stat;
            }
            
            // 获取佣金统计（已结算）
            $commissionStats = Db::name('invite_commission_log')
                ->whereIn('parent_id', $userIds)
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
                ->whereIn('parent_id', $userIds)
                ->where('status', 0)
                ->group('parent_id')
                ->field('parent_id, SUM(commission_amount) as pending_commission')
                ->select();
            $pendingMap = [];
            foreach ($pendingStats as $item) {
                $pendingMap[$item['parent_id']] = $item['pending_commission'];
            }
            
            // 获取每个用户的被邀请人ID列表（用于统计提现）
            $inviteeByParent = [];
            $inviteeQuery = Db::name('invite_relation')
                ->whereIn('parent_id', $userIds)
                ->field('parent_id, user_id')
                ->select();
            foreach ($inviteeQuery as $item) {
                if (!isset($inviteeByParent[$item['parent_id']])) {
                    $inviteeByParent[$item['parent_id']] = [];
                }
                $inviteeByParent[$item['parent_id']][] = $item['user_id'];
            }
            
            // 组装数据
            $list = [];
            foreach ($users as $user) {
                $userId = $user['id'];
                $level1Count = $level1Map[$userId] ?? 0;
                $level2Count = $level2Map[$userId] ?? 0;
                $stat = $statMap[$userId] ?? [];
                $commissionData = $commissionMap[$userId] ?? [];
                $pendingCommission = $pendingMap[$userId] ?? 0;
                
                // 获取被邀请人的提现总额
                $withdrawTotal = 0;
                $inviteeIds = $inviteeByParent[$userId] ?? [];
                if (!empty($inviteeIds)) {
                    $withdrawTotal = abs(Db::name('coin_log')
                        ->whereIn('user_id', $inviteeIds)
                        ->where('type', 'withdraw')
                        ->sum('amount'));
                }
                
                $list[] = [
                    'id' => $userId,
                    'username' => $user['username'] ?? '-',
                    'nickname' => $user['nickname'] ?? '-',
                    'mobile' => $user['mobile'] ?? '-',
                    'avatar' => $user['avatar'] ?? '/assets/img/avatar.png',
                    'level' => $user['level'] ?? 0,
                    'invite_code' => $user['invite_code'] ?? '-',
                    'parent_id' => $user['parent_id'] ?? 0,
                    'grandparent_id' => $user['grandparent_id'] ?? 0,
                    'level1_count' => $level1Count,
                    'level2_count' => $level2Count,
                    'total_invite_count' => $level1Count + $level2Count,
                    'valid_invite_count' => $stat['valid_invite_count'] ?? 0,
                    'withdraw_total' => $withdrawTotal,
                    'commission_total' => $commissionData['total_commission'] ?? 0,
                    'coin_commission' => $commissionData['total_coin'] ?? 0,
                    'pending_commission' => $pendingCommission,
                    'createtime' => $user['createtime'] ?? 0,
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
            
            // 分页
            $rows = array_slice($list, $offset, $limit);

            return json(['total' => $total, 'rows' => $rows]);
        }
        return $this->view->fetch();
    }

    /**
     * 获取用户的被邀请人列表
     */
    public function invitees()
    {
        $parentId = $this->request->get('parent_id', 0);
        
        if (!$parentId) {
            $this->error('参数错误');
        }
        
        // 非AJAX请求，返回视图
        if (!$this->request->isAjax()) {
            $this->view->assign('parent_id', $parentId);
            return $this->view->fetch();
        }
        
        // AJAX请求，返回数据
        $sort = $this->request->get('sort', 'createtime');
        $order = $this->request->get('order', 'desc');
        $offset = $this->request->get('offset', 0);
        $limit = $this->request->get('limit', 10);
        
        // 获取一级被邀请人
        $level1List = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u', 'u.id = ir.user_id', 'LEFT')
            ->where('ir.parent_id', $parentId)
            ->field('ir.*, u.username, u.nickname, u.mobile, u.avatar, u.level as user_level, 1 as level_num, "" as inviter_nickname')
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
            
            // 返现总额
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
