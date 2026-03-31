<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;

/**
 * 用户佣金统计模型
 */
class UserCommissionStat extends Model
{
    // 表名
    protected $name = 'user_commission_stat';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 缓存键前缀
    const CACHE_PREFIX = 'user_commission_stat:';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取或创建统计记录
     * @param int $userId 用户ID
     * @return UserCommissionStat
     */
    public static function getOrCreate($userId)
    {
        $stat = self::where('user_id', $userId)->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->user_id = $userId;
            $stat->total_commission = 0;
            $stat->total_coin = 0;
            $stat->level1_commission = 0;
            $stat->level2_commission = 0;
            $stat->withdraw_commission = 0;
            $stat->video_commission = 0;
            $stat->red_packet_commission = 0;
            $stat->game_commission = 0;
            $stat->other_commission = 0;
            $stat->today_commission = 0;
            $stat->today_coin = 0;
            $stat->yesterday_commission = 0;
            $stat->yesterday_coin = 0;
            $stat->week_commission = 0;
            $stat->month_commission = 0;
            $stat->pending_commission = 0;
            $stat->frozen_commission = 0;
            $stat->canceled_commission = 0;
            $stat->withdraw_count = 0;
            $stat->video_count = 0;
            $stat->red_packet_count = 0;
            $stat->game_count = 0;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 添加佣金记录并更新统计
     * @param InviteCommissionLog $log 分佣记录
     * @return bool
     */
    public function addCommission($log)
    {
        // 更新总金额
        $this->total_commission = $this->total_commission + $log->commission_amount;
        $this->total_coin = $this->total_coin + $log->coin_amount;
        
        // 更新层级统计
        if ($log->level == 1) {
            $this->level1_commission = $this->level1_commission + $log->commission_amount;
        } else {
            $this->level2_commission = $this->level2_commission + $log->commission_amount;
        }
        
        // 更新来源统计
        switch ($log->source_type) {
            case 'withdraw':
                $this->withdraw_commission = $this->withdraw_commission + $log->commission_amount;
                $this->withdraw_count = $this->withdraw_count + 1;
                break;
            case 'video':
                $this->video_commission = $this->video_commission + $log->commission_amount;
                $this->video_count = $this->video_count + 1;
                break;
            case 'red_packet':
                $this->red_packet_commission = $this->red_packet_commission + $log->commission_amount;
                $this->red_packet_count = $this->red_packet_count + 1;
                break;
            case 'game':
                $this->game_commission = $this->game_commission + $log->commission_amount;
                $this->game_count = $this->game_count + 1;
                break;
            default:
                $this->other_commission = $this->other_commission + $log->commission_amount;
        }
        
        // 更新今日统计
        $this->today_commission = $this->today_commission + $log->commission_amount;
        $this->today_coin = $this->today_coin + $log->coin_amount;
        
        // 更新本周统计
        $this->week_commission = $this->week_commission + $log->commission_amount;
        
        // 更新本月统计
        $this->month_commission = $this->month_commission + $log->commission_amount;
        
        $this->save();
        
        // 清除缓存
        $this->clearCache($this->user_id);
        
        return true;
    }
    
    /**
     * 增加待结算佣金
     * @param float $amount 金额
     */
    public function addPending($amount)
    {
        $this->pending_commission = $this->pending_commission + $amount;
        $this->save();
    }
    
    /**
     * 减少待结算佣金
     * @param float $amount 金额
     */
    public function reducePending($amount)
    {
        $this->pending_commission = max(0, $this->pending_commission - $amount);
        $this->save();
    }
    
    /**
     * 增加冻结佣金
     * @param float $amount 金额
     */
    public function addFrozen($amount)
    {
        $this->frozen_commission = $this->frozen_commission + $amount;
        $this->pending_commission = max(0, $this->pending_commission - $amount);
        $this->save();
    }
    
    /**
     * 增加取消佣金
     * @param float $amount 金额
     */
    public function addCanceled($amount)
    {
        $this->canceled_commission = $this->canceled_commission + $amount;
        $this->pending_commission = max(0, $this->pending_commission - $amount);
        $this->save();
    }
    
    /**
     * 重置每日统计
     * @return int 影响行数
     */
    public static function resetDaily()
    {
        return self::where('id', '>', 0)
            ->update([
                'yesterday_commission' => Db::raw('today_commission'),
                'yesterday_coin' => Db::raw('today_coin'),
                'today_commission' => 0,
                'today_coin' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 重置每周统计
     * @return int 影响行数
     */
    public static function resetWeekly()
    {
        return self::where('id', '>', 0)
            ->update([
                'week_commission' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 重置每月统计
     * @return int 影响行数
     */
    public static function resetMonthly()
    {
        return self::where('id', '>', 0)
            ->update([
                'month_commission' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 获取用户佣金概览
     * @param int $userId 用户ID
     * @return array
     */
    public static function getOverview($userId)
    {
        $stat = self::getOrCreate($userId);
        
        return [
            'total_commission' => $stat->total_commission,
            'total_coin' => $stat->total_coin,
            'level1_commission' => $stat->level1_commission,
            'level2_commission' => $stat->level2_commission,
            'withdraw_commission' => $stat->withdraw_commission,
            'video_commission' => $stat->video_commission,
            'red_packet_commission' => $stat->red_packet_commission,
            'game_commission' => $stat->game_commission,
            'today_commission' => $stat->today_commission,
            'today_coin' => $stat->today_coin,
            'yesterday_commission' => $stat->yesterday_commission,
            'week_commission' => $stat->week_commission,
            'month_commission' => $stat->month_commission,
            'pending_commission' => $stat->pending_commission,
            'frozen_commission' => $stat->frozen_commission,
            'withdraw_count' => $stat->withdraw_count,
            'video_count' => $stat->video_count,
            'red_packet_count' => $stat->red_packet_count,
            'game_count' => $stat->game_count,
        ];
    }
    
    /**
     * 获取佣金排行
     * @param string $type 排行类型: total/withdraw/video/red_packet
     * @param int $limit 限制条数
     * @return array
     */
    public static function getRanking($type = 'total', $limit = 100)
    {
        $field = $type == 'total' ? 'total_commission' : "{$type}_commission";
        
        return self::with(['user'])
            ->where($field, '>', 0)
            ->order($field, 'desc')
            ->limit($limit)
            ->select();
    }
    
    /**
     * 清除缓存
     * @param int $userId 用户ID
     */
    protected function clearCache($userId)
    {
        Cache::delete(self::CACHE_PREFIX . $userId);
    }
}
