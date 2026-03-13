<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\RiskControlService;
use app\common\library\AutoBanService;
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
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // 今日统计数据
        $todayStats = [
            'violation_count' => 0,
            'ban_count' => 0,
            'warn_count' => 0,
            'blacklist_count' => 0,
            'ip_blacklist' => 0,
            'device_blacklist' => 0,
            'user_blacklist' => 0,
        ];
        
        // 今日违规次数
        $todayStats['violation_count'] = Db::name('risk_log')
            ->whereTime('createtime', 'today')
            ->count();
        
        // 今日封禁人数
        $todayStats['ban_count'] = Db::name('ban_record')
            ->whereTime('createtime', 'today')
            ->count();
        
        // 昨日违规次数（用于对比）
        $yesterdayViolations = Db::name('risk_log')
            ->whereTime('createtime', 'yesterday')
            ->count();
        
        // 黑名单统计
        $blacklistStats = Db::name('risk_blacklist')
            ->field('type, COUNT(*) as count')
            ->where('enabled', 1)
            ->group('type')
            ->select();
        
        foreach ($blacklistStats as $stat) {
            $todayStats['blacklist_count'] += $stat['count'];
            if ($stat['type'] === 'ip') {
                $todayStats['ip_blacklist'] = $stat['count'];
            } elseif ($stat['type'] === 'device') {
                $todayStats['device_blacklist'] = $stat['count'];
            } elseif ($stat['type'] === 'user') {
                $todayStats['user_blacklist'] = $stat['count'];
            }
        }
        
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
            ->field('FROM_UNIXTIME(createtime, "%H") as hour, COUNT(*) as count')
            ->where('createtime', '>', time() - 86400)
            ->group('hour')
            ->order('hour', 'asc')
            ->select();
        
        // 规则触发统计
        $ruleTriggerStats = Db::name('risk_log')
            ->field('rule_code, rule_name, rule_type, COUNT(*) as trigger_count')
            ->where('createtime', '>', time() - 86400)
            ->group('rule_code')
            ->order('trigger_count', 'desc')
            ->limit(10)
            ->select();
        
        // 最近封禁记录 - 排除已撤销风控的用户
        // 获取最近7天内有撤销记录的用户
        $revokedUserIds = Db::name('risk_log')
            ->where('rule_code', 'like', 'REVOKE_%')
            ->where('createtime', '>', time() - 7 * 86400)
            ->column('user_id');
        
        $recentBansQuery = Db::name('ban_record')
            ->alias('br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->field('br.*, u.username, u.nickname')
            ->where('br.createtime', '>', time() - 86400);
        
        // 排除已撤销的用户
        if (!empty($revokedUserIds)) {
            $recentBansQuery->whereNotIn('br.user_id', $revokedUserIds);
        }
        
        $recentBans = $recentBansQuery->order('br.createtime', 'desc')
            ->limit(10)
            ->select();
        
        // 风险预警
        $alerts = [
            'high_risk' => [],
            'dangerous' => [],
            'recent_violators' => [],
        ];
        
        try {
            $alertData = (new AutoBanService())->getRiskUserAlerts();
            if (isset($alertData['high_risk'])) {
                $alerts['high_risk'] = $alertData['high_risk'];
            }
            if (isset($alertData['dangerous'])) {
                $alerts['dangerous'] = $alertData['dangerous'];
            }
            if (isset($alertData['recent_violators'])) {
                $alerts['recent_violators'] = $alertData['recent_violators'];
            }
            
            // 过滤已撤销的用户
            if (!empty($revokedUserIds)) {
                $alerts['high_risk'] = array_filter($alerts['high_risk'], function($item) use ($revokedUserIds) {
                    return !in_array($item['user_id'], $revokedUserIds);
                });
                $alerts['dangerous'] = array_filter($alerts['dangerous'], function($item) use ($revokedUserIds) {
                    return !in_array($item['user_id'], $revokedUserIds);
                });
                $alerts['recent_violators'] = array_filter($alerts['recent_violators'], function($item) use ($revokedUserIds) {
                    return !in_array($item['user_id'], $revokedUserIds);
                });
            }
            
            // 为每个预警用户添加当前状态信息
            $alertUserIds = [];
            foreach ($alerts['high_risk'] as $item) {
                $alertUserIds[] = $item['user_id'];
            }
            foreach ($alerts['dangerous'] as $item) {
                $alertUserIds[] = $item['user_id'];
            }
            
            if (!empty($alertUserIds)) {
                $userStatuses = Db::name('user_risk_score')
                    ->where('user_id', 'in', $alertUserIds)
                    ->column('status', 'user_id');
                
                // 为high_risk添加状态
                foreach ($alerts['high_risk'] as &$item) {
                    $item['current_status'] = $userStatuses[$item['user_id']] ?? 'normal';
                }
                // 为dangerous添加状态
                foreach ($alerts['dangerous'] as &$item) {
                    $item['current_status'] = $userStatuses[$item['user_id']] ?? 'normal';
                }
            }
            
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        // 计算变化
        $violationsChange = '-';
        if ($yesterdayViolations > 0) {
            $change = $todayStats['violation_count'] - $yesterdayViolations;
            if ($change > 0) {
                $violationsChange = '<span class="text-danger">↑ ' . $change . '</span>';
            } elseif ($change < 0) {
                $violationsChange = '<span class="text-success">↓ ' . abs($change) . '</span>';
            } else {
                $violationsChange = '<span class="text-muted">持平</span>';
            }
        }
        
        if ($this->request->isAjax()) {
            $this->success('', null, [
                'today_stats' => $todayStats,
                'yesterday_violations' => $yesterdayViolations,
                'violations_change' => $violationsChange,
                'risk_user_stats' => $riskUserStats ? (is_array($riskUserStats) ? $riskUserStats : $riskUserStats->toArray()) : [],
                'user_status_stats' => $userStatusStats ? (is_array($userStatusStats) ? $userStatusStats : $userStatusStats->toArray()) : [],
                'hourly_violations' => $hourlyViolations ? (is_array($hourlyViolations) ? $hourlyViolations : $hourlyViolations->toArray()) : [],
                'rule_trigger_stats' => $ruleTriggerStats ? (is_array($ruleTriggerStats) ? $ruleTriggerStats : $ruleTriggerStats->toArray()) : [],
                'recent_bans' => $recentBans ? (is_array($recentBans) ? $recentBans : $recentBans->toArray()) : [],
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
        
        $this->success('', null, [
            'online_risk_users' => $onlineRiskUsers ? (is_array($onlineRiskUsers) ? $onlineRiskUsers : $onlineRiskUsers->toArray()) : [],
            'recent_requests' => $recentRequests ? (is_array($recentRequests) ? $recentRequests : $recentRequests->toArray()) : [],
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
        
        $this->success('', null, [
            'rule_stats' => $ruleStats ? (is_array($ruleStats) ? $ruleStats : $ruleStats->toArray()) : [],
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
        
        $this->success('', null, [
            'score_history' => $history,
            'recent_violations' => $recentViolations ? (is_array($recentViolations) ? $recentViolations : $recentViolations->toArray()) : [],
            'behavior_stats' => $behaviorStats ? (is_array($behaviorStats) ? $behaviorStats : $behaviorStats->toArray()) : [],
        ]);
    }
    
    /**
     * 获取实时统计数据（用于定时刷新）
     */
    public function getRealtimeStats()
    {
        $data = [
            'violation_count' => Db::name('risk_log')->whereTime('createtime', 'today')->count(),
            'ban_count' => Db::name('ban_record')->whereTime('createtime', 'today')->count(),
            'high_risk_count' => Db::name('user_risk_score')
                ->whereIn('risk_level', ['high', 'dangerous'])
                ->where('status', 'normal')
                ->count(),
            'online_users' => Db::name('user')->where('logintime', '>', time() - 300)->count(),
        ];
        
        $this->success('', null, $data);
    }
    
    /**
     * 获取用户信息（用于冻结/封禁前展示）
     */
    public function getUserInfo()
    {
        $userId = $this->request->get('user_id');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 用户基本信息
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        
        // 风险评分详情
        $riskScore = Db::name('user_risk_score')->where('user_id', $userId)->find();
        if (!$riskScore) {
            $riskScore = [
                'total_score' => 0,
                'risk_level' => 'safe',
                'violation_count' => 0,
                'status' => 'normal',
            ];
        }
        
        // 最近违规记录（排除撤销类型）
        $riskLogs = Db::name('risk_log')
            ->where('user_id', $userId)
            ->where('rule_code', 'not like', 'REVOKE_%')
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();
        
        $logsData = [];
        if ($riskLogs) {
            foreach ($riskLogs as $log) {
                $log['createtime_text'] = date('Y-m-d H:i:s', $log['createtime']);
                $logsData[] = $log;
            }
        }
        
        $this->success('', null, [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'] ?? '-',
                'nickname' => $user['nickname'] ?? '-',
                'mobile' => $user['mobile'] ?? '-',
            ],
            'risk_score' => $riskScore,
            'risk_logs' => $logsData,
        ]);
    }
    
    /**
     * 冻结用户
     */
    public function freeze()
    {
        $userId = $this->request->post('user_id');
        $duration = $this->request->post('duration', 7); // 默认冻结7天
        $reason = $this->request->post('reason', '管理员手动冻结');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 检查用户当前状态
        $currentStatus = Db::name('user_risk_score')->where('user_id', $userId)->value('status');
        if ($currentStatus === 'frozen') {
            $this->error('用户已处于冻结状态');
        }
        if ($currentStatus === 'banned') {
            $this->error('用户已被封禁，无法冻结');
        }
        
        // 更新用户状态
        $freezeExpireTime = time() + $duration * 86400;
        Db::name('user_risk_score')->where('user_id', $userId)->update([
            'status' => 'frozen',
            'freeze_expire_time' => $freezeExpireTime,
            'updatetime' => time(),
        ]);
        
        // 记录封禁记录
        Db::name('ban_record')->insert([
            'user_id' => $userId,
            'ban_type' => 'temporary',
            'ban_reason' => $reason,
            'ban_source' => 'manual',
            'risk_score' => Db::name('user_risk_score')->where('user_id', $userId)->value('total_score') ?? 0,
            'start_time' => time(),
            'end_time' => $freezeExpireTime,
            'duration' => $duration * 86400,
            'status' => 'active',
            'admin_id' => $this->auth->id,
            'admin_name' => $this->auth->username,
            'createtime' => time(),
        ]);
        
        $this->success('冻结成功');
    }
    
    /**
     * 封禁用户
     */
    public function ban()
    {
        $userId = $this->request->post('user_id');
        $reason = $this->request->post('reason', '管理员手动封禁');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 检查用户当前状态
        $currentStatus = Db::name('user_risk_score')->where('user_id', $userId)->value('status');
        if ($currentStatus === 'banned') {
            $this->error('用户已被封禁');
        }
        
        // 更新用户状态
        Db::name('user_risk_score')->where('user_id', $userId)->update([
            'status' => 'banned',
            'ban_expire_time' => null, // 永久封禁
            'updatetime' => time(),
        ]);
        
        // 记录封禁记录
        Db::name('ban_record')->insert([
            'user_id' => $userId,
            'ban_type' => 'permanent',
            'ban_reason' => $reason,
            'ban_source' => 'manual',
            'risk_score' => Db::name('user_risk_score')->where('user_id', $userId)->value('total_score') ?? 0,
            'start_time' => time(),
            'end_time' => null,
            'duration' => 0,
            'status' => 'active',
            'admin_id' => $this->auth->id,
            'admin_name' => $this->auth->username,
            'createtime' => time(),
        ]);
        
        // 加入黑名单
        Db::name('risk_blacklist')->insert([
            'type' => 'user',
            'value' => $userId,
            'reason' => $reason,
            'enabled' => 1,
            'admin_id' => $this->auth->id,
            'admin_name' => $this->auth->username,
            'createtime' => time(),
        ]);
        
        $this->success('封禁成功');
    }
}
