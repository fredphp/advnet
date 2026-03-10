<?php

namespace app\common\model;

use think\Model;

/**
 * 视频模型
 */
class Video extends Model
{
    protected $name = 'video';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_OFFLINE = 0;
    const STATUS_ONLINE = 1;
    
    /**
     * 关联合集
     */
    public function collections()
    {
        return $this->belongsToMany('VideoCollection', 'video_collection_item', 'collection_id', 'video_id');
    }
}

/**
 * 视频合集模型
 */
class VideoCollection extends Model
{
    protected $name = 'video_collection';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联视频
     */
    public function videos()
    {
        return $this->belongsToMany('Video', 'video_collection_item', 'video_id', 'collection_id');
    }
}

/**
 * 视频观看记录模型
 */
class VideoWatchRecord extends Model
{
    protected $name = 'video_watch_record';
    
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
    
    /**
     * 关联视频
     */
    public function video()
    {
        return $this->belongsTo('Video', 'video_id');
    }
}

/**
 * 视频奖励规则模型
 */
class VideoRewardRule extends Model
{
    protected $name = 'video_reward_rule';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}

/**
 * 红包任务模型
 */
class RedPacketTask extends Model
{
    protected $name = 'red_packet_task';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REVOKED = 'revoked';
    
    // 类型常量
    const TYPE_NORMAL = 'normal';
    const TYPE_NEW_USER = 'new_user';
    const TYPE_DAILY = 'daily';
    const TYPE_ACTIVITY = 'activity';
}

/**
 * 任务参与记录模型
 */
class TaskParticipation extends Model
{
    protected $name = 'task_participation';
    
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
    
    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }
}

/**
 * 提现订单模型
 */
class WithdrawOrder extends Model
{
    protected $name = 'withdraw_order';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return 'WD' . date('YmdHis') . rand(100000, 999999);
    }
}

/**
 * 邀请关系模型
 */
class InviteRelation extends Model
{
    protected $name = 'invite_relation';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    /**
     * 邀请人
     */
    public function inviter()
    {
        return $this->belongsTo('User', 'inviter_id');
    }
    
    /**
     * 被邀请人
     */
    public function invitee()
    {
        return $this->belongsTo('User', 'invitee_id');
    }
}

/**
 * 邀请分佣日志模型
 */
class InviteCommissionLog extends Model
{
    protected $name = 'invite_commission_log';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 分佣来源
    const SOURCE_WITHDRAW = 'withdraw';
    const SOURCE_VIDEO = 'video';
    const SOURCE_REDPACKET = 'redpacket';
    const SOURCE_GAME = 'game';
}

/**
 * 邀请分佣配置模型
 */
class InviteCommissionConfig extends Model
{
    protected $name = 'invite_commission_config';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}

/**
 * 用户邀请统计模型
 */
class UserInviteStat extends Model
{
    protected $name = 'user_invite_stat';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}

/**
 * 金币账户模型
 */
class CoinAccount extends Model
{
    protected $name = 'coin_account';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建账户
     */
    public static function getOrCreate($userId)
    {
        $account = self::where('user_id', $userId)->find();
        
        if (!$account) {
            $account = new self();
            $account->user_id = $userId;
            $account->balance = 0;
            $account->frozen = 0;
            $account->total_income = 0;
            $account->total_expense = 0;
            $account->save();
        }
        
        return $account;
    }
}

/**
 * 金币流水模型
 */
class CoinLog extends Model
{
    protected $name = 'coin_log';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 类型常量
    const TYPE_VIDEO = 'video';             // 视频奖励
    const TYPE_TASK = 'task';               // 任务奖励
    const TYPE_REDPACKET = 'redpacket';     // 红包奖励
    const TYPE_INVITE = 'invite';           // 邀请奖励
    const TYPE_COMMISSION = 'commission';   // 分佣收入
    const TYPE_WITHDRAW = 'withdraw';       // 提现扣除
    const TYPE_WITHDRAW_RETURN = 'withdraw_return'; // 提现退还
    const TYPE_ADMIN_RECHARGE = 'admin_recharge';   // 后台充值
    const TYPE_ADMIN_DEDUCT = 'admin_deduct';       // 后台扣除
    const TYPE_NEW_USER = 'new_user';       // 新用户奖励
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}

/**
 * 用户模型
 */
class User extends Model
{
    protected $name = 'user';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_NORMAL = 'normal';
    const STATUS_FROZEN = 'frozen';
    const STATUS_BANNED = 'banned';
    
    /**
     * 金币账户
     */
    public function coinAccount()
    {
        return $this->hasOne('CoinAccount', 'user_id');
    }
    
    /**
     * 邀请统计
     */
    public function inviteStat()
    {
        return $this->hasOne('UserInviteStat', 'user_id');
    }
    
    /**
     * 风险评分
     */
    public function riskScore()
    {
        return $this->hasOne('UserRiskScore', 'user_id');
    }
}
