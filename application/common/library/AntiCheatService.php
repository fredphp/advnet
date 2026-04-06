<?php

namespace app\common\library;

use think\Cache;
use think\Db;
use think\Log;
use app\common\model\AnticheatLog;

/**
 * 防刷服务类
 */
class AntiCheatService
{
    // Redis键前缀
    const CACHE_PREFIX = 'anti_cheat:';
    
    /**
     * 检测视频奖励防刷
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     * @param array $options 检测选项
     * @return array
     */
    public function checkVideoReward($userId, $videoId, $options = [])
    {
        $result = [
            'pass' => true,
            'message' => '',
            'risk_score' => 0,
            'checks' => [],
        ];
        
        $ip = $options['ip'] ?? $this->getClientIp();
        $deviceId = $options['device_id'] ?? '';
        
        // 1. 同视频奖励间隔检测
        $intervalCheck = $this->checkRewardInterval($userId, $videoId);
        $result['checks']['interval'] = $intervalCheck;
        if (!$intervalCheck['pass']) {
            $result['pass'] = false;
            $result['message'] = $intervalCheck['message'];
            return $result;
        }
        
        // 2. 同IP每日奖励次数检测
        $ipCheck = $this->checkIpRewardLimit($ip);
        $result['checks']['ip_limit'] = $ipCheck;
        if (!$ipCheck['pass']) {
            $result['pass'] = false;
            $result['message'] = $ipCheck['message'];
            return $result;
        }
        
        // 3. 同设备每日奖励次数检测
        if ($deviceId) {
            $deviceCheck = $this->checkDeviceRewardLimit($deviceId);
            $result['checks']['device_limit'] = $deviceCheck;
            if (!$deviceCheck['pass']) {
                $result['pass'] = false;
                $result['message'] = $deviceCheck['message'];
                return $result;
            }
        }
        
        // 4. 观看速度检测
        $speedCheck = $this->checkWatchSpeed($userId, $videoId);
        $result['checks']['speed'] = $speedCheck;
        if (!$speedCheck['pass']) {
            $result['pass'] = false;
            $result['message'] = $speedCheck['message'];
            return $result;
        }
        
        // 5. 行为模式检测
        $behaviorCheck = $this->checkBehaviorPattern($userId);
        $result['checks']['behavior'] = $behaviorCheck;
        if (!$behaviorCheck['pass']) {
            $result['pass'] = false;
            $result['message'] = $behaviorCheck['message'];
            return $result;
        }
        
        // 6. 风控评分
        $riskScore = $this->calculateRiskScore($userId, [
            'ip' => $ip,
            'device_id' => $deviceId,
            'video_id' => $videoId,
        ]);
        $result['risk_score'] = $riskScore;
        
        $threshold = intval($this->getConfig('risk_score_threshold', 70));
        if ($riskScore >= $threshold) {
            $result['pass'] = false;
            $result['message'] = '账户存在风险，请稍后再试';
            AnticheatLog::log($userId, 'high_risk_score', [
                'score' => $riskScore,
                'video_id' => $videoId,
            ], $ip);
        }
        
        return $result;
    }
    
    /**
     * 检测同视频奖励间隔
     */
    protected function checkRewardInterval($userId, $videoId)
    {
        $result = [
            'pass' => true,
            'message' => '',
            'interval' => 0,
            'required' => 0,
        ];
        
        $minInterval = intval($this->getConfig('watch_interval', 300));
        
        if ($minInterval <= 0) {
            return $result;
        }
        
        $result['required'] = $minInterval;
        
        $cacheKey = self::CACHE_PREFIX . "last_reward:{$userId}:{$videoId}";
        $lastRewardTime = Cache::get($cacheKey);
        
        if ($lastRewardTime) {
            $elapsed = time() - $lastRewardTime;
            $result['interval'] = $elapsed;
            
            if ($elapsed < $minInterval) {
                $result['pass'] = false;
                $remain = $minInterval - $elapsed;
                $result['message'] = "请等待{$remain}秒后再领取该视频奖励";
            }
        }
        
        // 更新最后奖励时间
        Cache::set($cacheKey, time(), 86400);
        
        return $result;
    }
    
    /**
     * 检测同IP每日奖励次数
     */
    protected function checkIpRewardLimit($ip)
    {
        $result = [
            'pass' => true,
            'message' => '',
            'count' => 0,
            'limit' => 0,
        ];
        
        $ipLimit = intval($this->getConfig('same_ip_reward_limit', 100));
        
        if ($ipLimit <= 0) {
            return $result;
        }
        
        $result['limit'] = $ipLimit;
        
        $cacheKey = self::CACHE_PREFIX . "ip_reward:" . date('Ymd') . ":{$ip}";
        $count = Cache::get($cacheKey) ?: 0;
        
        $result['count'] = $count;
        
        if ($count >= $ipLimit) {
            $result['pass'] = false;
            $result['message'] = '该网络今日奖励次数已达上限';
        }
        
        // 增加计数
        Cache::set($cacheKey, $count + 1, 86400);
        
        return $result;
    }
    
    /**
     * 检测同设备每日奖励次数
     */
    protected function checkDeviceRewardLimit($deviceId)
    {
        $result = [
            'pass' => true,
            'message' => '',
            'count' => 0,
            'limit' => 0,
        ];
        
        $deviceLimit = intval($this->getConfig('same_device_reward_limit', 50));
        
        if ($deviceLimit <= 0) {
            return $result;
        }
        
        $result['limit'] = $deviceLimit;
        
        $cacheKey = self::CACHE_PREFIX . "device_reward:" . date('Ymd') . ":{$deviceId}";
        $count = Cache::get($cacheKey) ?: 0;
        
        $result['count'] = $count;
        
        if ($count >= $deviceLimit) {
            $result['pass'] = false;
            $result['message'] = '该设备今日奖励次数已达上限';
        }
        
        // 增加计数
        Cache::set($cacheKey, $count + 1, 86400);
        
        return $result;
    }
    
