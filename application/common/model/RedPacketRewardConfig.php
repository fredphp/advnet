<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 红包奖励配置模型
 * 统一配置：时间段 + 今日金额限制 + 奖励金额
 * 
 * 匹配逻辑：
 * 1. 分别获取时间段配置和今日金额配置
 * 2. 计算两个区间的交集
 * 3. 如果交集无效，使用时间配置（更严格）
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
        'accumulate_reward_range_text',
        'new_user_base_range_text',
        'new_user_accumulate_range_text'
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

    public function getNewUserBaseRangeTextAttr($value, $data)
    {
        $min = isset($data['new_user_base_min']) ? number_format($data['new_user_base_min']) : '0';
        $max = isset($data['new_user_base_max']) ? number_format($data['new_user_base_max']) : '0';
        
        return $min . ' - ' . $max;
    }

    public function getNewUserAccumulateRangeTextAttr($value, $data)
    {
        $min = isset($data['new_user_accumulate_min']) ? number_format($data['new_user_accumulate_min']) : '0';
        $max = isset($data['new_user_accumulate_max']) ? number_format($data['new_user_accumulate_max']) : '0';
        
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
     * 计算两个区间的交集
     * @param array $range1 ['min' => x, 'max' => y]
     * @param array $range2 ['min' => x, 'max' => y]
     * @return array|null 返回交集区间，如果没有交集返回null
     */
    public static function intersectRanges($range1, $range2)
    {
        $min = max($range1['min'], $range2['min']);
        $max = min($range1['max'], $range2['max']);
        
        if ($min > $max) {
            return null; // 没有交集
        }
        
        return ['min' => $min, 'max' => $max];
    }

    /**
     * 获取时间段配置
     * @param int $hour 当前小时
     * @return object|null
     */
    public static function getTimeConfig($hour)
    {
        return self::where('status', 'normal')
            ->where('start_hour', '<=', $hour)
            ->where('end_hour', '>', $hour)
            ->where('min_today_amount', 0)  // 无今日金额限制
            ->where('max_today_amount', 0)
            ->order('weigh', 'desc')
            ->find();
    }

    /**
     * 获取今日金额配置
     * @param int $todayAmount 今日已领取金额
     * @return object|null
     */
    public static function getTodayAmountConfig($todayAmount)
    {
        return self::where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($q) use ($todayAmount) {
                $q->where('max_today_amount', '>=', $todayAmount)
                  ->whereOr('max_today_amount', 0);
            })
            ->where('start_hour', 0)  // 无时间限制
            ->where('end_hour', 24)
            ->order('weigh', 'desc')
            ->find();
    }

    /**
     * 获取基础奖励区间（考虑时间与金额的交集）
     * 
     * 匹配逻辑：
     * 1. 获取时间段配置的奖励区间
     * 2. 获取今日金额配置的奖励区间
     * 3. 计算交集
     * 4. 如果交集无效，使用时间配置（更严格）
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时（null则使用当前时间）
     * @param bool $isNewUser 是否新用户
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getBaseRewardRange($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 获取时间段配置
        $timeConfig = self::getTimeConfig($hour);
        
        // 获取今日金额配置
        $amountConfig = self::getTodayAmountConfig($todayAmount);

        // 获取最高限制
        $maxLimit = self::getMaxRewardLimit();

        // 默认值
        $defaultTimeRange = $isNewUser ? ['min' => 5000, 'max' => 10000] : ['min' => 2000, 'max' => 4000];
        $defaultAmountRange = $isNewUser ? ['min' => 5000, 'max' => 10000] : ['min' => 4000, 'max' => 6000];

        // 提取时间配置的奖励区间
        if ($timeConfig) {
            $timeRange = $isNewUser 
                ? ['min' => $timeConfig->new_user_base_min, 'max' => min($timeConfig->new_user_base_max, $maxLimit)]
                : ['min' => $timeConfig->base_min_reward, 'max' => min($timeConfig->base_max_reward, $maxLimit)];
        } else {
            $timeRange = $defaultTimeRange;
        }

        // 提取今日金额配置的奖励区间
        if ($amountConfig) {
            $amountRange = $isNewUser 
                ? ['min' => $amountConfig->new_user_base_min, 'max' => min($amountConfig->new_user_base_max, $maxLimit)]
                : ['min' => $amountConfig->base_min_reward, 'max' => min($amountConfig->base_max_reward, $maxLimit)];
        } else {
            $amountRange = $defaultAmountRange;
        }

        // 计算交集
        $intersection = self::intersectRanges($timeRange, $amountRange);

        if ($intersection) {
            // 有交集，使用交集
            return $intersection;
        } else {
            // 没有交集，使用时间配置（更严格）
            return $timeRange;
        }
    }

    /**
     * 获取累加奖励区间（考虑时间与金额的交集）
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getAccumulateRewardRange($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 获取时间段配置
        $timeConfig = self::getTimeConfig($hour);
        
        // 获取今日金额配置
        $amountConfig = self::getTodayAmountConfig($todayAmount);

        // 获取最高限制
        $maxLimit = self::getMaxRewardLimit();

        // 默认值
        $defaultTimeRange = $isNewUser ? ['min' => 2000, 'max' => 4000] : ['min' => 500, 'max' => 1500];
        $defaultAmountRange = $isNewUser ? ['min' => 2000, 'max' => 4000] : ['min' => 2000, 'max' => 4000];

        // 提取时间配置的奖励区间
        if ($timeConfig) {
            $timeRange = $isNewUser 
                ? ['min' => $timeConfig->new_user_accumulate_min, 'max' => min($timeConfig->new_user_accumulate_max, $maxLimit)]
                : ['min' => $timeConfig->accumulate_min_reward, 'max' => min($timeConfig->accumulate_max_reward, $maxLimit)];
        } else {
            $timeRange = $defaultTimeRange;
        }

        // 提取今日金额配置的奖励区间
        if ($amountConfig) {
            $amountRange = $isNewUser 
                ? ['min' => $amountConfig->new_user_accumulate_min, 'max' => min($amountConfig->new_user_accumulate_max, $maxLimit)]
                : ['min' => $amountConfig->accumulate_min_reward, 'max' => min($amountConfig->accumulate_max_reward, $maxLimit)];
        } else {
            $amountRange = $defaultAmountRange;
        }

        // 计算交集
        $intersection = self::intersectRanges($timeRange, $amountRange);

        if ($intersection) {
            // 有交集，使用交集
            return $intersection;
        } else {
            // 没有交集，使用时间配置（更严格）
            return $timeRange;
        }
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
     * 获取完整的奖励配置信息（用于调试和展示）
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function getFullConfig($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        $timeConfig = self::getTimeConfig($hour);
        $amountConfig = self::getTodayAmountConfig($todayAmount);
        $maxLimit = self::getMaxRewardLimit();

        $baseRange = self::getBaseRewardRange($todayAmount, $hour, $isNewUser);
        $accumulateRange = self::getAccumulateRewardRange($todayAmount, $hour, $isNewUser);

        return [
            'time_config' => $timeConfig ? $timeConfig->name : '默认',
            'amount_config' => $amountConfig ? $amountConfig->name : '默认',
            'time_range' => $timeConfig ? $timeConfig->time_range_text : '全天',
            'today_amount_range' => $amountConfig ? $amountConfig->today_amount_range_text : '不限制',
            'base' => $baseRange,
            'accumulate' => $accumulateRange,
            'max_reward_limit' => $maxLimit,
            'current_hour' => $hour,
            'today_amount' => $todayAmount,
            'is_new_user' => $isNewUser
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
}
