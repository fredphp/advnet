<?php

namespace app\common\model;

use think\Model;

/**
 * 风控规则模型
 */
class RiskRule extends Model
{
    protected $name = 'risk_rule';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 类型枚举
    const TYPE_VIDEO = 'video';
    const TYPE_TASK = 'task';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_REDPACKET = 'redpacket';
    const TYPE_INVITE = 'invite';
    const TYPE_GLOBAL = 'global';
    
    // 动作枚举
    const ACTION_WARN = 'warn';
    const ACTION_BLOCK = 'block';
    const ACTION_FREEZE = 'freeze';
    const ACTION_BAN = 'ban';
    
    /**
     * 获取所有启用的规则
     */
    public static function getEnabledRules()
    {
        return self::where('enabled', 1)
            ->order('level desc, score_weight desc')
            ->select();
    }
    
    /**
     * 按类型获取规则
     */
    public static function getRulesByType($type)
    {
        return self::where('enabled', 1)
            ->where('rule_type', $type)
            ->order('level desc, score_weight desc')
            ->select();
    }
}

/**
 * 用户风险评分模型
 */
class UserRiskScore extends Model
{
    protected $name = 'user_risk_score';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 风险等级
    const LEVEL_SAFE = 'safe';
    const LEVEL_LOW = 'low';
    const LEVEL_MEDIUM = 'medium';
    const LEVEL_HIGH = 'high';
    const LEVEL_DANGEROUS = 'dangerous';
    
    // 状态
    const STATUS_NORMAL = 'normal';
    const STATUS_FROZEN = 'frozen';
    const STATUS_BANNED = 'banned';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建用户风险评分
     */
    public static function getOrCreate($userId)
    {
        $score = self::where('user_id', $userId)->find();
        
        if (!$score) {
            $score = new self();
            $score->user_id = $userId;
            $score->total_score = 0;
            $score->risk_level = self::LEVEL_SAFE;
            $score->status = self::STATUS_NORMAL;
            $score->video_score = 0;
            $score->task_score = 0;
            $score->withdraw_score = 0;
            $score->redpacket_score = 0;
            $score->invite_score = 0;
            $score->global_score = 0;
            $score->violation_count = 0;
            $score->save();
        }
        
        return $score;
    }
}

/**
 * 风控日志模型
 */
class RiskLog extends Model
{
    protected $name = 'risk_log';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}

/**
 * IP风险模型
 */
class IpRisk extends Model
{
    protected $name = 'ip_risk';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 风险等级
    const LEVEL_SAFE = 'safe';
    const LEVEL_SUSPICIOUS = 'suspicious';
    const LEVEL_DANGEROUS = 'dangerous';
    const LEVEL_BLACKLIST = 'blacklist';
}

/**
 * 设备指纹模型
 */
class DeviceFingerprint extends Model
{
    protected $name = 'device_fingerprint';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 设备类型
    const TYPE_IOS = 'ios';
    const TYPE_ANDROID = 'android';
    const TYPE_WEB = 'web';
    const TYPE_OTHER = 'other';
    
    // 风险等级
    const LEVEL_SAFE = 'safe';
    const LEVEL_SUSPICIOUS = 'suspicious';
    const LEVEL_DANGEROUS = 'dangerous';
    const LEVEL_BLACKLIST = 'blacklist';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}

/**
 * 用户行为记录模型
 */
class UserBehavior extends Model
{
    protected $name = 'user_behavior';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 行为类型
    const TYPE_LOGIN = 'login';
    const TYPE_VIDEO_WATCH = 'video_watch';
    const TYPE_TASK_COMPLETE = 'task_complete';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_REDPACKET_GRAB = 'redpacket_grab';
    const TYPE_INVITE = 'invite';
    const TYPE_OTHER = 'other';
}

/**
 * 用户行为统计模型
 */
class UserBehaviorStat extends Model
{
    protected $name = 'user_behavior_stat';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取或创建用户某天的统计
     */
    public static function getOrCreate($userId, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $stat = self::where('user_id', $userId)
            ->where('stat_date', $date)
            ->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->user_id = $userId;
            $stat->stat_date = $date;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 增加视频观看统计
     */
    public static function incVideoWatch($userId, $duration, $coinEarned)
    {
        $stat = self::getOrCreate($userId);
        
        $stat->inc('video_watch_count');
        $stat->setInc('video_watch_duration', $duration);
        $stat->setInc('video_coin_earned', $coinEarned);
        $stat->save();
    }
}

/**
 * 封禁记录模型
 */
class BanRecord extends Model
{
    protected $name = 'ban_record';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 封禁类型
    const TYPE_TEMPORARY = 'temporary';
    const TYPE_PERMANENT = 'permanent';
    
    // 状态
    const STATUS_ACTIVE = 'active';
    const STATUS_RELEASED = 'released';
    const STATUS_EXPIRED = 'expired';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}

/**
 * 风控白名单模型
 */
class RiskWhitelist extends Model
{
    protected $name = 'risk_whitelist';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 类型
    const TYPE_USER = 'user';
    const TYPE_IP = 'ip';
    const TYPE_DEVICE = 'device';
}

/**
 * 风控黑名单模型
 */
class RiskBlacklist extends Model
{
    protected $name = 'risk_blacklist';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 类型
    const TYPE_USER = 'user';
    const TYPE_IP = 'ip';
    const TYPE_DEVICE = 'device';
}
