<?php

namespace app\common\model;

use think\Model;

/**
 * 红包金额配置模型
 * 用于配置新用户红包和不同领取金额区间的奖励额度
 * 
 * 配置类型：
 * - new_user: 新用户红包（独立配置）
 * - tier: 阶梯配置（包含基础额度和累加额度）
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
        'base_reward_range_text',
        'accumulate_reward_range_text'
    ];

    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '禁用'
    ];

    // 配置类型列表
    public static $configTypeList = [
        'new_user' => '新用户红包',
        'tier' => '阶梯配置'
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

    public function getBaseRewardRangeTextAttr($value, $data)
    {
        $minReward = isset($data['base_min_reward']) ? number_format($data['base_min_reward']) : '0';
        $maxReward = isset($data['base_max_reward']) ? number_format($data['base_max_reward']) : '0';
        
        return $minReward . ' - ' . $maxReward;
    }

    public function getAccumulateRewardRangeTextAttr($value, $data)
    {
        if (!isset($data['config_type']) || $data['config_type'] === 'new_user') {
            return '-';
        }
        
        $minReward = isset($data['accumulate_min_reward']) ? number_format($data['accumulate_min_reward']) : '0';
        $maxReward = isset($data['accumulate_max_reward']) ? number_format($data['accumulate_max_reward']) : '0';
        
        return $minReward . ' - ' . $maxReward;
    }

    /**
     * 获取新用户红包金额区间
     * @return array ['min' => 下限, 'max' => 上限, 'accumulate_min' => 累加下限, 'accumulate_max' => 累加上限]
     */
    public static function getNewUserAmountRange()
    {
        $config = self::where('config_type', 'new_user')
            ->where('status', 'normal')
            ->order('weigh', 'desc')
            ->find();

        if (!$config) {
            // 默认值
            return [
                'min' => 5000,
                'max' => 15000,
                'accumulate_min' => 2000,
                'accumulate_max' => 4000
            ];
        }

        return [
            'min' => $config->base_min_reward,
            'max' => $config->base_max_reward,
            'accumulate_min' => $config->accumulate_min_reward,
            'accumulate_max' => $config->accumulate_max_reward
        ];
    }

    /**
     * 根据今日领取金额获取阶梯配置
     * @param int $todayAmount 今日已领取金额（金币）
     * @return array ['base_min' => 基础下限, 'base_max' => 基础上限, 'accumulate_min' => 累加下限, 'accumulate_max' => 累加上限]
     */
    public static function getTierConfig($todayAmount)
    {
        $config = self::where('config_type', 'tier')
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
            return [
                'base_min' => 3000,
                'base_max' => 5000,
                'accumulate_min' => 1500,
                'accumulate_max' => 3000,
                'name' => '默认配置'
            ];
        }

        return [
            'base_min' => $config->base_min_reward,
            'base_max' => $config->base_max_reward,
            'accumulate_min' => $config->accumulate_min_reward,
            'accumulate_max' => $config->accumulate_max_reward,
            'name' => $config->name
        ];
    }

    /**
     * 获取基础额度区间（兼容旧方法名）
     * @param int $todayAmount 今日已领取金额（金币）
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getBaseAmountRange($todayAmount)
    {
        $config = self::getTierConfig($todayAmount);
        return ['min' => $config['base_min'], 'max' => $config['base_max']];
    }

    /**
     * 获取累加额度区间（兼容旧方法名）
     * @param int $todayAmount 今日已领取金额（金币）
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getAccumulateAmountRange($todayAmount)
    {
        $config = self::getTierConfig($todayAmount);
        return ['min' => $config['accumulate_min'], 'max' => $config['accumulate_max']];
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

    /**
     * 获取所有阶梯配置（用于前端展示）
     * @return array
     */
    public static function getAllTierConfigs()
    {
        return self::where('config_type', 'tier')
            ->where('status', 'normal')
            ->order('weigh', 'desc')
            ->select()
            ->toArray();
    }
}
