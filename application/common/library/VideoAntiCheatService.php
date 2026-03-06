<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Cache;
use think\Log;
use app\common\model\UserBehaviorStat;

/**
 * 视频防刷服务
 * 
 * 功能：
 * 1. 视频观看行为检测
 * 2. 观看速度异常检测
 * 3. 重复观看检测
 * 4. 金币获取速度检测
 * 5. 跳过率分析
 */
class VideoAntiCheatService
{
    // 缓存前缀
    const CACHE_PREFIX = 'video_anti:';
    
    // 观看速度阈值
    const MIN_WATCH_RATIO = 0.3;       // 最小观看比例(30%)
    const MAX_SPEED_MULTIPLIER = 2.0;   // 最大倍速
    
    // 每日限制
    const DAILY_WATCH_LIMIT = 500;      // 每日观看次数限制
    const DAILY_COIN_LIMIT = 50000;     // 每日金币获取限制
    
    // 时间窗口
    const SPEED_WINDOW = 3600;          // 速度检测窗口(1小时)
    const REPEAT_WINDOW = 3600;         // 重复检测窗口(1小时)
    
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
     * 检查视频观看行为
     * 
     * @param array $watchData 观看数据
     * @return array
     */
    public function checkWatch($watchData)
    {
        $result = [
            'passed' => true,
            'reason' => '',
            'risk_score' => 0,
            'coin_earned' => 0,
            'warnings' => [],
        ];
        
        $videoId = $watchData['video_id'] ?? 0;
        $watchDuration = $watchData['watch_duration'] ?? 0;
        $videoDuration = $watchData['video_duration'] ?? 0;
        $isSkipped = $watchData['is_skipped'] ?? false;
        $coinReward = $watchData['coin_reward'] ?? 0;
        
        // 1. 基础检查
        $basicCheck = $this->checkBasicValidation($videoId, $watchDuration, $videoDuration);
        if (!$basicCheck['passed']) {
            return $basicCheck;
        }
        
        // 2. 观看速度检查
        $speedCheck = $this->checkWatchSpeed($watchDuration, $videoDuration);
        if (!$speedCheck['passed']) {
            $result['warnings'][] = $speedCheck['reason'];
            $result['risk_score'] += $speedCheck['risk_score'];
        }
        
        // 3. 重复观看检查
        $repeatCheck = $this->checkRepeatWatch($videoId);
        if (!$repeatCheck['passed']) {
            $result['warnings'][] = $repeatCheck['reason'];
            $result['risk_score'] += $repeatCheck['risk_score'];
            // 重复观看不给金币
            $coinReward = 0;
        }
        
        // 4. 每日限制检查
        $dailyCheck = $this->checkDailyLimit();
        if (!$dailyCheck['passed']) {
            return $dailyCheck;
        }
        
        // 5. 金币获取速度检查
        if ($coinReward > 0) {
            $coinCheck = $this->checkCoinSpeed($coinReward);
            if (!$coinCheck['passed']) {
                $result['warnings'][] = $coinCheck['reason'];
                $result['risk_score'] += $coinCheck['risk_score'];
                $coinReward = 0; // 暂停金币发放
            }
        }
        
        // 6. 跳过率检查
        if ($isSkipped) {
            $this->recordSkip();
            $skipCheck = $this->checkSkipRatio();
            if (!$skipCheck['passed']) {
                $result['warnings'][] = $skipCheck['reason'];
                $result['risk_score'] += $skipCheck['risk_score'];
            }
        }
        
        // 7. 行为模式分析
        $patternCheck = $this->analyzeBehaviorPattern($watchData);
        if (!$patternCheck['passed']) {
            $result['warnings'][] = $patternCheck['reason'];
            $result['risk_score'] += $patternCheck['risk_score'];
        }
        
        // 8. 如果风险分过高，上报风控系统
        if ($result['risk_score'] > 30) {
            $this->reportToRiskSystem($watchData, $result);
        }
        
        $result['coin_earned'] = $coinReward;
        
        return $result;
    }
    
