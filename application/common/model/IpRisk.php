<?php

namespace app\common\model;

use think\Model;

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
