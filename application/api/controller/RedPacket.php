<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CoinService;
use app\common\model\RedPacketRewardConfig;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 红包任务接口
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    // Redis键前缀 - 用于存储用户当前红包金额
    const REDIS_CLICK_PREFIX = 'red_packet:click:';
    
    // 过期时间：24小时
    const REDIS_EXPIRE = 86400;
    
    protected $service = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->service = new \app\common\library\RedPacketClickService();
    }
    
    /**
     * 点击红包 - 生成/累加红包金额
     * 
     * 简化逻辑：
     * 1. 如果用户当前红包金额为0或不存在 → 生成基础金额 → 返回基础金额
     * 2. 如果用户当前红包金额不为0 → 生成累加金额 → 返回 当前金额 + 累加金额
     * 
     * @api {post} /api/redpacket/click 点击红包
     * @apiParam {Number} [task_id] 任务ID(可选)
     * @apiSuccess {Number} amount 本次获得的金额（前端展示用）
     * @apiSuccess {Number} total_amount 当前红包总金额
     */
    public function click()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $taskId = $this->request->post('task_id/d', 0);
        
        try {
            $redis = Cache::store('redis')->handler();
            $redisKey = self::REDIS_CLICK_PREFIX . $userId;
            
            // 获取当前小时
            $currentHour = intval(date('H'));
            
            // 获取今日已领取金额
            $todayStart = strtotime(date('Y-m-d'));
            $todayAmount = Db::name('coin_log')
                ->where('user_id', $userId)
                ->whereIn('type', ['red_packet_grab', 'red_packet_click', 'red_packet_reward'])
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');
            $todayAmount = intval($todayAmount);
            
            // 判断是否新用户（注册7天内）
            $user = Db::name('user')->field('jointime')->find($userId);
            $isNewUser = $user && (time() - $user['jointime'] < 7 * 86400);
            
            // 获取封顶额度
            $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
            
            // 获取Redis中已有的红包数据
            $clickData = $redis->hGetAll($redisKey);
            $currentAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            // 本次获得的金额
            $addAmount = 0;
            
            if ($currentAmount <= 0) {
                // 当前红包金额为0或不存在 → 生成基础金额
                $addAmount = RedPacketRewardConfig::generateBaseAmount($todayAmount, $currentHour, $isNewUser);
                
                // 存储新数据
                $redis->hMSet($redisKey, [
                    'base_amount' => $addAmount,
                    'accumulate_amount' => 0,
                    'total_amount' => $addAmount,
                    'click_count' => 1,
                    'task_id' => $taskId,
                    'base_hour' => $currentHour,
                    'is_new_user' => $isNewUser ? 1 : 0,
                    'today_amount' => $todayAmount,
                    'createtime' => time(),
                    'updatetime' => time()
                ]);
            } else {
                // 当前红包金额不为0 → 生成累加金额
                $addAmount = RedPacketRewardConfig::generateAccumulateAmount($todayAmount, $currentHour, $isNewUser);
                
                // 检查封顶
                if ($currentAmount >= $maxLimit) {
                    $addAmount = 0;
                } elseif ($currentAmount + $addAmount > $maxLimit) {
                    $addAmount = $maxLimit - $currentAmount;
                }
                
                // 更新数据
                $newTotal = $currentAmount + $addAmount;
                $redis->hMSet($redisKey, [
                    'accumulate_amount' => intval($clickData['accumulate_amount'] ?? 0) + $addAmount,
                    'total_amount' => $newTotal,
                    'click_count' => intval($clickData['click_count'] ?? 0) + 1,
                    'updatetime' => time()
                ]);
            }
            
            $redis->expire($redisKey, self::REDIS_EXPIRE);
            
            // 获取最新的红包数据
            $latestData = $redis->hGetAll($redisKey);
            
            $this->success('获取成功', [
                'amount' => $addAmount,
                'total_amount' => intval($latestData['total_amount'] ?? 0),
                'max_limit' => $maxLimit,
                'reached_limit' => intval($latestData['total_amount'] ?? 0) >= $maxLimit
            ]);
            
        } catch (\Exception $e) {
            Log::error('红包点击失败: ' . $e->getMessage());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 领取红包金币 - 看完广告后领取
     * @api {post} /api/redpacket/claim 领取红包金币
     * @apiParam {Number} [task_id] 任务ID(可选)
     * @apiSuccess {Number} amount 领取到的金额
     * @apiSuccess {Number} balance 当前余额
     */
    public function claim()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $taskId = $this->request->post('task_id/d', 0);
        
        $redisKey = self::REDIS_CLICK_PREFIX . $userId;
        
        try {
            $redis = Cache::store('redis')->handler();
            
            $clickData = $redis->hGetAll($redisKey);
            
            if (!$clickData || !isset($clickData['total_amount']) || $clickData['total_amount'] <= 0) {
                $this->error('没有可领取的金币');
            }
            
            $totalAmount = intval($clickData['total_amount']);
            $baseAmount = intval($clickData['base_amount'] ?? 0);
            $accumulateAmount = intval($clickData['accumulate_amount'] ?? 0);
            $clickCount = intval($clickData['click_count'] ?? 0);
            
            // 删除Redis中的累计金额
            $redis->del($redisKey);
            
            // 发放金币
            $coinService = new CoinService();
            $result = $coinService->addCoin(
                $userId,
                $totalAmount,
                'red_packet_click',
                [
                    'relation_type' => 'click',
                    'relation_id' => $taskId ?: 0,
                    'description' => '观看广告领取红包金币',
                    'base_amount' => $baseAmount,
                    'accumulate_amount' => $accumulateAmount,
                    'click_count' => $clickCount
                ]
            );
            
            if ($result['success']) {
                $this->success('领取成功', [
                    'amount' => $totalAmount,
                    'balance' => $result['balance']
                ]);
            } else {
                // 如果发放失败，恢复Redis中的金额
                $redis->hMSet($redisKey, $clickData);
                $redis->expire($redisKey, self::REDIS_EXPIRE);
                $this->error($result['message'] ?? '发放失败');
            }
            
        } catch (\Exception $e) {
            Log::error('红包领取失败: ' . $e->getMessage());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取当前累计金额
     * @api {get} /api/redpacket/amount 获取累计金额
     */
    public function amount()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $redisKey = self::REDIS_CLICK_PREFIX . $userId;
        
        try {
            $redis = Cache::store('redis')->handler();
            $clickData = $redis->hGetAll($redisKey);
            
            $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
            
            $this->success('获取成功', [
                'total_amount' => $totalAmount,
                'max_limit' => $maxLimit,
                'reached_limit' => $totalAmount >= $maxLimit
            ]);
            
        } catch (\Exception $e) {
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取任务列表
     */
    public function tasks()
    {
        $filters = [
            'page' => $this->request->get('page/d', 1),
            'limit' => $this->request->get('limit/d', 20),
            'type' => $this->request->get('type/s', ''),
        ];
        
        $result = $this->service->getTaskList($this->auth->id, $filters);
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取任务详情
     */
    public function detail()
    {
        $taskId = $this->request->get('task_id/d');
        
        if (!$taskId) {
            $this->error('任务ID不能为空');
        }
        
        $task = $this->service->getTaskDetail($taskId, $this->auth->id);
        
        if (!$task) {
            $this->error('任务不存在');
        }
        
        $this->success('获取成功', $task);
    }
    
    /**
     * 获取用户红包状态
     */
    public function status()
    {
        $taskId = $this->request->get('task_id/d');
        
        if (!$taskId) {
            $this->error('任务ID不能为空');
        }
        
        $status = $this->service->getUserRedPacketStatus($this->auth->id, $taskId);
        
        $this->success('获取成功', $status);
    }
    
    /**
     * 获取今日统计
     */
    public function today()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $this->auth->id)
            ->where('type', 'red_packet_grab')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        $todayGrabCount = Db::name('user_red_packet_accumulate')
            ->where('user_id', $this->auth->id)
            ->where('createtime', '>=', $todayStart)
            ->count();

        $this->success('获取成功', [
            'today_amount' => intval($todayAmount),
            'today_grab_count' => $todayGrabCount,
        ]);
    }
    
    /**
     * 获取我的参与记录
     */
    public function records()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        
        $result = $this->service->getUserRecords($this->auth->id, $page, $limit);
        
        $this->success('获取成功', $result);
    }
}
