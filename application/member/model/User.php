<?php

namespace app\member\model;

use think\Model;

class User extends Model
{
    // 表名
    protected $name = 'user';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 追加属性
    protected $append = [
        'status_text',
        'user_type_text'
    ];

    // 状态列表
    public static $statusList = [
        'normal' => '正常',
        'hidden' => '隐藏'
    ];

    // 用户类型列表
    public static $userTypeList = [
        0 => '真实会员',
        1 => '系统会员'
    ];

    public function getStatusTextAttr($value, $data)
    {
        return isset($data['status']) ? self::$statusList[$data['status']] ?? '' : '';
    }

    public function getUserTypeTextAttr($value, $data)
    {
        return isset($data['user_type']) ? self::$userTypeList[$data['user_type']] ?? '未知' : '';
    }

    /**
     * 获取系统会员列表（用于任务发送）
     */
    public static function getSystemMembers()
    {
        return self::where('user_type', 1)
            ->where('status', 'normal')
            ->field('id,username,nickname,avatar')
            ->select();
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
