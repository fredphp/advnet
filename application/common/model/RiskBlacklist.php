<?php

namespace app\common\model;

use think\Model;

/**
 * 风险黑名单模型
 */
class RiskBlacklist extends Model
{
    // 表名
    protected $name = 'risk_blacklist';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 黑名单类型
    const TYPE_USER = 'user';       // 用户ID
    const TYPE_IP = 'ip';           // IP地址
    const TYPE_DEVICE = 'device';   // 设备ID
    const TYPE_PHONE = 'phone';     // 手机号
    
    /**
     * 检查是否在黑名单中
     */
    public static function isBlacklisted($type, $value)
    {
        $now = time();
        return self::where('type', $type)
            ->where('value', (string)$value)
            ->where(function($query) use ($now) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', $now);
            })
            ->count() > 0;
    }
    
    /**
     * 添加到黑名单
     */
    public static function addToList($type, $value, $reason = '', $source = 'auto', $expireTime = null)
    {
        // 检查是否已存在
        $exists = self::where('type', $type)
            ->where('value', (string)$value)
            ->find();
        
        if ($exists) {
            // 更新
            $exists->reason = $reason;
            $exists->source = $source;
            $exists->expire_time = $expireTime;
            $exists->save();
            return $exists;
        }
        
        // 新增
        $blacklist = new self();
        $blacklist->type = $type;
        $blacklist->value = (string)$value;
        $blacklist->reason = $reason;
        $blacklist->source = $source;
        $blacklist->expire_time = $expireTime;
        $blacklist->save();
        
        return $blacklist;
    }
    
    /**
     * 从黑名单移除
     */
    public static function removeFromList($type, $value)
    {
        return self::where('type', $type)
            ->where('value', (string)$value)
            ->delete();
    }
    
    /**
     * 获取黑名单类型列表
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_USER => '用户ID',
            self::TYPE_IP => 'IP地址',
            self::TYPE_DEVICE => '设备ID',
            self::TYPE_PHONE => '手机号',
        ];
    }
}
