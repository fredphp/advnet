<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Cache;
use think\Log;
use app\common\model\UserRiskScore;
use app\common\model\BanRecord;
use app\common\model\RiskBlacklist;
use app\common\model\RiskLog;

/**
 * 自动封号服务
 * 
 * 功能：
 * 1. 自动封号决策
 * 2. 封号执行
 * 3. 解封处理
 * 4. 封号申诉
 * 5. 批量封号
 */
class AutoBanService
{
    // 封号类型
    const BAN_TYPE_TEMPORARY = 'temporary';     // 临时封禁
    const BAN_TYPE_PERMANENT = 'permanent';     // 永久封禁
    
    // 封号原因模板
    const BAN_REASONS = [
        'risk_score_high' => '风险评分过高，系统自动封禁',
        'multi_account' => '多账户作弊',
        'emulator_detected' => '使用模拟器作弊',
        'hook_detected' => '使用外挂/修改器',
        'fake_behavior' => '检测到虚假行为',
        'withdraw_fraud' => '提现欺诈',
        'invite_fraud' => '邀请欺诈',
        'excessive_violations' => '违规次数过多',
        'device_blacklist' => '设备已被列入黑名单',
        'ip_blacklist' => 'IP已被列入黑名单',
    ];
    
    // 封号时长配置
    const BAN_DURATIONS = [
        'first_offense' => 86400,         // 首次违规：1天
        'second_offense' => 604800,       // 二次违规：7天
        'third_offense' => 2592000,       // 三次违规：30天
        'permanent' => 0,                 // 永久封禁
    ];
    
    // 风险分阈值
    const SCORE_THRESHOLD_HIGH = 300;     // 高风险
    const SCORE_THRESHOLD_DANGEROUS = 500; // 危险
    const SCORE_THRESHOLD_BAN = 700;       // 自动封禁
    
