<?php

namespace app\common\model;

use think\Model;

/**
 * 红包任务模型
 */
class RedPacketTask extends Model
{
    // 表名
    protected $name = 'red_packet_task';
    
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
        'pending' => '待发送',
        'normal' => '进行中',
        'finished' => '已抢完',
        'expired' => '已过期'
    ];
    
    // 类型列表
    public static $typeList = [
        'normal' => '普通红包',
        'lucky' => '拼手气红包',
        'video' => '视频红包',
        'miniapp' => '小程序红包',
        'download' => '下载红包',
        'game' => '游戏红包'
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
     * 关联资源
     */
    public function resource()
    {
        return $this->belongsTo('RedPacketResource', 'resource_id');
    }
    
    /**
     * 关联领取记录
     */
    public function records()
    {
        return $this->hasMany('RedPacketRecord', 'task_id');
    }
    
    /**
     * 获取推送数据
     */
    public function getPushData()
    {
        $data = [
            'task_id' => $this->id,
            'task_name' => $this->name,
            'task_type' => $this->type,
            'description' => $this->description,
            'total_amount' => $this->total_amount,
            'total_count' => $this->total_count,
            'remain_count' => $this->remain_count,
            'reward' => $this->reward,
            'status' => $this->status,
            'sender_name' => $this->sender_name,
            'sender_avatar' => $this->sender_avatar,
            'timestamp' => time(),
        ];
        
        // 关联资源信息
        if ($this->resource) {
            $resource = $this->resource;
            $data['resource'] = [
                'id' => $resource->id,
                'name' => $resource->name,
                'description' => $resource->description,
                'logo' => $resource->logo,
                'type' => $resource->type,
            ];
            
            // 根据资源类型添加跳转配置
            switch ($resource->type) {
                case 'miniapp':
                case 'game':
                    $data['resource']['miniapp_id'] = $resource->miniapp_id;
                    $data['resource']['miniapp_path'] = $resource->miniapp_path;
                    break;
                case 'download':
                    $data['resource']['download_url'] = $resource->download_url;
                    $data['resource']['download_type'] = $resource->download_type;
                    break;
                case 'video':
                    $data['resource']['video_url'] = $resource->video_url;
                    break;
            }
        }
        
        return $data;
    }
}
