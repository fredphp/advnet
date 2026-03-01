<?php

namespace app\common\model;

use think\Model;

/**
 * 视频模型
 */
class Video extends Model
{
    // 表名
    protected $name = 'video';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    // 状态列表
    public static $statusList = [
        0 => '待审核',
        1 => '已发布',
        2 => '已下架',
        3 => '已封禁',
        4 => '草稿'
    ];
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id')->setEagerlyType(0);
    }
    
    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo('Category', 'category_id')->setEagerlyType(0);
    }
    
    /**
     * 关联合集
     */
    public function collection()
    {
        return $this->belongsTo('VideoCollection', 'collection_id')->setEagerlyType(0);
    }
    
    /**
     * 关联收益规则
     */
    public function rewardRule()
    {
        return $this->belongsTo('VideoRewardRule', 'reward_rule_id')->setEagerlyType(0);
    }
}
