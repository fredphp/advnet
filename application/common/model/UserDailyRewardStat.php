<?php

namespace app\common\model;

use think\Model;

/**
 * 用户每日收益统计模型
 */
class UserDailyRewardStat extends Model
{
    // 表名
    protected $name = 'user_daily_reward_stat';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建今日统计
     * @param int $userId
     * @return UserDailyRewardStat
     */
    public static function getToday($userId)
    {
        $today = date('Y-m-d');
        $stat = self::where('user_id', $userId)
            ->where('date_key', $today)
            ->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->user_id = $userId;
            $stat->date_key = $today;
            $stat->video_watch_count = 0;
            $stat->video_watch_duration = 0;
            $stat->video_reward_count = 0;
            $stat->video_reward_coin = 0;
            $stat->task_reward_coin = 0;
            $stat->other_reward_coin = 0;
            $stat->total_reward_coin = 0;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 增加视频奖励统计
     * @param float $coin
     * @return bool
     */
    public function addVideoReward($coin)
    {
        $this->video_reward_count = $this->video_reward_count + 1;
        $this->video_reward_coin = $this->video_reward_coin + $coin;
        $this->total_reward_coin = $this->total_reward_coin + $coin;
        return $this->save();
    }
    
    /**
     * 增加观看统计
     * @param int $duration
     * @return bool
     */
    public function addWatchRecord($duration)
    {
        $this->video_watch_count = $this->video_watch_count + 1;
        $this->video_watch_duration = $this->video_watch_duration + $duration;
        return $this->save();
    }
    
    /**
     * 获取今日视频奖励次数
     * @param int $userId
     * @return int
     */
    public static function getTodayVideoRewardCount($userId)
    {
        $stat = self::where('user_id', $userId)
            ->where('date_key', date('Y-m-d'))
            ->find();
        
        return $stat ? $stat->video_reward_count : 0;
    }
    
    /**
     * 获取今日视频奖励金币
     * @param int $userId
     * @return float
     */
    public static function getTodayVideoRewardCoin($userId)
    {
        $stat = self::where('user_id', $userId)
            ->where('date_key', date('Y-m-d'))
            ->find();
        
        return $stat ? $stat->video_reward_coin : 0;
    }
}
