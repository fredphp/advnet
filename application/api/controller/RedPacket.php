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
    
    // Redis键前缀 - 用于标记用户离开红包页面（待清理）
    const REDIS_LEAVE_PREFIX = 'red_packet:leave:';
    
    // 过期时间：1小时
    const REDIS_EXPIRE = 3600;
    
    // 离开后清理倒计时：5分钟（用户离开5分钟后清理红包金额）
    const CLEANUP_DELAY = 300;
    
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
            $leaveKey = self::REDIS_LEAVE_PREFIX . $userId;
            
            // 用户返回红包页面，删除"待清理"标记
            $redis->del($leaveKey);
            
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
                'total_amount' => intval($latestData['total_amount'] ?? 0),
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
        $leaveKey = self::REDIS_LEAVE_PREFIX . $userId;
        
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
                // 发放成功后删除Redis中的累计金额，开启新一轮红包点击累加
                $redis->del($redisKey);
                $redis->del($leaveKey);
                
                $this->success('领取成功', [
                    'amount' => $totalAmount,
                    'balance' => $result['balance']
                ]);
            } else {
                $this->error($result['message'] ?? '发放失败');
            }
            
        } catch (\Exception $e) {
            Log::error('红包领取失败: ' . $e->getMessage());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 用户离开红包页面 - 标记待清理
     * 
     * 前端调用时机：
     * 1. 页面隐藏（visibilitychange -> hidden）
     * 2. 页面卸载（beforeunload）
     * 3. 跳转到其他页面
     * 
     * 清理逻辑：
     * - 用户离开后开始倒计时（默认5分钟）
     * - 倒计时内用户返回（调用click接口），取消清理
     * - 倒计时结束，自动清理红包金额
     * 
     * @api {post} /api/redpacket/leave 离开红包页面
     * @apiSuccess {Boolean} cleanup_scheduled 是否已安排清理
     * @apiSuccess {Number} cleanup_delay 清理倒计时（秒）
     */
    public function leave()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        try {
            $redis = Cache::store('redis')->handler();
            $redisKey = self::REDIS_CLICK_PREFIX . $userId;
            $leaveKey = self::REDIS_LEAVE_PREFIX . $userId;
            
            // 检查是否有红包金额
            $clickData = $redis->hGetAll($redisKey);
            $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            if ($totalAmount <= 0) {
                // 没有红包金额，无需标记清理
                $this->success('成功', [
                    'cleanup_scheduled' => false,
                    'message' => 'no_amount_to_cleanup'
                ]);
                return;
            }
            
            // 设置"待清理"标记，5分钟后过期
            // 过期后由定时任务或下次请求时检查并清理
            $redis->setex($leaveKey, self::CLEANUP_DELAY, time());
            
            $this->success('成功', [
                'cleanup_scheduled' => true,
                'cleanup_delay' => self::CLEANUP_DELAY,
                'total_amount' => $totalAmount
            ]);
            
        } catch (\Exception $e) {
            Log::error('红包离开标记失败: ' . $e->getMessage());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 检查并清理过期红包
     * 
     * 可由以下方式触发：
     * 1. 定时任务（推荐）
     * 2. 用户请求时被动检查
     * 
     * @api {get} /api/redpacket/cleanup 清理过期红包（内部接口）
     */
    public function cleanup()
    {
        // 简单的安全检查，只允许内部调用或定时任务
        $secret = $this->request->get('secret', '');
        if ($secret !== 'red_packet_cleanup_2024') {
            $this->error('无权访问');
        }
        
        try {
            $redis = Cache::store('redis')->handler();
            
            // 扫描所有离开标记键
            $pattern = self::REDIS_LEAVE_PREFIX . '*';
            $iterator = null;
            $cleanedCount = 0;
            
            while (($keys = $redis->scan($iterator, $pattern, 100)) !== false) {
                foreach ($keys as $leaveKey) {
                    // 检查标记是否还存在（未过期则存在）
                    if ($redis->exists($leaveKey)) {
                        // 提取用户ID
                        $userId = str_replace(self::REDIS_LEAVE_PREFIX, '', $leaveKey);
                        $redisKey = self::REDIS_CLICK_PREFIX . $userId;
                        
                        // 清理红包金额
                        $redis->del($redisKey);
                        $redis->del($leaveKey);
                        
                        $cleanedCount++;
                        Log::info("红包金额已清理: 用户{$userId}");
                    }
                }
            }
            
            $this->success('清理完成', [
                'cleaned_count' => $cleanedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('红包清理失败: ' . $e->getMessage());
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
            
            $this->success('获取成功', [
                'total_amount' => $totalAmount,
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
