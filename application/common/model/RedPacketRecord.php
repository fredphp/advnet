<?php

namespace app\common\model;

use think\Model;

/**
 * 红包领取记录模型
 */
class RedPacketRecord extends Model
{
    // 表名
    protected $name = 'red_packet_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 状态列表
    public static $statusList = [
        1 => '已领取',
        2 => '已提现'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }
    
    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id');
    }
}
