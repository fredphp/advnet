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

    // 任务类型列表 - 与资源和数据库保持一致
    public static $typeList = [
        'chat' => '普通聊天',
        'download' => '下载App',
        'miniapp' => '小程序游戏',
        'adv' => '广告时长',
        'video' => '观看视频'
    ];

    // 映射：数据库中的旧类型值 -> 新类型值
    public static $typeMap = [
        'download_app' => 'download',
        'mini_program' => 'miniapp',
        'play_game' => 'miniapp',
        'watch_video' => 'video',
        'share_link' => 'adv',
        'sign_in' => 'adv'
    ];

    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    public function getTypeTextAttr($value, $data)
    {
        if (!isset($data['type']) || empty($data['type'])) {
            return '';
        }
        $type = $data['type'];
        // 如果是旧类型，映射到新类型
        if (isset(self::$typeMap[$type])) {
            $type = self::$typeMap[$type];
        }
        return self::$typeList[$type] ?? $data['type'];
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
            'type' => $this->getData('type'),
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
                case 'chat':
                    $data['resource']['chat_duration'] = $resource->chat_duration;
                    $data['resource']['chat_requirement'] = $resource->chat_requirement;
                    break;
                case 'miniapp':
                    $data['resource']['miniapp_id'] = $resource->miniapp_id;
                    $data['resource']['miniapp_path'] = $resource->miniapp_path;
                    $data['resource']['miniapp_duration'] = $resource->miniapp_duration;
                    break;
                case 'download':
                    $data['resource']['download_url'] = $resource->download_url;
                    $data['resource']['download_type'] = $resource->download_type;
                    $data['resource']['package_name'] = $resource->package_name;
                    break;
                case 'adv':
                    $data['resource']['adv_id'] = $resource->adv_id;
                    $data['resource']['adv_platform'] = $resource->adv_platform;
                    $data['resource']['adv_duration'] = $resource->adv_duration;
                    break;
                case 'video':
                    $data['resource']['video_url'] = $resource->video_url;
                    $data['resource']['video_duration'] = $resource->video_duration;
                    break;
            }
        } else {
            // 普通聊天任务没有关联资源时，使用任务描述作为聊天内容
            $taskType = $this->getData('type');
            if ($taskType === 'chat') {
                $data['chat_content'] = $this->description ?: '';
                // 设置默认聊天时长
                $data['chat_duration'] = 30;
            }
        }

        return $data;
    }
}
