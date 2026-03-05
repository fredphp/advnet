<?php

namespace app\common\model;

use think\Model;

/**
 * 任务消息推送模型
 * 用于存储推送到客户端的任务消息
 */
class TaskMessage extends Model
{
    // 表名
    protected $name = 'task_message';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 消息类型
    const TYPE_TASK_PUSH = 'task_push';       // 任务推送
    const TYPE_REWARD_NOTICE = 'reward';      // 奖励通知
    const TYPE_SYSTEM_NOTICE = 'system';      // 系统通知
    
    // 消息状态
    const STATUS_PENDING = 0;    // 待发送
    const STATUS_SENT = 1;       // 已发送
    const STATUS_EXPIRED = 2;    // 已过期
    
    /**
     * 获取消息类型列表
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_TASK_PUSH => '任务推送',
            self::TYPE_REWARD_NOTICE => '奖励通知',
            self::TYPE_SYSTEM_NOTICE => '系统通知',
        ];
    }
    
    /**
     * 获取状态列表
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => '待发送',
            self::STATUS_SENT => '已发送',
            self::STATUS_EXPIRED => '已过期',
        ];
    }
    
    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }
    
    /**
     * 创建任务推送消息
     * @param int $taskId 任务ID
     * @param array $extraData 额外数据
     * @return bool|static
     */
    public static function createTaskPush($taskId, $extraData = [])
    {
        $task = RedPacketTask::get($taskId);
        if (!$task) {
            return false;
        }
        
        // 获取任务类型名称
        $typeList = RedPacketResource::getTypeList();
        $taskTypeName = $typeList[$task->type] ?? $task->type;
        
        // 构建消息内容
        $message = [
            'title' => '🎉 新任务来了！',
            'content' => sprintf(
                '【%s】%s，完成可得 %.2f 金币奖励！',
                $taskTypeName,
                $task->name,
                $task->amount_type == 'fixed' ? $task->single_amount : $task->max_amount
            ),
            'task_id' => $taskId,
            'task_type' => $task->type,
            'task_name' => $task->name,
            'reward' => $task->amount_type == 'fixed' ? $task->single_amount : $task->max_amount,
            'total_count' => $task->total_count,
            'icon' => $task->icon ?: '/assets/img/avatar.png',
            'start_time' => $task->start_time,
            'end_time' => $task->end_time,
        ];
        
        $data = [
            'task_id' => $taskId,
            'type' => self::TYPE_TASK_PUSH,
            'title' => $message['title'],
            'content' => $message['content'],
            'message_data' => json_encode(array_merge($message, $extraData)),
            'status' => self::STATUS_PENDING,
            'target_users' => json_encode([]), // 空表示全部用户
            'send_time' => time(),
            'expire_time' => $task->end_time ?: (time() + 86400 * 7),
        ];
        
        return self::create($data);
    }
    
    /**
     * 获取格式化后的消息数据
     */
    public function getFormattedData()
    {
        $data = json_decode($this->message_data, true) ?: [];
        $data['id'] = $this->id;
        $data['type'] = $this->type;
        $data['title'] = $this->title;
        $data['content'] = $this->content;
        $data['createtime'] = $this->createtime;
        $data['createtime_text'] = date('Y-m-d H:i:s', $this->createtime);
        return $data;
    }
}
