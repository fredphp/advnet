<?php

namespace app\common\library;

use think\Config;
use think\Cache;

/**
 * 系统配置服务
 * 
 * 统一管理所有业务配置参数
 * 配置从 advn_config 表读取，通过 config('site.xxx') 获取
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
    const GROUP_WECHAT = 'wechat';
    
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
        
        // ==================== 微信配置 ====================
        'wechat' => [
            // 微信App登录配置
            'wechat_app_enabled' => 0,
            'wechat_app_appid' => '',
            'wechat_app_secret' => '',
            
            // 微信小程序配置
            'wechat_mini_enabled' => 0,
            'wechat_mini_appid' => '',
            'wechat_mini_secret' => '',
            
            // 微信公众号配置
            'wechat_official_enabled' => 0,
            'wechat_official_appid' => '',
            'wechat_official_secret' => '',
            
            // 微信支付配置
            'wechat_pay_enabled' => 0,
            'wechat_pay_mchid' => '',
            'wechat_pay_key' => '',
            'wechat_pay_cert_pem' => '',
            'wechat_pay_key_pem' => '',
            'wechat_pay_notify_url' => '',
            
            // 企业付款配置（用于提现）
            'wechat_transfer_enabled' => 0,
            'wechat_transfer_mchid' => '',
            'wechat_transfer_key' => '',
            'wechat_transfer_cert_pem' => '',
            'wechat_transfer_key_pem' => '',
            
            // 登录配置
            'wechat_auto_register' => 1,
            'wechat_bind_mobile' => 0,
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
     * 优先从 Config::get('site.xxx') 读取，失败则回退到 config() helper
     * 
     * @param string $group 分组名
     * @return array
     */
    protected static function getGroupConfig($group)
    {
        // 检查内存缓存
        if (isset(self::$configCache[$group])) {
            return self::$configCache[$group];
        }
        
        // 尝试从缓存获取（但不信任旧缓存，如果缓存不完整则跳过）
        $cacheKey = self::CACHE_PREFIX . $group;
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null && is_array($cached)) {
            self::$configCache[$group] = $cached;
            return $cached;
        }
        
        // 从 site config 加载配置
        $config = self::loadFromSiteConfig($group);
        
        // 合并默认值（默认值作为基底，确保所有 key 都存在）
        $defaultConfig = self::$defaultConfig[$group] ?? [];
        $config = array_merge($defaultConfig, $config);
        
        // 缓存结果
        self::$configCache[$group] = $config;
        try {
            Cache::set($cacheKey, $config, self::CACHE_TTL);
        } catch (\Throwable $e) {
            // 缓存写入失败不影响配置读取
        }
        
        return $config;
    }
    
    /**
     * 从 site config 加载配置
     * 配置存储在 advn_config 表，通过 Config::get('site.xxx') 获取
     * 
     * @param string $group 分组名
     * @return array
     */
    protected static function loadFromSiteConfig($group)
    {
        $config = [];
        $defaultConfig = self::$defaultConfig[$group] ?? [];
        
        // 优先一次性获取整个 site 配置（性能更好，避免 N 次调用 Config::get）
        $allSiteConfig = null;
        try {
            $allSiteConfig = Config::get('site');
        } catch (\Throwable $e) {
            // Config::get 异常时忽略
        }
        
        // 如果 site 全局配置不可用，逐个 key 尝试 config() helper（兼容性更强）
        if (empty($allSiteConfig) || !is_array($allSiteConfig)) {
            foreach ($defaultConfig as $key => $defaultValue) {
                try {
                    // 直接用 config() helper 逐个读取，兼容不同加载方式
                    $siteValue = config('site.' . $key);
                    if ($siteValue === null) {
                        // 回退：尝试无 site 前缀（如 config('withdraw_amounts')）
                        $siteValue = config($key);
                    }
                } catch (\Throwable $e) {
                    $siteValue = null;
                }
                
                if ($siteValue !== null && $siteValue !== '') {
                    $config[$key] = self::castValue($siteValue, $defaultValue);
                }
            }
            return $config;
        }
        
        // 从全量 site 配置中提取当前分组的值
        foreach ($defaultConfig as $key => $defaultValue) {
            // 先尝试 site 全局配置中的直接 key
            $siteValue = $allSiteConfig[$key] ?? null;
            
            // 兼容：如果全局没有，尝试带分组前缀的 key（如 withdraw_min_withdraw）
            if (($siteValue === null || $siteValue === '') && isset($allSiteConfig[$group . '_' . $key])) {
                $siteValue = $allSiteConfig[$group . '_' . $key];
            }
            
            // 如果 site config 中有值，使用它
            if ($siteValue !== null && $siteValue !== '') {
                $config[$key] = self::castValue($siteValue, $defaultValue);
            }
        }
        
        return $config;
    }
    
    /**
     * 根据默认值类型转换配置值
     * 
     * @param mixed $value 配置值
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    protected static function castValue($value, $defaultValue)
    {
        if (is_int($defaultValue)) {
            return intval($value);
        } elseif (is_float($defaultValue)) {
            return floatval($value);
        } elseif (is_bool($defaultValue)) {
            return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on']);
        } elseif (is_array($defaultValue)) {
            return is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : $defaultValue);
        }
        
        return $value;
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
    
    /**
     * 获取微信配置
     */
    public static function getWechatConfig()
    {
        return self::getGroupConfig(self::GROUP_WECHAT);
    }
    
    /**
     * 检查微信App登录是否开启
     */
    public static function isWechatAppEnabled()
    {
        $value = Config::get('site.wechat_app_enabled');
        if ($value !== null && $value !== '') {
            return self::castValue($value, true);
        }
        return self::get('wechat.wechat_app_enabled', false);
    }
    
    /**
     * 检查微信小程序登录是否开启
     */
    public static function isWechatMiniEnabled()
    {
        $value = Config::get('site.wechat_mini_enabled');
        if ($value !== null && $value !== '') {
            return self::castValue($value, true);
        }
        return self::get('wechat.wechat_mini_enabled', false);
    }
    
    /**
     * 检查微信公众号登录是否开启
     */
    public static function isWechatOfficialEnabled()
    {
        $value = Config::get('site.wechat_official_enabled');
        if ($value !== null && $value !== '') {
            return self::castValue($value, true);
        }
        return self::get('wechat.wechat_official_enabled', false);
    }
    
    /**
     * 获取微信App配置
     * @return array ['appid' => '', 'secret' => '']
     */
    public static function getWechatAppConfig()
    {
        return [
            'appid' => Config::get('site.wechat_app_appid') ?: self::get('wechat.wechat_app_appid', ''),
            'secret' => Config::get('site.wechat_app_secret') ?: self::get('wechat.wechat_app_secret', ''),
            'enabled' => self::isWechatAppEnabled(),
        ];
    }
    
    /**
     * 获取微信小程序配置
     * @return array ['appid' => '', 'secret' => '']
     */
    public static function getWechatMiniConfig()
    {
        return [
            'appid' => Config::get('site.wechat_mini_appid') ?: self::get('wechat.wechat_mini_appid', ''),
            'secret' => Config::get('site.wechat_mini_secret') ?: self::get('wechat.wechat_mini_secret', ''),
            'enabled' => self::isWechatMiniEnabled(),
        ];
    }
    
    /**
     * 获取微信公众号配置
     * @return array ['appid' => '', 'secret' => '']
     */
    public static function getWechatOfficialConfig()
    {
        return [
            'appid' => Config::get('site.wechat_official_appid') ?: self::get('wechat.wechat_official_appid', ''),
            'secret' => Config::get('site.wechat_official_secret') ?: self::get('wechat.wechat_official_secret', ''),
            'enabled' => self::isWechatOfficialEnabled(),
        ];
    }
    
    /**
     * 获取微信支付配置
     * @return array
     */
    public static function getWechatPayConfig()
    {
        return [
            'enabled' => self::castValue(Config::get('site.wechat_pay_enabled') ?: self::get('wechat.wechat_pay_enabled', 0), true),
            'mchid' => Config::get('site.wechat_pay_mchid') ?: self::get('wechat.wechat_pay_mchid', ''),
            'key' => Config::get('site.wechat_pay_key') ?: self::get('wechat.wechat_pay_key', ''),
            'cert_pem' => Config::get('site.wechat_pay_cert_pem') ?: self::get('wechat.wechat_pay_cert_pem', ''),
            'key_pem' => Config::get('site.wechat_pay_key_pem') ?: self::get('wechat.wechat_pay_key_pem', ''),
            'notify_url' => Config::get('site.wechat_pay_notify_url') ?: self::get('wechat.wechat_pay_notify_url', ''),
        ];
    }
    
    /**
     * 获取企业付款配置
     * @return array
     */
    public static function getWechatTransferConfig()
    {
        return [
            'enabled' => self::castValue(Config::get('site.wechat_transfer_enabled') ?: self::get('wechat.wechat_transfer_enabled', 0), true),
            'mchid' => Config::get('site.wechat_transfer_mchid') ?: self::get('wechat.wechat_transfer_mchid', ''),
            'key' => Config::get('site.wechat_transfer_key') ?: self::get('wechat.wechat_transfer_key', ''),
            'cert_pem' => Config::get('site.wechat_transfer_cert_pem') ?: self::get('wechat.wechat_transfer_cert_pem', ''),
            'key_pem' => Config::get('site.wechat_transfer_key_pem') ?: self::get('wechat.wechat_transfer_key_pem', ''),
        ];
    }
    
    /**
     * 是否自动注册
     */
    public static function isWechatAutoRegister()
    {
        $value = Config::get('site.wechat_auto_register');
        if ($value !== null && $value !== '') {
            return self::castValue($value, true);
        }
        return self::get('wechat.wechat_auto_register', true);
    }
    
    /**
     * 是否强制绑定手机
     */
    public static function isWechatBindMobile()
    {
        $value = Config::get('site.wechat_bind_mobile');
        if ($value !== null && $value !== '') {
            return self::castValue($value, true);
        }
        return self::get('wechat.wechat_bind_mobile', false);
    }
}
