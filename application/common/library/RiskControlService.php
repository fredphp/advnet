<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Cache;
use think\Log;
use app\common\model\UserRiskScore;
use app\common\model\RiskRule;
use app\common\model\RiskLog;
use app\common\model\IpRisk;
use app\common\model\DeviceFingerprint;
use app\common\model\UserBehavior;
use app\common\model\UserBehaviorStat;
use app\common\model\BanRecord;
use app\common\model\RiskWhitelist;
use app\common\model\RiskBlacklist;

/**
 * 风控评分服务
 * 
 * 优化版本：
 * 1. 使用统一配置服务
 * 2. 修复潜在bug
 * 3. 优化性能
 */
class RiskControlService
{
    // 规则类型
    const RULE_TYPE_VIDEO = 'video';
    const RULE_TYPE_TASK = 'task';
    const RULE_TYPE_WITHDRAW = 'withdraw';
    const RULE_TYPE_REDPACKET = 'redpacket';
    const RULE_TYPE_INVITE = 'invite';
    const RULE_TYPE_GLOBAL = 'global';
    
    // 动作类型
    const ACTION_WARN = 'warn';
    const ACTION_BLOCK = 'block';
    const ACTION_FREEZE = 'freeze';
    const ACTION_BAN = 'ban';
    
    /**
     * @var array 规则缓存
     */
    protected static $rulesCache = null;
    
    /**
     * @var array 配置缓存
     */
    protected $config = null;
    
    /**
     * @var int 用户ID
     */
    protected $userId = 0;
    
    /**
     * @var string 设备ID
     */
    protected $deviceId = '';
    
    /**
     * @var string IP地址
     */
    protected $ip = '';
    
    /**
     * @var string User-Agent
     */
    protected $userAgent = '';
    
    /**
     * @var array 请求数据
     */
    protected $requestData = [];
    
    /**
     * 初始化风控服务
     */
    public function init($userId, $deviceId = '', $ip = '', $userAgent = '')
    {
        $this->userId = (int)$userId;
        $this->deviceId = trim($deviceId) ?: $this->getDeviceId();
        $this->ip = trim($ip) ?: $this->getClientIp();
        $this->userAgent = trim($userAgent) ?: $this->getUserAgent();
        
        // 加载配置
        $this->loadConfig();
        
        return $this;
    }
    
    /**
     * 加载配置
     */
    protected function loadConfig()
    {
        if ($this->config === null) {
            $this->config = SystemConfigService::getRiskConfig();
        }
    }
    
