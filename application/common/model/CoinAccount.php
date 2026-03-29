<?php

namespace app\common\model;

use think\Model;

/**
 * 金币账户模型
 */
class CoinAccount extends Model
{
    // 表名
    protected $name = 'coin_account';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取用户账户
     * @param int $userId
     * @return CoinAccount|null
     */
    public static function getAccount($userId)
    {
        return self::where('user_id', $userId)->find();
    }
    
    /**
     * 获取或创建账户
     * @param int $userId
     * @return CoinAccount
     */
    public static function getOrCreate($userId)
    {
        $account = self::where('user_id', $userId)->find();
        
        if (!$account) {
            $account = new self();
            $account->user_id = $userId;
            $account->balance = 0;
            $account->frozen = 0;
            $account->total_earn = 0;
            $account->total_spend = 0;
            $account->total_withdraw = 0;
            $account->today_earn = 0;
            $account->today_earn_date = date('Y-m-d');
            $account->version = 0;
            $account->save();
        }
        
        return $account;
    }
}
