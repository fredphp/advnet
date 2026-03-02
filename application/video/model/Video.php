<?php

namespace app\video\model;

use think\Model;
use traits\model\SoftDelete;

class Video extends Model
{
    // 使用软删除
    use SoftDelete;

    // 表名
    protected $name = 'video';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'updatetime_text'
    ];

    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '隐藏'
    ];

    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        return isset($data['createtime']) ? date('Y-m-d H:i:s', $data['createtime']) : '';
    }

    public function getUpdatetimeTextAttr($value, $data)
    {
        return isset($data['updatetime']) ? date('Y-m-d H:i:s', $data['updatetime']) : '';
    }
}
