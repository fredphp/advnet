<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;

/**
 * 红包奖励配置模型
 * 
 * 金额生成逻辑：
 * 1. 基础金额：先根据当天累计金额匹配配置并随机生成 → 再检查时间段配置
 *    - 如果基础金额在时间段范围内，直接使用
 *    - 如果基础金额 > 时间段最大值，在时间段范围内重新生成
 *    - 如果基础金额 < 时间段最小值，直接使用基础金额（不再时间段范围生成）
 * 
 * 2. 累加金额：同样的逻辑
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

    // ==================== 缓存相关常量 ====================
    
    const CACHE_KEY_ALL_CONFIGS = 'red_packet:reward_config:all';
    const CACHE_KEY_TIME_CONFIGS = 'red_packet:reward_config:time';
    const CACHE_KEY_AMOUNT_CONFIGS = 'red_packet:reward_config:amount';
    const CACHE_KEY_MAX_LIMIT = 'red_packet:reward_config:max_limit';
    const CACHE_TTL = 604800;

    // ==================== Redis 连接管理 ====================

    protected static function getRedis()
    {
        static $redis = null;
        static $checked = false;
        
        if ($checked) {
            return $redis;
        }
        
        $checked = true;
        
        try {
            $handler = Cache::store('redis');
            if ($handler) {
                $redis = $handler->handler();
                if ($redis instanceof \Redis) {
                    return $redis;
                }
            }
        } catch (\Exception $e) {
        }
        
        try {
            $redis = new \Redis();
            if ($redis->connect('127.0.0.1', 6379, 3)) {
                return $redis;
            }
        } catch (\Exception $e) {
        }
        
        $redis = null;
        return null;
    }

    protected static function isRedisAvailable()
    {
        return self::getRedis() !== null;
    }

    // ==================== 缓存管理方法 ====================

    public static function refreshCache()
    {
        $redis = self::getRedis();
        
        try {
            $allConfigs = self::where('status', 'normal')
                ->order('id', 'desc')
                ->select()
                ->toArray();
            
            if ($redis) {
                $redis->set(self::CACHE_KEY_ALL_CONFIGS, json_encode($allConfigs), self::CACHE_TTL);
                
                // 构建时间段配置索引（按小时分组）
                $timeConfigs = [];
                foreach ($allConfigs as $config) {
                    if ($config['min_today_amount'] == 0 && $config['max_today_amount'] == 0) {
                        $startHour = intval($config['start_hour']);
                        $endHour = intval($config['end_hour']);
                        for ($h = $startHour; $h < $endHour; $h++) {
                            if (!isset($timeConfigs[$h]) || $config['id'] > ($timeConfigs[$h]['id'] ?? 0)) {
                                $timeConfigs[$h] = $config;
                            }
                        }
                    }
                }
                $redis->set(self::CACHE_KEY_TIME_CONFIGS, json_encode($timeConfigs), self::CACHE_TTL);
                
                // 构建金额配置列表
                $amountConfigs = [];
                foreach ($allConfigs as $config) {
                    if ($config['start_hour'] == 0 && $config['end_hour'] == 24) {
                        $amountConfigs[] = $config;
                    }
                }
                usort($amountConfigs, function($a, $b) {
                    return ($b['id'] ?? 0) - ($a['id'] ?? 0);
                });
                $redis->set(self::CACHE_KEY_AMOUNT_CONFIGS, json_encode($amountConfigs), self::CACHE_TTL);
                
                // 缓存最高金额限制
                $maxLimit = Db::name('config')
                    ->where('name', 'red_packet_max_reward')
                    ->value('value');
                $maxLimit = $maxLimit ? intval($maxLimit) : 10000;
                $redis->set(self::CACHE_KEY_MAX_LIMIT, $maxLimit, self::CACHE_TTL);
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function clearCache()
    {
        $redis = self::getRedis();
        
        if ($redis) {
            try {
                $redis->del(self::CACHE_KEY_ALL_CONFIGS);
                $redis->del(self::CACHE_KEY_TIME_CONFIGS);
                $redis->del(self::CACHE_KEY_AMOUNT_CONFIGS);
                $redis->del(self::CACHE_KEY_MAX_LIMIT);
            } catch (\Exception $e) {
            }
        }
        
        return true;
    }

    protected static function getAllConfigsFromCache()
    {
        $redis = self::getRedis();
        
        try {
            if ($redis) {
                $cached = $redis->get(self::CACHE_KEY_ALL_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    return json_decode($cached, true);
                }
                
                self::refreshCache();
                $cached = $redis->get(self::CACHE_KEY_ALL_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    return json_decode($cached, true);
                }
            }
        } catch (\Exception $e) {
        }
        
        return self::where('status', 'normal')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    // ==================== 属性访问器 ====================

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

    // ==================== 核心业务方法 ====================

    /**
     * 获取红包最高金额限制
     * @return int
     */
    public static function getMaxRewardLimit()
    {
        $redis = self::getRedis();
        
        try {
            if ($redis) {
                $cached = $redis->get(self::CACHE_KEY_MAX_LIMIT);
                
                if ($cached !== false && $cached !== null) {
                    return intval($cached);
                }
            }
            
            $config = Db::name('config')
                ->where('name', 'red_packet_max_reward')
                ->value('value');
            
            $maxLimit = $config ? intval($config) : 10000;
            
            if ($redis) {
                $redis->set(self::CACHE_KEY_MAX_LIMIT, $maxLimit, self::CACHE_TTL);
            }
            
            return $maxLimit;
            
        } catch (\Exception $e) {
            return 10000;
        }
    }

    /**
     * 获取时间段配置
     * @param int $hour 当前小时
     * @return array|null
     */
    public static function getTimeConfig($hour)
    {
        $redis = self::getRedis();
        
        try {
            if ($redis) {
                $cached = $redis->get(self::CACHE_KEY_TIME_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    $timeConfigs = json_decode($cached, true);
                    if (isset($timeConfigs[$hour])) {
                        return $timeConfigs[$hour];
                    }
                }
                
                self::refreshCache();
                $cached = $redis->get(self::CACHE_KEY_TIME_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    $timeConfigs = json_decode($cached, true);
                    return $timeConfigs[$hour] ?? null;
                }
            }
        } catch (\Exception $e) {
        }
        
        return self::where('status', 'normal')
            ->where('start_hour', '<=', $hour)
            ->where('end_hour', '>', $hour)
            ->where('min_today_amount', 0)
            ->where('max_today_amount', 0)
            ->order('id', 'desc')
            ->find();
    }

    /**
     * 获取今日金额配置
     * @param int $todayAmount 今日已领取金额
     * @return array|null
     */
    public static function getTodayAmountConfig($todayAmount)
    {
        $redis = self::getRedis();
        
        try {
            if ($redis) {
                $cached = $redis->get(self::CACHE_KEY_AMOUNT_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    $amountConfigs = json_decode($cached, true);
                    
                    foreach ($amountConfigs as $config) {
                        $minAmount = intval($config['min_today_amount']);
                        $maxAmount = intval($config['max_today_amount']);
                        
                        if ($minAmount <= $todayAmount) {
                            if ($maxAmount == 0 || $maxAmount >= $todayAmount) {
                                return $config;
                            }
                        }
                    }
                    
                    return null;
                }
                
                self::refreshCache();
                $cached = $redis->get(self::CACHE_KEY_AMOUNT_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    $amountConfigs = json_decode($cached, true);
                    
                    foreach ($amountConfigs as $config) {
                        $minAmount = intval($config['min_today_amount']);
                        $maxAmount = intval($config['max_today_amount']);
                        
                        if ($minAmount <= $todayAmount) {
                            if ($maxAmount == 0 || $maxAmount >= $todayAmount) {
                                return $config;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
        
        return self::where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($q) use ($todayAmount) {
                $q->where('max_today_amount', '>=', $todayAmount)
                  ->whereOr('max_today_amount', 0);
            })
            ->where('start_hour', 0)
            ->where('end_hour', 24)
            ->order('id', 'desc')
            ->find();
    }

    /**
     * 在区间内随机生成金额
     * @param array|null $range ['min' => xx, 'max' => xx]
     * @return int
     */
    public static function randomAmount($range)
    {
        if ($range === null || !isset($range['min'])) {
            return 0;
        }
        
        $min = intval($range['min']);
        $max = intval($range['max'] ?? $min);

        if ($min >= $max) {
            return $min;
        }

        return mt_rand($min, $max);
    }

    /**
     * 从配置中提取基础金额区间
     * @param array|null $config 配置数据
     * @param bool $isNewUser 是否新用户
     * @return array|null
     */
    protected static function extractBaseRange($config, $isNewUser = false)
    {
        if (!$config) {
            return null;
        }
        
        $maxLimit = self::getMaxRewardLimit();
        
        if ($isNewUser) {
            return [
                'min' => intval($config['new_user_base_min'] ?? 0),
                'max' => min(intval($config['new_user_base_max'] ?? 0), $maxLimit)
            ];
        }
        
        return [
            'min' => intval($config['base_min_reward'] ?? 0),
            'max' => min(intval($config['base_max_reward'] ?? 0), $maxLimit)
        ];
    }

    /**
     * 从配置中提取累加金额区间
     * @param array|null $config 配置数据
     * @param bool $isNewUser 是否新用户
     * @return array|null
     */
    protected static function extractAccumulateRange($config, $isNewUser = false)
    {
        if (!$config) {
            return null;
        }
        
        $maxLimit = self::getMaxRewardLimit();
        
        if ($isNewUser) {
            return [
                'min' => intval($config['new_user_accumulate_min'] ?? 0),
                'max' => min(intval($config['new_user_accumulate_max'] ?? 0), $maxLimit)
            ];
        }
        
        return [
            'min' => intval($config['accumulate_min_reward'] ?? 0),
            'max' => min(intval($config['accumulate_max_reward'] ?? 0), $maxLimit)
        ];
    }

    /**
     * 生成基础金额
     * 
     * 逻辑：
     * 1. 先根据当天累计金额配置随机生成基础金额
     * 2. 再检查时间段配置：
     *    - 如果基础金额在时间段范围内[min, max]，直接使用
     *    - 如果基础金额 > 时间段最大值，在时间段范围内重新生成
     *    - 如果基础金额 < 时间段最小值，直接使用基础金额
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return int 生成的基础金额
     */
    public static function generateBaseAmount($todayAmount, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 1. 根据当天累计金额获取配置
        $amountConfig = self::getTodayAmountConfig($todayAmount);
        
        // 2. 提取基础金额区间
        $amountBaseRange = self::extractBaseRange($amountConfig, $isNewUser);
        
        // 如果没有金额配置，返回0
        if ($amountBaseRange === null) {
            return 0;
        }
        
        // 3. 随机生成基础金额
        $baseAmount = self::randomAmount($amountBaseRange);
        
        // 4. 获取时间段配置
        $timeConfig = self::getTimeConfig($hour);
        $timeBaseRange = self::extractBaseRange($timeConfig, $isNewUser);
        
        // 如果没有时间段配置，直接返回基础金额
        if ($timeBaseRange === null) {
            return $baseAmount;
        }
        
        $timeMin = $timeBaseRange['min'];
        $timeMax = $timeBaseRange['max'];
        
        // 5. 检查基础金额是否在时间范围内
        if ($baseAmount >= $timeMin && $baseAmount <= $timeMax) {
            // 在范围内，直接返回
            return $baseAmount;
        } elseif ($baseAmount > $timeMax) {
            // 超过时间范围最大值，在时间范围内重新生成
            return self::randomAmount($timeBaseRange);
        } else {
            // 低于时间范围最小值，直接返回基础金额
            return $baseAmount;
        }
    }

    /**
     * 生成累加金额
     * 
     * 逻辑与 generateBaseAmount 相同
     * 
     * @param int $todayAmount 今日已领取金额
     * @param int|null $hour 当前小时
     * @param bool $isNewUser 是否新用户
     * @return int 生成的累加金额
     */
    public static function generateAccumulateAmount($todayAmount, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        // 1. 根据当天累计金额获取配置
        $amountConfig = self::getTodayAmountConfig($todayAmount);
        
        // 2. 提取累加金额区间
        $amountAccRange = self::extractAccumulateRange($amountConfig, $isNewUser);
        
        // 如果没有金额配置，返回0
        if ($amountAccRange === null) {
            return 0;
        }
        
        // 3. 随机生成累加金额
        $accAmount = self::randomAmount($amountAccRange);
        
        // 4. 获取时间段配置
        $timeConfig = self::getTimeConfig($hour);
        $timeAccRange = self::extractAccumulateRange($timeConfig, $isNewUser);
        
        // 如果没有时间段配置，直接返回累加金额
        if ($timeAccRange === null) {
            return $accAmount;
        }
        
        $timeMin = $timeAccRange['min'];
        $timeMax = $timeAccRange['max'];
        
        // 5. 检查累加金额是否在时间范围内
        if ($accAmount >= $timeMin && $accAmount <= $timeMax) {
            // 在范围内，直接返回
            return $accAmount;
        } elseif ($accAmount > $timeMax) {
            // 超过时间范围最大值，在时间范围内重新生成
            return self::randomAmount($timeAccRange);
        } else {
            // 低于时间范围最小值，直接返回累加金额
            return $accAmount;
        }
    }

    /**
     * 获取完整的奖励配置信息（用于调试和展示）
     */
    public static function getFullConfig($todayAmount = 0, $hour = null, $isNewUser = false)
    {
        if ($hour === null) {
            $hour = intval(date('H'));
        }

        $timeConfig = self::getTimeConfig($hour);
        $amountConfig = self::getTodayAmountConfig($todayAmount);
        $maxLimit = self::getMaxRewardLimit();

        return [
            'time_config' => $timeConfig ? ($timeConfig['name'] ?? '默认') : '无',
            'amount_config' => $amountConfig ? ($amountConfig['name'] ?? '默认') : '无',
            'time_range' => $timeConfig ? self::formatTimeRange($timeConfig) : '无',
            'today_amount_range' => $amountConfig ? self::formatAmountRange($amountConfig) : '无',
            'time_base_range' => self::extractBaseRange($timeConfig, $isNewUser),
            'time_accumulate_range' => self::extractAccumulateRange($timeConfig, $isNewUser),
            'amount_base_range' => self::extractBaseRange($amountConfig, $isNewUser),
            'amount_accumulate_range' => self::extractAccumulateRange($amountConfig, $isNewUser),
            'max_reward_limit' => $maxLimit,
            'current_hour' => $hour,
            'today_amount' => $todayAmount,
            'is_new_user' => $isNewUser,
            'redis_available' => self::isRedisAvailable(),
        ];
    }

    protected static function formatTimeRange($config)
    {
        $startHour = intval($config['start_hour'] ?? 0);
        $endHour = intval($config['end_hour'] ?? 24);
        
        if ($startHour == 0 && $endHour == 24) {
            return '全天';
        }
        
        return str_pad($startHour, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00';
    }

    protected static function formatAmountRange($config)
    {
        $minAmount = intval($config['min_today_amount'] ?? 0);
        $maxAmount = intval($config['max_today_amount'] ?? 0);
        
        if ($minAmount == 0 && $maxAmount == 0) {
            return '不限制';
        }
        
        if ($maxAmount == 0) {
            return number_format($minAmount) . '以上';
        }
        
        return number_format($minAmount) . ' - ' . number_format($maxAmount);
    }

    public static function getAllConfigs()
    {
        return self::getAllConfigsFromCache();
    }
}
