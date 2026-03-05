<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;

/**
 * 红包奖励配置模型
 * 统一配置：时间段 + 今日金额限制 + 奖励金额
 * 
 * 匹配逻辑：
 * 1. 分别获取时间段配置和今日金额配置
 * 2. 计算两个区间的交集
 * 3. 如果交集无效，使用时间配置（更严格）
 * 
 * 缓存策略：
 * - 配置数据缓存到 Redis，减少数据库查询
 * - 后台更新配置时自动刷新缓存
 * - Redis 不可用时自动降级到数据库查询
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
    
    // 缓存键：所有配置数据
    const CACHE_KEY_ALL_CONFIGS = 'red_packet:reward_config:all';
    
    // 缓存键：时间段配置（按小时索引）
    const CACHE_KEY_TIME_CONFIGS = 'red_packet:reward_config:time';
    
    // 缓存键：金额配置
    const CACHE_KEY_AMOUNT_CONFIGS = 'red_packet:reward_config:amount';
    
    // 缓存键：最高金额限制
    const CACHE_KEY_MAX_LIMIT = 'red_packet:reward_config:max_limit';
    
    // 缓存时间：7天（配置不常变化，可设置较长缓存时间）
    const CACHE_TTL = 604800;

    // ==================== Redis 连接管理 ====================

    /**
     * 安全获取 Redis 连接
     * @return \Redis|null
     */
    protected static function getRedis()
    {
        static $redis = null;
        static $checked = false;
        
        // 已经检查过，直接返回结果
        if ($checked) {
            return $redis;
        }
        
        $checked = true;
        
        try {
            // 方式1：尝试通过 Cache 门面获取 Redis
            $handler = Cache::store('redis');
            if ($handler) {
                $redis = $handler->handler();
                if ($redis instanceof \Redis) {
                    return $redis;
                }
            }
        } catch (\Exception $e) {
            // Cache::store('redis') 失败
        }
        
        // 方式2：尝试直接连接本地 Redis
        try {
            $redis = new \Redis();
            if ($redis->connect('127.0.0.1', 6379, 3)) {
                return $redis;
            }
        } catch (\Exception $e) {
            // 直接连接失败
        }
        
        $redis = null;
        return null;
    }

    /**
     * 检查 Redis 是否可用
     * @return bool
     */
    protected static function isRedisAvailable()
    {
        return self::getRedis() !== null;
    }

    // ==================== 缓存管理方法 ====================

    /**
     * 刷新所有缓存
     * 在后台更新配置后调用
     * @return bool
     */
    public static function refreshCache()
    {
        $redis = self::getRedis();
        
        try {
            // 1. 获取所有有效配置
            $allConfigs = self::where('status', 'normal')
                ->order('weigh', 'desc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            if ($redis) {
                // 2. 缓存所有配置
                $redis->set(self::CACHE_KEY_ALL_CONFIGS, json_encode($allConfigs), self::CACHE_TTL);
                
                // 3. 构建时间段配置索引（按小时分组）
                $timeConfigs = [];
                foreach ($allConfigs as $config) {
                    if ($config['min_today_amount'] == 0 && $config['max_today_amount'] == 0) {
                        $startHour = intval($config['start_hour']);
                        $endHour = intval($config['end_hour']);
                        for ($h = $startHour; $h < $endHour; $h++) {
                            if (!isset($timeConfigs[$h]) || $config['weigh'] > ($timeConfigs[$h]['weigh'] ?? 0)) {
                                $timeConfigs[$h] = $config;
                            }
                        }
                    }
                }
                $redis->set(self::CACHE_KEY_TIME_CONFIGS, json_encode($timeConfigs), self::CACHE_TTL);
                
                // 4. 构建金额配置列表
                $amountConfigs = [];
                foreach ($allConfigs as $config) {
                    if ($config['start_hour'] == 0 && $config['end_hour'] == 24) {
                        $amountConfigs[] = $config;
                    }
                }
                usort($amountConfigs, function($a, $b) {
                    return ($b['weigh'] ?? 0) - ($a['weigh'] ?? 0);
                });
                $redis->set(self::CACHE_KEY_AMOUNT_CONFIGS, json_encode($amountConfigs), self::CACHE_TTL);
                
                // 5. 缓存最高金额限制
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

    /**
     * 清除所有缓存
     * @return bool
     */
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

    /**
     * 从缓存获取所有配置
     * @return array
     */
    protected static function getAllConfigsFromCache()
    {
        $redis = self::getRedis();
        
        try {
            if ($redis) {
                $cached = $redis->get(self::CACHE_KEY_ALL_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    return json_decode($cached, true);
                }
                
                // 缓存不存在，重新加载
                self::refreshCache();
                $cached = $redis->get(self::CACHE_KEY_ALL_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    return json_decode($cached, true);
                }
            }
        } catch (\Exception $e) {
        }
        
        // 降级：直接从数据库读取
        return self::where('status', 'normal')
            ->order('weigh', 'desc')
            ->order('id', 'asc')
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

    // ==================== 核心业务方法（使用缓存） ====================

    /**
     * 获取红包最高金额限制（从缓存读取）
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
            
            // 缓存不存在或Redis不可用，从数据库读取
            $config = Db::name('config')
                ->where('name', 'red_packet_max_reward')
                ->value('value');
            
            $maxLimit = $config ? intval($config) : 10000;
            
            if ($redis) {
                $redis->set(self::CACHE_KEY_MAX_LIMIT, $maxLimit, self::CACHE_TTL);
            }
            
            return $maxLimit;
            
        } catch (\Exception $e) {
            return 10000; // 默认值
        }
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
     * 获取时间段配置（从缓存读取）
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
                
                // 缓存不存在，重新加载
                self::refreshCache();
                $cached = $redis->get(self::CACHE_KEY_TIME_CONFIGS);
                
                if ($cached !== false && $cached !== null) {
                    $timeConfigs = json_decode($cached, true);
                    return $timeConfigs[$hour] ?? null;
                }
            }
        } catch (\Exception $e) {
        }
        
        // 降级：从数据库读取
        return self::where('status', 'normal')
            ->where('start_hour', '<=', $hour)
            ->where('end_hour', '>', $hour)
            ->where('min_today_amount', 0)
            ->where('max_today_amount', 0)
            ->order('weigh', 'desc')
            ->find();
    }

    /**
     * 获取今日金额配置（从缓存读取）
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
                
                // 缓存不存在，重新加载
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
        
        // 降级：从数据库读取
        return self::where('status', 'normal')
            ->where('min_today_amount', '<=', $todayAmount)
            ->where(function ($q) use ($todayAmount) {
                $q->where('max_today_amount', '>=', $todayAmount)
                  ->whereOr('max_today_amount', 0);
            })
            ->where('start_hour', 0)
            ->where('end_hour', 24)
            ->order('weigh', 'desc')
            ->find();
    }

    /**
     * 获取基础奖励区间（考虑时间与金额的交集）
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
                ? ['min' => intval($timeConfig['new_user_base_min']), 'max' => min(intval($timeConfig['new_user_base_max']), $maxLimit)]
                : ['min' => intval($timeConfig['base_min_reward']), 'max' => min(intval($timeConfig['base_max_reward']), $maxLimit)];
        } else {
            $timeRange = $defaultTimeRange;
        }

        // 提取今日金额配置的奖励区间
        if ($amountConfig) {
            $amountRange = $isNewUser 
                ? ['min' => intval($amountConfig['new_user_base_min']), 'max' => min(intval($amountConfig['new_user_base_max']), $maxLimit)]
                : ['min' => intval($amountConfig['base_min_reward']), 'max' => min(intval($amountConfig['base_max_reward']), $maxLimit)];
        } else {
            $amountRange = $defaultAmountRange;
        }

        // 计算交集
        $intersection = self::intersectRanges($timeRange, $amountRange);

        if ($intersection) {
            return $intersection;
        } else {
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
                ? ['min' => intval($timeConfig['new_user_accumulate_min']), 'max' => min(intval($timeConfig['new_user_accumulate_max']), $maxLimit)]
                : ['min' => intval($timeConfig['accumulate_min_reward']), 'max' => min(intval($timeConfig['accumulate_max_reward']), $maxLimit)];
        } else {
            $timeRange = $defaultTimeRange;
        }

        // 提取今日金额配置的奖励区间
        if ($amountConfig) {
            $amountRange = $isNewUser 
                ? ['min' => intval($amountConfig['new_user_accumulate_min']), 'max' => min(intval($amountConfig['new_user_accumulate_max']), $maxLimit)]
                : ['min' => intval($amountConfig['accumulate_min_reward']), 'max' => min(intval($amountConfig['accumulate_max_reward']), $maxLimit)];
        } else {
            $amountRange = $defaultAmountRange;
        }

        // 计算交集
        $intersection = self::intersectRanges($timeRange, $amountRange);

        if ($intersection) {
            return $intersection;
        } else {
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
        
        if ($currentAmount >= $maxLimit) {
            return 0;
        }
        
        $maxAddable = $maxLimit - $currentAmount;
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
            'time_config' => $timeConfig ? ($timeConfig['name'] ?? '默认') : '默认',
            'amount_config' => $amountConfig ? ($amountConfig['name'] ?? '默认') : '默认',
            'time_range' => $timeConfig ? self::formatTimeRange($timeConfig) : '全天',
            'today_amount_range' => $amountConfig ? self::formatAmountRange($amountConfig) : '不限制',
            'base' => $baseRange,
            'accumulate' => $accumulateRange,
            'max_reward_limit' => $maxLimit,
            'current_hour' => $hour,
            'today_amount' => $todayAmount,
            'is_new_user' => $isNewUser,
            'redis_available' => self::isRedisAvailable(),
        ];
    }

    /**
     * 格式化时间范围显示
     */
    protected static function formatTimeRange($config)
    {
        $startHour = intval($config['start_hour'] ?? 0);
        $endHour = intval($config['end_hour'] ?? 24);
        
        if ($startHour == 0 && $endHour == 24) {
            return '全天';
        }
        
        return str_pad($startHour, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00';
    }

    /**
     * 格式化金额范围显示
     */
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

    /**
     * 获取所有有效配置
     * @return array
     */
    public static function getAllConfigs()
    {
        return self::getAllConfigsFromCache();
    }
}
