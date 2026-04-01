<?php

namespace app\common\model;

/**
 * 单页分类模型
 */
class SinglepageCategory extends BaseModel
{
    // 表名
    protected $name = 'singlepage_category';

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
     * 获取分类列表（用于下拉选择）
     * @param int $status 状态筛选
     * @return array
     */
    public static function getCategoryList($status = null)
    {
        $where = [];
        if (!is_null($status)) {
            $where['status'] = $status;
        }
        $list = collection(self::where($where)->order('weigh', 'desc')->order('id', 'asc')->select())->toArray();
        return $list;
    }
}
