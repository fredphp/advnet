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

    // 关闭严格字段检查（允许访问不存在的字段而不报错）
    protected $strict = false;

    // 追加属性
    protected $append = [
        'status_text',
        'type_text',
        'show_red_packet_text'
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

    // 是否显示红包
    public static $showRedPacketList = [
        0 => '否',
        1 => '是'
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

    public function getShowRedPacketTextAttr($value, $data)
    {
        return isset($data['show_red_packet']) ? self::$showRedPacketList[$data['show_red_packet']] ?? '' : '';
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
     * 关联累计记录
     */
    public function accumulates()
    {
        return $this->hasMany('UserRedPacketAccumulate', 'task_id');
    }

    /**
     * 判断任务类型是否需要显示红包
     * 只有"小程序游戏"类型才显示红包
     */
    public function shouldShowRedPacket()
    {
        $type = $this->getData('type');
        // 只有小程序游戏类型显示红包
        return $type === 'miniapp' || $this->show_red_packet == 1;
    }

    /**
     * 检查任务是否已被领取
     */
    public function isClaimed()
    {
        return UserRedPacketAccumulate::where('task_id', $this->id)
            ->where('is_collected', 1)
            ->count() > 0;
    }

    /**
     * 检查任务是否正在被抢占
     */
    public function isBeingGrabbed()
    {
        return UserRedPacketAccumulate::where('task_id', $this->id)
            ->where('is_collected', 0)
            ->where('total_amount', '>', 0)
            ->count() > 0;
    }

    /**
     * 安全获取资源的字段值
     */
    protected function getResourceField($field, $default = '')
    {
        if (!$this->resource) {
            return $default;
        }
        
        $data = $this->resource->getData();
        return isset($data[$field]) ? $data[$field] : $default;
    }

    /**
     * 获取展示数据（用于前端展示）
     * 背景图、跳转链接等信息从关联资源获取
     */
    public function getDisplayData()
    {
        $type = $this->getData('type');
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'display_title' => $this->display_title ?: $this->name,
            'display_description' => $this->display_description ?: $this->description,
            'type' => $type,
            'type_text' => $this->type_text,
            'show_red_packet' => $this->shouldShowRedPacket(),
            'background_image' => '',
            'jump_url' => '',
            'is_claimed' => $this->isClaimed(),
        ];

        // 如果有关联资源，使用资源中的信息
        if ($this->resource) {
            $resourceData = $this->resource->getData();
            
            $data['resource'] = [
                'id' => $this->resource->id,
                'name' => isset($resourceData['name']) ? $resourceData['name'] : '',
                'logo' => isset($resourceData['logo']) ? $resourceData['logo'] : '',
                'images' => isset($resourceData['images']) && $resourceData['images'] ? json_decode($resourceData['images'], true) : [],
                'type' => isset($resourceData['type']) ? $resourceData['type'] : '',
            ];

            // 展示标题优先级：任务display_title > 资源name > 任务name
            if (empty($data['display_title'])) {
                $data['display_title'] = isset($resourceData['name']) ? $resourceData['name'] : $this->name;
            }

            // 展示描述优先级：任务display_description > 资源description > 任务description
            if (empty($data['display_description'])) {
                $data['display_description'] = isset($resourceData['description']) ? $resourceData['description'] : $this->description;
            }

            // 背景图：优先使用资源的logo
            $data['background_image'] = isset($resourceData['logo']) ? $resourceData['logo'] : '';

            // 跳转链接：使用资源的url
            $data['jump_url'] = isset($resourceData['url']) ? $resourceData['url'] : '';

            // 根据资源类型补充跳转信息
            $resourceType = isset($resourceData['type']) ? $resourceData['type'] : '';
            switch ($resourceType) {
                case 'download':
                case 'download_app':
                    $data['download_type'] = isset($resourceData['download_type']) ? $resourceData['download_type'] : '';
                    $data['package_name'] = isset($resourceData['package_name']) ? $resourceData['package_name'] : '';
                    break;
                case 'miniapp':
                case 'mini_program':
                    $data['miniapp_id'] = isset($resourceData['miniapp_id']) ? $resourceData['miniapp_id'] : (isset($resourceData['app_id']) ? $resourceData['app_id'] : '');
                    $data['miniapp_path'] = isset($resourceData['miniapp_path']) ? $resourceData['miniapp_path'] : '';
                    $data['miniapp_type'] = isset($resourceData['miniapp_type']) ? $resourceData['miniapp_type'] : 'release';
                    break;
                case 'video':
                case 'watch_video':
                    $data['video_url'] = isset($resourceData['video_url']) ? $resourceData['video_url'] : '';
                    $data['video_duration'] = isset($resourceData['video_duration']) ? $resourceData['video_duration'] : 0;
                    break;
            }
        }

        return $data;
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
            'display_title' => $this->display_title ?: $this->name,
            'display_description' => $this->display_description ?: $this->description,
            'show_red_packet' => $this->shouldShowRedPacket(),
            'background_image' => '',
            'jump_url' => '',
            'status' => $this->status,
            'sender_name' => $this->sender_name,
            'sender_avatar' => $this->sender_avatar,
            'timestamp' => time(),
        ];

        // 关联资源信息
        if ($this->resource) {
            $resourceData = $this->resource->getData();
            
            $data['resource'] = [
                'id' => $this->resource->id,
                'name' => isset($resourceData['name']) ? $resourceData['name'] : '',
                'description' => isset($resourceData['description']) ? $resourceData['description'] : '',
                'logo' => isset($resourceData['logo']) ? $resourceData['logo'] : '',
                'images' => isset($resourceData['images']) && $resourceData['images'] ? json_decode($resourceData['images'], true) : [],
                'type' => isset($resourceData['type']) ? $resourceData['type'] : '',
            ];

            // 展示标题优先级
            if (empty($data['display_title'])) {
                $data['display_title'] = isset($resourceData['name']) ? $resourceData['name'] : $this->name;
            }

            // 展示描述优先级
            if (empty($data['display_description'])) {
                $data['display_description'] = isset($resourceData['description']) ? $resourceData['description'] : $this->description;
            }

            // 背景图和跳转链接
            $data['background_image'] = isset($resourceData['logo']) ? $resourceData['logo'] : '';
            $data['jump_url'] = isset($resourceData['url']) ? $resourceData['url'] : '';

            // 根据资源类型添加跳转配置
            $resourceType = isset($resourceData['type']) ? $resourceData['type'] : '';
            switch ($resourceType) {
                case 'chat':
                    $data['resource']['chat_duration'] = isset($resourceData['chat_duration']) ? $resourceData['chat_duration'] : 30;
                    $data['resource']['chat_requirement'] = isset($resourceData['chat_requirement']) ? $resourceData['chat_requirement'] : '';
                    break;
                case 'miniapp':
                case 'mini_program':
                    $data['resource']['miniapp_id'] = isset($resourceData['miniapp_id']) ? $resourceData['miniapp_id'] : (isset($resourceData['app_id']) ? $resourceData['app_id'] : '');
                    $data['resource']['miniapp_path'] = isset($resourceData['miniapp_path']) ? $resourceData['miniapp_path'] : '';
                    $data['resource']['miniapp_duration'] = isset($resourceData['miniapp_duration']) ? $resourceData['miniapp_duration'] : 0;
                    break;
                case 'download':
                case 'download_app':
                    $data['resource']['download_url'] = isset($resourceData['download_url']) ? $resourceData['download_url'] : (isset($resourceData['url']) ? $resourceData['url'] : '');
                    $data['resource']['download_type'] = isset($resourceData['download_type']) ? $resourceData['download_type'] : '';
                    $data['resource']['package_name'] = isset($resourceData['package_name']) ? $resourceData['package_name'] : '';
                    if (empty($data['jump_url'])) {
                        $data['jump_url'] = isset($resourceData['url']) ? $resourceData['url'] : (isset($resourceData['download_url']) ? $resourceData['download_url'] : '');
                    }
                    break;
                case 'adv':
                    $data['resource']['adv_id'] = isset($resourceData['adv_id']) ? $resourceData['adv_id'] : '';
                    $data['resource']['adv_platform'] = isset($resourceData['adv_platform']) ? $resourceData['adv_platform'] : '';
                    $data['resource']['adv_duration'] = isset($resourceData['adv_duration']) ? $resourceData['adv_duration'] : 0;
                    break;
                case 'video':
                case 'watch_video':
                    $data['resource']['video_url'] = isset($resourceData['video_url']) ? $resourceData['video_url'] : '';
                    $data['resource']['video_duration'] = isset($resourceData['video_duration']) ? $resourceData['video_duration'] : 0;
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
