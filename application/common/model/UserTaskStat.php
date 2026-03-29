<?php

namespace app\common\model;

use think\Model;

/**
 * 用户任务统计模型
 */
class UserTaskStat extends Model
{
    // 表名
    protected $name = 'user_task_stat';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取或创建今日统计
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
            $stat->receive_count = 0;
            $stat->complete_count = 0;
            $stat->reward_count = 0;
            $stat->reward_coin = 0;
            $stat->reject_count = 0;
            $stat->download_count = 0;
            $stat->mini_program_count = 0;
            $stat->game_count = 0;
            $stat->video_count = 0;
            $stat->share_count = 0;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 增加统计
     */
    public function increment($field, $value = 1, $extraCoin = 0)
    {
        if (isset($this->$field)) {
            $this->$field = $this->$field + $value;
        }
        if ($extraCoin > 0 && $field == 'reward_count') {
            $this->reward_coin = $this->reward_coin + $extraCoin;
        }
        $this->save();
    }
}
