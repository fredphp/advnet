<?php

namespace app\common\model;

use think\Model;

/**
 * 用户风险评分模型
 */
class UserRiskScore extends Model
{
    protected $name = 'user_risk_score';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 风险等级
    const LEVEL_SAFE = 'safe';
    const LEVEL_LOW = 'low';
    const LEVEL_MEDIUM = 'medium';
    const LEVEL_HIGH = 'high';
    const LEVEL_DANGEROUS = 'dangerous';
    
    // 状态
    const STATUS_NORMAL = 'normal';
    const STATUS_FROZEN = 'frozen';
    const STATUS_BANNED = 'banned';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建用户风险评分
     */
    public static function getOrCreate($userId)
    {
        $score = self::where('user_id', $userId)->find();
        
        if (!$score) {
            $score = new self();
            $score->user_id = $userId;
            $score->total_score = 0;
            $score->risk_level = self::LEVEL_SAFE;
            $score->status = self::STATUS_NORMAL;
            $score->video_score = 0;
            $score->task_score = 0;
            $score->withdraw_score = 0;
            $score->redpacket_score = 0;
            $score->invite_score = 0;
            $score->global_score = 0;
            $score->violation_count = 0;
            $score->save();
        }
        
        return $score;
    }
}
