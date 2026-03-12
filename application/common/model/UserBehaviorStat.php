<?php

namespace app\common\model;

use think\Model;

/**
 * 用户行为统计模型
 */
class UserBehaviorStat extends Model
{
    protected $name = 'user_behavior_stat';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取或创建用户某天的统计
     */
    public static function getOrCreate($userId, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $stat = self::where('user_id', $userId)
            ->where('stat_date', $date)
            ->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->user_id = $userId;
            $stat->stat_date = $date;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 增加视频观看统计
     */
    public static function incVideoWatch($userId, $duration, $coinEarned)
    {
        $stat = self::getOrCreate($userId);
        
        $stat->inc('video_watch_count');
        $stat->setInc('video_watch_duration', $duration);
        $stat->setInc('video_coin_earned', $coinEarned);
        $stat->save();
    }
}
