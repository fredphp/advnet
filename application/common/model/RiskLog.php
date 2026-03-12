<?php

namespace app\common\model;

use think\Model;

/**
 * 风险日志模型
 */
class RiskLog extends Model
{
    // 表名
    protected $name = 'risk_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 日志类型
    const TYPE_RISK_DETECTED = 'risk_detected';     // 风险检测
    const TYPE_RULE_TRIGGERED = 'rule_triggered';   // 规则触发
    const TYPE_SCORE_CHANGED = 'score_changed';     // 分数变化
    const TYPE_BAN_EXECUTED = 'ban_executed';       // 执行封禁
    const TYPE_BAN_RELEASED = 'ban_released';       // 解封
    const TYPE_FREEZE = 'freeze';                   // 冻结
    const TYPE_UNFREEZE = 'unfreeze';               // 解冻
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 记录日志
     */
    public static function record($userId, $type, $data = [])
    {
        $log = new self();
        $log->user_id = $userId;
        $log->log_type = $type;
        $log->risk_score = $data['risk_score'] ?? 0;
        $log->rule_code = $data['rule_code'] ?? '';
        $log->rule_name = $data['rule_name'] ?? '';
        $log->trigger_value = $data['trigger_value'] ?? '';
        $log->threshold = $data['threshold'] ?? 0;
        $log->action = $data['action'] ?? '';
        $log->detail = isset($data['detail']) ? json_encode($data['detail'], JSON_UNESCAPED_UNICODE) : '';
        $log->ip = request()->ip() ?? '';
        $log->user_agent = request()->header('user-agent') ?? '';
        $log->save();
        
        return $log;
    }
    
    /**
     * 获取用户日志
     */
    public static function getUserLogs($userId, $limit = 20)
    {
        return self::where('user_id', $userId)
            ->order('createtime', 'desc')
            ->limit($limit)
            ->select();
    }
    
    /**
     * 获取类型列表
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_RISK_DETECTED => '风险检测',
            self::TYPE_RULE_TRIGGERED => '规则触发',
            self::TYPE_SCORE_CHANGED => '分数变化',
            self::TYPE_BAN_EXECUTED => '执行封禁',
            self::TYPE_BAN_RELEASED => '解封',
            self::TYPE_FREEZE => '冻结',
            self::TYPE_UNFREEZE => '解冻',
        ];
    }
}