    /**
     * 基础验证
     */
    protected function checkBasicValidation($videoId, $watchDuration, $videoDuration)
    {
        if ($videoId <= 0) {
            return [
                'passed' => false,
                'reason' => '无效的视频ID',
                'risk_score' => 10,
            ];
        }
        
        if ($watchDuration <= 0) {
            return [
                'passed' => false,
                'reason' => '观看时长无效',
                'risk_score' => 10,
            ];
        }
        
        if ($videoDuration <= 0) {
            return [
                'passed' => false,
                'reason' => '视频时长无效',
                'risk_score' => 10,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查观看速度
     */
    protected function checkWatchSpeed($watchDuration, $videoDuration)
    {
        // 计算观看比例
        $watchRatio = $watchDuration / $videoDuration;
        
        // 观看时间超过视频时长(可能倍速播放)
        if ($watchDuration > $videoDuration) {
            $speedMultiplier = $watchDuration / $videoDuration;
            if ($speedMultiplier > self::MAX_SPEED_MULTIPLIER) {
                return [
                    'passed' => false,
                    'reason' => '观看速度异常',
                    'risk_score' => 20,
                ];
            }
        }
        
        // 观看比例过低
        if ($watchRatio < self::MIN_WATCH_RATIO) {
            return [
                'passed' => false,
                'reason' => '观看时长过短',
                'risk_score' => 15,
            ];
        }
        
        // 检查最近观看速度趋势
        $cacheKey = self::CACHE_PREFIX . 'speed_trend:' . $this->userId;
        $speedTrend = Cache::get($cacheKey, []);
        
        $speedTrend[] = [
            'ratio' => $watchRatio,
            'time' => time(),
        ];
        
        // 只保留最近20条记录
        $speedTrend = array_slice($speedTrend, -20);
        Cache::set($cacheKey, $speedTrend, self::SPEED_WINDOW);
        
        // 计算平均观看比例
        if (count($speedTrend) >= 5) {
            $avgRatio = array_sum(array_column($speedTrend, 'ratio')) / count($speedTrend);
            
            // 平均观看比例过低
            if ($avgRatio < 0.5) {
                return [
                    'passed' => false,
                    'reason' => '平均观看时长过短，疑似刷视频',
                    'risk_score' => 25,
                ];
            }
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查重复观看
     */
    protected function checkRepeatWatch($videoId)
    {
        $cacheKey = self::CACHE_PREFIX . 'watch_history:' . $this->userId;
        $watchHistory = Cache::get($cacheKey, []);
        
        // 统计同一视频的观看次数
        $watchCount = 0;
        foreach ($watchHistory as $record) {
            if ($record['video_id'] == $videoId) {
                $watchCount++;
            }
        }
        
        // 添加当前观看记录
        $watchHistory[] = [
            'video_id' => $videoId,
            'time' => time(),
        ];
        
        // 只保留最近100条记录
        $watchHistory = array_slice($watchHistory, -100);
        Cache::set($cacheKey, $watchHistory, self::REPEAT_WINDOW);
        
        // 同一视频重复观看超过5次
        if ($watchCount >= 5) {
            return [
                'passed' => false,
                'reason' => '重复观看同一视频次数过多',
                'risk_score' => 30,
            ];
        }
        
        // 同一视频重复观看超过3次，增加风险分
        if ($watchCount >= 3) {
            return [
                'passed' => true,
                'reason' => '重复观看同一视频',
                'risk_score' => 10,
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
        
        // 检查观看次数
        if ($stat->video_watch_count >= self::DAILY_WATCH_LIMIT) {
            return [
                'passed' => false,
                'reason' => '今日观看次数已达上限',
                'risk_score' => 0,
            ];
        }
        
        // 检查金币获取
        if ($stat->video_coin_earned >= self::DAILY_COIN_LIMIT) {
            return [
                'passed' => false,
                'reason' => '今日金币获取已达上限',
                'risk_score' => 0,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查金币获取速度
     */
    protected function checkCoinSpeed($coinReward)
    {
        $cacheKey = self::CACHE_PREFIX . 'coin_speed:' . $this->userId;
        $hourlyData = Cache::get($cacheKey, [
            'total_coin' => 0,
            'start_time' => time(),
        ]);
        
        // 如果缓存过期，重置
        if (time() - $hourlyData['start_time'] > self::SPEED_WINDOW) {
            $hourlyData = [
                'total_coin' => 0,
                'start_time' => time(),
            ];
        }
        
        $hourlyData['total_coin'] += $coinReward;
        Cache::set($cacheKey, $hourlyData, self::SPEED_WINDOW);
        
        // 每小时金币获取超过限制
        if ($hourlyData['total_coin'] > 10000) {
            return [
                'passed' => false,
                'reason' => '金币获取速度异常',
                'risk_score' => 40,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 记录跳过
     */
    protected function recordSkip()
    {
        $cacheKey = self::CACHE_PREFIX . 'skip_stats:' . $this->userId;
        $stats = Cache::get($cacheKey, ['total' => 0, 'skip' => 0]);
        
        $stats['total']++;
        $stats['skip']++;
        
        Cache::set($cacheKey, $stats, 86400);
    }
    
    /**
     * 检查跳过率
     */
    protected function checkSkipRatio()
    {
        $cacheKey = self::CACHE_PREFIX . 'skip_stats:' . $this->userId;
        $stats = Cache::get($cacheKey, ['total' => 0, 'skip' => 0]);
        
        // 更新总数
        $stats['total']++;
        Cache::set($cacheKey, $stats, 86400);
        
        if ($stats['total'] < 10) {
            return ['passed' => true];
        }
        
        $skipRatio = $stats['skip'] / $stats['total'];
        
        // 跳过率超过90%
        if ($skipRatio > 0.9) {
            return [
                'passed' => false,
                'reason' => '视频跳过率过高',
                'risk_score' => 20,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 分析行为模式
     */
    protected function analyzeBehaviorPattern($watchData)
    {
        $cacheKey = self::CACHE_PREFIX . 'timestamps:' . $this->userId;
        $timestamps = Cache::get($cacheKey, []);
        
        $now = time();
        $timestamps[] = $now;
        
        // 只保留最近50个时间戳
        $timestamps = array_slice($timestamps, -50);
        Cache::set($cacheKey, $timestamps, 3600);
        
        if (count($timestamps) < 5) {
            return ['passed' => true];
        }
        
        // 计算时间间隔
        $intervals = [];
        for ($i = 1; $i < count($timestamps); $i++) {
            $intervals[] = $timestamps[$i] - $timestamps[$i - 1];
        }
        
        // 检查间隔是否过于规律（可能是脚本）
        if (count($intervals) >= 5) {
            $variance = $this->calculateVariance($intervals);
            
            // 方差过小，说明间隔过于规律
            if ($variance < 5) {
                return [
                    'passed' => false,
                    'reason' => '观看行为过于规律，疑似脚本',
                    'risk_score' => 35,
                ];
            }
        }
        
        // 检查连续快速操作
        $recentIntervals = array_slice($intervals, -5);
        $avgInterval = array_sum($recentIntervals) / count($recentIntervals);
        
        // 平均间隔小于5秒
        if ($avgInterval < 5) {
            return [
                'passed' => false,
                'reason' => '操作过于频繁',
                'risk_score' => 25,
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 上报风控系统
     */
    protected function reportToRiskSystem($watchData, $checkResult)
    {
        try {
            $this->riskService->addRiskScore(
                $checkResult['risk_score'],
                'video',
                'VIDEO_BEHAVIOR_ANOMALY'
            );
        } catch (\Exception $e) {
            Log::error('Report to risk system error: ' . $e->getMessage());
        }
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
     * 获取用户视频行为统计
     */
    public function getUserStats($userId = null)
    {
        $userId = $userId ?: $this->userId;
        $today = date('Y-m-d');
        
        $stat = UserBehaviorStat::where('user_id', $userId)
            ->where('stat_date', $today)
            ->find();
        
        return [
            'today_watch_count' => $stat ? $stat->video_watch_count : 0,
            'today_watch_duration' => $stat ? $stat->video_watch_duration : 0,
            'today_coin_earned' => $stat ? $stat->video_coin_earned : 0,
            'daily_watch_limit' => self::DAILY_WATCH_LIMIT,
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
        Cache::rm(self::CACHE_PREFIX . 'watch_history:' . $userId);
        Cache::rm(self::CACHE_PREFIX . 'coin_speed:' . $userId);
        Cache::rm(self::CACHE_PREFIX . 'skip_stats:' . $userId);
        Cache::rm(self::CACHE_PREFIX . 'timestamps:' . $userId);
    }
}
