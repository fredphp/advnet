<?php

namespace app\common\model;

use think\Model;

/**
 * 红包金额配置模型
 * 用于配置新用户红包金额区间和不同今日领取金额对应的额度区间
 */
class RedPacketAmountConfig extends Model
{
    // 表名
    protected $name = 'red_packet_amount_config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'status_text',
        'config_type_text',
        'today_range_text',
        'reward_range_text'
    ];

    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '禁用'
    ];

    // 配置类型列表
    public static $configTypeList = [
        'new_user' => '新用户红包',
        'base_amount' => '基础额度',
        'accumulate_amount' => '累加额度'
    ];

    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    public function getConfigTypeTextAttr($value, $data)
    {
        return isset($data['config_type']) ? self::$configTypeList[$data['config_type']] ?? '' : '';
    }

    public function getTodayRangeTextAttr($value, $data)
    {
        if (!isset($data['config_type'])) {
            return '';
        }
        
        if ($data['config_type'] === 'new_user') {
            return '-';
        }
        
        $minAmount = isset($data['min_today_amount']) ? number_format($data['min_today_amount']) : '0';
        $maxAmount = isset($data['max_today_amount']) ? $data['max_today_amount'] : 0;
        
        if ($maxAmount > 0) {
            return $minAmount . ' - ' . number_format($maxAmount);
        } else {
            return $minAmount . '以上';
        }
    }

    public function getRewardRangeTextAttr($value, $data)
    {
        $minReward = isset($data['min_reward']) ? number_format($data['min_reward']) : '0';
        $maxReward = isset($data['max_reward']) ? number_format($data['max_reward']) : '0';
        
        return $minReward . ' - ' . $maxReward;
    }

    /**
     * 获取新用户红包金额区间
     * @return array
     */
    public static function getNewUserAmountRange()
    {
        $config = self::where('config_type', 'new_user')
            ->where('status', 'normal')
            ->order('weigh', 'desc')
            ->find();

        if (!$config) {
            // 默认值
            return ['min' => 5000, 'max' => 15000];
        }

        return ['min' => $config->min_reward, 'max' => $config->max_reward];
    }

    /**
     * 根据今日领取金额获取基础额度区间
     * @param int $todayAmount 今日已领取金额（金币）
     * @return array
     */
    public static function getBaseAmountRange($todayAmount)
    {
        $config = self::where('config_type', 'base_amount')
            ->where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($query) use ($todayAmount) {
                $query->where('max_today_amount', '>=', $todayAmount)
                    ->whereOr('max_today_amount', 0);
            })
            ->order('weigh', 'desc')
            ->find();

        if (!$config) {
            // 默认值
            return ['min' => 3000, 'max' => 5000];
        }

        return ['min' => $config->min_reward, 'max' => $config->max_reward];
    }

    /**
     * 根据今日领取金额获取累加额度区间
     * @param int $todayAmount 今日已领取金额（金币）
     * @return array
     */
    public static function getAccumulateAmountRange($todayAmount)
    {
        $config = self::where('config_type', 'accumulate_amount')
            ->where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($query) use ($todayAmount) {
                $query->where('max_today_amount', '>=', $todayAmount)
                    ->whereOr('max_today_amount', 0);
            })
            ->order('weigh', 'desc')
            ->find();

        if (!$config) {
            // 默认值
            return ['min' => 1500, 'max' => 3000];
        }

        return ['min' => $config->min_reward, 'max' => $config->max_reward];
    }

    /**
     * 在区间内随机生成金额
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
}
