<?php

namespace app\member\model;

use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    // 使用软删除
    use SoftDelete;

    // 表名
    protected $name = 'user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

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
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    /**
     * 获取会员统计
     */
    public static function getStatistics($startDate, $endDate)
    {
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate . ' 23:59:59');

        $total = self::count();
        $newCount = self::where('createtime', 'between', [$startTime, $endTime])->count();
        $activeCount = self::where('logintime', 'between', [$startTime, $endTime])->count();

        return [
            'total' => $total,
            'new' => $newCount,
            'active' => $activeCount
        ];
    }
}
