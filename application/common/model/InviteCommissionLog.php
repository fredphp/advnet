<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 分佣记录模型
 */
class InviteCommissionLog extends Model
{
    // 表名
    protected $name = 'invite_commission_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_PENDING = 0;    // 待结算
    const STATUS_SETTLED = 1;    // 已结算
    const STATUS_CANCELED = 2;   // 已取消
    const STATUS_FROZEN = 3;     // 已冻结
    
    /**
     * 关联用户(产生收益的下级)
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 关联上级
     */
    public function parent()
    {
        return $this->belongsTo('User', 'parent_id');
    }
    
    /**
     * 生成分佣订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'IC' . date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * 创建分佣记录
     * @param array $data 分佣数据
     * @return InviteCommissionLog|null
     */
    public static function createLog($data)
    {
        $log = new self();
        $log->order_no = self::generateOrderNo();
        $log->source_type = $data['source_type'];
        $log->source_id = $data['source_id'] ?? null;
        $log->source_order_no = $data['source_order_no'] ?? null;
        $log->user_id = $data['user_id'];
        $log->parent_id = $data['parent_id'];
        $log->level = $data['level'];
        $log->source_amount = $data['source_amount'];
        $log->commission_rate = $data['commission_rate'] ?? 0;
        $log->commission_fixed = $data['commission_fixed'] ?? 0;
        $log->commission_amount = $data['commission_amount'];
        $log->coin_amount = $data['coin_amount'] ?? 0;
        $log->config_id = $data['config_id'] ?? null;
        $log->remark = $data['remark'] ?? null;
        $log->status = self::STATUS_PENDING;
        $log->save();
        
        return $log;
    }
    
    /**
     * 取消分佣
     *
     * ★ 取消冻结分佣(status=3)时，退还已扣除的金币到用户的 ad_freeze_balance。
     * 因为分佣金币在 handleAdCallback 中已从用户奖励中扣除，取消分佣必须退还。
     *
     * @param string $reason 取消原因
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancel($reason = '')
    {
        if ($this->status == self::STATUS_SETTLED) {
            return ['success' => false, 'message' => '已结算的记录不能取消'];
        }

        // 已取消的不重复操作
        if ($this->status == self::STATUS_CANCELED) {
            return ['success' => false, 'message' => '该记录已取消'];
        }

        $coinAmount = (int)$this->coin_amount;

        // ★ 冻结分佣取消时退还金币给用户（从 ad_freeze_balance 退还）
        if ($this->status == self::STATUS_FROZEN && $coinAmount > 0) {
            try {
                Db::startTrans();

                // 更新分佣记录状态
                $this->status = self::STATUS_CANCELED;
                $this->cancel_reason = $reason;
                $this->updatetime = time();
                $this->save();

                // 退还金币到用户的 ad_freeze_balance（使用乐观锁）
                $account = Db::name('coin_account')
                    ->where('user_id', $this->user_id)
                    ->lock(true)
                    ->find();

                if ($account) {
                    $affected = Db::name('coin_account')
                        ->where('user_id', $this->user_id)
                        ->where('version', $account['version'])
                        ->update([
                            'ad_freeze_balance' => Db::raw('ad_freeze_balance + ' . $coinAmount),
                            'version' => (int)$account['version'] + 1,
                            'updatetime' => time(),
                        ]);

                    if ($affected === 0) {
                        Db::rollback();
                        return ['success' => false, 'message' => '退还金币失败（账户版本冲突），请重试'];
                    }

                    // 写入退还流水
                    $logTable = \app\common\model\CoinLog::getOrCreateTable();
                    Db::name($logTable)->insert([
                        'user_id'        => $this->user_id,
                        'type'           => 'commission_refund',
                        'amount'         => $coinAmount,
                        'balance_before' => (int)$account['ad_freeze_balance'],
                        'balance_after'  => (int)$account['ad_freeze_balance'] + $coinAmount,
                        'relation_type'  => 'commission_cancel',
                        'relation_id'    => $this->id,
                        'description'    => '分佣取消退还' . $coinAmount . '金币（' . ($reason ?: '管理员取消') . '）',
                        'createtime'     => time(),
                        'create_date'    => date('Y-m-d'),
                    ]);
                }

                Db::commit();
                return ['success' => true, 'message' => '取消成功，已退还' . $coinAmount . '金币给用户'];

            } catch (\Exception $e) {
                Db::rollback();
                return ['success' => false, 'message' => '取消失败: ' . $e->getMessage()];
            }
        }

        // 非冻结状态（如 STATUS_PENDING），直接取消不退钱
        $this->status = self::STATUS_CANCELED;
        $this->cancel_reason = $reason;
        $this->updatetime = time();
        $this->save();

        return ['success' => true, 'message' => '取消成功'];
    }
    
    /**
     * 更新统计
     */
    protected function updateStat()
    {
        // 更新用户佣金统计
        $stat = UserCommissionStat::getOrCreate($this->parent_id);
        $stat->addCommission($this);
        
        // 更新每日统计
        DailyCommissionStat::addStat($this);
    }
    
    /**
     * 获取备注文本
     * @return string
     */
    public function getRemarkText()
    {
        $typeNames = [
            'withdraw' => '提现',
            'video' => '视频',
            'video_watch' => '视频',
            'video_share' => '视频',
            'red_packet' => '红包',
            'red_packet_grab' => '红包',
            'red_packet_click' => '红包',
            'ad_red_packet' => '红包',
            'game' => '游戏',
            'game_reward' => '游戏',
            'sign_in' => '签到',
            'sign_fillup_reward' => '签到',
            'task_reward' => '任务',
            'freeze_balance_claim' => '领取金币',
            'freeze_balance_claim_feed' => '领取金币',
            'freeze_balance_claim_reward' => '领取金币',
        ];
        
        $typeName = $typeNames[$this->source_type] ?? $this->source_type;
        $levelText = $this->level == 1 ? '一级' : '二级';
        
        return "{$levelText}下级{$typeName}分佣";
    }
    
    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            self::STATUS_PENDING => '待结算',
            self::STATUS_SETTLED => '已结算',
            self::STATUS_CANCELED => '已取消',
            self::STATUS_FROZEN => '已冻结',
        ];
        
        return $statusMap[$data['status']] ?? '未知';
    }
    
    /**
     * 获取用户的分佣记录
     * @param int $userId 用户ID(上级)
     * @param array $filters 筛选条件
     * @return array
     */
    public static function getUserLogs($userId, $filters = [])
    {
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $sourceType = $filters['source_type'] ?? '';
        $level = $filters['level'] ?? 0;
        
        $query = self::where('parent_id', $userId);
        
        if ($sourceType) {
            $query->where('source_type', $sourceType);
        }
        
        if ($level > 0) {
            $query->where('level', $level);
        }
        
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        
        return [
            'total' => $total,
            'list' => $list
        ];
    }
}
