<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Cache;
use app\common\model\UserBehaviorStat;

/**
 * 任务防刷服务
 * 
 * 功能：
 * 1. 任务完成行为检测
 * 2. 任务完成速度检测
 * 3. 任务虚假行为检测
 * 4. 任务重复提交检测
 */
class TaskAntiCheatService
{
    // 缓存前缀
    const CACHE_PREFIX = 'task_anti:';
    
    // 每日限制
    const DAILY_TASK_LIMIT = 100;      // 每日任务完成次数限制
    const DAILY_COIN_LIMIT = 30000;    // 每日任务金币获取限制
    
    // 时间窗口
    const SPEED_WINDOW = 3600;         // 速度检测窗口(1小时)
    const REPEAT_WINDOW = 3600;        // 重复检测窗口(1小时)
    
    /**
     * @var int 用户ID
     */
    protected $userId;
    
    /**
     * @var RiskControlService 风控服务
     */
    protected $riskService;
    
    /**
     * 初始化
     */
    public function __construct($userId = 0)
    {
        $this->userId = $userId;
        $this->riskService = new RiskControlService();
        $this->riskService->init($userId);
    }
    
    /**
     * 检查任务完成行为
     * 
     * @param array $taskData 任务数据
     * @return array
     */
    public function checkTaskComplete($taskData)
    {
        $result = [
            'passed' => true,
            'reason' => '',
            'risk_score' => 0,
            'coin_earned' => 0,
            'warnings' => [],
        ];
        
        $taskId = $taskData['task_id'] ?? 0;
        $taskType = $taskData['type'] ?? '';
        $taskDuration = $taskData['task_duration'] ?? 0;
        $coinReward = $taskData['coin_reward'] ?? 0;
        $expectedDuration = $taskData['expected_duration'] ?? 60;
        
        // 1. 基础检查
        $basicCheck = $this->checkBasicValidation($taskId, $taskDuration);
        if (!$basicCheck['passed']) {
            return $basicCheck;
        }
        
        // 2. 任务完成速度检查
        $speedCheck = $this->checkCompleteSpeed($taskDuration, $expectedDuration);
        if (!$speedCheck['passed']) {
            $result['warnings'][] = $speedCheck['reason'];
            $result['risk_score'] += $speedCheck['risk_score'];
        }
        
        // 3. 重复提交检查
        $repeatCheck = $this->checkRepeatSubmit($taskId);
        if (!$repeatCheck['passed']) {
            return $repeatCheck;
        }
        
        // 4. 每日限制检查
        $dailyCheck = $this->checkDailyLimit();
        if (!$dailyCheck['passed']) {
            return $dailyCheck;
        }
        
        // 5. 虚假行为检测
        $fakeCheck = $this->detectFakeBehavior($taskData);
        if (!$fakeCheck['passed']) {
            $result['warnings'][] = $fakeCheck['reason'];
            $result['risk_score'] += $fakeCheck['risk_score'];
            
            // 虚假行为直接不给金币
            if ($fakeCheck['risk_score'] >= 50) {
                $coinReward = 0;
            }
        }
        
        // 6. 点击行为分析
        if (!empty($taskData['click_data'])) {
            $clickCheck = $this->analyzeClickBehavior($taskData['click_data']);
            if (!$clickCheck['passed']) {
                $result['warnings'][] = $clickCheck['reason'];
                $result['risk_score'] += $clickCheck['risk_score'];
            }
        }
        
        // 7. 时间戳分析
        $timestampCheck = $this->analyzeTimestamps($taskData);
        if (!$timestampCheck['passed']) {
            $result['warnings'][] = $timestampCheck['reason'];
            $result['risk_score'] += $timestampCheck['risk_score'];
        }
        
        // 8. 如果风险分过高，上报风控系统
        if ($result['risk_score'] > 40) {
            $this->reportToRiskSystem($taskData, $result);
        }
        
        $result['coin_earned'] = $coinReward;
        
        return $result;
    }
    
