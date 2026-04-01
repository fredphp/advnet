<?php

namespace app\common\model;

/**
 * 单页模型
 */
class Singlepage extends BaseModel
{
    // 表名
    protected $name = 'singlepage';

    // 追加属性
    protected $append = [
        'status_text',
    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            if (!$row['weigh']) {
                $row->save(['weigh' => $row['id']]);
            }
        });
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo('SinglepageCategory', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
