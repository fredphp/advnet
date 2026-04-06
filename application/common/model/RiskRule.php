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
