<?php

namespace app\common\model;

use think\Model;

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
