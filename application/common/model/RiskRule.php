<?php

namespace app\common\model;

use think\Model;

/**
 * 风控规则模型
 */
class RiskRule extends Model
{
    // 表名
    protected $name = 'risk_rule';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 规则类型
    const TYPE_VIDEO = 'video';           // 视频相关
    const TYPE_WITHDRAW = 'withdraw';     // 提现相关
    const TYPE_INVITE = 'invite';         // 邀请相关
    const TYPE_TASK = 'task';             // 任务相关
    const TYPE_BEHAVIOR = 'behavior';     // 行为相关
    
    // 处理动作
    const ACTION_WARN = 'warn';           // 警告
    const ACTION_FREEZE = 'freeze';       // 冻结
    const ACTION_BAN = 'ban';             // 封禁
    
    /**
     * 获取启用的规则列表
     */
    public static function getEnabledRules()
    {
        return self::where('enabled', 1)
            ->order('sort', 'asc')
            ->select();
    }
    
    /**
     * 根据规则代码获取规则
     */
    public static function getByCode($code)
    {
        return self::where('rule_code', $code)
            ->where('enabled', 1)
            ->find();
    }
    
    /**
     * 根据类型获取规则
     */
    public static function getByType($type)
    {
        return self::where('rule_type', $type)
            ->where('enabled', 1)
            ->order('sort', 'asc')
            ->select();
    }
}
