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
 * 1. 用户获取奖励时，CoinService::addCoin() 自动计算分佣并从奖励中扣除
 * 2. 分佣记录写入 invite_commission_log（status=0 待结算）
 * 3. 本定时任务定期结算待处理的分佣记录，将金币发放到上级账户
 */
class InviteCommissionTask
{
    /**
     * 结算待处理的分佣记录
     * @param int $limit 每次处理数量
     * @return array
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
     * 检查冻结超过一定时间的分佣，自动取消
     * @param int $freezeDays 冻结天数
     * @return int 取消数量
     */
    public function processFrozenCommission($freezeDays = 30)
    {
        $expireTime = time() - ($freezeDays * 86400);
        
        $logs = InviteCommissionLog::where('status', InviteCommissionLog::STATUS_FROZEN)
            ->where('createtime', '<', $expireTime)
            ->select();
        
        $count = 0;
        foreach ($logs as $log) {
            if ($log->cancel('冻结超时自动取消')) {
                $count++;
            }
        }
        
        Log::info("处理冻结分佣: 取消{$count}条");
        
        return $count;
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
