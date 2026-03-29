<?php

namespace app\common\model;

use think\Model;

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
