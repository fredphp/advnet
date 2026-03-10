<?php

namespace app\common\library;

use think\Db;
use think\Cache;
use think\Log;
use app\common\model\InviteRelation;
use app\common\model\InviteCommissionConfig;
use app\common\model\InviteCommissionLog;
use app\common\model\UserInviteStat;
use app\common\model\UserCommissionStat;
use app\common\model\User;

/**
 * 邀请分佣服务类
 * 
 * 核心功能：
 * 1. 绑定邀请关系
 * 2. 触发分佣结算（提现/视频/红包/游戏）
 * 3. 统计邀请人数和收益
 */
class InviteCommissionService
{
    // Redis键前缀
    const CACHE_PREFIX = 'invite_commission:';
    const LOCK_PREFIX = 'lock:invite_commission:';
    
    // 金币转换比例：10000金币 = 1元
    const COIN_RATE = 10000;
    
    /**
     * 绑定邀请关系
     * @param int $userId 新用户ID
     * @param string $inviteCode 邀请码
     * @param array $options 额外选项
     * @return array
     */
    public function bindInvite($userId, $inviteCode, $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null,
        ];
        
        if (empty($inviteCode)) {
            $result['message'] = '邀请码不能为空';
            return $result;
        }
        
        // 查找邀请人
        $inviter = User::where('invite_code', $inviteCode)->find();
        if (!$inviter) {
            $result['message'] = '邀请码无效';
            return $result;
        }
        
        if ($inviter->id == $userId) {
            $result['message'] = '不能使用自己的邀请码';
            return $result;
        }
        
        // 检查是否已绑定
        $existRelation = InviteRelation::where('user_id', $userId)->find();
        if ($existRelation) {
            $result['message'] = '已绑定邀请关系';
            return $result;
        }
        
