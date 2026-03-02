<?php

namespace app\common\model;

/**
 * 红包任务资源模型
 */
class RedPacketResource extends BaseModel
{
    // 表名
    protected $name = 'red_packet_resource';
    
    // 资源类型
    public static $typeList = [
        'app' => 'App下载',
        'mini_program' => '小程序',
        'game' => '游戏',
        'video' => '视频',
        'link' => '分享链接'
    ];
    
    // 资源类型对应的任务类型映射
    public static $taskTypeMap = [
        'download_app' => 'app',
        'mini_program' => 'mini_program',
        'play_game' => 'game',
        'watch_video' => 'video',
        'share_link' => 'link',
        'sign_in' => 'link'
    ];
    
    /**
     * 获取资源类型列表
     */
    public static function getTypeList()
    {
        return self::$typeList;
    }
    
    /**
     * 根据任务类型获取资源类型
     */
    public static function getResourceTypeByTaskType($taskType)
    {
        return self::$taskTypeMap[$taskType] ?? null;
    }
    
    /**
     * 根据类型获取资源列表
     */
    public static function getListByType($type, $status = 1)
    {
        $query = self::where('type', $type);
        if ($status !== null) {
            $query->where('status', $status);
        }
        return $query->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();
    }
    
    /**
     * 获取资源详情
     */
    public static function getDetail($id)
    {
        return self::where('id', $id)
            ->where('status', 1)
            ->find();
    }
    
    /**
     * 格式化输出
     */
    public function getFormattedData()
    {
        $data = $this->toArray();
        $data['type_text'] = self::$typeList[$data['type']] ?? '';
        $data['params'] = $data['params'] ? json_decode($data['params'], true) : [];
        $data['images'] = $data['images'] ? json_decode($data['images'], true) : [];
        return $data;
    }
}
