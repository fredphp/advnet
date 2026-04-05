<?php

namespace app\common\model;

use think\Model;

/**
 * 广告红包模型（保留用于向后兼容）
 *
 * ★ 注意：新增数据已通过 AdRedPacketSplit 写入月度分表
 * ★ 本模型保留用于向后兼容，静态查询方法已委托给分表模型
 * ★ 主表中的数据为分表迁移前的历史数据
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
     * ★ 获取用户未领取红包数量和总金额（跨分表统计）
     * @param int $userId
     * @return array
     */
    public static function getUnclaimedSummary($userId)
    {
        return AdRedPacketSplit::getUnclaimedSummary($userId);
    }

    /**
     * ★ 过期红包处理（跨所有分表）
     * @param int|null $beforeTime
     * @return int
     */
    public static function expirePackets($beforeTime = null)
    {
        return AdRedPacketSplit::expireAllPackets($beforeTime);
    }

    /**
     * 获取用户可领取红包列表（跨分表分页）
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getUserPackets($userId, $page = 1, $limit = 20)
    {
        return AdRedPacketSplit::getUserPacketsPaginated($userId, $page, $limit);
    }
}
