<?php

namespace app\common\model;

use think\Model;

/**
 * 提现订单模型
 */
class WithdrawOrder extends Model
{
    // 表名
    protected $name = 'withdraw_order';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态列表
    public static $statusList = [
        0 => '待审核',
        1 => '审核通过',
        2 => '打款中',
        3 => '提现成功',
        4 => '审核拒绝',
        5 => '打款失败',
        6 => '已取消'
    ];
    
    // 提现方式
    public static $typeList = [
        'alipay' => '支付宝',
        'wechat' => '微信',
        'bank' => '银行卡'
    ];
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 生成订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'WD' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 获取提现方式文本
     */
    public function getWithdrawTypeTextAttr($value, $data)
    {
        return self::$typeList[$data['withdraw_type']] ?? '';
    }
}
