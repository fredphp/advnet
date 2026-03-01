<?php

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 红包任务模型
 */
class RedPacketTask extends Model
{
    // 表名
    protected $name = 'red_packet_task';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'task_type_text',
        'amount_type_text',
        'verify_method_text',
        'audit_type_text',
        'status_text'
    ];
    
    // 任务类型
    public static $taskTypeList = [
        'download_app' => '下载App',
        'mini_program' => '跳转小程序',
        'play_game' => '玩游戏时长',
        'watch_video' => '观看视频',
        'share_link' => '分享链接',
        'sign_in' => '签到任务'
    ];
    
    // 金额类型
    public static $amountTypeList = [
        'fixed' => '固定金额',
        'random' => '随机金额'
    ];
    
    // 验证方式
    public static $verifyMethodList = [
        'auto' => '自动验证',
        'manual' => '人工审核',
        'third_party' => '第三方验证'
    ];
    
    // 审核方式
    public static $auditTypeList = [
        'auto' => '自动审核',
        'manual' => '人工审核'
    ];
    
    // 状态
    public static $statusList = [
        0 => '禁用',
        1 => '进行中',
        2 => '已结束',
        3 => '已抢完'
    ];
    
    public function getTaskTypeTextAttr($value, $data)
    {
        return self::$taskTypeList[$data['task_type']] ?? '';
    }
    
    public function getAmountTypeTextAttr($value, $data)
    {
        return self::$amountTypeList[$data['amount_type']] ?? '';
    }
    
    public function getVerifyMethodTextAttr($value, $data)
    {
        return self::$verifyMethodList[$data['verify_method']] ?? '';
    }
    
    public function getAuditTypeTextAttr($value, $data)
    {
        return self::$auditTypeList[$data['audit_type']] ?? '';
    }
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo('TaskCategory', 'category_id');
    }
    
    /**
     * 获取进行中的任务列表
     */
    public static function getActiveTasks($limit = 10, $userId = null)
    {
        $query = self::where('status', 1)
            ->where(function ($q) {
                $q->whereNull('start_time')->whereOr('start_time', '<=', time());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->whereOr('end_time', '>=', time());
            })
            ->where('remain_count', '>', 0)
            ->order('is_recommend', 'desc')
            ->order('is_hot', 'desc')
            ->order('sort', 'asc');
        
        $list = $query->limit($limit)->select();
        
        // 如果提供了用户ID，标记领取状态
        if ($userId) {
            foreach ($list as $task) {
                $task->user_receive_count = TaskParticipation::where('user_id', $userId)
                    ->where('task_id', $task->id)
                    ->whereIn('status', [0, 1, 2, 3])
                    ->count();
                $task->can_receive = $task->user_receive_count < $task->user_limit;
            }
        }
        
        return $list;
    }
    
    /**
     * 检查用户是否可以领取
     */
    public function canUserReceive($userId)
    {
        // 检查任务状态
        if ($this->status != 1) {
            return ['can' => false, 'message' => '任务已结束'];
        }
        
        // 检查时间
        if ($this->start_time && time() < $this->start_time) {
            return ['can' => false, 'message' => '任务尚未开始'];
        }
        
        if ($this->end_time && time() > $this->end_time) {
            return ['can' => false, 'message' => '任务已结束'];
        }
        
        // 检查库存
        if ($this->remain_count <= 0) {
            return ['can' => false, 'message' => '红包已抢完'];
        }
        
        // 检查用户领取次数
        $receiveCount = TaskParticipation::where('user_id', $userId)
            ->where('task_id', $this->id)
            ->whereIn('status', [0, 1, 2, 3])
            ->count();
        
        if ($receiveCount >= $this->user_limit) {
            return ['can' => false, 'message' => '您已达到领取上限'];
        }
        
        // 检查每日限制
        if ($this->daily_limit > 0) {
            $todayCount = TaskParticipation::where('user_id', $userId)
                ->where('task_id', $this->id)
                ->whereTime('createtime', 'today')
                ->count();
            
            if ($todayCount >= $this->daily_limit) {
                return ['can' => false, 'message' => '今日领取次数已达上限'];
            }
        }
        
        // 检查新用户限制
        if ($this->new_user_only) {
            $user = Db::name('user')->where('id', $userId)->find();
            $registerDays = floor((time() - $user['createtime']) / 86400);
            if ($registerDays > $this->new_user_days) {
                return ['can' => false, 'message' => '仅限新用户参与'];
            }
        }
        
        // 检查用户等级
        $user = Db::name('user')->where('id', $userId)->find();
        if ($user['level'] < $this->user_level_min || $user['level'] > $this->user_level_max) {
            return ['can' => false, 'message' => '您的等级不满足要求'];
        }
        
        return ['can' => true, 'message' => ''];
    }
    
    /**
     * 计算奖励金额
     */
    public function calculateReward()
    {
        if ($this->amount_type == 'random') {
            return mt_rand($this->min_amount * 100, $this->max_amount * 100) / 100;
        }
        return $this->single_amount;
    }
    
    /**
     * 减少库存
     */
    public function decreaseStock($amount)
    {
        return self::where('id', $this->id)
            ->where('remain_count', '>=', 1)
            ->where('remain_amount', '>=', $amount)
            ->update([
                'remain_count' => Db::raw('remain_count - 1'),
                'remain_amount' => Db::raw('remain_amount - ' . $amount),
                'receive_count' => Db::raw('receive_count + 1'),
                'receive_amount' => Db::raw('receive_amount + ' . $amount)
            ]);
    }
}
