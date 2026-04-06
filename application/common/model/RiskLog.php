<?php

namespace app\common\model;

use think\Model;

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
