<?php

namespace app\common\model;

use think\Model;

/**
 * 封禁记录模型
 */
class BanRecord extends Model
{
    // 表名
    protected $name = 'ban_record';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 封禁类型
    const TYPE_TEMPORARY = 'temporary';   // 临时封禁
    const TYPE_PERMANENT = 'permanent';   // 永久封禁
    
    // 封禁来源
    const SOURCE_AUTO = 'auto';           // 自动封禁
    const SOURCE_MANUAL = 'manual';       // 手动封禁
    
    // 封禁状态
    const STATUS_ACTIVE = 'active';       // 生效中
    const STATUS_RELEASED = 'released';   // 已解封
    const STATUS_EXPIRED = 'expired';     // 已过期
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取生效中的封禁记录
     */
    public static function getActiveBans($userId)
    {
        $now = time();
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function($query) use ($now) {
                $query->whereNull('end_time')
                    ->whereOr('end_time', '>', $now);
            })
            ->select();
    }
    
    /**
     * 检查用户是否被封禁
     */
    public static function isBanned($userId)
    {
        $now = time();
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function($query) use ($now) {
                $query->whereNull('end_time')
                    ->whereOr('end_time', '>', $now);
            })
            ->count() > 0;
    }
    
    /**
     * 获取封禁记录详情
     */
    public static function getBanDetail($userId)
    {
        $now = time();
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function($query) use ($now) {
                $query->whereNull('end_time')
                    ->whereOr('end_time', '>', $now);
            })
            ->order('createtime', 'desc')
            ->find();
    }
}
