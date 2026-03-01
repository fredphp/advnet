<?php

namespace app\common\library;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use app\common\model\VideoRewardRule;
use app\common\model\VideoWatchRecord;
use app\common\model\VideoCollection;
use app\common\model\VideoCollectionItem;
use app\common\model\UserDailyRewardStat;
use app\common\model\AnticheatLog;

/**
 * 视频收益服务类
 */
class VideoRewardService
{
    // 奖励类型常量
    const REWARD_TYPE_FIXED = 'fixed';
    const REWARD_TYPE_RANDOM = 'random';
    
    // 条件类型常量
    const CONDITION_COMPLETE = 'complete';
    const CONDITION_DURATION = 'duration';
    const CONDITION_COUNT = 'count';
    
    // 奖励状态
    const REWARD_STATUS_PENDING = 0;
    const REWARD_STATUS_CLAIMED = 1;
    const REWARD_STATUS_EXPIRED = 2;
    
    // Redis键前缀
    const CACHE_PREFIX = 'video_reward:';
    const LOCK_PREFIX = 'lock:video_reward:';
    
    /**
     * @var AntiCheatService
     */
    protected $antiCheatService;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->antiCheatService = new AntiCheatService();
    }
    
    /**
     * 上报观看进度
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     * @param array $data 观看数据
     * @return array
     */
    public function reportWatchProgress($userId, $videoId, $data = [])
    {
        $result = [
            'watch_recorded' => false,
            'can_reward' => false,
            'reward_coin' => 0,
            'reward_reason' => '',
            'watch_progress' => 0,
            'watch_duration' => 0,
            'is_completed' => false,
        ];
        
        // 获取视频信息
        $video = Db::name('video')->where('id', $videoId)->where('status', 1)->find();
        if (!$video) {
            return $result;
        }
        
        // 获取观看数据
        $watchDuration = intval($data['watch_duration'] ?? 0);
        $watchProgress = intval($data['watch_progress'] ?? 0);
        $currentPosition = intval($data['current_position'] ?? 0);
        $collectionId = $video['collection_id'] ?? null;
        
        // 获取或创建观看记录
        $record = VideoWatchRecord::getOrCreate($userId, $videoId, $collectionId);
        
        // 检查是否已领取奖励
        if ($record->reward_status == self::REWARD_STATUS_CLAIMED) {
            $result['watch_recorded'] = true;
            $result['watch_progress'] = $record->watch_progress;
            $result['watch_duration'] = $record->watch_duration;
            $result['is_completed'] = $record->is_completed == 1;
            return $result;
        }
        
        // 更新观看进度
        $record->updateProgress($watchDuration, $watchProgress, $currentPosition);
        
        // 判断是否完成观看
        $completeThreshold = $this->getConfig('watch_complete_threshold', 95);
        if ($record->watch_progress >= $completeThreshold && $record->is_completed == 0) {
            $record->markAsCompleted();
            $result['is_completed'] = true;
        }
        
        // 创建观看会话
        if (!empty($data['session_id'])) {
            $this->createWatchSession($userId, $videoId, $data['session_id'], [
                'duration' => $watchDuration,
                'progress' => $watchProgress,
                'ip' => $data['ip'] ?? null,
                'device_id' => $data['device_id'] ?? null,
            ]);
        }
        
        $result['watch_recorded'] = true;
        $result['watch_progress'] = $record->watch_progress;
        $result['watch_duration'] = $record->watch_duration;
        
        // 检查是否可以领取奖励
        if ($record->reward_status == self::REWARD_STATUS_PENDING) {
            $rewardCheck = $this->checkRewardCondition($userId, $video, $record);
            if ($rewardCheck['can_reward']) {
                $result['can_reward'] = true;
                $result['reward_coin'] = $rewardCheck['reward_coin'];
                $result['reward_reason'] = $rewardCheck['reason'];
            }
        }
        
        return $result;
    }
    
    /**
     * 领取奖励
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     * @param array $options 额外选项
     * @return array
     */
    public function claimReward($userId, $videoId, $options = [])
    {
        $result = [
            'success' => false,
            'reward_coin' => 0,
            'message' => '',
            'balance' => 0,
        ];
        
        // 分布式锁，防止并发重复领取
        $lockKey = self::LOCK_PREFIX . "{$userId}:{$videoId}";
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作过于频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取视频信息
            $video = Db::name('video')->where('id', $videoId)->lock(true)->find();
            if (!$video || $video['status'] != 1) {
                $result['message'] = '视频不存在或已下架';
                Db::rollback();
                return $result;
            }
            
            // 获取观看记录
            $record = VideoWatchRecord::where('user_id', $userId)
                ->where('video_id', $videoId)
                ->lock(true)
                ->find();
            
            if (!$record) {
                $result['message'] = '未找到观看记录';
                Db::rollback();
                return $result;
            }
            
            // 检查是否已领取
            if ($record->reward_status == self::REWARD_STATUS_CLAIMED) {
                $result['message'] = '奖励已领取';
                Db::rollback();
                return $result;
            }
            
            // 检查奖励条件
            $rewardCheck = $this->checkRewardCondition($userId, $video, $record);
            if (!$rewardCheck['can_reward']) {
                $result['message'] = $rewardCheck['message'];
                Db::rollback();
                return $result;
            }
            
            // 防刷检测
            $antiCheatResult = $this->antiCheatService->checkVideoReward($userId, $videoId, [
                'ip' => $options['ip'] ?? null,
                'device_id' => $options['device_id'] ?? null,
            ]);
            
            if (!$antiCheatResult['pass']) {
                $result['message'] = $antiCheatResult['message'];
                Db::rollback();
                return $result;
            }
            
            // 检查每日上限
            $dailyLimitCheck = $this->checkDailyLimit($userId);
            if (!$dailyLimitCheck['pass']) {
                $result['message'] = $dailyLimitCheck['message'];
                Db::rollback();
                return $result;
            }
            
            // 计算奖励金额
            $rewardCoin = $rewardCheck['reward_coin'];
            
            // 发放金币
            $coinService = new CoinService();
            $coinResult = $coinService->addCoin($userId, $rewardCoin, 'video_watch', [
                'relation_type' => 'video',
                'relation_id' => $videoId,
                'description' => "观看视频奖励",
            ]);
            
            if (!$coinResult['success']) {
                $result['message'] = '金币发放失败';
                Db::rollback();
                return $result;
            }
            
            // 更新观看记录
            $record->markRewarded($rewardCoin, $rewardCheck['rule_id']);
            
            // 更新每日统计
            $stat = UserDailyRewardStat::getToday($userId);
            $stat->addVideoReward($rewardCoin);
            
            // 更新视频统计
            Db::name('video')->where('id', $videoId)->inc('reward_count')->inc('reward_coin_total', $rewardCoin)->update();
            
            // 发放邀请佣金
            $this->processInviteCommission($userId, $rewardCoin, $videoId);
            
            Db::commit();
            
            $result['success'] = true;
            $result['reward_coin'] = $rewardCoin;
            $result['message'] = "恭喜获得 {$rewardCoin} 金币";
            $result['balance'] = $coinResult['balance'];
            
            // 清除缓存
            $this->clearRewardCache($userId, $videoId);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('视频奖励领取失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 检查奖励条件
     */
    protected function checkRewardCondition($userId, $video, $record)
    {
        $result = [
            'can_reward' => false,
            'reward_coin' => 0,
            'rule_id' => null,
            'reason' => '',
            'message' => '',
        ];
        
        // 获取匹配的奖励规则
        $rule = VideoRewardRule::getMatchedRule($userId, $video);
        if (!$rule) {
            // 使用默认规则
            $rewardCoin = $this->getConfig('default_reward_coin', 100);
            $conditionType = self::CONDITION_COMPLETE;
            $watchProgress = $this->getConfig('watch_complete_threshold', 95);
        } else {
            $rewardCoin = $rule->calculateReward();
            $conditionType = $rule->condition_type;
            $watchProgress = $rule->watch_progress ?? 95;
            $result['rule_id'] = $rule->id;
        }
        
        $result['reward_coin'] = $rewardCoin;
        
        // 根据条件类型检查
        switch ($conditionType) {
            case self::CONDITION_COMPLETE:
                if ($record->watch_progress >= $watchProgress) {
                    $result['can_reward'] = true;
                    $result['reason'] = '完整观看视频';
                } else {
                    $result['message'] = "需观看至{$watchProgress}%";
                }
                break;
                
            case self::CONDITION_DURATION:
                $requiredDuration = $rule ? $rule->watch_duration : 30;
                if ($record->watch_duration >= $requiredDuration) {
                    $result['can_reward'] = true;
                    $result['reason'] = "观看满{$requiredDuration}秒";
                } else {
                    $remain = $requiredDuration - $record->watch_duration;
                    $result['message'] = "还需观看{$remain}秒";
                }
                break;
                
            case self::CONDITION_COUNT:
                $collectionId = $video['collection_id'] ?? null;
                if (!$collectionId) {
                    $result['message'] = '该视频不属于任何合集';
                    return $result;
                }
                
                $watchCount = $rule ? $rule->watch_count : 1;
                $completedCount = VideoWatchRecord::where('user_id', $userId)
                    ->where('collection_id', $collectionId)
                    ->where('is_completed', 1)
                    ->count();
                
                if ($completedCount >= $watchCount) {
                    $result['can_reward'] = true;
                    $result['reason'] = "已观看{$completedCount}集";
                } else {
                    $remain = $watchCount - $completedCount;
                    $result['message'] = "还需观看{$remain}集";
                }
                break;
        }
        
        return $result;
    }
    
    /**
     * 检查每日上限
     */
    protected function checkDailyLimit($userId)
    {
        $result = [
            'pass' => true,
            'message' => '',
        ];
        
        $dailyLimit = $this->getConfig('daily_watch_limit', 50);
        if ($dailyLimit <= 0) {
            return $result;
        }
        
        $currentCount = UserDailyRewardStat::getTodayVideoRewardCount($userId);
        
        if ($currentCount >= $dailyLimit) {
            $result['pass'] = false;
            $result['message'] = "今日观看奖励已达上限({$dailyLimit}次)";
        }
        
        return $result;
    }
    
    /**
     * 处理邀请佣金
     */
    protected function processInviteCommission($userId, $rewardCoin, $videoId)
    {
        $inviteRelation = Db::name('invite_relation')->where('user_id', $userId)->find();
        
        if (!$inviteRelation) {
            return;
        }
        
        $level1Rate = floatval($this->getConfig('level1_watch_commission', 0.01));
        $level2Rate = floatval($this->getConfig('level2_watch_commission', 0.005));
        
        $coinService = new CoinService();
        
        // 一级上级佣金
        if ($inviteRelation['parent_id'] > 0 && $level1Rate > 0) {
            $commission = round($rewardCoin * $level1Rate, 2);
            if ($commission > 0) {
                $coinService->addCoin($inviteRelation['parent_id'], $commission, 'commission_level1', [
                    'relation_type' => 'video',
                    'relation_id' => $videoId,
                    'description' => '下级观看视频佣金',
                ]);
            }
        }
        
        // 二级上级佣金
        if ($inviteRelation['grandparent_id'] > 0 && $level2Rate > 0) {
            $commission = round($rewardCoin * $level2Rate, 2);
            if ($commission > 0) {
                $coinService->addCoin($inviteRelation['grandparent_id'], $commission, 'commission_level2', [
                    'relation_type' => 'video',
                    'relation_id' => $videoId,
                    'description' => '间接下级观看视频佣金',
                ]);
            }
        }
    }
    
    /**
     * 创建观看会话
     */
    protected function createWatchSession($userId, $videoId, $sessionId, $data)
    {
        try {
            Db::name('video_watch_session')->insert([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'video_id' => $videoId,
                'start_time' => time(),
                'duration' => $data['duration'] ?? 0,
                'progress' => $data['progress'] ?? 0,
                'ip' => $data['ip'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'createtime' => time(),
            ]);
        } catch (\Exception $e) {
            Log::error('创建观看会话失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 批量获取奖励状态
     */
    public function batchGetRewardStatus($userId, $videoIds)
    {
        $result = [];
        
        if (empty($videoIds)) {
            return $result;
        }
        
        $records = VideoWatchRecord::where('user_id', $userId)
            ->whereIn('video_id', $videoIds)
            ->column('*', 'video_id');
        
        foreach ($videoIds as $videoId) {
            $record = $records[$videoId] ?? null;
            
            if ($record) {
                $result[$videoId] = [
                    'watched' => true,
                    'watch_progress' => $record['watch_progress'],
                    'watch_duration' => $record['watch_duration'],
                    'is_completed' => $record['is_completed'] == 1,
                    'reward_status' => $record['reward_status'],
                    'rewarded' => $record['reward_status'] == self::REWARD_STATUS_CLAIMED,
                    'reward_coin' => $record['reward_coin'],
                ];
            } else {
                $result[$videoId] = [
                    'watched' => false,
                    'watch_progress' => 0,
                    'watch_duration' => 0,
                    'is_completed' => false,
                    'reward_status' => self::REWARD_STATUS_PENDING,
                    'rewarded' => false,
                    'reward_coin' => 0,
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 获取合集进度
     */
    public function getCollectionProgress($userId, $collectionId)
    {
        $collection = VideoCollection::find($collectionId);
        if (!$collection) {
            return [];
        }
        
        $videos = VideoCollectionItem::where('collection_id', $collectionId)
            ->order('episode', 'asc')
            ->column('video_id');
        
        $records = VideoWatchRecord::where('user_id', $userId)
            ->where('collection_id', $collectionId)
            ->column('*', 'video_id');
        
        $totalVideos = count($videos);
        $watchedCount = 0;
        $rewardedCount = 0;
        $totalReward = 0;
        
        $videoStatus = [];
        foreach ($videos as $index => $videoId) {
            $record = $records[$videoId] ?? null;
            
            $status = [
                'video_id' => $videoId,
                'episode' => $index + 1,
                'watched' => false,
                'completed' => false,
                'rewarded' => false,
                'reward_coin' => 0,
            ];
            
            if ($record) {
                $status['watched'] = true;
                $status['completed'] = $record['is_completed'] == 1;
                $status['rewarded'] = $record['reward_status'] == self::REWARD_STATUS_CLAIMED;
                $status['reward_coin'] = $record['reward_coin'];
                
                if ($record['watch_duration'] > 0) {
                    $watchedCount++;
                }
                if ($status['rewarded']) {
                    $rewardedCount++;
                    $totalReward += $record['reward_coin'];
                }
            }
            
            $videoStatus[] = $status;
        }
        
        return [
            'collection_id' => $collectionId,
            'total_videos' => $totalVideos,
            'watched_count' => $watchedCount,
            'rewarded_count' => $rewardedCount,
            'total_reward' => $totalReward,
            'progress_percent' => $totalVideos > 0 ? round($watchedCount / $totalVideos * 100) : 0,
            'videos' => $videoStatus,
        ];
    }
    
    /**
     * 获取配置
     */
    protected function getConfig($name, $default = null)
    {
        $value = Db::name('config')->where('name', $name)->value('value');
        return $value !== null ? $value : $default;
    }
    
    /**
     * 获取分布式锁
     */
    protected function getLock($key, $expire = 5)
    {
        try {
            $redis = Cache::store('redis')->handler();
            return $redis->set($key, 1, ['NX', 'EX' => $expire]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 释放锁
     */
    protected function releaseLock($key)
    {
        try {
            Cache::store('redis')->handler()->del($key);
        } catch (\Exception $e) {
        }
    }
    
    /**
     * 清除奖励缓存
     */
    protected function clearRewardCache($userId, $videoId)
    {
        $cacheKey = self::CACHE_PREFIX . "status:{$userId}:{$videoId}";
        Cache::delete($cacheKey);
    }
}
