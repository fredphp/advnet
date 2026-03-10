<?php

namespace app\common\library;

use think\Db;
use think\Cache;

/**
 * 系统配置服务
 * 
 * 统一管理所有业务配置参数
 * 支持数据库配置、缓存、默认值
 */
class SystemConfigService
{
    // 缓存前缀
    const CACHE_PREFIX = 'sys_config:';
    
    // 缓存时间（秒）
    const CACHE_TTL = 3600;
    
    // 配置分组
    const GROUP_COIN = 'coin';
    const GROUP_WITHDRAW = 'withdraw';
    const GROUP_INVITE = 'invite';
    const GROUP_VIDEO = 'video';
    const GROUP_REDPACKET = 'redpacket';
    const GROUP_RISK = 'risk';
    const GROUP_SYSTEM = 'system';
    
    /**
     * @var array 配置缓存
     */
    protected static $configCache = [];
    
    /**
     * @var array 默认配置
     */
    protected static $defaultConfig = [
        // ==================== 金币配置 ====================
        'coin' => [
            // 金币汇率：多少金币等于1元人民币
            'coin_rate' => 10000,
            // 新用户注册奖励金币
            'new_user_coin' => 1000,
            // 视频观看奖励金币
            'video_coin_reward' => 100,
            // 有效观看时长(秒)
            'video_watch_duration' => 30,
            // 每日视频观看上限
            'daily_video_limit' => 500,
            // 每日金币获取上限
            'daily_coin_limit' => 50000,
            // 每小时金币获取上限
            'hourly_coin_limit' => 10000,
        ],
        
        // ==================== 提现配置 ====================
        'withdraw' => [
            // 是否开启提现
            'withdraw_enabled' => 1,
            // 最低提现金额(元)
            'min_withdraw' => 1,
            // 最高提现金额(元)
            'max_withdraw' => 500,
            // 可选提现金额(元)，逗号分隔
            'withdraw_amounts' => '10,20,50,100',
            // 每日提现次数限制
            'daily_withdraw_limit' => 3,
            // 每日提现金额限制(元)
            'daily_withdraw_amount' => 500,
            // 提现手续费率
            'fee_rate' => 0,
            // 自动审核金额阈值(元)
            'auto_audit_amount' => 10,
            // 人工审核金额阈值(元)
            'manual_audit_amount' => 50,
            // 新用户提现天数限制
            'new_user_withdraw_days' => 3,
            // 同IP提现次数限制
            'same_ip_limit' => 5,
            // 同设备提现次数限制
            'same_device_limit' => 3,
            // 提现重试次数
            'transfer_retry_count' => 3,
            // 提现重试间隔(秒)
            'transfer_retry_interval' => 300,
        ],
        
        // ==================== 邀请配置 ====================
        'invite' => [
            // 是否开启邀请
            'invite_enabled' => 1,
            // 一级邀请奖励金币
            'invite_level1_reward' => 1000,
            // 二级邀请奖励金币
            'invite_level2_reward' => 500,
            // 是否开启分佣
            'commission_enabled' => 1,
            // 一级分佣比例
            'level1_commission_rate' => 0.10,
            // 二级分佣比例
            'level2_commission_rate' => 0.05,
            // 每日邀请次数限制
            'daily_invite_limit' => 50,
        ],
        
        // ==================== 视频配置 ====================
        'video' => [
            // 视频最小观看比例
            'min_watch_ratio' => 0.3,
            // 视频重复观看限制
            'repeat_watch_limit' => 5,
            // 视频跳过率阈值
            'skip_ratio_threshold' => 0.9,
            // 视频列表缓存时间(秒)
            'list_cache_ttl' => 300,
        ],
        
        // ==================== 红包配置 ====================
        'redpacket' => [
            // 每日抢红包次数限制
            'daily_grab_limit' => 50,
            // 抢红包最小间隔(秒)
            'min_grab_interval' => 0.5,
            // 红包过期时间(秒)
            'expire_time' => 86400,
        ],
        
        // ==================== 风控配置 ====================
        'risk' => [
            // 是否开启风控
            'risk_enabled' => 1,
            // 是否自动封禁
            'auto_ban_enabled' => 1,
            // 自动封禁阈值
            'ban_threshold' => 700,
            // 自动冻结阈值
            'freeze_threshold' => 300,
            // 风险分每日衰减率
            'score_decay_rate' => 0.1,
            // 风险分最大值
            'max_risk_score' => 1000,
            // 模拟器拦截
            'emulator_block' => 1,
            // Hook框架拦截
            'hook_block' => 1,
            // 代理检测
            'proxy_detect' => 1,
            // IP多账户阈值
            'ip_multi_account_threshold' => 5,
            // 设备多账户阈值
            'device_multi_account_threshold' => 3,
            // 风控规则缓存时间(秒)
            'rule_cache_ttl' => 300,
        ],
        
        // ==================== 系统配置 ====================
        'system' => [
            // API限流：每分钟请求次数
            'api_rate_limit' => 60,
            // 用户限流：每分钟请求次数
            'user_rate_limit' => 30,
            // 高风险操作限流：每5分钟次数
            'high_risk_rate_limit' => 5,
            // 会话超时时间(秒)
            'session_timeout' => 86400,
            // Token有效期(秒)
            'token_ttl' => 604800,
            // 日志保留天数
            'log_retention_days' => 30,
        ],
    ];
    