    /**
     * 检查是否需要自动封号
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function checkAutoBan($userId)
    {
        $userScore = UserRiskScore::where('user_id', $userId)->find();
        
        if (!$userScore) {
            return ['need_ban' => false];
        }
        
        $totalScore = $userScore['total_score'];
        $violationCount = $userScore['violation_count'];
        
        // 检查风险分阈值
        if ($totalScore >= self::SCORE_THRESHOLD_BAN) {
            return [
                'need_ban' => true,
                'reason' => self::BAN_REASONS['risk_score_high'],
                'type' => self::BAN_TYPE_PERMANENT,
                'duration' => 0,
            ];
        }
        
        // 检查违规次数
        if ($violationCount >= 10) {
            return [
                'need_ban' => true,
                'reason' => self::BAN_REASONS['excessive_violations'],
                'type' => self::BAN_TYPE_PERMANENT,
                'duration' => 0,
            ];
        }
        
        // 根据风险分和违规次数综合判断
        if ($totalScore >= self::SCORE_THRESHOLD_DANGEROUS) {
            $duration = $this->calculateBanDuration($violationCount);
            return [
                'need_ban' => true,
                'reason' => self::BAN_REASONS['risk_score_high'],
                'type' => $duration > 0 ? self::BAN_TYPE_TEMPORARY : self::BAN_TYPE_PERMANENT,
                'duration' => $duration,
            ];
        }
        
        return ['need_ban' => false];
    }
    
    /**
     * 执行封号
     * 
     * @param int $userId 用户ID
     * @param string $reason 封号原因
     * @param int $duration 持续时间(秒)，0表示永久
     * @param string $source 来源(auto/manual)
     * @param int $adminId 管理员ID(手动封禁时)
     * @return array
     */
    public function executeBan($userId, $reason, $duration = 0, $source = 'auto', $adminId = null)
    {
        Db::startTrans();
        try {
            $now = time();
            $banType = $duration > 0 ? self::BAN_TYPE_TEMPORARY : self::BAN_TYPE_PERMANENT;
            
            // 1. 更新用户风控状态
            $userScore = UserRiskScore::where('user_id', $userId)->lock(true)->find();
            if ($userScore) {
                $userScore->status = 'banned';
                $userScore->ban_expire_time = $duration > 0 ? $now + $duration : null;
                $userScore->save();
            } else {
                $userScore = new UserRiskScore();
                $userScore->user_id = $userId;
                $userScore->status = 'banned';
                $userScore->ban_expire_time = $duration > 0 ? $now + $duration : null;
                $userScore->save();
            }
            
            // 2. 更新用户表状态
            Db::name('user')->where('id', $userId)->update([
                'status' => 'banned',
                'updatetime' => $now,
            ]);
            
            // 3. 创建封禁记录
            $banRecord = new BanRecord();
            $banRecord->user_id = $userId;
            $banRecord->ban_type = $banType;
            $banRecord->ban_reason = $reason;
            $banRecord->ban_source = $source;
            $banRecord->risk_score = $userScore ? $userScore['total_score'] : 0;
            $banRecord->start_time = $now;
            $banRecord->end_time = $duration > 0 ? $now + $duration : null;
            $banRecord->duration = $duration;
            $banRecord->status = 'active';
            $banRecord->admin_id = $adminId;
            $banRecord->save();
            
            // 4. 加入黑名单
            $this->addToBlacklist('user', $userId, $reason, $duration);
            
            // 5. 冻结用户金币账户
            $this->freezeUserAccount($userId);
            
            // 6. 清除用户登录Token
            $this->invalidateUserTokens($userId);
            
            // 7. 记录日志
            Log::info("User {$userId} banned. Reason: {$reason}, Duration: {$duration}");
            
            Db::commit();
            
            return [
                'success' => true,
                'ban_id' => $banRecord->id,
                'user_id' => $userId,
                'ban_type' => $banType,
                'duration' => $duration,
                'expire_time' => $duration > 0 ? $now + $duration : null,
            ];
            
        } catch (Exception $e) {
            Db::rollback();
            Log::error('AutoBan execute error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 解封用户
     * 
     * @param int $userId 用户ID
     * @param string $reason 解封原因
     * @param int $adminId 管理员ID
     * @return array
     */
    public function releaseBan($userId, $reason, $adminId = null)
    {
        Db::startTrans();
        try {
            $now = time();
            
            // 1. 更新用户风控状态
            $userScore = UserRiskScore::where('user_id', $userId)->lock(true)->find();
            if ($userScore) {
                $userScore->status = 'normal';
                $userScore->ban_expire_time = null;
                // 解封时降低风险分
                $userScore->total_score = intval($userScore['total_score'] * 0.5);
                $userScore->risk_level = (new RiskControlService())->getRiskLevel($userScore['total_score']);
                $userScore->save();
            }
            
            // 2. 更新用户表状态
            Db::name('user')->where('id', $userId)->update([
                'status' => 'normal',
                'updatetime' => $now,
            ]);
            
            // 3. 更新封禁记录状态
            BanRecord::where('user_id', $userId)
                ->where('status', 'active')
                ->update([
                    'status' => 'released',
                    'release_time' => $now,
                    'release_reason' => $reason,
                    'release_admin_id' => $adminId,
                ]);
            
            // 4. 从黑名单移除
            $this->removeFromBlacklist('user', $userId);
            
            // 5. 解冻用户账户
            $this->unfreezeUserAccount($userId);
            
            Db::commit();
            
            return [
                'success' => true,
                'user_id' => $userId,
            ];
            
        } catch (Exception $e) {
            Db::rollback();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 临时解封(申诉期间)
     * 
     * @param int $userId 用户ID
     * @param int $appealId 申诉ID
     * @param int $duration 临时解封时长(秒)
     * @return array
     */
    public function temporaryRelease($userId, $appealId, $duration = 86400)
    {
        Db::startTrans();
        try {
            $now = time();
            
            // 临时解封用户
            Db::name('user')->where('id', $userId)->update([
                'status' => 'normal',
                'updatetime' => $now,
            ]);
            
            // 更新风控状态
            UserRiskScore::where('user_id', $userId)->update([
                'status' => 'normal',
                'freeze_expire_time' => $now + $duration,
            ]);
            
            // 记录临时解封
            Db::name('appeal_temporary_release')->insert([
                'user_id' => $userId,
                'appeal_id' => $appealId,
                'release_time' => $now,
                'expire_time' => $now + $duration,
            ]);
            
            Db::commit();
            
            return [
                'success' => true,
                'expire_time' => $now + $duration,
            ];
            
        } catch (Exception $e) {
            Db::rollback();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 批量封号
     * 
     * @param array $userIds 用户ID列表
     * @param string $reason 封号原因
     * @param int $duration 持续时间
     * @param int $adminId 管理员ID
     * @return array
     */
    public function batchBan($userIds, $reason, $duration = 0, $adminId = null)
    {
        $results = [
            'total' => count($userIds),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];
        
        foreach ($userIds as $userId) {
            $result = $this->executeBan($userId, $reason, $duration, 'manual', $adminId);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            
            $results['details'][] = [
                'user_id' => $userId,
                'success' => $result['success'],
                'message' => $result['message'] ?? '',
            ];
        }
        
        return $results;
    }
    
    /**
     * 自动处理过期封禁
     * 
     * @return array
     */
    public function processExpiredBans()
    {
        $now = time();
        $results = [
            'released' => 0,
            'still_banned' => 0,
            'errors' => 0,
        ];
        
        // 查找已过期但状态仍为active的封禁记录
        $expiredBans = BanRecord::where('status', 'active')
            ->where('ban_type', self::BAN_TYPE_TEMPORARY)
            ->whereNotNull('end_time')
            ->where('end_time', '<=', $now)
            ->select();
        
        foreach ($expiredBans as $ban) {
            $result = $this->releaseBan($ban['user_id'], '封禁期满自动解封');
            
            if ($result['success']) {
                $results['released']++;
            } else {
                $results['errors']++;
            }
        }
        
        // 查找仍在封禁期的记录
        $stillBanned = BanRecord::where('status', 'active')
            ->where('end_time', '>', $now)
            ->count();
        
        $results['still_banned'] = $stillBanned;
        
        return $results;
    }
    
    /**
     * 处理冻结过期
     * 
     * @return array
     */
    public function processExpiredFreezes()
    {
        $now = time();
        $results = [
            'released' => 0,
            'errors' => 0,
        ];
        
        $expiredFreezes = UserRiskScore::where('status', 'frozen')
            ->whereNotNull('freeze_expire_time')
            ->where('freeze_expire_time', '<=', $now)
            ->select();
        
        foreach ($expiredFreezes as $userScore) {
            Db::startTrans();
            try {
                $userScore->status = 'normal';
                $userScore->freeze_expire_time = null;
                $userScore->save();
                
                Db::name('user')->where('id', $userScore['user_id'])->update([
                    'status' => 'normal',
                    'updatetime' => $now,
                ]);
                
                $results['released']++;
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $results['errors']++;
            }
        }
        
        return $results;
    }
    
    /**
     * 获取封号历史
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function getBanHistory($userId)
    {
        $records = BanRecord::where('user_id', $userId)
            ->order('createtime', 'desc')
            ->select();
        
        return $records ? $records->toArray() : [];
    }
    
    /**
     * 检查用户封号状态
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function checkBanStatus($userId)
    {
        $userScore = UserRiskScore::where('user_id', $userId)->find();
        
        if (!$userScore) {
            return [
                'is_banned' => false,
                'is_frozen' => false,
                'status' => 'normal',
            ];
        }
        
        $now = time();
        
        // 检查封禁状态
        $isBanned = false;
        $banExpireTime = null;
        $banReason = '';
        
        if ($userScore['status'] == 'banned') {
            if ($userScore['ban_expire_time'] === null) {
                // 永久封禁
                $isBanned = true;
            } elseif ($userScore['ban_expire_time'] > $now) {
                // 临时封禁未过期
                $isBanned = true;
                $banExpireTime = $userScore['ban_expire_time'];
            }
            
            if ($isBanned) {
                $banRecord = BanRecord::where('user_id', $userId)
                    ->where('status', 'active')
                    ->order('createtime', 'desc')
                    ->find();
                $banReason = $banRecord ? $banRecord['ban_reason'] : '';
            }
        }
        
        // 检查冻结状态
        $isFrozen = false;
        $freezeExpireTime = null;
        
        if ($userScore['status'] == 'frozen' && $userScore['freeze_expire_time'] > $now) {
            $isFrozen = true;
            $freezeExpireTime = $userScore['freeze_expire_time'];
        }
        
        return [
            'is_banned' => $isBanned,
            'is_frozen' => $isFrozen,
            'status' => $userScore['status'],
            'risk_score' => $userScore['total_score'],
            'risk_level' => $userScore['risk_level'],
            'ban_expire_time' => $banExpireTime,
            'ban_reason' => $banReason,
            'freeze_expire_time' => $freezeExpireTime,
            'violation_count' => $userScore['violation_count'],
        ];
    }
    
    /**
     * 计算封禁时长
     * 
     * @param int $violationCount 违规次数
     * @return int 秒数
     */
    protected function calculateBanDuration($violationCount)
    {
        if ($violationCount >= 5) {
            return 0; // 永久封禁
        } elseif ($violationCount >= 3) {
            return self::BAN_DURATIONS['third_offense'];
        } elseif ($violationCount >= 2) {
            return self::BAN_DURATIONS['second_offense'];
        } else {
            return self::BAN_DURATIONS['first_offense'];
        }
    }
    
    /**
     * 添加到黑名单
     */
    protected function addToBlacklist($type, $value, $reason, $duration)
    {
        $exists = RiskBlacklist::where('type', $type)
            ->where('value', (string)$value)
            ->find();
        
        if ($exists) {
            return;
        }
        
        $blacklist = new RiskBlacklist();
        $blacklist->type = $type;
        $blacklist->value = (string)$value;
        $blacklist->reason = $reason;
        $blacklist->source = 'auto';
        $blacklist->risk_score = 0;
        $blacklist->expire_time = $duration > 0 ? time() + $duration : null;
        $blacklist->save();
    }
    
    /**
     * 从黑名单移除
     */
    protected function removeFromBlacklist($type, $value)
    {
        RiskBlacklist::where('type', $type)
            ->where('value', (string)$value)
            ->delete();
    }
    
    /**
     * 冻结用户账户
     */
    protected function freezeUserAccount($userId)
    {
        // 冻结金币账户
        Db::name('coin_account')->where('user_id', $userId)->update([
            'status' => 'frozen',
            'updatetime' => time(),
        ]);
        
        // 取消所有待处理的提现
        Db::name('withdraw_order')->where('user_id', $userId)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'reject_reason' => '账户已被封禁',
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 解冻用户账户
     */
    protected function unfreezeUserAccount($userId)
    {
        Db::name('coin_account')->where('user_id', $userId)->update([
            'status' => 'normal',
            'updatetime' => time(),
        ]);
    }
    
    /**
     * 使所有Token失效
     */
    protected function invalidateUserTokens($userId)
    {
        // 清除Redis中的Token
        $cache = Cache::getInstance();
        $pattern = 'token:user:' . $userId . ':*';
        
        // 这里需要根据实际Token存储方式实现
        // 如果使用Redis，可以直接删除所有相关key
    }
    
    /**
     * 获取封号统计数据
     * 
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public function getBanStatistics($startDate, $endDate)
    {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        // 封禁统计
        $totalBans = BanRecord::where('createtime', 'between', [$startTimestamp, $endTimestamp])->count();
        $autoBans = BanRecord::where('ban_source', 'auto')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->count();
        $manualBans = BanRecord::where('ban_source', 'manual')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->count();
        $permanentBans = BanRecord::where('ban_type', self::BAN_TYPE_PERMANENT)
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->count();
        $temporaryBans = BanRecord::where('ban_type', self::BAN_TYPE_TEMPORARY)
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->count();
        
        // 解封统计
        $totalReleases = BanRecord::where('status', 'released')
            ->where('release_time', 'between', [$startTimestamp, $endTimestamp])
            ->count();
        
        // 按原因分组统计
        $reasonStats = BanRecord::field('ban_reason, COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('ban_reason')
            ->select();
        
        return [
            'total_bans' => $totalBans,
            'auto_bans' => $autoBans,
            'manual_bans' => $manualBans,
            'permanent_bans' => $permanentBans,
            'temporary_bans' => $temporaryBans,
            'total_releases' => $totalReleases,
            'reason_stats' => $reasonStats ? $reasonStats->toArray() : [],
        ];
    }
    
    /**
     * 风险用户预警
     * 
     * @return array
     */
    public function getRiskUserAlerts()
    {
        $alerts = [];
        
        // 高风险用户
        $highRiskUsers = UserRiskScore::where('risk_level', 'high')
            ->where('status', 'normal')
            ->order('total_score', 'desc')
            ->limit(20)
            ->select();
        
        if ($highRiskUsers) {
            $alerts['high_risk'] = $highRiskUsers->toArray();
        }
        
        // 危险用户
        $dangerousUsers = UserRiskScore::where('risk_level', 'dangerous')
            ->where('status', 'normal')
            ->order('total_score', 'desc')
            ->limit(20)
            ->select();
        
        if ($dangerousUsers) {
            $alerts['dangerous'] = $dangerousUsers->toArray();
        }
        
        // 近期频繁违规用户
        $recentViolators = RiskLog::field('user_id, COUNT(*) as violation_count')
            ->where('createtime', '>', time() - 86400)
            ->group('user_id')
            ->having('violation_count >= 5')
            ->order('violation_count', 'desc')
            ->limit(20)
            ->select();
        
        if ($recentViolators) {
            $alerts['recent_violators'] = $recentViolators->toArray();
        }
        
        return $alerts;
    }
}
