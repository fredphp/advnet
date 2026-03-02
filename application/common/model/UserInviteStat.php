<?php

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 用户邀请统计模型
 */
class UserInviteStat extends Model
{
    // 表名
    protected $name = 'user_invite_stat';
    
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
     * 获取或创建统计记录
     * @param int $userId 用户ID
     * @return UserInviteStat
     */
    public static function getOrCreate($userId)
    {
        $stat = self::where('user_id', $userId)->find();
        
        if (!$stat) {
            $stat = new self();
            $stat->user_id = $userId;
            $stat->total_invite_count = 0;
            $stat->level1_count = 0;
            $stat->level2_count = 0;
            $stat->valid_invite_count = 0;
            $stat->save();
        }
        
        return $stat;
    }
    
    /**
     * 增加一级邀请人数
     * @param int $userId 上级用户ID
     * @param int $newUserId 新用户ID
     * @return bool
     */
    public static function incrementLevel1($userId, $newUserId)
    {
        $stat = self::getOrCreate($userId);
        
        $stat->total_invite_count = $stat->total_invite_count + 1;
        $stat->level1_count = $stat->level1_count + 1;
        $stat->new_invite_today = $stat->new_invite_today + 1;
        $stat->last_invite_time = time();
        $stat->last_invite_user_id = $newUserId;
        $stat->save();
        
        return true;
    }
    
    /**
     * 增加二级邀请人数
     * @param int $userId 二级上级用户ID
     * @return bool
     */
    public static function incrementLevel2($userId)
    {
        $stat = self::getOrCreate($userId);
        
        $stat->level2_count = $stat->level2_count + 1;
        $stat->save();
        
        return true;
    }
    
    /**
     * 增加有效邀请人数
     * @param int $userId 用户ID
     * @return bool
     */
    public static function incrementValidInvite($userId)
    {
        $stat = self::getOrCreate($userId);
        $stat->valid_invite_count = $stat->valid_invite_count + 1;
        $stat->save();
        
        return true;
    }
    
    /**
     * 重置每日统计
     * @return int 影响行数
     */
    public static function resetDaily()
    {
        return self::where('id', '>', 0)
            ->update([
                'new_invite_yesterday' => Db::raw('new_invite_today'),
                'new_invite_today' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 重置每周统计
     * @return int 影响行数
     */
    public static function resetWeekly()
    {
        return self::where('id', '>', 0)
            ->update([
                'new_invite_week' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 重置每月统计
     * @return int 影响行数
     */
    public static function resetMonthly()
    {
        return self::where('id', '>', 0)
            ->update([
                'new_invite_month' => 0,
                'updatetime' => time(),
            ]);
    }
    
    /**
     * 获取邀请排行
     * @param string $type 排行类型: total/level1/valid
     * @param int $limit 限制条数
     * @return array
     */
    public static function getRanking($type = 'total', $limit = 100)
    {
        $field = $type == 'total' ? 'total_invite_count' : 
                 ($type == 'level1' ? 'level1_count' : 'valid_invite_count');
        
        return self::with(['user'])
            ->where($field, '>', 0)
            ->order($field, 'desc')
            ->limit($limit)
            ->select();
    }
    
    /**
     * 获取邀请统计概览
     * @param int $userId 用户ID
     * @return array
     */
    public static function getOverview($userId)
    {
        $stat = self::getOrCreate($userId);
        
        return [
            'total_invite_count' => $stat->total_invite_count,
            'level1_count' => $stat->level1_count,
            'level2_count' => $stat->level2_count,
            'valid_invite_count' => $stat->valid_invite_count,
            'new_invite_today' => $stat->new_invite_today,
            'new_invite_yesterday' => $stat->new_invite_yesterday,
            'new_invite_week' => $stat->new_invite_week,
            'new_invite_month' => $stat->new_invite_month,
            'last_invite_time' => $stat->last_invite_time,
        ];
    }
}
