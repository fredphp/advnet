<?php

namespace app\common\model;

use think\Model;

/**
 * 视频观看记录模型
 */
class VideoWatchRecord extends Model
{
    // 表名
    protected $name = 'video_watch_record';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'is_completed_text',
        'reward_status_text'
    ];
    
    // 完成状态
    public static $isCompletedList = [
        0 => '未完成',
        1 => '已完成'
    ];
    
    // 奖励状态
    public static $rewardStatusList = [
        0 => '未领取',
        1 => '已领取',
        2 => '已失效'
    ];
    
    public function getIsCompletedTextAttr($value, $data)
    {
        return self::$isCompletedList[$data['is_completed']] ?? '';
    }
    
    public function getRewardStatusTextAttr($value, $data)
    {
        return self::$rewardStatusList[$data['reward_status']] ?? '';
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id')->setEagerlyType(0);
    }
    
    /**
     * 关联视频
     */
    public function video()
    {
        return $this->belongsTo('Video', 'video_id')->setEagerlyType(0);
    }
    
    /**
     * 关联合集
     */
    public function collection()
    {
        return $this->belongsTo('VideoCollection', 'collection_id')->setEagerlyType(0);
    }
    
    /**
     * 关联规则
     */
    public function rule()
    {
        return $this->belongsTo('VideoRewardRule', 'reward_rule_id')->setEagerlyType(0);
    }
    
    /**
     * 获取或创建观看记录
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     * @param int $collectionId 合集ID
     * @return VideoWatchRecord
     */
    public static function getOrCreate($userId, $videoId, $collectionId = null)
    {
        $record = self::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->find();
        
        if (!$record) {
            $record = new self();
            $record->user_id = $userId;
            $record->video_id = $videoId;
            $record->collection_id = $collectionId;
            $record->watch_duration = 0;
            $record->watch_progress = 0;
            $record->watch_count = 0;
            $record->last_position = 0;
            $record->is_completed = 0;
            $record->reward_status = 0;
            $record->reward_coin = 0;
            $record->date_key = date('Y-m-d');
            $record->save();
        }
        
        return $record;
    }
    
    /**
     * 更新观看进度
     * @param int $duration 本次观看时长
     * @param int $progress 本次观看进度
     * @param int $position 当前位置
     * @return bool
     */
    public function updateProgress($duration, $progress, $position)
    {
        $this->watch_duration = $this->watch_duration + $duration;
        $this->watch_progress = max($this->watch_progress, $progress);
        $this->last_position = $position;
        $this->date_key = date('Y-m-d');
        $this->updatetime = time();
        
        return $this->save();
    }
    
    /**
     * 标记为已完成
     * @return bool
     */
    public function markAsCompleted()
    {
        if ($this->is_completed == 0) {
            $this->is_completed = 1;
            $this->complete_time = time();
            return $this->save();
        }
        return true;
    }
    
    /**
     * 标记奖励已领取
     * @param float $coin 奖励金币
     * @param int $ruleId 规则ID
     * @return bool
     */
    public function markRewarded($coin, $ruleId)
    {
        $this->reward_status = 1;
        $this->reward_coin = $coin;
        $this->reward_rule_id = $ruleId;
        $this->reward_time = time();
        return $this->save();
    }
}
