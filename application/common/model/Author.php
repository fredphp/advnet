<?php

namespace app\common\model;

/**
 * 发布者/作者模型
 */
class Author extends BaseModel
{
    // 表名
    protected $name = 'author';
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '隐藏'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 关联视频
     */
    public function videos()
    {
        return $this->hasMany('Video', 'user_id', 'id');
    }
}
