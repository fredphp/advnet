<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 任务参与记录模型
 */
class TaskParticipation extends Model
{
    // 表名
    protected $name = 'task_participation';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_RECEIVED = 0;      // 已领取待完成
    const STATUS_COMPLETED = 1;     // 已完成待审核
    const STATUS_AUDIT_PASS = 2;    // 审核通过待发放
    const STATUS_REWARDED = 3;      // 已发放
    const STATUS_REJECTED = 4;      // 审核拒绝
    const STATUS_EXPIRED = 5;       // 已过期
    const STATUS_CANCELLED = 6;     // 已取消
    
    // 状态列表
    public static $statusList = [
        self::STATUS_RECEIVED => '已领取待完成',
        self::STATUS_COMPLETED => '已完成待审核',
        self::STATUS_AUDIT_PASS => '审核通过待发放',
        self::STATUS_REWARDED => '已发放',
        self::STATUS_REJECTED => '审核拒绝',
        self::STATUS_EXPIRED => '已过期',
        self::STATUS_CANCELLED => '已取消'
    ];
    
    // 审核状态
    const AUDIT_PENDING = 0;
    const AUDIT_PASS = 1;
    const AUDIT_REJECT = 2;
    
    public static $auditStatusList = [
        self::AUDIT_PENDING => '待审核',
        self::AUDIT_PASS => '通过',
        self::AUDIT_REJECT => '拒绝'
    ];
    
    // 追加属性
    protected $append = ['status_text', 'audit_status_text'];
    
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    public function getAuditStatusTextAttr($value, $data)
    {
        return self::$auditStatusList[$data['audit_status']] ?? '';
    }
    
    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return 'TP' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 创建参与记录
     */
    public static function createParticipation($userId, $taskId, $data = [])
    {
        $record = new self();
        $record->order_no = self::generateOrderNo();
        $record->user_id = $userId;
        $record->task_id = $taskId;
        $record->status = self::STATUS_RECEIVED;
        $record->start_time = time();
        $record->ip = $data['ip'] ?? null;
        $record->device_id = $data['device_id'] ?? null;
        $record->device_info = json_encode($data['device_info'] ?? []);
        $record->platform = $data['platform'] ?? null;
        $record->app_version = $data['app_version'] ?? null;
        $record->save();
        
        return $record;
    }
    
    /**
     * 完成任务
     */
    public function complete($data = [])
    {
        $this->status = self::STATUS_COMPLETED;
        $this->end_time = time();
        $this->duration = $this->end_time - $this->start_time;
        $this->progress = $data['progress'] ?? 100;
        
        if (!empty($data['screenshots'])) {
            $this->screenshot_urls = json_encode($data['screenshots']);
        }
        
        if (!empty($data['proof_data'])) {
            $this->proof_data = json_encode($data['proof_data']);
        }
        
        $this->extra_data = json_encode($data['extra_data'] ?? []);
        $this->save();
        
        return $this;
    }
    
    /**
     * 审核通过
     */
    public function auditPass($adminId = null, $adminName = null, $remark = '')
    {
        $this->status = self::STATUS_AUDIT_PASS;
        $this->audit_status = self::AUDIT_PASS;
        $this->audit_time = time();
        $this->audit_admin_id = $adminId;
        $this->audit_admin_name = $adminName;
        $this->audit_remark = $remark;
        $this->save();
        
        return $this;
    }
    
    /**
     * 审核拒绝
     */
    public function auditReject($reason, $adminId = null, $adminName = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->audit_status = self::AUDIT_REJECT;
        $this->audit_time = time();
        $this->audit_admin_id = $adminId;
        $this->audit_admin_name = $adminName;
        $this->reject_reason = $reason;
        $this->save();
        
        return $this;
    }
    
    /**
     * 发放奖励
     */
    public function reward($coin)
    {
        $this->status = self::STATUS_REWARDED;
        $this->reward_coin = $coin;
        $this->reward_status = 1;
        $this->reward_time = time();
        $this->save();
        
        return $this;
    }
    
    /**
     * 检查是否过期
     */
    public function isExpired($expireHours)
    {
        if ($this->status != self::STATUS_RECEIVED) {
            return false;
        }
        
        return (time() - $this->createtime) > ($expireHours * 3600);
    }
}
