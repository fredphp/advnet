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
     * 获取用户详情（用于重新绑定上级）
     */
    public function getUserDetail()
    {
        $userId = $this->request->get('user_id', 0);
        
        if (!$userId) {
            $this->error('参数错误');
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        $user = is_array($user) ? $user : $user->toArray();
        
        // 获取当前上级信息
        $currentParent = null;
        if (!empty($user['parent_id'])) {
            $currentParent = Db::name('user')->where('id', $user['parent_id'])->find();
            if ($currentParent) {
                $currentParent = is_array($currentParent) ? $currentParent : $currentParent->toArray();
                
                // 获取当前上级的一级邀请人数
                $currentParent['level1_count'] = Db::name('invite_relation')
                    ->where('parent_id', $currentParent['id'])
                    ->count();
                // 获取当前上级的二级邀请人数
                $currentParent['level2_count'] = Db::name('invite_relation')
                    ->where('grandparent_id', $currentParent['id'])
                    ->where('grandparent_id', '>', 0)
                    ->count();
            }
        }
        
        // 获取用户自己的一级邀请人数
        $user['level1_count'] = Db::name('invite_relation')
            ->where('parent_id', $userId)
            ->count();
        
        // 获取用户自己的二级邀请人数
        $user['level2_count'] = Db::name('invite_relation')
            ->where('grandparent_id', $userId)
            ->where('grandparent_id', '>', 0)
            ->count();
        
        $this->success('', null, [
            'user' => $user,
            'current_parent' => $currentParent,
        ]);
    }
    
    /**
     * 获取新上级详情（选择后）
     */
    public function getNewParentDetail()
    {
        $newParentId = $this->request->get('new_parent_id', 0);
        $userId = $this->request->get('user_id', 0);
        
        if (!$newParentId) {
            $this->error('参数错误');
        }
        
        // 不能绑定自己为上级
        if ($newParentId == $userId) {
            $this->error('不能将自己设置为上级');
        }
        
        // 获取新上级信息
        $newParent = Db::name('user')->where('id', $newParentId)->find();
        if (!$newParent) {
            $this->error('用户不存在');
        }
        $newParent = is_array($newParent) ? $newParent : $newParent->toArray();
        
        // 获取新上级的一级邀请人数
        $newParent['level1_count'] = Db::name('invite_relation')
            ->where('parent_id', $newParentId)
            ->count();
        
        // 获取新上级的二级邀请人数
        $newParent['level2_count'] = Db::name('invite_relation')
            ->where('grandparent_id', $newParentId)
            ->where('grandparent_id', '>', 0)
            ->count();
        
        // 检查是否会形成循环关系（新上级不能是当前用户的下级）
        $isChild = Db::name('invite_relation')
            ->where('user_id', $newParentId)
            ->where('parent_id', $userId)
            ->whereOr('grandparent_id', $userId)
            ->find();
        if ($isChild) {
            $this->error('不能将自己的下级设置为上级，会形成循环关系');
        }
        
        $this->success('', null, [
            'new_parent' => $newParent,
        ]);
    }
    
    /**
     * 重新绑定上级
     */
    public function rebindParent()
    {
        $userId = $this->request->post('user_id', 0);
        $newParentId = $this->request->post('new_parent_id', 0);
        $reason = $this->request->post('reason', '');
        
        if (!$userId || !$newParentId) {
            $this->error('参数错误');
        }
        
        // 不能绑定自己为上级
        if ($newParentId == $userId) {
            $this->error('不能将自己设置为上级');
        }
        
        // 获取用户当前的邀请关系
        $inviteRelation = Db::name('invite_relation')->where('user_id', $userId)->find();
        $oldParentId = 0;
        $oldGrandparentId = 0;
        if ($inviteRelation) {
            $inviteRelation = is_array($inviteRelation) ? $inviteRelation : $inviteRelation->toArray();
            $oldParentId = $inviteRelation['parent_id'] ?? 0;
            $oldGrandparentId = $inviteRelation['grandparent_id'] ?? 0;
        }
        
        // 检查是否会形成循环关系
        $isChild = Db::name('invite_relation')
            ->where('user_id', $newParentId)
            ->where(function($query) use ($userId) {
                $query->where('parent_id', $userId)
                    ->whereOr('grandparent_id', $userId);
            })
            ->find();
        if ($isChild) {
            $this->error('不能将自己的下级设置为上级，会形成循环关系');
        }
        
        Db::startTrans();
        try {
            // 获取新上级的上级（作为新的grandparent）
            $newGrandparentId = 0;
            $newParentRelation = Db::name('invite_relation')->where('user_id', $newParentId)->find();
            if ($newParentRelation) {
                $newParentRelation = is_array($newParentRelation) ? $newParentRelation : $newParentRelation->toArray();
                if (!empty($newParentRelation['parent_id'])) {
                    $newGrandparentId = $newParentRelation['parent_id'];
                }
            }
            
            // 更新邀请关系表
            if ($inviteRelation) {
                Db::name('invite_relation')
                    ->where('user_id', $userId)
                    ->update([
                        'parent_id' => $newParentId,
                        'grandparent_id' => $newGrandparentId,
                        'updatetime' => time(),
                    ]);
            } else {
                // 如果没有邀请关系记录，                Db::name('invite_relation')->insert([
                    'user_id' => $userId,
                    'parent_id' => $newParentId,
                    'grandparent_id' => $newGrandparentId,
                    'invite_code' => '',
                    'invite_channel' => 'admin',
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }
            
            // 更新用户表的parent_id和grandparent_id
            Db::name('user')
                ->where('id', $userId)
                ->update([
                    'parent_id' => $newParentId,
                    'grandparent_id' => $newGrandparentId,
                ]);
            
            // 更新该用户所有下级的grandparent_id
            // 一级下级的grandparent_id需要更新为新上级
            Db::name('invite_relation')
                ->where('parent_id', $userId)
                ->update([
                    'grandparent_id' => $newParentId,
                    'updatetime' => time(),
                ]);
            
            // 更新用户表的parent_id
            $level1UserIds = Db::name('invite_relation')
                ->where('parent_id', $userId)
                ->column('user_id');
            if (!empty($level1UserIds)) {
                Db::name('user')
                    ->whereIn('id', $level1UserIds)
                    ->update([
                        'grandparent_id' => $newParentId,
                    ]);
            }
            
            // 记录迁移日志
            Db::name('invite_relation_migration_log')->insert([
                'user_id' => $userId,
                'old_parent_id' => $oldParentId,
                'old_grandparent_id' => $oldGrandparentId,
                'new_parent_id' => $newParentId,
                'new_grandparent_id' => $newGrandparentId,
                'admin_id' => $this->auth->id,
                'reason' => $reason,
                'createtime' => time(),
            ]);

            Db::commit();
            $this->success('绑定上级成功');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('绑定失败：' . $e->getMessage());
        }
    }
    
    /**
     * 邀请关系迁移日志列表
     */
    public function migrationLog()
    {
        if ($this->request->isAjax()) {
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');
            $offset = intval($this->request->get('offset', 0));
            $limit = intval($this->request->get('limit', 15));
            
            $total = Db::name('invite_relation_migration_log')->count();

            $list = Db::name('invite_relation_migration_log')
                ->alias('log')
                ->join('user u', 'u.id = log.user_id', 'LEFT')
                ->join('user old_parent', 'old_parent.id = log.old_parent_id', 'LEFT')
                ->join('user new_parent', 'new_parent.id = log.new_parent_id', 'LEFT')
                ->join('admin a', 'a.id = log.admin_id', 'LEFT')
                ->field('log.*, u.nickname as user_nickname, u.username as user_username, 
                         old_parent.nickname as old_parent_nickname, old_parent.username as old_parent_username,
                         new_parent.nickname as new_parent_nickname, new_parent.username as new_parent_username,
                         a.username as admin_username')
                ->order('log.' . $sort, $order)
                ->limit($offset, $limit)
                ->select();

            $rows = [];
            foreach ($list as $item) {
                $rows[] = is_array($item) ? $item : $item->toArray();
            }

            return json(['total' => $total, 'rows' => $rows]);
        }
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