        Db::startTrans();
        try {
            // 创建邀请关系
            $relation = InviteRelation::createRelation($userId, $inviteCode, $options['channel'] ?? 'link');
            
            // 更新用户表的parent_id和grandparent_id
            User::where('id', $userId)->update([
                'parent_id' => $relation->parent_id,
                'grandparent_id' => $relation->grandparent_id,
            ]);
            
            // 更新一级上级邀请统计
            if ($relation->parent_id > 0) {
                UserInviteStat::incrementLevel1($relation->parent_id, $userId);
            }
            
            // 更新二级上级邀请统计
            if ($relation->grandparent_id > 0) {
                UserInviteStat::incrementLevel2($relation->grandparent_id);
            }
            
            // 发放注册奖励（如果有）
            $this->giveRegisterReward($userId, $relation);
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '绑定成功';
            $result['data'] = [
                'parent_id' => $relation->parent_id,
                'grandparent_id' => $relation->grandparent_id,
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '绑定失败: ' . $e->getMessage();
            Log::error('绑定邀请关系失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 提现触发分佣
     * @param int $userId 提现用户ID
     * @param float $cashAmount 提现金额(元)
     * @param string $orderNo 提现订单号
     * @param int $withdrawId 提现记录ID
     * @return array
     */
    public function triggerWithdrawCommission($userId, $cashAmount, $orderNo, $withdrawId)
    {
        return $this->triggerCommission($userId, 'withdraw', $cashAmount, [
            'source_id' => $withdrawId,
            'source_order_no' => $orderNo,
        ]);
    }
    
    /**
     * 视频收益触发分佣
     * @param int $userId 用户ID
     * @param float $coinAmount 金币数量
     * @param int $videoId 视频ID
     * @param int $watchRecordId 观看记录ID
     * @return array
     */
    public function triggerVideoCommission($userId, $coinAmount, $videoId, $watchRecordId)
    {
        // 金币转人民币
        $cashAmount = $coinAmount / self::COIN_RATE;
        
        return $this->triggerCommission($userId, 'video', $cashAmount, [
            'source_id' => $watchRecordId,
            'coin_amount' => $coinAmount,
        ]);
    }
    
    /**
     * 红包收益触发分佣
     * @param int $userId 用户ID
     * @param float $coinAmount 金币数量
     * @param int $packetId 红包ID
     * @param int $recordId 领取记录ID
     * @return array
     */
    public function triggerRedPacketCommission($userId, $coinAmount, $packetId, $recordId)
    {
        $cashAmount = $coinAmount / self::COIN_RATE;
        
        return $this->triggerCommission($userId, 'red_packet', $cashAmount, [
            'source_id' => $recordId,
            'coin_amount' => $coinAmount,
        ]);
    }
    
    /**
     * 游戏收益触发分佣
     * @param int $userId 用户ID
     * @param float $coinAmount 金币数量
     * @param int $gameRecordId 游戏记录ID
     * @return array
     */
    public function triggerGameCommission($userId, $coinAmount, $gameRecordId)
    {
        $cashAmount = $coinAmount / self::COIN_RATE;
        
        return $this->triggerCommission($userId, 'game', $cashAmount, [
            'source_id' => $gameRecordId,
            'coin_amount' => $coinAmount,
        ]);
    }
    
    /**
     * 核心分佣触发方法
     * @param int $userId 产生收益的用户ID
     * @param string $sourceType 来源类型
     * @param float $sourceAmount 来源金额(元)
     * @param array $options 额外选项
     * @return array
     */
    protected function triggerCommission($userId, $sourceType, $sourceAmount, $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => [],
        ];
        
        // 检查是否开启分佣
        if (!$this->isCommissionEnabled()) {
            $result['message'] = '分佣功能未开启';
            return $result;
        }
        
        // 获取用户邀请关系
        $relation = InviteRelation::where('user_id', $userId)->find();
        if (!$relation) {
            $result['message'] = '无邀请关系';
            return $result;
        }
        
        // 获取分佣配置
        $config = InviteCommissionConfig::getByCode($sourceType);
        if (!$config || $config['status'] != 1) {
            $result['message'] = '分佣配置不存在或已禁用';
            return $result;
        }
        
        // 检查最低触发金额
        if ($sourceAmount < $config['min_amount']) {
            $result['message'] = '金额未达分佣门槛';
            return $result;
        }
        
        $commissionLogs = [];
        
        Db::startTrans();
        try {
            // 一级分佣
            if ($relation->parent_id > 0) {
                $level1Result = $this->createCommissionLog(
                    $userId,
                    $relation->parent_id,
                    1,
                    $sourceType,
                    $sourceAmount,
                    $config,
                    $options
                );
                
                if ($level1Result) {
                    $commissionLogs[] = $level1Result;
                    
                    // 标记为有效邀请
                    UserInviteStat::incrementValidInvite($relation->parent_id);
                }
            }
            
            // 二级分佣
            if ($relation->grandparent_id > 0) {
                $level2Result = $this->createCommissionLog(
                    $userId,
                    $relation->grandparent_id,
                    2,
                    $sourceType,
                    $sourceAmount,
                    $config,
                    $options
                );
                
                if ($level2Result) {
                    $commissionLogs[] = $level2Result;
                    
                    // 标记为有效邀请
                    UserInviteStat::incrementValidInvite($relation->grandparent_id);
                }
            }
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '分佣创建成功';
            $result['data'] = [
                'commission_count' => count($commissionLogs),
                'logs' => $commissionLogs,
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '分佣创建失败: ' . $e->getMessage();
            Log::error('分佣创建失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 创建分佣记录
     */
    protected function createCommissionLog($userId, $parentId, $level, $sourceType, $sourceAmount, $config, $options)
    {
        // 计算佣金
        $commission = InviteCommissionConfig::calculateCommission($sourceType, $sourceAmount, $level);
        
        if ($commission['amount'] <= 0) {
            return null;
        }
        
        // 佣金转金币
        $coinAmount = $commission['amount'] * self::COIN_RATE;
        
        // 创建分佣记录
        $log = InviteCommissionLog::createLog([
            'source_type' => $sourceType,
            'source_id' => $options['source_id'] ?? null,
            'source_order_no' => $options['source_order_no'] ?? null,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'level' => $level,
            'source_amount' => $sourceAmount,
            'commission_rate' => $commission['rate'],
            'commission_fixed' => $commission['fixed'],
            'commission_amount' => $commission['amount'],
            'coin_amount' => $coinAmount,
            'config_id' => $config['id'],
        ]);
        
        // 更新用户佣金统计（待结算）
        $stat = UserCommissionStat::getOrCreate($parentId);
        $stat->addPending($commission['amount']);
        
        return $log;
    }
    
    /**
     * 结算分佣
     * @param int $logId 分佣记录ID
     * @return array
     */
    public function settleCommission($logId)
    {
        $result = [
            'success' => false,
            'message' => '',
        ];
        
        $log = InviteCommissionLog::find($logId);
        if (!$log) {
            $result['message'] = '分佣记录不存在';
            return $result;
        }
        
        if ($log->status != InviteCommissionLog::STATUS_PENDING) {
            $result['message'] = '分佣状态异常';
            return $result;
        }
        
        // 分布式锁
        $lockKey = self::LOCK_PREFIX . "settle:{$logId}";
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            if ($log->settle()) {
                // 减少待结算统计
                $stat = UserCommissionStat::getOrCreate($log->parent_id);
                $stat->reducePending($log->commission_amount);
                
                $result['success'] = true;
                $result['message'] = '结算成功';
            } else {
                $result['message'] = '结算失败';
            }
        } catch (\Exception $e) {
            $result['message'] = '结算异常: ' . $e->getMessage();
            Log::error('分佣结算异常: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 批量结算分佣
     * @param array $logIds 分佣记录ID数组
     * @return array
     */
    public function batchSettleCommission($logIds)
    {
        $result = [
            'success' => 0,
            'failed' => 0,
            'total' => count($logIds),
        ];
        
        foreach ($logIds as $logId) {
            $settleResult = $this->settleCommission($logId);
            if ($settleResult['success']) {
                $result['success']++;
            } else {
                $result['failed']++;
            }
        }
        
        return $result;
    }
    
    /**
     * 发放注册奖励
     */
    protected function giveRegisterReward($userId, $relation)
    {
        $rewardCoin = $this->getConfig('invite_register_reward', 0);
        
        if ($rewardCoin <= 0) {
            return false;
        }
        
        // 给新用户发注册奖励
        $coinService = new CoinService();
        $coinService->addCoin($userId, $rewardCoin, 'register_reward', [
            'description' => '新用户注册奖励',
        ]);
        
        // 给一级上级发邀请奖励（如果有）
        if ($relation->parent_id > 0) {
            $inviteReward = $rewardCoin * 0.5; // 邀请人获得50%
            $coinService->addCoin($relation->parent_id, $inviteReward, 'invite_reward', [
                'relation_type' => 'user',
                'relation_id' => $userId,
                'description' => '邀请新用户注册奖励',
            ]);
        }
        
        return true;
    }
    
    /**
     * 获取邀请统计概览
     * @param int $userId 用户ID
     * @return array
     */
    public function getInviteOverview($userId)
    {
        // 邀请人数统计
        $inviteStat = UserInviteStat::getOverview($userId);
        
        // 佣金统计
        $commissionStat = UserCommissionStat::getOverview($userId);
        
        // 获取邀请人信息
        $relation = InviteRelation::where('user_id', $userId)->find();
        $parentInfo = null;
        if ($relation && $relation->parent_id > 0) {
            $parent = User::find($relation->parent_id);
            if ($parent) {
                $parentInfo = [
                    'user_id' => $parent->id,
                    'nickname' => $parent->nickname,
                    'avatar' => $parent->avatar,
                ];
            }
        }
        
        return [
            // 邀请人数
            'total_invite_count' => $inviteStat['total_invite_count'],
            'level1_count' => $inviteStat['level1_count'],
            'level2_count' => $inviteStat['level2_count'],
            'valid_invite_count' => $inviteStat['valid_invite_count'],
            'new_invite_today' => $inviteStat['new_invite_today'],
            'new_invite_yesterday' => $inviteStat['new_invite_yesterday'],
            
            // 佣金收益
            'total_commission' => $commissionStat['total_commission'],
            'total_coin' => $commissionStat['total_coin'],
            'today_commission' => $commissionStat['today_commission'],
            'yesterday_commission' => $commissionStat['yesterday_commission'],
            'withdraw_commission' => $commissionStat['withdraw_commission'],
            'video_commission' => $commissionStat['video_commission'],
            'red_packet_commission' => $commissionStat['red_packet_commission'],
            'game_commission' => $commissionStat['game_commission'],
            
            // 邀请人信息
            'parent_info' => $parentInfo,
            'invite_code' => User::where('id', $userId)->value('invite_code'),
        ];
    }
    
    /**
     * 获取邀请列表
     * @param int $userId 用户ID
     * @param int $level 层级 1=一级 2=二级 0=全部
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getInviteList($userId, $level = 0, $page = 1, $limit = 20)
    {
        // 获取一级下级
        if ($level == 0 || $level == 1) {
            $level1Ids = InviteRelation::where('parent_id', $userId)
                ->column('user_id');
        } else {
            $level1Ids = [];
        }
        
        // 获取二级下级
        if ($level == 0 || $level == 2) {
            $level2Ids = InviteRelation::where('grandparent_id', $userId)
                ->column('user_id');
        } else {
            $level2Ids = [];
        }
        
        // 合并ID
        $userIds = [];
        if ($level == 0) {
            $userIds = array_merge($level1Ids, $level2Ids);
        } elseif ($level == 1) {
            $userIds = $level1Ids;
        } elseif ($level == 2) {
            $userIds = $level2Ids;
        }
        
        $total = count($userIds);
        
        if ($total == 0) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }
        
        // 分页
        $userIds = array_slice($userIds, ($page - 1) * $limit, $limit);
        
        // 获取用户信息
        $users = User::whereIn('id', $userIds)
            ->field('id, nickname, avatar, createtime')
            ->select();
        
        // 获取用户的佣金贡献
        $commissionMap = [];
        $commissions = InviteCommissionLog::whereIn('user_id', $userIds)
            ->where('parent_id', $userId)
            ->field('user_id, SUM(commission_amount) as total_commission')
            ->group('user_id')
            ->select();
        
        foreach ($commissions as $c) {
            $commissionMap[$c['user_id']] = $c['total_commission'];
        }
        
        // 组装数据
        $list = [];
        foreach ($users as $user) {
            $userLevel = in_array($user->id, $level1Ids) ? 1 : 2;
            $list[] = [
                'user_id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'level' => $userLevel,
                'commission_contribution' => $commissionMap[$user->id] ?? 0,
                'register_time' => $user->createtime,
            ];
        }
        
        return [
            'total' => $total,
            'list' => $list,
        ];
    }
    
    /**
     * 获取佣金明细
     * @param int $userId 用户ID
     * @param array $filters 筛选条件
     * @return array
     */
    public function getCommissionList($userId, $filters = [])
    {
        return InviteCommissionLog::getUserLogs($userId, $filters);
    }
    
    /**
     * 检查分佣是否开启
     * @return bool
     */
    protected function isCommissionEnabled()
    {
        return $this->getConfig('invite_commission_enabled', 1) == 1;
    }
    
    /**
     * 获取配置
     * @param string $name 配置名称
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getConfig($name, $default = null)
    {
        try {
            $value = Db::name('config')->where('name', $name)->value('value');
            return $value !== null ? $value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * 获取分布式锁
     */
    protected function getLock($key, $expire = 5)
    {
        try {
            $redis = Cache::store('redis')->handler();
            return $redis->set($key, 1, ['NX', 'EX' => $expire]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 释放锁
     */
    protected function releaseLock($key)
    {
        try {
            Cache::store('redis')->handler()->del($key);
        } catch (\Exception $e) {
        }
    }
}
