<?php

namespace app\common\model;

use think\Model;

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
