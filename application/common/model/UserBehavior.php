<?php

namespace app\common\model;

use think\Model;

/**
 * 用户行为记录模型
 */
class UserBehavior extends Model
{
    protected $name = 'user_behavior';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 行为类型
    const TYPE_LOGIN = 'login';
    const TYPE_VIDEO_WATCH = 'video_watch';
    const TYPE_TASK_COMPLETE = 'task_complete';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_REDPACKET_GRAB = 'redpacket_grab';
    const TYPE_INVITE = 'invite';
    const TYPE_OTHER = 'other';
}
