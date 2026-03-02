<?php

namespace app\common\model;

/**
 * 红包任务资源模型
 */
class RedPacketResource extends BaseModel
{
    // 表名
    protected $name = 'red_packet_resource';
    
    // 资源类型（与数据库中的type字段对应）
    public static $typeList = [
        'app' => 'App下载',
        'mini_program' => '小程序',
        'game' => '游戏',
        'video' => '视频',
        'link' => '分享链接'
    ];
    
    // 任务类型定义（用于任务管理页面）
    public static $taskTypeList = [
        'download_app' => '下载App',
        'mini_program' => '跳转小程序',
        'play_game' => '玩游戏时长',
        'watch_video' => '观看视频',
        'share_link' => '分享链接',
        'sign_in' => '签到任务'
    ];
    
    // 任务类型 -> 资源类型 映射关系
    public static $taskTypeMap = [
        'download_app' => 'app',        // 下载App -> app资源
        'mini_program' => 'mini_program', // 小程序 -> mini_program资源
        'play_game' => 'game',          // 玩游戏 -> game资源
        'watch_video' => 'video',       // 看视频 -> video资源
        'share_link' => 'link',         // 分享链接 -> link资源
        'sign_in' => null               // 签到任务 -> 无资源
    ];
    
    /**
     * 获取资源类型列表
     */
    public static function getTypeList()
    {
        return self::$typeList;
    }
    
    /**
     * 获取任务类型列表
     */
    public static function getTaskTypeList()
    {
        return self::$taskTypeList;
    }
    
    /**
     * 根据任务类型获取资源类型
     * @param string $taskType 任务类型
     * @return string|null 资源类型，如果不需要资源则返回null
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
