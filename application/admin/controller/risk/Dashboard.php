<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\RiskControlService;
use app\common\library\AutoBanService;
use app\common\library\DeviceFingerprintService;
use think\Db;

/**
 * 风控仪表盘
 */
class Dashboard extends Backend
{
    /**
     * 风控概览
     */
    public function index()
    {
        $today = date('Y-m-d');
        
        // 今日统计数据
        $todayStats = Db::name('risk_stat')->where('stat_date', $today)->find();
        
        // 风险用户统计
        $riskUserStats = Db::name('user_risk_score')
            ->field('risk_level, COUNT(*) as count')
            ->group('risk_level')
            ->select();
        
        // 用户状态统计
        $userStatusStats = Db::name('user_risk_score')
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select();
        
        // 最近24小时违规统计
        $hourlyViolations = Db::name('risk_log')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d %H:00") as hour, COUNT(*) as count')
            ->where('createtime', '>', time() - 86400)
            ->group('hour')
            ->order('hour', 'asc')
            ->select();
        
        // 规则触发统计
        $ruleTriggerStats = Db::name('risk_log')
            ->field('rule_code, rule_name, COUNT(*) as trigger_count')
            ->where('createtime', '>', time() - 86400)
            ->group('rule_code')
            ->order('trigger_count', 'desc')
            ->limit(10)
            ->select();
        
        // 最近封禁记录
        $recentBans = Db::name('ban_record')
            ->alias('br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->field('br.*, u.username, u.nickname')
            ->where('br.createtime', '>', time() - 86400)
            ->order('br.createtime', 'desc')
            ->limit(10)
            ->select();
        
        // 风险预警
        $alerts = [];
        try {
            $alerts = (new AutoBanService())->getRiskUserAlerts();
        } catch (\Exception $e) {
            $alerts = [];
        }
        
        if ($this->request->isAjax()) {
            $this->success('', [
                'today_stats' => $todayStats,
                'risk_user_stats' => $riskUserStats,
                'user_status_stats' => $userStatusStats,
                'hourly_violations' => $hourlyViolations,
                'rule_trigger_stats' => $ruleTriggerStats,
                'recent_bans' => $recentBans,
                'alerts' => $alerts,
            ]);
        }
        
        $this->view->assign('today_stats', $todayStats);
        $this->view->assign('risk_user_stats', $riskUserStats);
        $this->view->assign('user_status_stats', $userStatusStats);
        $this->view->assign('hourly_violations', $hourlyViolations);
        $this->view->assign('rule_trigger_stats', $ruleTriggerStats);
        $this->view->assign('recent_bans', $recentBans);
        $this->view->assign('alerts', $alerts);
        return $this->view->fetch();
    }
    
    /**
     * 实时监控数据
     */
    public function realtime()
    {
        // 当前在线风险用户
        $onlineRiskUsers = Db::name('user_risk_score rs')
            ->join('user u', 'u.id = rs.user_id')
            ->field('u.id, u.username, u.nickname, rs.total_score, rs.risk_level, rs.violation_count')
            ->where('rs.total_score', '>=', 150)
            ->where('rs.status', 'normal')
            ->order('rs.total_score', 'desc')
            ->limit(20)
            ->select();
        
        // 最近10分钟请求统计
        $recentRequests = Db::name('risk_log')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d %H:%i") as minute, COUNT(*) as count')
            ->where('createtime', '>', time() - 600)
            ->group('minute')
            ->order('minute', 'asc')
            ->select();
        
        // IP黑名单统计
        $ipBlacklistCount = Db::name('risk_blacklist')
            ->where('type', 'ip')
            ->where('enabled', 1)
            ->count();
        
        // 设备黑名单统计
        $deviceBlacklistCount = Db::name('risk_blacklist')
            ->where('type', 'device')
            ->where('enabled', 1)
            ->count();
        
        $this->success('', [
            'online_risk_users' => $onlineRiskUsers,
            'recent_requests' => $recentRequests,
            'ip_blacklist_count' => $ipBlacklistCount,
            'device_blacklist_count' => $deviceBlacklistCount,
        ]);
    }
    
    /**
     * 规则统计
     */
    public function ruleStats()
    {
        $days = $this->request->get('days', 7);
        $startDate = date('Y-m-d', time() - $days * 86400);
        
        // 按规则统计触发次数
        $ruleStats = Db::name('risk_log')
            ->field('rule_code, rule_name, rule_type, 
                     COUNT(*) as total_count,
                     SUM(CASE WHEN action = "warn" THEN 1 ELSE 0 END) as warn_count,
                     SUM(CASE WHEN action = "block" THEN 1 ELSE 0 END) as block_count,
                     SUM(CASE WHEN action = "freeze" THEN 1 ELSE 0 END) as freeze_count,
                     SUM(CASE WHEN action = "ban" THEN 1 ELSE 0 END) as ban_count')
            ->where('createtime', '>=', strtotime($startDate))
            ->group('rule_code')
            ->order('total_count', 'desc')
            ->select();
        
        $this->success('', [
            'rule_stats' => $ruleStats,
            'start_date' => $startDate,
            'end_date' => date('Y-m-d'),
        ]);
    }
    
    /**
     * 用户风险趋势
     */
    public function userTrend()
    {
        $userId = $this->request->get('user_id');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 获取最近30天的风险分变化
        $riskHistory = Db::name('user_risk_score')
            ->where('user_id', $userId)
            ->field('score_history')
            ->find();
        
        $history = [];
        if ($riskHistory && $riskHistory['score_history']) {
            $history = json_decode($riskHistory['score_history'], true);
            // 只返回最近30条
            $history = array_slice($history, -30);
        }
        
        // 最近违规记录
        $recentViolations = Db::name('risk_log')
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->limit(20)
            ->select();
        
        // 行为统计
        $behaviorStats = Db::name('user_behavior_stat')
            ->where('user_id', $userId)
            ->order('stat_date', 'desc')
            ->limit(30)
            ->select();
        
        $this->success('', [
            'score_history' => $history,
            'recent_violations' => $recentViolations,
            'behavior_stats' => $behaviorStats,
        ]);
    }
}
