<?php

namespace app\common\model;

use think\Model;

/**
 * 任务分类模型
 */
class TaskCategory extends Model
{
    // 表名
    protected $name = 'task_category';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 获取启用的分类列表
     */
    public static function getActiveCategories()
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select();
    }
}