    /**
     * 获取配置值
     */
    protected function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 风控检查入口
     */
    public function check($ruleType, $action, array $context = [])
    {
        $result = [
            'passed' => true,
            'risk_level' => 'safe',
            'risk_score' => 0,
            'actions' => [],
            'violations' => [],
            'message' => '',
        ];
        
        try {
            // 检查风控是否启用
            if (!$this->getConfig('risk_enabled', true)) {
                return $result;
            }
            
            // 1. 检查白名单
            if ($this->isInWhitelist()) {
                return $result;
            }
            
            // 2. 检查黑名单
            $blacklistResult = $this->checkBlacklist();
            if (!$blacklistResult['passed']) {
                return $blacklistResult;
            }
            
            // 3. 检查用户状态
            $statusResult = $this->checkUserStatus();
            if (!$statusResult['passed']) {
                return $statusResult;
            }
            
            // 4. 执行规则检查
            $ruleResult = $this->executeRules($ruleType, $action, $context);
            if (!$ruleResult['passed']) {
                $result = $ruleResult;
            }
            
            // 5. 计算综合风险评分
            $result['risk_score'] = $this->calculateRiskScore();
            $result['risk_level'] = $this->getRiskLevel($result['risk_score']);
            
            // 6. 执行风险动作（仅在开启自动封禁时）
            if ($this->getConfig('auto_ban_enabled', true) && $result['risk_score'] >= $this->getConfig('freeze_threshold', 300)) {
                $this->executeRiskAction($result);
            }
            
        } catch (Exception $e) {
            // 风控异常不影响正常流程，记录日志
            Log::error('RiskControl check error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 执行规则检查
     */
    protected function executeRules($ruleType, $action, array $context)
    {
        $result = [
            'passed' => true,
            'risk_level' => 'safe',
            'risk_score' => 0,
            'actions' => [],
            'violations' => [],
            'message' => '',
        ];
        
        // 获取启用的规则
        $rules = $this->getRules($ruleType);
        
        foreach ($rules as $rule) {
            $checkResult = $this->checkRule($rule, $action, $context);
            
            if (!$checkResult['passed']) {
                $result['passed'] = false;
                $result['violations'][] = [
                    'rule_code' => $rule['rule_code'],
                    'rule_name' => $rule['rule_name'],
                    'trigger_value' => $checkResult['trigger_value'],
                    'threshold' => $rule['threshold'],
                ];
                
                // 增加风险分
                $this->addRiskScore($rule['score_weight'], $rule['rule_type'], $rule['rule_code']);
                
                // 执行动作
                if ($rule['action'] !== self::ACTION_WARN) {
                    $result['actions'][] = [
                        'action' => $rule['action'],
                        'duration' => $rule['action_duration'],
                        'rule_code' => $rule['rule_code'],
                    ];
                }
                
                // 记录日志
                $this->logRiskEvent($rule, $checkResult['trigger_value']);
                
                // 更新统计
                $this->updateRuleTriggerStat($rule['rule_code']);
            }
        }
        
        if (!$result['passed']) {
            $result['message'] = '检测到异常行为，请稍后再试';
        }
        
        return $result;
    }
    
    /**
     * 检查单个规则
     */
    protected function checkRule($rule, $action, array $context)
    {
        $result = [
            'passed' => true,
            'trigger_value' => 0,
        ];
        
        $ruleCode = $rule['rule_code'];
        $threshold = floatval($rule['threshold']);
        
        // 根据规则编码执行不同的检查逻辑
        switch ($ruleCode) {
            // 视频相关规则
            case 'VIDEO_WATCH_SPEED':
                $result = $this->checkVideoWatchSpeed($context, $threshold);
                break;
            case 'VIDEO_WATCH_REPEAT':
                $result = $this->checkVideoWatchRepeat($context, $threshold);
                break;
            case 'VIDEO_DAILY_LIMIT':
                $result = $this->checkVideoDailyLimit($threshold);
                break;
            case 'VIDEO_REWARD_SPEED':
                $result = $this->checkVideoRewardSpeed($context, $threshold);
                break;
            case 'VIDEO_SKIP_RATIO':
                $result = $this->checkVideoSkipRatio($threshold);
                break;
                
            // 任务相关规则
            case 'TASK_COMPLETE_SPEED':
                $result = $this->checkTaskCompleteSpeed($context, $threshold);
                break;
            case 'TASK_DAILY_LIMIT':
                $result = $this->checkTaskDailyLimit($threshold);
                break;
            case 'TASK_REPEAT_SUBMIT':
                $result = $this->checkTaskRepeatSubmit($context, $threshold);
                break;
            case 'TASK_FAKE_BEHAVIOR':
                $result = $this->checkTaskFakeBehavior($context, $threshold);
                break;
                
            // 提现相关规则
            case 'WITHDRAW_FREQUENCY':
                $result = $this->checkWithdrawFrequency($threshold);
                break;
            case 'WITHDRAW_AMOUNT_ANOMALY':
                $result = $this->checkWithdrawAmountAnomaly($context, $threshold);
                break;
            case 'WITHDRAW_NEW_ACCOUNT':
                $result = $this->checkWithdrawNewAccount($context, $threshold);
                break;
                
            // 红包相关规则
            case 'REDPACKET_GRAB_SPEED':
                $result = $this->checkRedpacketGrabSpeed($context, $threshold);
                break;
            case 'REDPACKET_DAILY_LIMIT':
                $result = $this->checkRedpacketDailyLimit($threshold);
                break;
                
            // 邀请相关规则
            case 'INVITE_SPEED':
                $result = $this->checkInviteSpeed($threshold);
                break;
            case 'INVITE_FAKE_ACCOUNT':
                $result = $this->checkInviteFakeAccount($context, $threshold);
                break;
                
            // 全局规则
            case 'IP_MULTI_ACCOUNT':
                $result = $this->checkIpMultiAccount($threshold);
                break;
            case 'DEVICE_MULTI_ACCOUNT':
                $result = $this->checkDeviceMultiAccount($threshold);
                break;
            case 'BEHAVIOR_PATTERN':
                $result = $this->checkBehaviorPattern($threshold);
                break;
        }
        
        return $result;
    }
    
    // ==================== 视频防刷规则 ====================
    
    /**
     * 检查视频观看速度
     */
    protected function checkVideoWatchSpeed($context, $threshold)
    {
        $watchDuration = (float)($context['watch_duration'] ?? 0);
        $videoDuration = (float)($context['video_duration'] ?? 0);
        
        if ($videoDuration <= 0) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        // 计算观看速度比率
        $speedRatio = $watchDuration / $videoDuration;
        
        // 检查最近N个视频的平均观看速度
        $cacheKey = "risk:video_speed:{$this->userId}";
        $recentSpeeds = Cache::get($cacheKey, []);
        
        $recentSpeeds[] = $speedRatio;
        $recentSpeeds = array_slice($recentSpeeds, -10); // 只保留最近10个
        
        Cache::set($cacheKey, $recentSpeeds, 3600);
        
        $avgSpeed = count($recentSpeeds) > 0 ? array_sum($recentSpeeds) / count($recentSpeeds) : 1;
        
        // 如果平均观看速度过快（小于阈值的比例）
        $passed = $avgSpeed >= $threshold;
        
        return [
            'passed' => $passed,
            'trigger_value' => round($avgSpeed, 4),
        ];
    }
    
    /**
     * 检查重复观看同一视频
     */
    protected function checkVideoWatchRepeat($context, $threshold)
    {
        $videoId = (int)($context['video_id'] ?? 0);
        if (!$videoId) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        // 获取最近观看的视频ID列表
        $cacheKey = "risk:video_repeat:{$this->userId}";
        $recentVideos = Cache::get($cacheKey, []);
        
        // 统计同一视频的观看次数
        $watchCount = 0;
        foreach ($recentVideos as $vid) {
            if ($vid == $videoId) {
                $watchCount++;
            }
        }
        
        // 添加当前视频
        $recentVideos[] = $videoId;
        $recentVideos = array_slice($recentVideos, -100);
        Cache::set($cacheKey, $recentVideos, 3600);
        
        return [
            'passed' => $watchCount < $threshold,
            'trigger_value' => $watchCount,
        ];
    }
    
    /**
     * 检查视频观看每日限制
     */
    protected function checkVideoDailyLimit($threshold)
    {
        $today = date('Y-m-d');
        $stat = UserBehaviorStat::where('user_id', $this->userId)
            ->where('stat_date', $today)
            ->find();
        
        $watchCount = $stat ? (int)$stat->video_watch_count : 0;
        
        return [
            'passed' => $watchCount < $threshold,
            'trigger_value' => $watchCount,
        ];
    }
    
    /**
     * 检查金币获取速度
     */
    protected function checkVideoRewardSpeed($context, $threshold)
    {
        $coinEarned = (int)($context['coin_earned'] ?? 0);
        
        // 检查最近1小时的金币获取
        $cacheKey = "risk:coin_speed:{$this->userId}";
        $hourlyCoin = (int)Cache::get($cacheKey, 0);
        
        $hourlyCoin += $coinEarned;
        Cache::set($cacheKey, $hourlyCoin, 3600);
        
        return [
            'passed' => $hourlyCoin < $threshold,
            'trigger_value' => $hourlyCoin,
        ];
    }
    
    /**
     * 检查视频跳过率
     */
    protected function checkVideoSkipRatio($threshold)
    {
        $cacheKey = "risk:video_skip:{$this->userId}";
        $stats = Cache::get($cacheKey, ['total' => 0, 'skip' => 0]);
        
        // 确保数值有效
        $total = max(0, (int)$stats['total']);
        $skip = max(0, (int)$stats['skip']);
        
        if ($total < 10) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $skipRatio = $skip / $total;
        
        return [
            'passed' => $skipRatio < $threshold,
            'trigger_value' => round($skipRatio, 4),
        ];
    }
    
    // ==================== 任务防刷规则 ====================
    
    /**
     * 检查任务完成速度
     */
    protected function checkTaskCompleteSpeed($context, $threshold)
    {
        $taskDuration = (float)($context['task_duration'] ?? 0);
        
        // 检查最近完成的任务平均时长
        $cacheKey = "risk:task_speed:{$this->userId}";
        $recentDurations = Cache::get($cacheKey, []);
        
        $recentDurations[] = $taskDuration;
        $recentDurations = array_slice($recentDurations, -10);
        
        Cache::set($cacheKey, $recentDurations, 3600);
        
        $avgDuration = count($recentDurations) > 0 ? array_sum($recentDurations) / count($recentDurations) : 60;
        
        return [
            'passed' => $avgDuration >= $threshold,
            'trigger_value' => round($avgDuration, 2),
        ];
    }
    
    /**
     * 检查任务每日限制
     */
    protected function checkTaskDailyLimit($threshold)
    {
        $today = date('Y-m-d');
        $stat = UserBehaviorStat::where('user_id', $this->userId)
            ->where('stat_date', $today)
            ->find();
        
        $completeCount = $stat ? (int)$stat->task_complete_count : 0;
        
        return [
            'passed' => $completeCount < $threshold,
            'trigger_value' => $completeCount,
        ];
    }
    
    /**
     * 检查重复提交任务
     */
    protected function checkTaskRepeatSubmit($context, $threshold)
    {
        $taskId = (int)($context['task_id'] ?? 0);
        if (!$taskId) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $cacheKey = "risk:task_repeat:{$this->userId}";
        $recentTasks = Cache::get($cacheKey, []);
        
        $submitCount = 0;
        foreach ($recentTasks as $tid) {
            if ($tid == $taskId) {
                $submitCount++;
            }
        }
        
        $recentTasks[] = $taskId;
        $recentTasks = array_slice($recentTasks, -50);
        Cache::set($cacheKey, $recentTasks, 3600);
        
        return [
            'passed' => $submitCount < $threshold,
            'trigger_value' => $submitCount,
        ];
    }
    
    /**
     * 检查任务虚假行为
     */
    protected function checkTaskFakeBehavior($context, $threshold)
    {
        $suspicionScore = 0.0;
        
        // 1. 检查点击模式（是否过于规律）
        if (isset($context['click_intervals']) && is_array($context['click_intervals'])) {
            $intervals = $context['click_intervals'];
            if (count($intervals) >= 3) {
                $variance = $this->calculateVariance($intervals);
                if ($variance < 0.1) {
                    $suspicionScore += 0.3;
                }
            }
        }
        
        // 2. 检查屏幕触摸点是否集中
        if (isset($context['touch_positions']) && is_array($context['touch_positions'])) {
            $positions = $context['touch_positions'];
            if (count($positions) >= 5) {
                $avgDistance = $this->calculateAvgDistance($positions);
                if ($avgDistance < 50) {
                    $suspicionScore += 0.4;
                }
            }
        }
        
        // 3. 检查设备环境
        $deviceRisk = $this->getDeviceRisk();
        $suspicionScore += $deviceRisk * 0.3;
        
        return [
            'passed' => $suspicionScore < $threshold,
            'trigger_value' => round($suspicionScore, 4),
        ];
    }
    
    // ==================== 提现相关规则 ====================
    
    /**
     * 检查提现频率
     */
    protected function checkWithdrawFrequency($threshold)
    {
        $count = Db::name('withdraw_order')
            ->where('user_id', $this->userId)
            ->where('createtime', '>', time() - 86400)
            ->count();
        
        return [
            'passed' => $count < $threshold,
            'trigger_value' => $count,
        ];
    }
    
    /**
     * 检查提现金额异常
     */
    protected function checkWithdrawAmountAnomaly($context, $threshold)
    {
        $amount = (float)($context['amount'] ?? 0);
        
        // 获取用户历史提现统计
        $stats = Db::name('withdraw_order')
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->field('AVG(amount) as avg_amount, MAX(amount) as max_amount, COUNT(*) as total')
            ->find();
        
        if (!$stats || (int)$stats['total'] < 3) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $avgAmount = (float)$stats['avg_amount'];
        if ($avgAmount <= 0) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $ratio = $amount / $avgAmount;
        
        return [
            'passed' => $ratio < $threshold,
            'trigger_value' => round($ratio, 4),
        ];
    }
    
    /**
     * 检查新账户大额提现
     */
    protected function checkWithdrawNewAccount($context, $threshold)
    {
        $amount = (float)($context['amount'] ?? 0);
        
        $user = Db::name('user')->where('id', $this->userId)->find();
        if (!$user) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $accountAge = time() - (int)$user['createtime'];
        $days = $accountAge / 86400;
        
        // 新账户(7天内)提现超过阈值
        $passed = true;
        if ($days < 7 && $amount > $threshold) {
            $passed = false;
        }
        
        return [
            'passed' => $passed,
            'trigger_value' => round($amount, 2),
        ];
    }
    
    // ==================== 红包相关规则 ====================
    
    /**
     * 检查抢红包速度
     */
    protected function checkRedpacketGrabSpeed($context, $threshold)
    {
        $grabTime = (float)($context['grab_time'] ?? 0);
        
        return [
            'passed' => $grabTime > $threshold,
            'trigger_value' => $grabTime,
        ];
    }
    
    /**
     * 检查抢红包每日限制
     */
    protected function checkRedpacketDailyLimit($threshold)
    {
        $today = date('Y-m-d');
        $stat = UserBehaviorStat::where('user_id', $this->userId)
            ->where('stat_date', $today)
            ->find();
        
        $grabCount = $stat ? (int)$stat->redpacket_grab_count : 0;
        
        return [
            'passed' => $grabCount < $threshold,
            'trigger_value' => $grabCount,
        ];
    }
    
    // ==================== 邀请相关规则 ====================
    
    /**
     * 检查邀请速度
     */
    protected function checkInviteSpeed($threshold)
    {
        $count = Db::name('invite_relation')
            ->where('inviter_id', $this->userId)
            ->where('createtime', '>', time() - 86400)
            ->count();
        
        return [
            'passed' => $count < $threshold,
            'trigger_value' => $count,
        ];
    }
    
    /**
     * 检查邀请虚假账户
     */
    protected function checkInviteFakeAccount($context, $threshold)
    {
        $inviteeId = (int)($context['invitee_id'] ?? 0);
        if (!$inviteeId) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        // 统计邀请的虚假账户总数
        $totalFake = Db::name('user_risk_score rs')
            ->join('invite_relation ir', 'ir.invitee_id = rs.user_id')
            ->where('ir.inviter_id', $this->userId)
            ->where('rs.risk_level', 'dangerous')
            ->count();
        
        return [
            'passed' => $totalFake < $threshold,
            'trigger_value' => $totalFake,
        ];
    }
    
    // ==================== 全局规则 ====================
    
    /**
     * 检查IP多账户关联
     */
    protected function checkIpMultiAccount($threshold)
    {
        $ipRisk = IpRisk::where('ip', $this->ip)->find();
        $accountCount = $ipRisk ? (int)$ipRisk['account_count'] : 1;
        
        return [
            'passed' => $accountCount < $threshold,
            'trigger_value' => $accountCount,
        ];
    }
    
    /**
     * 检查设备多账户关联
     */
    protected function checkDeviceMultiAccount($threshold)
    {
        if (empty($this->deviceId)) {
            return ['passed' => true, 'trigger_value' => 0];
        }
        
        $device = DeviceFingerprint::where('device_id', $this->deviceId)->find();
        $accountCount = $device ? (int)$device['account_count'] : 1;
        
        return [
            'passed' => $accountCount < $threshold,
            'trigger_value' => $accountCount,
        ];
    }
    
    /**
     * 检查行为模式异常
     */
    protected function checkBehaviorPattern($threshold)
    {
        $deviationScore = 0.0;
        
        // 1. 活跃时间段异常
        $hour = (int)date('H');
        $normalActiveHours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];
        if (!in_array($hour, $normalActiveHours)) {
            $deviationScore += 0.1;
        }
        
        // 2. 行为间隔过于规律
        $cacheKey = "risk:behavior_pattern:{$this->userId}";
        $intervals = Cache::get($cacheKey, []);
        if (count($intervals) >= 5) {
            $variance = $this->calculateVariance($intervals);
            if ($variance < 5) {
                $deviationScore += 0.2;
            }
        }
        
        // 3. 设备风险
        $deviceRisk = $this->getDeviceRisk();
        $deviationScore += $deviceRisk * 0.3;
        
        // 4. IP风险
        $ipRisk = $this->getIpRisk();
        $deviationScore += $ipRisk * 0.2;
        
        return [
            'passed' => $deviationScore < $threshold,
            'trigger_value' => round($deviationScore, 4),
        ];
    }
    
    // ==================== 风险评分管理 ====================
    
    /**
     * 计算综合风险评分
     */
    public function calculateRiskScore()
    {
        $userScore = UserRiskScore::where('user_id', $this->userId)->find();
        
        if (!$userScore) {
            return 0;
        }
        
        // 获取原始分值
        $totalScore = (int)$userScore['total_score'];
        
        // 应用衰减算法
        $lastUpdateTime = (int)($userScore['updatetime'] ?: time());
        $daysSinceUpdate = (time() - $lastUpdateTime) / 86400;
        
        if ($daysSinceUpdate >= 1) {
            $decayRate = (float)$this->getConfig('score_decay_rate', 0.1);
            $decayFactor = pow(1 - $decayRate, floor($daysSinceUpdate));
            $totalScore = (int)($totalScore * $decayFactor);
        }
        
        // 添加设备风险加权
        $deviceRisk = $this->getDeviceRisk();
        $totalScore += (int)($deviceRisk * 50);
        
        // 添加IP风险加权
        $ipRisk = $this->getIpRisk();
        $totalScore += (int)($ipRisk * 50);
        
        $maxScore = (int)$this->getConfig('max_risk_score', 1000);
        
        return min($totalScore, $maxScore);
    }
    
    /**
     * 增加风险分
     */
    public function addRiskScore($score, $type, $ruleCode)
    {
        $score = max(0, (int)$score);
        
        Db::startTrans();
        try {
            $userScore = UserRiskScore::where('user_id', $this->userId)->lock(true)->find();
            
            if (!$userScore) {
                $userScore = new UserRiskScore();
                $userScore->user_id = $this->userId;
                $userScore->total_score = 0;
                $userScore->video_score = 0;
                $userScore->task_score = 0;
                $userScore->withdraw_score = 0;
                $userScore->redpacket_score = 0;
                $userScore->invite_score = 0;
                $userScore->global_score = 0;
                $userScore->violation_count = 0;
            }
            
            // 根据类型增加对应分值
            $typeField = $type . '_score';
            if (isset($userScore->$typeField)) {
                $userScore->$typeField = (int)$userScore->$typeField + $score;
            }
            
            $userScore->total_score = (int)$userScore->total_score + $score;
            $userScore->violation_count = (int)$userScore->violation_count + 1;
            $userScore->last_violation_time = time();
            
            // 更新风险等级
            $userScore->risk_level = $this->getRiskLevel($userScore->total_score);
            
            // 记录评分历史
            $history = json_decode($userScore->score_history ?: '[]', true);
            if (!is_array($history)) {
                $history = [];
            }
            $history[] = [
                'time' => time(),
                'score' => $score,
                'type' => $type,
                'rule_code' => $ruleCode,
            ];
            $userScore->score_history = json_encode(array_slice($history, -100));
            
            $userScore->save();
            
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 执行风险动作
     */
    protected function executeRiskAction(&$result)
    {
        $riskScore = $result['risk_score'];
        
        $banThreshold = (int)$this->getConfig('ban_threshold', 700);
        $freezeThreshold = (int)$this->getConfig('freeze_threshold', 300);
        
        if ($riskScore >= $banThreshold) {
            // 危险：永久封禁
            $this->banUser('permanent', '风险评分过高，系统自动封禁', $riskScore);
            $result['actions'][] = ['action' => 'ban', 'duration' => 0];
        } elseif ($riskScore >= $freezeThreshold) {
            // 高风险：临时封禁7天
            $this->banUser('temporary', '风险评分过高，系统临时封禁', $riskScore, 604800);
            $result['actions'][] = ['action' => 'ban', 'duration' => 604800];
        } elseif ($riskScore >= 200) {
            // 中风险：冻结账户3天
            $this->freezeUser($riskScore, 259200);
            $result['actions'][] = ['action' => 'freeze', 'duration' => 259200];
        }
    }
    
    /**
     * 封禁用户
     */
    public function banUser($type, $reason, $riskScore, $duration = 0)
    {
        $duration = max(0, (int)$duration);
        
        Db::startTrans();
        try {
            // 更新用户风控状态
            $userScore = UserRiskScore::where('user_id', $this->userId)->lock(true)->find();
            if ($userScore) {
                $userScore->status = 'banned';
                $userScore->ban_expire_time = $type == 'permanent' ? null : time() + $duration;
                $userScore->save();
            }
            
            // 更新用户状态
            Db::name('user')->where('id', $this->userId)->update([
                'status' => 'banned',
                'updatetime' => time(),
            ]);
            
            // 创建封禁记录
            $banRecord = new BanRecord();
            $banRecord->user_id = $this->userId;
            $banRecord->ban_type = $type;
            $banRecord->ban_reason = $reason;
            $banRecord->ban_source = 'auto';
            $banRecord->risk_score = (int)$riskScore;
            $banRecord->start_time = time();
            $banRecord->end_time = $type == 'permanent' ? null : time() + $duration;
            $banRecord->duration = $duration;
            $banRecord->status = 'active';
            $banRecord->save();
            
            // 加入黑名单
            $this->addToBlacklist('user', $this->userId, $reason, (int)$riskScore);
            
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 冻结用户
     */
    public function freezeUser($riskScore, $duration)
    {
        $duration = max(0, (int)$duration);
        
        Db::startTrans();
        try {
            $userScore = UserRiskScore::where('user_id', $this->userId)->lock(true)->find();
            if ($userScore) {
                $userScore->status = 'frozen';
                $userScore->freeze_expire_time = time() + $duration;
                $userScore->save();
            }
            
            Db::name('user')->where('id', $this->userId)->update([
                'status' => 'frozen',
                'updatetime' => time(),
            ]);
            
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    // ==================== 辅助方法 ====================
    
    /**
     * 获取风险等级
     */
    public function getRiskLevel($score)
    {
        $score = max(0, (int)$score);
        
        if ($score >= 500) {
            return 'dangerous';
        } elseif ($score >= 300) {
            return 'high';
        } elseif ($score >= 150) {
            return 'medium';
        } elseif ($score >= 50) {
            return 'low';
        }
        return 'safe';
    }
    
    /**
     * 获取规则列表
     */
    protected function getRules($type)
    {
        $cacheTtl = (int)$this->getConfig('rule_cache_ttl', 300);
        
        if (self::$rulesCache === null) {
            $cacheKey = 'risk:rules';
            self::$rulesCache = Cache::get($cacheKey);
            
            if (self::$rulesCache === false || self::$rulesCache === null) {
                self::$rulesCache = RiskRule::where('enabled', 1)
                    ->order('level desc, score_weight desc')
                    ->select()
                    ->toArray();
                Cache::set($cacheKey, self::$rulesCache, $cacheTtl);
            }
        }
        
        $rules = [];
        foreach (self::$rulesCache as $rule) {
            if ($rule['rule_type'] == $type || $rule['rule_type'] == 'global') {
                $rules[] = $rule;
            }
        }
        
        return $rules;
    }
    
    /**
     * 清除规则缓存
     */
    public static function clearRulesCache()
    {
        Cache::rm('risk:rules');
        self::$rulesCache = null;
    }
    
    /**
     * 检查白名单
     */
    protected function isInWhitelist()
    {
        // 检查用户白名单
        if (RiskWhitelist::where('type', 'user')
            ->where('value', (string)$this->userId)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find()) {
            return true;
        }
        
        // 检查IP白名单
        if (RiskWhitelist::where('type', 'ip')
            ->where('value', $this->ip)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find()) {
            return true;
        }
        
        // 检查设备白名单
        if (!empty($this->deviceId)) {
            if (RiskWhitelist::where('type', 'device')
                ->where('value', $this->deviceId)
                ->where('enabled', 1)
                ->where(function ($query) {
                    $query->whereNull('expire_time')
                        ->whereOr('expire_time', '>', time());
                })
                ->find()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查黑名单
     */
    protected function checkBlacklist()
    {
        $maxScore = (int)$this->getConfig('max_risk_score', 1000);
        
        // 检查用户黑名单
        $userBlacklist = RiskBlacklist::where('type', 'user')
            ->where('value', (string)$this->userId)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        if ($userBlacklist) {
            return [
                'passed' => false,
                'risk_level' => 'dangerous',
                'risk_score' => $maxScore,
                'actions' => [['action' => 'ban', 'duration' => 0]],
                'violations' => [],
                'message' => '账户已被封禁',
            ];
        }
        
        // 检查IP黑名单
        $ipBlacklist = RiskBlacklist::where('type', 'ip')
            ->where('value', $this->ip)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        if ($ipBlacklist) {
            return [
                'passed' => false,
                'risk_level' => 'high',
                'risk_score' => 300,
                'actions' => [['action' => 'block', 'duration' => 3600]],
                'violations' => [],
                'message' => '当前网络环境异常，请更换网络后重试',
            ];
        }
        
        // 检查设备黑名单
        if (!empty($this->deviceId)) {
            $deviceBlacklist = RiskBlacklist::where('type', 'device')
                ->where('value', $this->deviceId)
                ->where('enabled', 1)
                ->where(function ($query) {
                    $query->whereNull('expire_time')
                        ->whereOr('expire_time', '>', time());
                })
                ->find();
            
            if ($deviceBlacklist) {
                return [
                    'passed' => false,
                    'risk_level' => 'high',
                    'risk_score' => 300,
                    'actions' => [['action' => 'block', 'duration' => 86400]],
                    'violations' => [],
                    'message' => '当前设备已被限制访问',
                ];
            }
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查用户状态
     */
    protected function checkUserStatus()
    {
        $userScore = UserRiskScore::where('user_id', $this->userId)->find();
        
        if (!$userScore) {
            return ['passed' => true];
        }
        
        // 检查封禁状态
        if ($userScore['status'] == 'banned') {
            $expireTime = $userScore['ban_expire_time'];
            if ($expireTime === null || (int)$expireTime > time()) {
                return [
                    'passed' => false,
                    'risk_level' => 'dangerous',
                    'risk_score' => (int)$this->getConfig('max_risk_score', 1000),
                    'actions' => [['action' => 'ban', 'duration' => 0]],
                    'violations' => [],
                    'message' => '账户已被封禁',
                ];
            }
        }
        
        // 检查冻结状态
        if ($userScore['status'] == 'frozen') {
            $expireTime = (int)$userScore['freeze_expire_time'];
            if ($expireTime > time()) {
                $remaining = $expireTime - time();
                return [
                    'passed' => false,
                    'risk_level' => 'high',
                    'risk_score' => 300,
                    'actions' => [['action' => 'freeze', 'duration' => $remaining]],
                    'violations' => [],
                    'message' => '账户已被冻结，请稍后再试',
                ];
            }
        }
        
        return ['passed' => true];
    }
    
    /**
     * 获取设备风险
     */
    protected function getDeviceRisk()
    {
        if (empty($this->deviceId)) {
            return 0.0;
        }
        
        $device = DeviceFingerprint::where('device_id', $this->deviceId)->find();
        
        if (!$device) {
            return 0.0;
        }
        
        $risk = 0.0;
        
        // Root/越狱检测
        if (!empty($device['root_detected'])) {
            $risk += 0.3;
        }
        
        // 模拟器检测
        if (!empty($device['emulator_detected'])) {
            $risk += 0.5;
        }
        
        // Hook框架检测
        if (!empty($device['hook_detected'])) {
            $risk += 0.4;
        }
        
        // 代理/VPN检测
        if (!empty($device['proxy_detected']) || !empty($device['vpn_detected'])) {
            $risk += 0.2;
        }
        
        // 多账户检测
        $accountCount = (int)$device['account_count'];
        if ($accountCount > 3) {
            $risk += min(($accountCount - 3) * 0.1, 0.3);
        }
        
        return min($risk, 1.0);
    }
    
    /**
     * 获取IP风险
     */
    protected function getIpRisk()
    {
        $ipRisk = IpRisk::where('ip', $this->ip)->find();
        
        if (!$ipRisk) {
            return 0.0;
        }
        
        $risk = 0.0;
        
        // 代理检测
        if (!empty($ipRisk['proxy_detected'])) {
            $risk += 0.3;
        }
        
        // 风险等级
        switch ($ipRisk['risk_level']) {
            case 'dangerous':
                $risk += 0.5;
                break;
            case 'suspicious':
                $risk += 0.2;
                break;
            case 'blacklist':
                $risk += 0.8;
                break;
        }
        
        // 多账户检测
        $accountCount = (int)$ipRisk['account_count'];
        if ($accountCount > 5) {
            $risk += min(($accountCount - 5) * 0.05, 0.3);
        }
        
        return min($risk, 1.0);
    }
    
    /**
     * 记录风险事件
     */
    protected function logRiskEvent($rule, $triggerValue)
    {
        try {
            $log = new RiskLog();
            $log->user_id = $this->userId;
            $log->rule_code = (string)$rule['rule_code'];
            $log->rule_name = (string)$rule['rule_name'];
            $log->rule_type = (string)$rule['rule_type'];
            $log->risk_level = (int)$rule['level'];
            $log->trigger_value = (float)$triggerValue;
            $log->threshold = (float)$rule['threshold'];
            $log->score_add = (int)$rule['score_weight'];
            $log->action = (string)$rule['action'];
            $log->action_duration = (int)$rule['action_duration'];
            $log->device_id = $this->deviceId;
            $log->ip = $this->ip;
            $log->user_agent = $this->userAgent;
            $log->request_data = json_encode($this->requestData);
            $log->save();
        } catch (\Exception $e) {
            Log::error('RiskLog save error: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新规则触发统计
     */
    protected function updateRuleTriggerStat($ruleCode)
    {
        $today = date('Y-m-d');
        $cacheKey = "risk:rule_stat:{$today}";
        $stats = Cache::get($cacheKey, []);
        
        if (!is_array($stats)) {
            $stats = [];
        }
        
        if (!isset($stats[$ruleCode])) {
            $stats[$ruleCode] = 0;
        }
        $stats[$ruleCode]++;
        
        Cache::set($cacheKey, $stats, 86400);
    }
    
    /**
     * 添加到黑名单
     */
    protected function addToBlacklist($type, $value, $reason, $riskScore)
    {
        $exists = RiskBlacklist::where('type', $type)
            ->where('value', (string)$value)
            ->find();
        
        if ($exists) {
            return;
        }
        
        $blacklist = new RiskBlacklist();
        $blacklist->type = $type;
        $blacklist->value = (string)$value;
        $blacklist->reason = $reason;
        $blacklist->source = 'auto';
        $blacklist->risk_score = (int)$riskScore;
        $blacklist->save();
    }
    
    /**
     * 计算方差
     */
    protected function calculateVariance($data)
    {
        if (!is_array($data) || count($data) < 2) {
            return 0.0;
        }
        
        $count = count($data);
        $mean = array_sum($data) / $count;
        $variance = 0.0;
        
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / $count;
    }
    
    /**
     * 计算平均距离
     */
    protected function calculateAvgDistance($positions)
    {
        if (!is_array($positions) || count($positions) < 2) {
            return 0.0;
        }
        
        $count = count($positions);
        $totalDistance = 0.0;
        $pairCount = 0;
        
        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $dx = (float)($positions[$i]['x'] ?? 0) - (float)($positions[$j]['x'] ?? 0);
                $dy = (float)($positions[$i]['y'] ?? 0) - (float)($positions[$j]['y'] ?? 0);
                $totalDistance += sqrt($dx * $dx + $dy * $dy);
                $pairCount++;
            }
        }
        
        return $pairCount > 0 ? $totalDistance / $pairCount : 0.0;
    }
    
    /**
     * 获取客户端IP
     */
    protected function getClientIp()
    {
        return request()->ip();
    }
    
    /**
     * 获取设备ID
     */
    protected function getDeviceId()
    {
        return request()->header('X-Device-Id', '');
    }
    
    /**
     * 获取User-Agent
     */
    protected function getUserAgent()
    {
        return request()->header('user-agent', '');
    }
    
    /**
     * 设置请求数据
     */
    public function setRequestData($data)
    {
        $this->requestData = is_array($data) ? $data : [];
        return $this;
    }
    
    // ==================== 静态方法 ====================
    
    /**
     * 快速风控检查
     */
    public static function quickCheck($userId, $ruleType, $action, array $context = [])
    {
        $service = new self();
        return $service->init($userId)
            ->setRequestData($context)
            ->check($ruleType, $action, $context);
    }
    
    /**
     * 获取用户风险信息
     */
    public static function getUserRiskInfo($userId)
    {
        $userScore = UserRiskScore::where('user_id', $userId)->find();
        
        if (!$userScore) {
            return [
                'risk_score' => 0,
                'risk_level' => 'safe',
                'status' => 'normal',
            ];
        }
        
        return [
            'risk_score' => (int)$userScore['total_score'],
            'risk_level' => (string)$userScore['risk_level'],
            'status' => (string)$userScore['status'],
            'violation_count' => (int)$userScore['violation_count'],
            'video_score' => (int)$userScore['video_score'],
            'task_score' => (int)$userScore['task_score'],
            'withdraw_score' => (int)$userScore['withdraw_score'],
            'redpacket_score' => (int)$userScore['redpacket_score'],
            'invite_score' => (int)$userScore['invite_score'],
            'global_score' => (int)$userScore['global_score'],
        ];
    }
    
    /**
     * 解冻/解封过期用户
     */
    public static function releaseExpiredUsers()
    {
        $now = time();
        
        // 解冻过期冻结用户
        $frozenUsers = UserRiskScore::where('status', 'frozen')
            ->whereNotNull('freeze_expire_time')
            ->where('freeze_expire_time', '<=', $now)
            ->select();
        
        $released = count($frozenUsers);
        
        foreach ($frozenUsers as $userScore) {
            Db::startTrans();
            try {
                $userScore->status = 'normal';
                $userScore->freeze_expire_time = null;
                $userScore->save();
                
                Db::name('user')->where('id', $userScore['user_id'])->update([
                    'status' => 'normal',
                    'updatetime' => time(),
                ]);
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
        
        // 解封过期临时封禁用户
        $bannedUsers = UserRiskScore::where('status', 'banned')
            ->whereNotNull('ban_expire_time')
            ->where('ban_expire_time', '<=', $now)
            ->select();
        
        $releasedBanned = count($bannedUsers);
        
        foreach ($bannedUsers as $userScore) {
            Db::startTrans();
            try {
                $userScore->status = 'normal';
                $userScore->ban_expire_time = null;
                $userScore->save();
                
                Db::name('user')->where('id', $userScore['user_id'])->update([
                    'status' => 'normal',
                    'updatetime' => time(),
                ]);
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
        
        return [
            'released_frozen' => $released,
            'released_banned' => $releasedBanned,
        ];
    }
}
