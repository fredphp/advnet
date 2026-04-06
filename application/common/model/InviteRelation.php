<?php

namespace app\common\model;

use think\Model;

/**
 * 邀请关系模型
 */
class InviteRelation extends Model
{
    // 表名
    protected $name = 'invite_relation';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    /**
     * 关联用户(被邀请人)
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 关联一级上级
     */
    public function parent()
    {
        return $this->belongsTo('User', 'parent_id');
    }
    
    /**
     * 关联二级上级
     */
    public function grandparent()
    {
        return $this->belongsTo('User', 'grandparent_id');
    }
    
    /**
     * 获取用户邀请关系
     * @param int $userId
     * @return InviteRelation|null
     */
    public static function getByUserId($userId)
    {
        return self::where('user_id', $userId)->find();
    }
    
    /**
     * 创建邀请关系
     * @param int $userId 被邀请人ID
     * @param string $inviteCode 邀请码
     * @param string $channel 邀请渠道
     * @return InviteRelation
     */
    public static function createRelation($userId, $inviteCode, $channel = 'link')
    {
        // 查找邀请人
        $inviter = User::where('invite_code', $inviteCode)->find();
        
        $relation = new self();
        $relation->user_id = $userId;
        $relation->invite_code = $inviteCode;
        $relation->invite_channel = $channel;
        
        if ($inviter) {
            $relation->parent_id = $inviter->id;
            // 查找邀请人的上级
            $parentRelation = self::getByUserId($inviter->id);
            if ($parentRelation && $parentRelation->parent_id) {
                $relation->grandparent_id = $parentRelation->parent_id;
            }
        } else {
            $relation->parent_id = 0;
            $relation->grandparent_id = 0;
        }
        
        $relation->save();
        return $relation;
    }
}
