<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\VideoRewardService;
use app\common\library\CoinService;
use think\facade\Db;

/**
 * 视频收益接口
 */
class VideoReward extends Api
{
    // 无需登录的接口
    protected $noNeedLogin = [];
    
    // 无需鉴权的接口
    protected $noNeedRight = ['*'];
    
    /**
     * @var VideoRewardService
     */
    protected $videoRewardService;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->videoRewardService = new VideoRewardService();
    }
    
    /**
     * 上报观看进度
     * @api {post} /api/video_reward/watch 上报观看进度
     * @apiName WatchVideo
     * @apiGroup 视频收益
     * @apiParam {Number} video_id 视频ID
     * @apiParam {Number} watch_duration 本次观看时长(秒)
     * @apiParam {Number} watch_progress 本次观看进度(0-100)
     * @apiParam {Number} [current_position] 当前播放位置(秒)
     * @apiParam {String} [session_id] 会话ID
     */
    public function watch()
    {
        $videoId = $this->request->post('video_id/d');
        $watchDuration = $this->request->post('watch_duration/d');
        $watchProgress = $this->request->post('watch_progress/d');
        $currentPosition = $this->request->post('current_position/d', 0);
        $sessionId = $this->request->post('session_id/s', '');
        
        // 参数验证
        if (!$videoId) {
            $this->error('视频ID不能为空');
        }
        
        if ($watchDuration < 0 || $watchDuration > 86400) {
            $this->error('观看时长参数错误');
        }
        
        if ($watchProgress < 0 || $watchProgress > 100) {
            $this->error('观看进度参数错误');
        }
        
        $data = [
            'watch_duration' => $watchDuration,
            'watch_progress' => $watchProgress,
            'current_position' => $currentPosition,
            'session_id' => $sessionId,
            'ip' => $this->request->ip(),
            'device_id' => $this->request->header('x-device-id', ''),
        ];
        
        $result = $this->videoRewardService->reportWatchProgress(
            $this->auth->id,
            $videoId,
            $data
        );
        
        $this->success('上报成功', $result);
    }
    
    /**
     * 领取奖励
     * @api {post} /api/video_reward/claim 领取奖励
     * @apiName ClaimReward
     * @apiGroup 视频收益
     * @apiParam {Number} video_id 视频ID
     */
    public function claim()
    {
        $videoId = $this->request->post('video_id/d');
        
        if (!$videoId) {
            $this->error('视频ID不能为空');
        }
        
        $options = [
            'ip' => $this->request->ip(),
            'device_id' => $this->request->header('x-device-id', ''),
        ];
        
        $result = $this->videoRewardService->claimReward(
            $this->auth->id,
            $videoId,
            $options
        );
        
        if ($result['success']) {
            $this->success($result['message'], $result);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量获取奖励状态
     * @api {post} /api/video_reward/status 批量获取奖励状态
     * @apiName GetRewardStatus
     * @apiGroup 视频收益
     * @apiParam {Number[]} video_ids 视频ID数组
     */
    public function status()
    {
        $videoIds = $this->request->post('video_ids/a', []);
        
        if (empty($videoIds)) {
            $this->error('请提供视频ID');
        }
        
        // 限制批量查询数量
        $videoIds = array_slice($videoIds, 0, 50);
        
        $result = $this->videoRewardService->batchGetRewardStatus(
            $this->auth->id,
            $videoIds
        );
        
        $this->success('获取成功', ['list' => $result]);
    }
    
    /**
     * 获取合集观看进度
     * @api {get} /api/video_reward/collection 获取合集进度
     * @apiName GetCollectionProgress
     * @apiGroup 视频收益
     * @apiParam {Number} collection_id 合集ID
     */
    public function collection()
    {
        $collectionId = $this->request->get('collection_id/d');
        
        if (!$collectionId) {
            $this->error('合集ID不能为空');
        }
        
        $result = $this->videoRewardService->getCollectionProgress(
            $this->auth->id,
            $collectionId
        );
        
        if (empty($result)) {
            $this->error('合集不存在');
        }
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取今日收益统计
     * @api {get} /api/video_reward/daily 获取今日统计
     * @apiName GetDailyStats
     * @apiGroup 视频收益
     */
    public function daily()
    {
        $userId = $this->auth->id;
        $today = date('Y-m-d');
        
        // 获取今日统计
        $stat = Db::name('user_daily_reward_stat')
            ->where('user_id', $userId)
            ->where('date_key', $today)
            ->find();
        
        // 获取每日上限
        $dailyLimit = Db::name('config')
            ->where('name', 'daily_watch_limit')
            ->value('value') ?: 50;
        
        $videoRewardCount = $stat['video_reward_count'] ?? 0;
        
        $this->success('获取成功', [
            'video_reward_count' => $videoRewardCount,
            'video_reward_coin' => $stat['video_reward_coin'] ?? 0,
            'daily_limit' => intval($dailyLimit),
            'remain_count' => max(0, intval($dailyLimit) - $videoRewardCount),
        ]);
    }
    
    /**
     * 获取奖励配置信息
     * @api {get} /api/video_reward/config 获取奖励配置
     * @apiName GetRewardConfig
     * @apiGroup 视频收益
     */
    public function config()
    {
        $configs = Db::name('config')
            ->whereIn('name', [
                'watch_complete_threshold',
                'daily_watch_limit',
                'default_reward_coin',
                'new_user_reward_coin',
            ])
            ->column('value', 'name');
        
        $this->success('获取成功', [
            'watch_complete_threshold' => intval($configs['watch_complete_threshold'] ?? 95),
            'daily_watch_limit' => intval($configs['daily_watch_limit'] ?? 50),
            'default_reward_coin' => floatval($configs['default_reward_coin'] ?? 100),
            'new_user_reward_coin' => floatval($configs['new_user_reward_coin'] ?? 200),
        ]);
    }
}
