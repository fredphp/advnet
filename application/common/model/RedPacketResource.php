<?php

namespace app\common\model;

/**
 * 红包任务资源模型
 */
class RedPacketResource extends BaseModel
{
    // 表名
    protected $name = 'red_packet_resource';
    
    /**
     * 资源/任务类型列表（统一使用相同的类型定义）
     * 资源管理页面和任务管理页面使用相同的类型
     */
    public static $typeList = [
        'download_app' => '下载App',
        'mini_program' => '跳转小程序',
        'play_game' => '玩游戏时长',
        'watch_video' => '观看视频',
        'share_link' => '分享链接',
        'sign_in' => '签到任务'
    ];
    
    /**
     * 获取资源类型列表
     */
    public static function getTypeList()
    {
        return self::$typeList;
    }
    
    /**
     * 获取任务类型列表（与资源类型一致）
     */
    public static function getTaskTypeList()
    {
        return self::$typeList;
    }
    
    /**
     * 根据任务类型获取资源类型
     * 由于类型统一，直接返回任务类型即可
     * @param string $taskType 任务类型
     * @return string|null 资源类型，签到任务返回null
     */
    public static function getResourceTypeByTaskType($taskType)
    {
        // 签到任务不需要资源
        if ($taskType === 'sign_in') {
            return null;
        }
        return $taskType;
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
