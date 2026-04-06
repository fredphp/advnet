<?php

namespace app\common\model;

use think\Model;

/**
 * 广告收益记录模型（保留用于向后兼容）
 *
 * ★ 注意：新增数据已通过 AdIncomeLogSplit 写入月度分表
 * ★ 本模型保留用于向后兼容，静态查询方法已委托给分表模型
 * ★ 主表中的数据为分表迁移前的历史数据
 */
class AdIncomeLog extends Model
{
    // 表名
    protected $name = 'ad_income_log';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 广告类型
    const AD_TYPE_FEED = 'feed';
    const AD_TYPE_REWARD = 'reward';

    // 广告平台
    const PROVIDER_UNIAD = 'uniad';
    const PROVIDER_CSJ = 'csj';    // 穿山甲
    const PROVIDER_YLH = 'ylh';    // 优量汇

    // 状态
    const STATUS_PENDING = 0;       // 待确认
    const STATUS_CONFIRMED = 1;     // 已确认（已计入 ad_freeze_balance）
    const STATUS_RELEASED = 2;      // 已释放（已生成红包）
    const STATUS_INVALID = 3;       // 已失效

    /**
     * 广告类型列表
     */
    public static $typeList = [
        self::AD_TYPE_FEED => '信息流广告',
        self::AD_TYPE_REWARD => '激励视频',
    ];

    /**
     * 广告平台列表
     */
    public static $providerList = [
        self::PROVIDER_UNIAD => 'uni-ad',
        self::PROVIDER_CSJ => '穿山甲',
        self::PROVIDER_YLH => '优量汇',
    ];

    /**
     * 状态列表
     */
    public static $statusList = [
        self::STATUS_PENDING => '待确认',
        self::STATUS_CONFIRMED => '已确认',
        self::STATUS_RELEASED => '已释放',
        self::STATUS_INVALID => '已失效',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    /**
     * 获取广告类型文本
     */
    public function getAdTypeTextAttr($value, $data)
    {
        return self::$typeList[$data['ad_type']] ?? '未知';
    }

    /**
     * 获取广告平台文本
     */
    public function getAdProviderTextAttr($value, $data)
    {
        return self::$providerList[$data['ad_provider']] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '未知';
    }

    /**
     * ★ 根据交易ID查找记录（跨分表查找，委托给 AdIncomeLogSplit）
     * @param string $transactionId
     * @return array|null
     */
    public static function findByTransactionId($transactionId)
    {
        return AdIncomeLogSplit::findByTransactionId($transactionId);
    }

    /**
     * ★ 获取用户今日广告收益（跨分表统计）
     * @param int $userId
     * @return int 金币数
     */
    public static function getTodayIncome($userId)
    {
        return AdIncomeLogSplit::getTodayIncome($userId);
    }

    /**
     * ★ 获取用户待释放的收益记录（跨分表查找）
     * @param int $userId
     * @return array
     */
    public static function getPendingRecords($userId)
    {
        return AdIncomeLogSplit::getPendingRecords($userId);
    }
}