    /**
     * 检测观看速度
     */
    protected function checkWatchSpeed($userId, $videoId)
    {
        $result = [
            'pass' => true,
            'message' => '',
            'speed' => 0,
            'threshold' => 0,
        ];
        
        $video = Db::name('video')->where('id', $videoId)->find();
        if (!$video) {
            return $result;
        }
        
        $watchRecord = Db::name('video_watch_record')
            ->where('user_id', $userId)
            ->where('video_id', $videoId)
            ->find();
        
        if (!$watchRecord) {
            return $result;
        }
        
        $actualDuration = $video['duration'] ?? 0;
        $claimedDuration = $watchRecord['watch_duration'] ?? 0;
        
        if ($actualDuration > 0 && $claimedDuration > 0) {
            $maxSpeed = floatval($this->getConfig('max_watch_speed', 2.0));
            $result['threshold'] = $maxSpeed;
            
            $sessions = Db::name('video_watch_session')
                ->where('user_id', $userId)
                ->where('video_id', $videoId)
                ->where('createtime', '>=', time() - 3600)
                ->select()
                ->toArray();
            
            if (!empty($sessions)) {
                $totalSessionDuration = array_sum(array_column($sessions, 'duration'));
                $firstSessionTime = min(array_column($sessions, 'createtime'));
                $lastSessionTime = time();
                
                $realElapsedTime = $lastSessionTime - $firstSessionTime;
                
                if ($realElapsedTime > 0) {
                    $speed = $claimedDuration / $realElapsedTime;
                    $result['speed'] = round($speed, 2);
                    
                    if ($speed > $maxSpeed) {
                        $result['pass'] = false;
                        $result['message'] = '观看速度异常，请正常观看视频';
                        
                        AnticheatLog::log($userId, 'abnormal_speed', [
                            'video_id' => $videoId,
                            'speed' => $speed,
                            'claimed_duration' => $claimedDuration,
                            'real_elapsed' => $realElapsedTime,
                        ]);
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 检测行为模式
     */
    protected function checkBehaviorPattern($userId)
    {
        $result = [
            'pass' => true,
            'message' => '',
            'patterns' => [],
        ];
        
        $recentRecords = Db::name('video_watch_record')
            ->where('user_id', $userId)
            ->where('createtime', '>=', time() - 3600)
            ->count();
        
        $hourlyLimit = intval($this->getConfig('hourly_watch_limit', 100));
        if ($recentRecords > $hourlyLimit) {
            $result['pass'] = false;
            $result['message'] = '观看频率异常，请稍后再试';
            $result['patterns']['hourly_exceed'] = true;
            
            AnticheatLog::log($userId, 'hourly_watch_exceed', [
                'count' => $recentRecords,
                'limit' => $hourlyLimit,
            ]);
            
            return $result;
        }
        
        $sameVideoCount = Db::name('video_watch_session')
            ->where('user_id', $userId)
            ->where('createtime', '>=', time() - 1800)
            ->group('video_id')
            ->having('count(*) > 5')
            ->count();
        
        if ($sameVideoCount > 0) {
            $result['pass'] = false;
            $result['message'] = '检测到异常观看行为';
            $result['patterns']['repeated_watch'] = true;
        }
        
        return $result;
    }
    
    /**
     * 计算风险评分
     */
    protected function calculateRiskScore($userId, $context)
    {
        $score = 0;
        
        // 1. 账户因素(0-25分)
        $user = Db::name('user')->where('id', $userId)->find();
        if ($user) {
            $registerDays = floor((time() - $user['createtime']) / 86400);
            if ($registerDays < 3) {
                $score += 15;
            } elseif ($registerDays < 7) {
                $score += 10;
            } elseif ($registerDays < 30) {
                $score += 5;
            }
        }
        
        // 2. 设备因素(0-25分)
        if (!empty($context['device_id'])) {
            $deviceAccounts = Db::name('user')
                ->where('device_id', $context['device_id'])
                ->where('id', '<>', $userId)
                ->count();
            
            if ($deviceAccounts >= 5) {
                $score += 25;
            } elseif ($deviceAccounts >= 3) {
                $score += 15;
            } elseif ($deviceAccounts >= 1) {
                $score += 5;
            }
        }
        
        // 3. IP因素(0-25分)
        if (!empty($context['ip'])) {
            $ipAccounts = Db::name('user')
                ->where('loginip', $context['ip'])
                ->where('id', '<>', $userId)
                ->count();
            
            if ($ipAccounts >= 10) {
                $score += 25;
            } elseif ($ipAccounts >= 5) {
                $score += 15;
            } elseif ($ipAccounts >= 2) {
                $score += 5;
            }
        }
        
        // 4. 行为因素(0-25分)
        $suspiciousCount = Db::name('anticheat_log')
            ->where('user_id', $userId)
            ->where('createtime', '>=', time() - 86400 * 7)
            ->count();
        
        if ($suspiciousCount >= 5) {
            $score += 25;
        } elseif ($suspiciousCount >= 3) {
            $score += 15;
        } elseif ($suspiciousCount >= 1) {
            $score += 5;
        }
        
        return min(100, $score);
    }
    
    /**
     * 获取客户端IP
     */
    protected function getClientIp()
    {
        return request()->ip() ?? '0.0.0.0';
    }
    
    /**
     * 获取配置
     */
    protected function getConfig($name, $default = null)
    {
        $value = Db::name('config')->where('name', $name)->value('value');
        return $value !== null ? $value : $default;
    }
}
