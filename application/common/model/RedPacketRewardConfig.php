<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 红包奖励配置模型
 * 统一配置：时间段 + 今日金额限制 + 奖励金额
 */
class RedPacketRewardConfig extends Model
{
    // 表名
    protected $name = 'red_packet_reward_config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'status_text',
        'time_range_text',
        'today_amount_range_text',
        'base_reward_range_text',
        'accumulate_reward_range_text'
    ];

    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '禁用'
    ];

    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    public function getTimeRangeTextAttr($value, $data)
    {
        $startHour = isset($data['start_hour']) ? intval($data['start_hour']) : 0;
        $endHour = isset($data['end_hour']) ? intval($data['end_hour']) : 24;
        
        if ($startHour == 0 && $endHour == 24) {
            return '全天';
        }
        
        return str_pad($startHour, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00';
    }

    public function getTodayAmountRangeTextAttr($value, $data)
    {
        $minAmount = isset($data['min_today_amount']) ? intval($data['min_today_amount']) : 0;
        $maxAmount = isset($data['max_today_amount']) ? intval($data['max_today_amount']) : 0;
        
        if ($minAmount == 0 && $maxAmount == 0) {
            return '不限制';
        }
        
        if ($maxAmount == 0) {
            return number_format($minAmount) . '以上';
        }
        
        return number_format($minAmount) . ' - ' . number_format($maxAmount);
    }

    public function getBaseRewardRangeTextAttr($value, $data)
    {
        $min = isset($data['base_min_reward']) ? number_format($data['base_min_reward']) : '0';
        $max = isset($data['base_max_reward']) ? number_format($data['base_max_reward']) : '0';
        
        return $min . ' - ' . $max;
    }

    public function getAccumulateRewardRangeTextAttr($value, $data)
    {
        $min = isset($data['accumulate_min_reward']) ? number_format($data['accumulate_min_reward']) : '0';
        $max = isset($data['accumulate_max_reward']) ? number_format($data['accumulate_max_reward']) : '0';
        
        return $min . ' - ' . $max;
    }

    /**
     * 获取红包最高金额限制（从基础配置读取）
     * @return int
     */
    public static function getMaxRewardLimit()
    {
        // 从系统配置读取，默认10000
        $config = Db::name('config')
            ->where('name', 'red_packet_max_reward')
            ->value('value');
        
        return $config ? intval($config) : 10000;
    }

    /**
     * 获取匹配的奖励配置
     * 优先级：先匹配今日金额限制，再匹配时间段
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时（null则使用当前时间）
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function getMatchedConfig($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 第一步：先按今日金额限制筛选
        $query = self::where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($q) use ($todayAmount) {
                $q->where('max_today_amount', '>=', $todayAmount)
                  ->whereOr('max_today_amount', 0);
            });

        // 第二步：再按时间段筛选
        $query->where('start_hour', '<=', $hour)
            ->where('end_hour', '>', $hour);

        // 按权重排序，取第一个匹配的
        $config = $query->order('weigh', 'desc')->find();

        return $config;
    }

    /**
     * 获取基础奖励区间
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getBaseRewardRange($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        $config = self::getMatchedConfig($todayAmount, $hour, $isNewUser);

        if (!$config) {
            // 返回默认值
            return $isNewUser 
                ? ['min' => 5000, 'max' => 10000]
                : ['min' => 2000, 'max' => 4000];
        }

        return [
            'min' => $config->base_min_reward,
            'max' => min($config->base_max_reward, self::getMaxRewardLimit())
        ];
    }

    /**
     * 获取累加奖励区间
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getAccumulateRewardRange($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        $config = self::getMatchedConfig($todayAmount, $hour, $isNewUser);

        if (!$config) {
            // 返回默认值
            return $isNewUser 
                ? ['min' => 2000, 'max' => 4000]
                : ['min' => 500, 'max' => 1500];
        }

        return [
            'min' => $config->accumulate_min_reward,
            'max' => min($config->accumulate_max_reward, self::getMaxRewardLimit())
        ];
    }

    /**
     * 计算实际奖励金额（考虑最高限制）
     * 
     * @param int $currentAmount 当前已累计金额
     * @param int $addAmount 待累加金额
     * @return int 实际可累加金额
     */
    public static function calculateActualReward($currentAmount, $addAmount)
    {
        $maxLimit = self::getMaxRewardLimit();
        
        // 如果当前已达到或超过最高限制，不能再累加
        if ($currentAmount >= $maxLimit) {
            return 0;
        }
        
        // 计算可累加的最大金额
        $maxAddable = $maxLimit - $currentAmount;
        
        // 返回实际可累加金额
        return min($addAmount, $maxAddable);
    }

    /**
     * 在区间内随机生成金额
     * 
     * @param array $range ['min' => xx, 'max' => xx]
     * @return int
     */
    public static function randomAmount($range)
    {
        $min = $range['min'] ?? 0;
        $max = $range['max'] ?? $min;

        if ($min >= $max) {
            return $min;
        }

        return mt_rand($min, $max);
    }

    /**
     * 获取完整的奖励配置信息
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function getFullConfig($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        $config = self::getMatchedConfig($todayAmount, $hour, $isNewUser);
        $maxLimit = self::getMaxRewardLimit();

        if (!$config) {
            $defaultBase = $isNewUser ? ['min' => 5000, 'max' => 10000] : ['min' => 2000, 'max' => 4000];
            $defaultAcc = $isNewUser ? ['min' => 2000, 'max' => 4000] : ['min' => 500, 'max' => 1500];
            
            return [
                'name' => '默认配置',
                'time_range' => '全天',
                'today_amount_range' => '不限制',
                'base' => $defaultBase,
                'accumulate' => $defaultAcc,
                'max_reward_limit' => $maxLimit
            ];
        }

        return [
            'name' => $config->name,
            'time_range' => $config->time_range_text,
            'today_amount_range' => $config->today_amount_range_text,
            'base' => [
                'min' => $config->base_min_reward,
                'max' => min($config->base_max_reward, $maxLimit)
            ],
            'accumulate' => [
                'min' => $config->accumulate_min_reward,
                'max' => min($config->accumulate_max_reward, $maxLimit)
            ],
            'max_reward_limit' => $maxLimit
        ];
    }

    /**
     * 获取所有有效配置
     * @return array
     */
    public static function getAllConfigs()
    {
        return self::where('status', 'normal')
            ->order('weigh', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 检查配置是否冲突
     * @param int $startHour 开始小时
     * @param int $endHour 结束小时
     * @param int $minTodayAmount 今日金额下限
     * @param int $maxTodayAmount 今日金额上限
     * @param int|null $excludeId 排除的配置ID
     * @return bool true=有冲突, false=无冲突
     */
    public static function hasConflict($startHour, $endHour, $minTodayAmount, $maxTodayAmount, $excludeId = null)
    {
        // 这里不做严格限制，允许配置重叠，按权重优先匹配
        return false;
    }
}
