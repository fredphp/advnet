<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 广告红包模型（系统自动生成，区别于 RedPacketTask 的人工任务红包）
 */
class AdRedPacket extends Model
{
    // 表名
    protected $name = 'ad_red_packet';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 来源
    const SOURCE_AD_INCOME = 'ad_income';

    // 状态
    const STATUS_UNCLAIMED = 0;    // 未领取
    const STATUS_CLAIMED = 1;      // 已领取
    const STATUS_EXPIRED = 2;      // 已过期

    /**
     * 状态列表
     */
    public static $statusList = [
        self::STATUS_UNCLAIMED => '未领取',
        self::STATUS_CLAIMED => '已领取',
        self::STATUS_EXPIRED => '已过期',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '未知';
    }

    /**
     * 获取用户可领取红包列表
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getUserPackets($userId, $page = 1, $limit = 20)
    {
        $list = self::where('user_id', $userId)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = self::where('user_id', $userId)->count();
        $unclaimedTotal = (float)self::where('user_id', $userId)
            ->where('status', self::STATUS_UNCLAIMED)
            ->sum('amount');

        return [
            'list' => $list,
            'total' => $total,
            'unclaimed_count' => self::where('user_id', $userId)
                ->where('status', self::STATUS_UNCLAIMED)
                ->count(),
            'unclaimed_total' => $unclaimedTotal,
        ];
    }

    /**
     * 获取用户未领取红包数量和总金额
     * @param int $userId
     * @return array
     */
    public static function getUnclaimedSummary($userId)
    {
        // 优化：使用 SQL 聚合查询替代全量加载到 PHP 内存
        $result = Db::name('ad_red_packet')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_UNCLAIMED)
            ->field('COUNT(*) AS cnt, IFNULL(SUM(amount), 0) AS total')
            ->find();

        return [
            'count' => (int)$result['cnt'],
            'total_amount' => (float)$result['total'],
        ];
    }

    /**
     * 过期红包处理
     * @param int $beforeTime 时间戳，此时间之前的未领取红包将被标记为过期
     * @return int 过期数量
     */
    public static function expirePackets($beforeTime = null)
    {
        if ($beforeTime === null) {
            $beforeTime = time();
        }

        $packets = self::where('status', self::STATUS_UNCLAIMED)
            ->where('expire_time', '<=', $beforeTime)
            ->select();

        $count = 0;
        foreach ($packets as $packet) {
            $packet->status = self::STATUS_EXPIRED;
            $packet->save();
            $count++;

            // 过期的红包金额退回 ad_freeze_balance（由定时任务处理）
        }

        return $count;
    }
}
