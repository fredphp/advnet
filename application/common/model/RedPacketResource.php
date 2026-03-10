<?php

namespace app\common\model;

use think\Model;

/**
 * 红包资源模型
 */
class RedPacketResource extends Model
{
    // 表名
    protected $name = 'red_packet_resource';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'status_text',
        'type_text'
    ];
    
    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '隐藏'
    ];
    
    // 类型列表
    public static $typeList = [
        'chat' => '普通聊天',
        'download' => '下载App',
        'miniapp' => '小程序游戏',
        'adv' => '广告时长',
        'video' => '观看视频'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }
    
    public function getTypeTextAttr($value, $data)
    {
        return isset($data['type']) ? self::$typeList[$data['type']] ?? '' : '';
    }
    
    /**
     * 关联红包任务
     */
    public function tasks()
    {
        return $this->hasMany('RedPacketTask', 'resource_id');
    }
    
    /**
     * 获取跳转配置
     */
    public function getJumpConfigAttr($value, $data)
    {
        $type = $data['type'] ?? '';
        $config = [];
        
        switch ($type) {
            case 'chat':
                $config = [
                    'chat_duration' => $data['chat_duration'] ?? 30,
                    'chat_requirement' => $data['chat_requirement'] ?? '',
                ];
                break;
            case 'miniapp':
            case 'game':
                $config = [
                    'miniapp_id' => $data['miniapp_id'] ?? '',
                    'miniapp_path' => $data['miniapp_path'] ?? '',
                    'miniapp_type' => $data['miniapp_type'] ?? 'release',
                ];
                break;
            case 'download':
                $config = [
                    'download_url' => $data['download_url'] ?? '',
                    'download_type' => $data['download_type'] ?? '',
                    'package_name' => $data['package_name'] ?? '',
                ];
                break;
            case 'video':
                $config = [
                    'video_url' => $data['video_url'] ?? '',
                    'video_duration' => $data['video_duration'] ?? 0,
                ];
                break;
        }
        
        return $config;
    }
}
