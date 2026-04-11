<?php

namespace app\common\library;

use think\Db;
use think\Log;
use app\common\model\InviteCommissionLog;
use app\common\model\UserInviteStat;
use app\common\model\UserCommissionStat;
use app\common\model\DailyCommissionStat;

/**
 * 邀请分佣定时任务
 * 
 * 分佣流程说明：
 * 1. 用户观看广告获得广告待释放金币时，AdIncomeService::handleAdCallback() 计算分佣
 * 2. 分佣记录写入 invite_commission_log（status=3 冻结状态）
 * 3. 用户领取待释放金币(claimFreezeBalance)时，冻结分佣同步解冻结算到上级账户
 * 4. 本定时任务用于：清理过期分佣、汇总统计等维护操作
 */
class InviteCommissionTask
{
    /**
     * 结算待处理的分佣记录
     * 
     * ★ 已废弃：新分佣流程使用冻结模式(status=3)，在用户领取待释放金币时同步结算。
     * 本方法仅处理旧的 STATUS_PENDING(0) 记录，用于兼容历史数据。
     * 如果没有历史遗留的 PENDING 记录，此方法会直接返回空结果。
     * 
     * @param int $limit 每次处理数量
     * @return array
     * @deprecated 新分佣走 handleAdCallback → claimFreezeBalance → settleFrozenCommissions
     */
    public function settlePendingCommission($limit = 100)
    {
        $result = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
        ];
        
        // 获取延迟时间（奖励产生后延迟多少秒发放佣金，用于风控缓冲）
        $delay = $this->getConfig('invite_commission_delay', 300);
        $delayTime = time() - $delay;
        
        // 获取待结算的分佣记录
        $logs = InviteCommissionLog::where('status', InviteCommissionLog::STATUS_PENDING)
            ->where('createtime', '<=', $delayTime)
            ->limit($limit)
            ->select();
        
        $result['total'] = count($logs);
        
        if ($result['total'] == 0) {
            return $result;
        }
        
        $service = new InviteCommissionService();
        
        foreach ($logs as $log) {
            $settleResult = $service->settleCommission($log->id);
            if ($settleResult['success']) {
                $result['success']++;
            } else {
                $result['failed']++;
                Log::error("分佣结算失败: ID={$log->id}, Error={$settleResult['message']}");
            }
        }
        
