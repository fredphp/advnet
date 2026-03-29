<?php

namespace app\common\model;

use think\Model;

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