    /**
     * 获取配置值
     * 
     * @param string $group 配置分组
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($group, $key = null, $default = null)
    {
        // 如果只传入一个参数，尝试解析为 group.key 格式
        if ($key === null && strpos($group, '.') !== false) {
            list($group, $key) = explode('.', $group, 2);
        }
        
        // 获取分组配置
        $groupConfig = self::getGroupConfig($group);
        
        // 如果没有指定key，返回整个分组
        if ($key === null) {
            return $groupConfig;
        }
        
        // 返回指定配置值
        return $groupConfig[$key] ?? $default ?? self::$defaultConfig[$group][$key] ?? $default;
    }
    
    /**
     * 获取分组配置
     * 
     * @param string $group 分组名
     * @return array
     */
    protected static function getGroupConfig($group)
    {
        // 检查缓存
        if (isset(self::$configCache[$group])) {
            return self::$configCache[$group];
        }
        
        // 尝试从缓存获取
        $cacheKey = self::CACHE_PREFIX . $group;
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            self::$configCache[$group] = $cached;
            return $cached;
        }
        
        // 从数据库加载
        $config = self::loadFromDatabase($group);
        
        // 合并默认值
        $defaultConfig = self::$defaultConfig[$group] ?? [];
        $config = array_merge($defaultConfig, $config);
        
        // 缓存结果
        self::$configCache[$group] = $config;
        Cache::set($cacheKey, $config, self::CACHE_TTL);
        