        return $result;
    }
    
    /**
     * 重置每日统计
     * @return array
     */
    public function resetDailyStats()
    {
        $result = [
            'invite_stat' => 0,
            'commission_stat' => 0,
        ];
        
        try {
            // 重置邀请统计
            $result['invite_stat'] = UserInviteStat::resetDaily();
            
            // 重置佣金统计
            $result['commission_stat'] = UserCommissionStat::resetDaily();
            
            Log::info("每日统计重置完成: " . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error("每日统计重置失败: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 重置每周统计
     * @return array
     */
    public function resetWeeklyStats()
    {
        $result = [
            'invite_stat' => 0,
            'commission_stat' => 0,
        ];
        
        try {
            $result['invite_stat'] = UserInviteStat::resetWeekly();
            $result['commission_stat'] = UserCommissionStat::resetWeekly();
            
            Log::info("每周统计重置完成: " . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error("每周统计重置失败: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 重置每月统计
     * @return array
     */
    public function resetMonthlyStats()
    {
        $result = [
            'invite_stat' => 0,
            'commission_stat' => 0,
        ];
        
        try {
            $result['invite_stat'] = UserInviteStat::resetMonthly();
            $result['commission_stat'] = UserCommissionStat::resetMonthly();
            
            Log::info("每月统计重置完成: " . json_encode($result));
            
        } catch (\Exception $e) {
            Log::error("每月统计重置失败: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 更新每周/每月佣金统计
     * 将今日佣金累加到本周/本月统计
     * @return array
     */
    public function updatePeriodStats()
    {
        $result = [
            'week' => 0,
            'month' => 0,
        ];
        
        try {
            // 更新本周佣金
            $result['week'] = Db::name('user_commission_stat')
                ->where('id', '>', 0)
                ->update([
                    'week_commission' => Db::raw('week_commission + today_commission'),
                    'updatetime' => time(),
                ]);
            
            // 更新本月佣金
            $result['month'] = Db::name('user_commission_stat')
                ->where('id', '>', 0)
                ->update([
                    'month_commission' => Db::raw('month_commission + today_commission'),
                    'updatetime' => time(),
                ]);
            
        } catch (\Exception $e) {
            Log::error("周期统计更新失败: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 清理过期的分佣记录
     * @param int $days 保留天数
     * @return int 删除数量
     */
    public function cleanExpiredLogs($days = 90)
    {
        $expireTime = time() - ($days * 86400);
        
        // 只删除已取消和已结算的记录
        $count = InviteCommissionLog::whereIn('status', [
            InviteCommissionLog::STATUS_CANCELED,
            InviteCommissionLog::STATUS_SETTLED,
        ])
            ->where('createtime', '<', $expireTime)
            ->delete();
        
        Log::info("清理过期分佣记录: {$count}条");
        
        return $count;
    }
    
    /**
     * 汇总每日佣金统计
     * @param string $date 日期 Y-m-d
     * @return array
     */
    public function summaryDailyCommission($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $result = [
            'date' => $date,
            'total' => 0,
            'count' => 0,
        ];
        
        try {
            // 统计当天已结算的分佣
            $stat = InviteCommissionLog::where('status', InviteCommissionLog::STATUS_SETTLED)
                ->where('settle_time', '>=', strtotime($date . ' 00:00:00'))
                ->where('settle_time', '<=', strtotime($date . ' 23:59:59'))
                ->field([
                    'COUNT(*) as count',
                    'SUM(commission_amount) as total',
                    'SUM(coin_amount) as total_coin',
                    'source_type',
                ])
                ->group('source_type')
                ->select();
            
            $dailyStat = DailyCommissionStat::getOrCreate($date);
            
            foreach ($stat as $item) {
                $field = $item['source_type'] . '_commission';
                Db::name('daily_commission_stat')
                    ->where('id', $dailyStat->id)
                    ->update([
                        $field => $item['total'],
                        'total_commission' => Db::raw('total_commission + ' . $item['total']),
                        'total_coin' => Db::raw('total_coin + ' . $item['total_coin']),
                        'total_count' => Db::raw('total_count + ' . $item['count']),
                        'updatetime' => time(),
                    ]);
                
                $result['total'] += $item['total'];
                $result['count'] += $item['count'];
            }
            
        } catch (\Exception $e) {
            Log::error("每日佣金汇总失败: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 处理冻结的分佣
     * 
     * ★ 注意：新的广告分佣流程中，冻结分佣(status=3)会在用户领取待释放金币时
     * 同步解冻结算(claimFreezeBalance → settleFrozenCommissions)。
     * 分佣金币已从用户奖励中扣除，如果自动取消分佣记录，会导致用户金币已扣但上级未收到佣金。
     * 
     * 因此此方法改为：仅记录告警日志，不自动取消。
     * 如需清理长期未领取的冻结分佣，应由管理员手动处理（先退回用户金币，再取消分佣）。
     * 
     * @param int $freezeDays 冻结天数（默认90天）
     * @return array ['warn_count' => int, 'details' => array]
     */
    public function processFrozenCommission($freezeDays = 90)
    {
        $expireTime = time() - ($freezeDays * 86400);
        
        $logs = InviteCommissionLog::where('status', InviteCommissionLog::STATUS_FROZEN)
            ->where('createtime', '<', $expireTime)
            ->select();
        
        $warnCount = count($logs);
        
        if ($warnCount > 0) {
            // 汇总告警信息
            $userIds = [];
            $totalCoin = 0;
            foreach ($logs as $log) {
                $userIds[$log->user_id] = true;
                $totalCoin += (int)$log->coin_amount;
            }
            Log::warning("冻结分佣超时告警: {$warnCount}条记录超过{$freezeDays}天未结算，涉及" . count($userIds) . "个用户，共{$totalCoin}金币。请管理员手动处理。");
        }
        
        return [
            'warn_count' => $warnCount,
            'message' => $warnCount > 0 
                ? "发现{$warnCount}条超时冻结分佣，已记录告警日志，请手动处理" 
                : "无超时冻结分佣",
        ];
    }
    
    /**
     * 获取配置
     */
    protected function getConfig($name, $default = null)
    {
        try {
            $value = Db::name('config')->where('name', $name)->value('value');
            return $value !== null ? $value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