    /**
     * 基础验证
     */
    protected function checkBasicValidation($taskId, $taskDuration)
    {
        if ($taskId <= 0) {
            return [
                'passed' => false,
                'reason' => '无效的任务ID',
                'risk_score' => 10,
            ];
        }
        
        if ($taskDuration <= 0) {
            return [
                'passed' => false,
                'reason' => '任务时长无效',
                'risk_score' => 10,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查任务完成速度
     */
    protected function checkCompleteSpeed($taskDuration, $expectedDuration)
    {
        // 完成时间过短
        $minDuration = $expectedDuration * 0.3; // 最少30%时间
        if ($taskDuration < $minDuration) {
            return [
                'passed' => false,
                'reason' => '任务完成时间过短',
                'risk_score' => 30,
            ];
        }
        
        // 检查最近完成速度趋势
        $cacheKey = self::CACHE_PREFIX . 'speed_trend:' . $this->userId;
        $speedTrend = Cache::get($cacheKey, []);
        
        $speedRatio = $taskDuration / max($expectedDuration, 1);
        $speedTrend[] = [
            'ratio' => $speedRatio,
            'time' => time(),
        ];
        
        // 只保留最近20条记录
        $speedTrend = array_slice($speedTrend, -20);
        Cache::set($cacheKey, $speedTrend, self::SPEED_WINDOW);
        
        // 计算平均完成比例
        if (count($speedTrend) >= 5) {
            $avgRatio = array_sum(array_column($speedTrend, 'ratio')) / count($speedTrend);
            
            // 平均完成时间过低
            if ($avgRatio < 0.5) {
                return [
                    'passed' => false,
                    'reason' => '平均任务完成时间过短，疑似作弊',
                    'risk_score' => 35,
                ];
            }
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查重复提交
     */
    protected function checkRepeatSubmit($taskId)
    {
        $cacheKey = self::CACHE_PREFIX . 'submit_history:' . $this->userId;
        $submitHistory = Cache::get($cacheKey, []);
        
        $now = time();
        $recentSubmits = 0;
        
        // 统计最近1小时内对同一任务的提交次数
        foreach ($submitHistory as $record) {
            if ($record['task_id'] == $taskId && ($now - $record['time']) < 3600) {
                $recentSubmits++;
            }
        }
        
        // 添加当前提交记录
        $submitHistory[] = [
            'task_id' => $taskId,
            'time' => $now,
        ];
        
        // 只保留最近50条记录
        $submitHistory = array_slice($submitHistory, -50);
        Cache::set($cacheKey, $submitHistory, self::REPEAT_WINDOW);
        
        // 1小时内重复提交超过3次
        if ($recentSubmits >= 3) {
            return [
                'passed' => false,
                'reason' => '任务重复提交次数过多，请稍后再试',
                'risk_score' => 25,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查每日限制
     */
    protected function checkDailyLimit()
    {
        $today = date('Y-m-d');
        $stat = UserBehaviorStat::where('user_id', $this->userId)
            ->where('stat_date', $today)
            ->find();
        
        if (!$stat) {
            return ['passed' => true];
        }
        
        // 检查任务完成次数
        if ($stat->task_complete_count >= self::DAILY_TASK_LIMIT) {
            return [
                'passed' => false,
                'reason' => '今日任务完成次数已达上限',
                'risk_score' => 0,
            ];
        }
        
        // 检查金币获取
        if ($stat->task_coin_earned >= self::DAILY_COIN_LIMIT) {
            return [
                'passed' => false,
                'reason' => '今日任务金币获取已达上限',
                'risk_score' => 0,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检测虚假行为
     */
    protected function detectFakeBehavior($taskData)
    {
        $riskScore = 0;
        $reasons = [];
        
        // 1. 检查是否在后台执行
        if (!empty($taskData['is_background'])) {
            $riskScore += 40;
            $reasons[] = '任务在后台执行';
        }
        
        // 2. 检查屏幕是否关闭
        if (!empty($taskData['screen_off'])) {
            $riskScore += 35;
            $reasons[] = '屏幕关闭状态执行任务';
        }
        
        // 3. 检查应用切换次数
        if (isset($taskData['app_switch_count']) && $taskData['app_switch_count'] > 3) {
            $riskScore += 20;
            $reasons[] = '任务执行期间频繁切换应用';
        }
        
        // 4. 检查GPS位置变化
        if (!empty($taskData['location_changes'])) {
            $changes = $taskData['location_changes'];
            if (count($changes) > 0) {
                // 检查位置跳跃(短时间内距离变化过大)
                foreach ($changes as $i => $change) {
                    if ($i > 0) {
                        $distance = $this->calculateDistance(
                            $changes[$i - 1]['lat'], $changes[$i - 1]['lng'],
                            $change['lat'], $change['lng']
                        );
                        $timeDiff = $change['time'] - $changes[$i - 1]['time'];
                        
                        // 短时间内移动超过10公里
                        if ($distance > 10000 && $timeDiff < 60) {
                            $riskScore += 30;
                            $reasons[] = '位置信息异常';
                            break;
                        }
                    }
                }
            }
        }
        
        // 5. 检查设备传感器数据
        if (!empty($taskData['sensor_data'])) {
            $sensorCheck = $this->analyzeSensorData($taskData['sensor_data']);
            if (!$sensorCheck['passed']) {
                $riskScore += $sensorCheck['risk_score'];
                $reasons[] = $sensorCheck['reason'];
            }
        }
        
        // 6. 检查网络环境变化
        if (!empty($taskData['network_changes']) && $taskData['network_changes'] > 2) {
            $riskScore += 15;
            $reasons[] = '网络环境频繁变化';
        }
        
        $passed = $riskScore < 50;
        
        return [
            'passed' => $passed,
            'reason' => implode('; ', $reasons) ?: '',
            'risk_score' => $riskScore,
        ];
    }
    
    /**
     * 分析点击行为
     */
    protected function analyzeClickBehavior($clickData)
    {
        $riskScore = 0;
        $reasons = [];
        
        $positions = $clickData['positions'] ?? [];
        $timestamps = $clickData['timestamps'] ?? [];
        $pressures = $clickData['pressures'] ?? [];
        
        // 1. 点击位置分析
        if (count($positions) >= 5) {
            // 计算点击位置分布
            $xCoords = array_column($positions, 'x');
            $yCoords = array_column($positions, 'y');
            
            $xVariance = $this->calculateVariance($xCoords);
            $yVariance = $this->calculateVariance($yCoords);
            
            // 点击位置过于集中(可能是脚本点击同一位置)
            if ($xVariance < 100 && $yVariance < 100) {
                $riskScore += 30;
                $reasons[] = '点击位置过于集中';
            }
        }
        
        // 2. 点击间隔分析
        if (count($timestamps) >= 5) {
            $intervals = [];
            for ($i = 1; $i < count($timestamps); $i++) {
                $intervals[] = $timestamps[$i] - $timestamps[$i - 1];
            }
            
            $variance = $this->calculateVariance($intervals);
            
            // 点击间隔过于规律
            if ($variance < 10) {
                $riskScore += 25;
                $reasons[] = '点击间隔过于规律';
            }
            
            // 点击间隔过短
            $avgInterval = array_sum($intervals) / count($intervals);
            if ($avgInterval < 100) { // 平均间隔小于100ms
                $riskScore += 20;
                $reasons[] = '点击速度异常';
            }
        }
        
        // 3. 触摸压力分析(iOS 3D Touch / Android pressure)
        if (count($pressures) >= 5) {
            $avgPressure = array_sum($pressures) / count($pressures);
            $pressureVariance = $this->calculateVariance($pressures);
            
            // 压力值完全一致(模拟器特征)
            if ($pressureVariance == 0 && count(array_unique($pressures)) == 1) {
                $riskScore += 20;
                $reasons[] = '触摸压力无变化';
            }
        }
        
        return [
            'passed' => $riskScore < 40,
            'reason' => implode('; ', $reasons) ?: '',
            'risk_score' => $riskScore,
        ];
    }
    
    /**
     * 分析传感器数据
     */
    protected function analyzeSensorData($sensorData)
    {
        $riskScore = 0;
        $reasons = [];
        
        // 陀螺仪数据
        if (!empty($sensorData['gyroscope'])) {
            $gyroData = $sensorData['gyroscope'];
            $variance = $this->calculateVariance($gyroData);
            
            // 陀螺仪数据完全静止
            if ($variance < 0.001) {
                $riskScore += 25;
                $reasons[] = '设备无自然晃动';
            }
        }
        
        // 加速度计数据
        if (!empty($sensorData['accelerometer'])) {
            $accelData = $sensorData['accelerometer'];
            $variance = $this->calculateVariance($accelData);
            
            // 加速度计数据完全静止
            if ($variance < 0.001) {
                $riskScore += 25;
                $reasons[] = '设备无自然运动';
            }
        }
        
        return [
            'passed' => $riskScore < 30,
            'reason' => implode('; ', $reasons) ?: '',
            'risk_score' => $riskScore,
        ];
    }
    
    /**
     * 分析时间戳
     */
    protected function analyzeTimestamps($taskData)
    {
        $riskScore = 0;
        $reasons = [];
        
        // 检查开始时间和结束时间
        $startTime = $taskData['start_time'] ?? 0;
        $endTime = $taskData['end_time'] ?? 0;
        $reportTime = time();
        
        // 结束时间早于开始时间
        if ($endTime < $startTime) {
            $riskScore += 20;
            $reasons[] = '时间戳异常';
        }
        
        // 上报时间与结束时间差距过大
        if ($reportTime - $endTime > 300) { // 超过5分钟
            $riskScore += 15;
            $reasons[] = '任务上报延迟异常';
        }
        
        // 检查设备时间与服务器时间偏差
        if (!empty($taskData['device_time'])) {
            $timeOffset = abs($taskData['device_time'] - $reportTime);
            if ($timeOffset > 300) { // 超过5分钟
                $riskScore += 20;
                $reasons[] = '设备时间与服务器不同步';
            }
        }
        
        return [
            'passed' => $riskScore < 30,
            'reason' => implode('; ', $reasons) ?: '',
            'risk_score' => $riskScore,
        ];
    }
    
    /**
     * 上报风控系统
     */
    protected function reportToRiskSystem($taskData, $checkResult)
    {
        try {
            $this->riskService->addRiskScore(
                $checkResult['risk_score'],
                'task',
                'TASK_BEHAVIOR_ANOMALY'
            );
        } catch (\Exception $e) {
            Log::error('Report to risk system error: ' . $e->getMessage());
        }
    }
    
    /**
     * 计算两点距离
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // 地球半径(米)
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * 计算方差
     */
    protected function calculateVariance($data)
    {
        $count = count($data);
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($data) / $count;
        $variance = 0;
        
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / $count;
    }
    
    /**
     * 获取用户任务行为统计
     */
    public function getUserStats($userId = null)
    {
        $userId = $userId ?: $this->userId;
        $today = date('Y-m-d');
        
        $stat = UserBehaviorStat::where('user_id', $userId)
            ->where('stat_date', $today)
            ->find();
        
        return [
            'today_task_count' => $stat ? $stat->task_complete_count : 0,
            'today_coin_earned' => $stat ? $stat->task_coin_earned : 0,
            'daily_task_limit' => self::DAILY_TASK_LIMIT,
            'daily_coin_limit' => self::DAILY_COIN_LIMIT,
        ];
    }
    
    /**
     * 清除用户缓存
     */
    public function clearUserCache($userId = null)
    {
        $userId = $userId ?: $this->userId;
        
        Cache::rm(self::CACHE_PREFIX . 'speed_trend:' . $userId);
        Cache::rm(self::CACHE_PREFIX . 'submit_history:' . $userId);
    }
}
