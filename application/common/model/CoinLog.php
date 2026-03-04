<?php

namespace app\common\model;

use think\Model;

/**
 * 金币流水模型
 */
class CoinLog extends Model
{
    // 表名 (基础表名，实际使用按月分表)
    protected $name = 'coin_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 流水类型
    public static $typeList = [
        // 收入类型
        'register_reward' => '注册奖励',
        'video_watch' => '观看视频',
        'video_share' => '分享视频',
        'task_reward' => '任务奖励',
        'sign_in' => '签到奖励',
        'invite_level1' => '一级邀请奖励',
        'invite_level2' => '二级邀请奖励',
        'commission_level1' => '一级佣金',
        'commission_level2' => '二级佣金',
        'red_packet' => '红包奖励',
        'game_reward' => '游戏奖励',
        'admin_add' => '后台增加',
        'withdraw_return' => '提现退回',
        // 支出类型
        'withdraw' => '提现',
        'withdraw_fee' => '提现手续费',
        'admin_reduce' => '后台扣减',
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取当月表名
     * @return string
     */
    public static function getCurrentTable()
    {
        return 'coin_log_' . date('Ym');
    }
    
    /**
     * 获取指定月份表名
     * @param string $date 日期 Y-m 或 Y-m-d
     * @return string
     */
    public static function getTableByDate($date)
    {
        return 'coin_log_' . date('Ym', strtotime($date));
    }
}