        return $config;
    }
    
    /**
     * 从数据库加载配置
     * 
     * @param string $group 分组名
     * @return array
     */
    protected static function loadFromDatabase($group)
    {
        try {
            $list = Db::name('system_config')
                ->where('group', $group)
                ->where('status', 1)
                ->select()
                ->toArray();
            
            $config = [];
            foreach ($list as $item) {
                $value = $item['value'];
                
                // 根据类型转换值
                switch ($item['type'] ?? 'string') {
                    case 'int':
                    case 'integer':
                        $value = intval($value);
                        break;
                    case 'float':
                    case 'number':
                        $value = floatval($value);
                        break;
                    case 'bool':
                    case 'boolean':
                        $value = in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
                        break;
                    case 'json':
                    case 'array':
                        $value = is_string($value) ? json_decode($value, true) : $value;
                        break;
                }
                
                $config[$item['name']] = $value;
            }
            
            return $config;
        } catch (\Exception $e) {
            // 数据库异常时返回空数组，使用默认值
            return [];
        }
    }
    
    /**
     * 设置配置值
     * 
     * @param string $group 配置分组
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @return bool
     */
    public static function set($group, $key, $value)
    {
        try {
            // 类型转换
            $type = 'string';
            $dbValue = $value;
            
            if (is_bool($value)) {
                $type = 'boolean';
                $dbValue = $value ? '1' : '0';
            } elseif (is_int($value)) {
                $type = 'integer';
                $dbValue = (string)$value;
            } elseif (is_float($value)) {
                $type = 'float';
                $dbValue = (string)$value;
            } elseif (is_array($value)) {
                $type = 'json';
                $dbValue = json_encode($value);
            }
            
            // 更新或插入数据库
            $exists = Db::name('system_config')
                ->where('group', $group)
                ->where('name', $key)
                ->find();
            
            if ($exists) {
                Db::name('system_config')
                    ->where('id', $exists['id'])
                    ->update([
                        'value' => $dbValue,
                        'type' => $type,
                        'updatetime' => time(),
                    ]);
            } else {
                Db::name('system_config')->insert([
                    'group' => $group,
                    'name' => $key,
                    'value' => $dbValue,
                    'type' => $type,
                    'status' => 1,
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }
            
            // 清除缓存
            self::clearCache($group);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 清除配置缓存
     * 
     * @param string|null $group 分组名，null表示清除所有
     */
    public static function clearCache($group = null)
    {
        if ($group === null) {
            // 清除所有配置缓存
            foreach (array_keys(self::$defaultConfig) as $g) {
                Cache::rm(self::CACHE_PREFIX . $g);
                unset(self::$configCache[$g]);
            }
        } else {
            Cache::rm(self::CACHE_PREFIX . $group);
            unset(self::$configCache[$group]);
        }
    }
    
    /**
     * 刷新配置缓存
     */
    public static function refresh()
    {
        self::clearCache();
    }
    
    /**
     * 获取所有默认配置
     * 
     * @return array
     */
    public static function getDefaults()
    {
        return self::$defaultConfig;
    }
    
    /**
     * 批量获取配置
     * 
     * @param array $keys 配置键列表 ['group.key', 'group.key2']
     * @return array
     */
    public static function getMultiple(array $keys)
    {
        $result = [];
        
        // 按分组收集
        $groups = [];
        foreach ($keys as $key) {
            if (strpos($key, '.') !== false) {
                list($group, $k) = explode('.', $key, 2);
                $groups[$group][] = $k;
            }
        }
        
        // 获取配置
        foreach ($groups as $group => $groupKeys) {
            $groupConfig = self::getGroupConfig($group);
            foreach ($groupKeys as $k) {
                $result[$group . '.' . $k] = $groupConfig[$k] ?? null;
            }
        }
        
        return $result;
    }
    
    // ==================== 便捷方法 ====================
    
    /**
     * 获取金币配置
     */
    public static function getCoinConfig()
    {
        return self::getGroupConfig(self::GROUP_COIN);
    }
    
    /**
     * 获取提现配置
     */
    public static function getWithdrawConfig()
    {
        return self::getGroupConfig(self::GROUP_WITHDRAW);
    }
    
    /**
     * 获取可选提现金额数组
     * @return array
     */
    public static function getWithdrawAmounts()
    {
        $amountsStr = self::get('withdraw.withdraw_amounts', '10,20,50,100');
        $amounts = array_map('floatval', array_filter(explode(',', $amountsStr)));
        sort($amounts);
        return $amounts;
    }
    
    /**
     * 检查提现金额是否在可选范围内
     * @param float $amount 提现金额
     * @return bool
     */
    public static function isValidWithdrawAmount($amount)
    {
        $amounts = self::getWithdrawAmounts();
        return in_array(floatval($amount), $amounts, true);
    }
    
    /**
     * 获取邀请配置
     */
    public static function getInviteConfig()
    {
        return self::getGroupConfig(self::GROUP_INVITE);
    }
    
    /**
     * 获取风控配置
     */
    public static function getRiskConfig()
    {
        return self::getGroupConfig(self::GROUP_RISK);
    }
    
    /**
     * 获取金币汇率
     */
    public static function getCoinRate()
    {
        return self::get('coin.coin_rate', 10000);
    }
    
    /**
     * 金币转人民币
     */
    public static function coinToCash($coin)
    {
        $rate = self::getCoinRate();
        return round($coin / $rate, 4);
    }
    
    /**
     * 人民币转金币
     */
    public static function cashToCoin($cash)
    {
        $rate = self::getCoinRate();
        return intval($cash * $rate);
    }
}
