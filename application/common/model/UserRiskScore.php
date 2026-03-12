<?php

namespace app\common\model;

use think\Model;

/**
 * 用户风险评分模型
 */
class UserRiskScore extends Model
{
    // 表名
    protected $name = 'user_risk_score';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 风险等级
    const LEVEL_LOW = 'low';           // 低风险
    const LEVEL_MEDIUM = 'medium';     // 中风险
    const LEVEL_HIGH = 'high';         // 高风险
    const LEVEL_DANGEROUS = 'dangerous'; // 危险
    
    // 状态
    const STATUS_NORMAL = 'normal';    // 正常
    const STATUS_FROZEN = 'frozen';    // 冻结
    const STATUS_BANNED = 'banned';    // 封禁
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建用户风险评分记录
     */
    public static function getOrCreate($userId)
    {
        $record = self::where('user_id', $userId)->find();
        
        if (!$record) {
            $record = new self();
            $record->user_id = $userId;
            $record->total_score = 0;
            $record->risk_level = self::LEVEL_LOW;
            $record->status = self::STATUS_NORMAL;
            $record->violation_count = 0;
            $record->save();
        }
        
        return $record;
    }
    
    /**
     * 增加风险分
     */
    public function addScore($score, $reason = '')
    {
        $this->total_score = $this->total_score + $score;
        $this->violation_count = $this->violation_count + 1;
        
        // 更新风险等级
        $this->risk_level = $this->calculateRiskLevel($this->total_score);
        
        $this->save();
        
        return $this;
    }
    
    /**
     * 减少风险分
     */
    public function reduceScore($score)
    {
        $this->total_score = max(0, $this->total_score - $score);
        $this->risk_level = $this->calculateRiskLevel($this->total_score);
        $this->save();
        
        return $this;
    }
    
    /**
     * 计算风险等级
     */
    protected function calculateRiskLevel($score)
    {
        if ($score >= 700) {
            return self::LEVEL_DANGEROUS;
        } elseif ($score >= 300) {
            return self::LEVEL_HIGH;
        } elseif ($score >= 100) {
            return self::LEVEL_MEDIUM;
        } else {
            return self::LEVEL_LOW;
        }
    }
    
    /**
     * 获取风险等级列表
     */
    public static function getRiskLevelList()
    {
        return [
            self::LEVEL_LOW => '低风险',
            self::LEVEL_MEDIUM => '中风险',
            self::LEVEL_HIGH => '高风险',
            self::LEVEL_DANGEROUS => '危险',
        ];
    }
    
    /**
     * 获取状态列表
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_NORMAL => '正常',
            self::STATUS_FROZEN => '冻结',
            self::STATUS_BANNED => '封禁',
        ];
    }
}
