<?php

namespace app\common\model;

use think\Model;

/**
 * 红包时间段配置模型
 * 用于配置不同时间段的红包奖励金额区间
 */
class RedPacketTimeConfig extends Model
{
    // 表名
    protected $name = 'red_packet_time_config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'status_text',
        'time_range_text',
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
        $startHour = isset($data['start_hour']) ? str_pad($data['start_hour'], 2, '0', STR_PAD_LEFT) : '00';
        $endHour = isset($data['end_hour']) ? str_pad($data['end_hour'], 2, '0', STR_PAD_LEFT) : '24';
        
        return $startHour . ':00 - ' . $endHour . ':00';
    }

    public function getBaseRewardRangeTextAttr($value, $data)
    {
        $min = isset($data['base_min_reward']) ? number_format($data['base_min_reward']) : '0';
        $max = isset($data['base_max_reward']) ? number_format($data['base_max_reward']) : '0';
        
        return $min . ' - ' . $max . ' 金币';
    }

    public function getAccumulateRewardRangeTextAttr($value, $data)
    {
        $min = isset($data['accumulate_min_reward']) ? number_format($data['accumulate_min_reward']) : '0';
        $max = isset($data['accumulate_max_reward']) ? number_format($data['accumulate_max_reward']) : '0';
        
        return $min . ' - ' . $max . ' 金币';
    }

    /**
     * 根据当前时间获取配置
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @return array|null
     */
    public static function getConfigByHour($hour = null)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 查找当前时间段对应的配置
        $config = self::where('status', 'normal')
            ->where('start_hour', '<=', $hour)
            ->where('end_hour', '>', $hour)
            ->order('weigh', 'desc')
            ->find();

        return $config;
    }

    /**
     * 获取老用户基础奖励区间
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getBaseRewardRange($hour = null)
    {
        $config = self::getConfigByHour($hour);

        if (!$config) {
            // 没有匹配的时间段配置，返回默认值
            return [
                'min' => 2000,
                'max' => 4000
            ];
        }

        return [
            'min' => $config->base_min_reward,
            'max' => $config->base_max_reward
        ];
    }

    /**
     * 获取老用户累加奖励区间
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getAccumulateRewardRange($hour = null)
    {
        $config = self::getConfigByHour($hour);

        if (!$config) {
            // 没有匹配的时间段配置，返回默认值
            return [
                'min' => 500,
                'max' => 1500
            ];
        }

        return [
            'min' => $config->accumulate_min_reward,
            'max' => $config->accumulate_max_reward
        ];
    }

    /**
     * 获取新用户基础奖励区间
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getNewUserBaseRewardRange($hour = null)
    {
        $config = self::getConfigByHour($hour);

        if (!$config) {
            // 没有匹配的时间段配置，返回默认值
            return [
                'min' => 5000,
                'max' => 10000
            ];
        }

        return [
            'min' => $config->new_user_base_min,
            'max' => $config->new_user_base_max
        ];
    }

    /**
     * 获取新用户累加奖励区间
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @return array ['min' => 下限, 'max' => 上限]
     */
    public static function getNewUserAccumulateRewardRange($hour = null)
    {
        $config = self::getConfigByHour($hour);

        if (!$config) {
            // 没有匹配的时间段配置，返回默认值
            return [
                'min' => 2000,
                'max' => 4000
            ];
        }

        return [
            'min' => $config->new_user_accumulate_min,
            'max' => $config->new_user_accumulate_max
        ];
    }

    /**
     * 获取完整的奖励配置（包含新老用户）
     * @param int|null $hour 指定小时(0-23), null则使用当前时间
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function getFullConfig($hour = null, $isNewUser = false)
    {
        $config = self::getConfigByHour($hour);

        if (!$config) {
            // 返回默认配置
            if ($isNewUser) {
                return [
                    'base' => ['min' => 5000, 'max' => 10000],
                    'accumulate' => ['min' => 2000, 'max' => 4000],
                    'name' => '默认配置',
                    'time_range' => '全天'
                ];
            } else {
                return [
                    'base' => ['min' => 2000, 'max' => 4000],
                    'accumulate' => ['min' => 500, 'max' => 1500],
                    'name' => '默认配置',
                    'time_range' => '全天'
                ];
            }
        }

        if ($isNewUser) {
            return [
                'base' => [
                    'min' => $config->new_user_base_min,
                    'max' => $config->new_user_base_max
                ],
                'accumulate' => [
                    'min' => $config->new_user_accumulate_min,
                    'max' => $config->new_user_accumulate_max
                ],
                'name' => $config->name,
                'time_range' => $config->time_range_text
            ];
        } else {
            return [
                'base' => [
                    'min' => $config->base_min_reward,
                    'max' => $config->base_max_reward
                ],
                'accumulate' => [
                    'min' => $config->accumulate_min_reward,
                    'max' => $config->accumulate_max_reward
                ],
                'name' => $config->name,
                'time_range' => $config->time_range_text
            ];
        }
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
     * 获取所有有效的时间段配置
     * @return array
     */
    public static function getAllConfigs()
    {
        return self::where('status', 'normal')
            ->order('start_hour', 'asc')
            ->order('weigh', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 检查时间段是否重叠
     * @param int $startHour 开始小时
     * @param int $endHour 结束小时
     * @param int|null $excludeId 排除的配置ID（编辑时使用）
     * @return bool true=有重叠, false=无重叠
     */
    public static function hasOverlap($startHour, $endHour, $excludeId = null)
    {
        $query = self::where('status', 'normal')
            ->where(function ($q) use ($startHour, $endHour) {
                // 新的时间段开始时间在已有时间段内
                $q->whereOr(function ($q2) use ($startHour) {
                    $q2->where('start_hour', '<=', $startHour)
                       ->where('end_hour', '>', $startHour);
                });
                // 新的时间段结束时间在已有时间段内
                $q->whereOr(function ($q2) use ($endHour) {
                    $q2->where('start_hour', '<', $endHour)
                       ->where('end_hour', '>=', $endHour);
                });
                // 新的时间段包含已有时间段
                $q->whereOr(function ($q2) use ($startHour, $endHour) {
                    $q2->where('start_hour', '>=', $startHour)
                       ->where('end_hour', '<=', $endHour);
                });
            });

        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->count() > 0;
    }
}
