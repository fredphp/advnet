<?php

namespace addons\shop\model;

use think\Model;


class OrderElectronics extends Model
{

    // 表名
    protected $name = 'shop_order_electronics';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

}
