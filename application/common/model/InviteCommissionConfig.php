<?php

namespace app\common\model;

use think\Model;
use think\Cache;

/**
 * 分佣配置模型
 */
class InviteCommissionConfig extends Model
{
    // 表名
    protected $name = 'invite_commission_config';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 缓存键
    const CACHE_KEY = 'invite_commission_config';
    
    /**
     * 根据代码获取配置
     * @param string $code 配置代码
     * @return array|null
     */
    public static function getByCode($code)
    {
        $configs = self::getAllConfigs();
        return $configs[$code] ?? null;
    }
    
    /**
     * 获取所有启用的配置
     * @return array
     */
    public static function getAllConfigs()
    {
        $cacheKey = self::CACHE_KEY . ':all';
        $configs = Cache::get($cacheKey);
        
        if ($configs === null) {
            $list = self::where('status', 1)->select();
            $configs = [];
            foreach ($list as $item) {
                $configs[$item->code] = $item->toArray();
            }
            Cache::set($cacheKey, $configs, 3600);
        }
        
        return $configs;
    }
    
    /**
     * 计算佣金
     * @param string $code 配置代码
     * @param float $sourceAmount 来源金额(元)
     * @param int $level 层级(1或2)
     * @return array ['amount' => 佣金金额, 'rate' => 比例, 'fixed' => 固定金额]
     */
    public static function calculateCommission($code, $sourceAmount, $level)
    {
        $result = [
            'amount' => 0,
            'rate' => 0,
            'fixed' => 0,
        ];
        
        $config = self::getByCode($code);
        if (!$config) {
            return $result;
        }
        
        // 检查最低触发金额
        if ($sourceAmount < $config['min_amount']) {
            return $result;
        }
        
        $rate = $level == 1 ? $config['level1_rate'] : $config['level2_rate'];
        $fixed = $level == 1 ? $config['level1_fixed'] : $config['level2_fixed'];
        
        $commission = 0;
        switch ($config['calc_type']) {
            case 'rate':
                $commission = $sourceAmount * $rate;
                break;
            case 'fixed':
                $commission = $fixed;
                break;
            case 'rate_and_fixed':
                $commission = $sourceAmount * $rate + $fixed;
                break;
        }
        
        // 最大佣金限制
        if ($config['max_commission'] > 0 && $commission > $config['max_commission']) {
            $commission = $config['max_commission'];
        }
        
        $result['amount'] = round($commission, 4);
        $result['rate'] = $rate;
        $result['fixed'] = $fixed;
        
        return $result;
    }
    
    /**
     * 清除配置缓存
     */
    public static function clearCache()
    {
        Cache::delete(self::CACHE_KEY . ':all');
    }
    
    /**
     * 获取来源类型列表
     */
    public static function getSourceTypes()
    {
        return [
            'withdraw' => '提现分佣',
            'video' => '视频分佣',
            'red_packet' => '红包分佣',
            'game' => '游戏分佣',
        ];
    }
}
