<?php

namespace app\common\model;

use think\Model;
use think\facade\Cache;
use think\Db;

/**
 * 红包奖励配置模型
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
    
    // 关闭严格模式（允许访问不存在的字段）
    protected $strict = false;
    
    // 缓存键
    const CACHE_KEY_CONFIG = 'red_packet:reward_config';
    const CACHE_KEY_MAX_LIMIT = 'red_packet:max_limit';
    
    // 状态列表
    public static $statusList = [
        0 => '禁用',
        1 => '启用'
    ];
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }
    
    // ==================== 缓存管理方法 ====================
    
    /**
     * 清除配置缓存
     */
    public static function clearCache()
    {
        try {
            $redis = Cache::store('redis')->handler();
            $redis->del(self::CACHE_KEY_CONFIG);
            $redis->del(self::CACHE_KEY_MAX_LIMIT);
        } catch (\Exception $e) {
            // 缓存清除失败不影响主流程
        }
    }
    
    /**
     * 模型保存后钩子 - 清除缓存
     */
    public static function onAfterSave($model)
    {
        self::clearCache();
    }
    
    /**
     * 模型删除后钩子 - 清除缓存
     */
    public static function onAfterDelete($model)
    {
        self::clearCache();
    }
    
    // ==================== 核心业务方法（使用缓存） ====================
    
    /**
     * 获取红包最高金额限制（从缓存读取）
     * @return int
     */
    public static function getMaxRewardLimit()
    {
        try {
            $redis = Cache::store('redis')->handler();
            $cached = $redis->get(self::CACHE_KEY_MAX_LIMIT);
            
            if ($cached !== false && $cached !== null) {
                return intval($cached);
            }
            
            // 缓存不存在，从数据库读取并缓存
            $config = Db::name('config')->where('name', 'red_packet_max_reward')->find();
            $maxLimit = $config ? intval($config['value']) : 50000;
            
            // 缓存1小时
            $redis->set(self::CACHE_KEY_MAX_LIMIT, $maxLimit, 3600);
            
            return $maxLimit;
        } catch (\Exception $e) {
            return 50000; // 默认值
        }
    }
    
    /**
     * 获取所有启用的配置（从缓存读取）
     * @return array
     */
    public static function getAllConfigs()
    {
        try {
            $redis = Cache::store('redis')->handler();
            $cached = $redis->get(self::CACHE_KEY_CONFIG);
            
            if ($cached !== false && $cached !== null) {
                return json_decode($cached, true);
            }
            
            // 缓存不存在，从数据库读取
            $configs = self::where('status', 1)
                ->order('min_amount asc, start_time asc')
                ->select()
                ->toArray();
            
            // 缓存1小时
            $redis->set(self::CACHE_KEY_CONFIG, json_encode($configs), 3600);
            
            return $configs;
        } catch (\Exception $e) {
            // 如果缓存失败，直接从数据库读取
            return self::where('status', 1)
                ->order('min_amount asc, start_time asc')
                ->select()
                ->toArray();
        }
    }
    
    /**
     * 根据今日已领取金额和当前时间段获取匹配的配置
     * @param int $todayClaimedAmount 今日已领取金额
     * @return array|null
     */
    public static function getMatchingConfig($todayClaimedAmount)
    {
        $configs = self::getAllConfigs();
        $currentHour = intval(date('H'));
        
        // 找到同时满足以下条件的配置：
        // 1. 今日已领取金额在 [min_amount, max_amount] 范围内
        // 2. 当前时间在 [start_time, end_time) 范围内
        foreach ($configs as $config) {
            if ($todayClaimedAmount >= $config['min_amount'] 
                && $todayClaimedAmount <= $config['max_amount']
                && $currentHour >= $config['start_time']
                && $currentHour < $config['end_time']
            ) {
                return $config;
            }
        }
        
        // 如果没有精确匹配，尝试只匹配今日金额范围
        foreach ($configs as $config) {
            if ($todayClaimedAmount >= $config['min_amount']
                && $todayClaimedAmount <= $config['max_amount']
            ) {
                return $config;
            }
        }
        
        return null;
    }
    
    /**
     * 获取基础奖励金额范围
     * @param int $todayClaimedAmount 今日已领取金额
     * @return array ['min' => 最小值, 'max' => 最大值]
     */
    public static function getBaseRewardRange($todayClaimedAmount)
    {
        $config = self::getMatchingConfig($todayClaimedAmount);
        
        if (!$config) {
            return ['min' => 1000, 'max' => 3000]; // 默认值
        }
        
        return [
            'min' => $config['base_min'],
            'max' => $config['base_max']
        ];
    }
    
    /**
     * 获取累加奖励金额范围
     * @param int $todayClaimedAmount 今日已领取金额
     * @return array ['min' => 最小值, 'max' => 最大值]
     */
    public static function getAccumulateRewardRange($todayClaimedAmount)
    {
        $config = self::getMatchingConfig($todayClaimedAmount);
        
        if (!$config) {
            return ['min' => 500, 'max' => 1500]; // 默认值
        }
        
        return [
            'min' => $config['accumulate_min'],
            'max' => $config['accumulate_max']
        ];
    }
    
    /**
     * 获取时间配置
     * @param int $todayClaimedAmount 今日已领取金额
     * @return array ['start' => 开始时间, 'end' => 结束时间]
     */
    public static function getTimeConfig($todayClaimedAmount)
    {
        $config = self::getMatchingConfig($todayClaimedAmount);
        
        if (!$config) {
            return ['start' => 0, 'end' => 24]; // 默认全天
        }
        
        return [
            'start' => $config['start_time'],
            'end' => $config['end_time']
        ];
    }
    
    /**
     * 获取今日金额配置
     * @param int $todayClaimedAmount 今日已领取金额
     * @return array ['min' => 最小值, 'max' => 最大值]
     */
    public static function getTodayAmountConfig($todayClaimedAmount)
    {
        $config = self::getMatchingConfig($todayClaimedAmount);
        
        if (!$config) {
            return ['min' => 0, 'max' => 200000]; // 默认值
        }
        
        return [
            'min' => $config['min_amount'],
            'max' => $config['max_amount']
        ];
    }
}
