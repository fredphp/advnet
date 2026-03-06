<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 每日佣金统计模型
 */
class DailyCommissionStat extends Model
{
    // 表名
    protected $name = 'daily_commission_stat';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取或创建每日统计
     * @param string $date 日期 Y-m-d
     * @return DailyCommissionStat
     */
    public static function getOrCreate($date)
    {
        $stat = self::where('date_key', $date)->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->date_key = $date;
            $stat->total_commission = 0;
            $stat->total_coin = 0;
            $stat->total_count = 0;
            $stat->user_count = 0;
            $stat->level1_count = 0;
            $stat->level2_count = 0;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 添加统计数据
     * @param InviteCommissionLog $log 分佣记录
     * @return bool
     */
    public static function addStat($log)
    {
        $date = date('Y-m-d', $log->createtime);
        $stat = self::getOrCreate($date);
        
        // 使用乐观锁更新
        $affected = self::where('id', $stat->id)
            ->update([
                'total_commission' => Db::raw('total_commission + ' . $log->commission_amount),
                'total_coin' => Db::raw('total_coin + ' . $log->coin_amount),
                'total_count' => Db::raw('total_count + 1'),
                'level1_count' => $log->level == 1 ? Db::raw('level1_count + 1') : Db::raw('level1_count'),
                'level2_count' => $log->level == 2 ? Db::raw('level2_count + 1') : Db::raw('level2_count'),
                'updatetime' => time(),
            ]);
        
        return $affected > 0;
    }
    
    /**
     * 更新来源统计
     * @param string $date 日期
     * @param string $sourceType 来源类型
     * @param float $amount 金额
     */
    public static function updateSourceStat($date, $sourceType, $amount)
    {
        $stat = self::getOrCreate($date);
        
        $field = "{$sourceType}_commission";
        
        if (in_array($field, ['withdraw_commission', 'video_commission', 'red_packet_commission', 'game_commission'])) {
            self::where('id', $stat->id)
                ->update([
                    $field => Db::raw("$field + $amount"),
                    'updatetime' => time(),
                ]);
        }
    }
    
    /**
     * 获取日期范围统计
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public static function getRangeStat($startDate, $endDate)
    {
        return self::where('date_key', '>=', $startDate)
            ->where('date_key', '<=', $endDate)
            ->order('date_key', 'asc')
            ->select();
    }
    
    /**
     * 获取今日统计
     * @return DailyCommissionStat
     */
    public static function getToday()
    {
        return self::getOrCreate(date('Y-m-d'));
    }
    
    /**
     * 获取平台总统计
     * @return array
     */
    public static function getTotalStat()
    {
        return self::field([
            'SUM(total_commission) as total_commission',
            'SUM(total_coin) as total_coin',
            'SUM(total_count) as total_count',
            'SUM(level1_count) as level1_count',
            'SUM(level2_count) as level2_count',
        ])->find();
    }
}
