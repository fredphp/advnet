<?php

namespace app\common\model;

use think\Model;

/**
 * 防刷日志模型
 */
class AnticheatLog extends Model
{
    // 表名
    protected $name = 'anticheat_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 类型列表
    public static $typeList = [
        'abnormal_speed' => '异常观看速度',
        'hourly_watch_exceed' => '每小时观看超限',
        'high_risk_score' => '高风险评分',
        'ip_limit' => 'IP限制',
        'device_limit' => '设备限制',
        'repeated_watch' => '重复观看'
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 记录日志
     * @param int $userId
     * @param string $type
     * @param array $data
     * @param string $ip
     * @return AnticheatLog
     */
    public static function log($userId, $type, $data = [], $ip = null)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->type = $type;
        $log->data = json_encode($data);
        $log->ip = $ip;
        $log->save();
        
        return $log;
    }
}
