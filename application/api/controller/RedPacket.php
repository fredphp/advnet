<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\RedPacketClickService;
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
    
    // Redis键前缀 - 用于存储用户点击累计金额（使用Hash结构）
    const REDIS_CLICK_PREFIX = 'red_packet:click:';
    
    // 过期时间：24小时
    const REDIS_EXPIRE = 86400;
    
    protected $service = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->service = new RedPacketClickService();
    }
    
    /**
     * 点击红包 - 根据配置生成/累加红包金额
     * 
     * 逻辑说明：
     * 1. 如果Redis中没有红包金额或金额为0，生成基础金额
     * 2. 如果有累计金额，检查基础金额是否符合当前时间段配置
     * 3. 如果不符合当前时间段，重新生成基础金额
     * 4. 累加金额根据当前时间段和今日已领取金额获取
     * 5. 总金额不能超过封顶额度
     * 
     * @api {post} /api/redpacket/click 点击红包
     * @apiParam {Number} [task_id] 任务ID(可选)
     * @apiSuccess {Number} amount 本次点击获得的金额
     * @apiSuccess {Number} base_amount 基础金额
     * @apiSuccess {Number} accumulate_amount 累加金额
     * @apiSuccess {Number} total_amount 总金额
     * @apiSuccess {Number} click_count 点击次数
     * @apiSuccess {Boolean} is_new_base 是否重新生成基础金额
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
            
            // 从缓存获取封顶额度
            $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
            
            // 从缓存获取当前时间段的配置
            $currentTimeRange = RedPacketRewardConfig::getTimeConfig($currentHour);
            
            // 获取Redis中已有的数据
            $clickData = $redis->hGetAll($redisKey);
            
            $baseAmount = 0;
            $accumulateAmount = 0;
            $totalAmount = 0;
            $clickCount = 0;
            $isNewBase = false;
            $baseHour = null;
            
            if ($clickData && isset($clickData['total_amount'])) {
                $baseAmount = intval($clickData['base_amount'] ?? 0);
                $accumulateAmount = intval($clickData['accumulate_amount'] ?? 0);
                $totalAmount = intval($clickData['total_amount'] ?? 0);
                $clickCount = intval($clickData['click_count'] ?? 0);
                $baseHour = isset($clickData['base_hour']) ? intval($clickData['base_hour']) : null;
            }
            
            // 判断是否需要生成/重新生成基础金额
            $needNewBase = false;
            if ($totalAmount <= 0) {
                // 没有累计金额，需要生成基础金额
                $needNewBase = true;
            } else {
                // 获取基础金额生成时的时间段配置
                $baseStartHour = isset($clickData['base_start_hour']) ? intval($clickData['base_start_hour']) : null;
                $baseEndHour = isset($clickData['base_end_hour']) ? intval($clickData['base_end_hour']) : null;
                
                // 判断当前时间是否仍在基础金额的时间段内
                $stillInSameRange = false;
                if ($baseStartHour !== null && $baseEndHour !== null) {
                    if ($currentHour >= $baseStartHour && $currentHour < $baseEndHour) {
                        $stillInSameRange = true;
                    }
                }
                
                if (!$stillInSameRange && $currentTimeRange) {
                    // 时间段变化，检查基础金额是否在新时间段范围内
                    $currentBaseMin = $isNewUser ? intval($currentTimeRange['new_user_base_min']) : intval($currentTimeRange['base_min_reward']);
                    $currentBaseMax = $isNewUser ? intval($currentTimeRange['new_user_base_max']) : intval($currentTimeRange['base_max_reward']);
                    
                    // 如果基础金额不在当前时间段范围内，需要重新生成
                    if ($baseAmount < $currentBaseMin || $baseAmount > $currentBaseMax) {
                        $needNewBase = true;
                        Log::info("红包基础金额重新生成: 用户{$userId}, 旧基础金额{$baseAmount}不在新时间段[{$currentBaseMin}-{$currentBaseMax}]内");
                    }
                }
            }
            
            if ($needNewBase) {
                // 生成新的基础金额（从缓存获取配置范围）
                $baseRange = RedPacketRewardConfig::getBaseRewardRange($todayAmount, $currentHour, $isNewUser);
                $baseAmount = RedPacketRewardConfig::randomAmount($baseRange);
                $baseAmount = min($baseAmount, $maxLimit);
                
                $accumulateAmount = 0;
                $totalAmount = $baseAmount;
                $clickCount = 1;
                $baseHour = $currentHour;
                $isNewBase = true;
                
                // 记录基础金额生成时的时间段
                $baseStartHour = $currentTimeRange ? intval($currentTimeRange['start_hour']) : 0;
                $baseEndHour = $currentTimeRange ? intval($currentTimeRange['end_hour']) : 24;
                
                $addedAmount = $baseAmount;
            } else {
                // 累加金额（从缓存获取配置范围）
                $accumulateRange = RedPacketRewardConfig::getAccumulateRewardRange($todayAmount, $currentHour, $isNewUser);
                $addAmount = RedPacketRewardConfig::randomAmount($accumulateRange);
                
                // 检查封顶
                if ($totalAmount >= $maxLimit) {
                    $addAmount = 0;
                } elseif ($totalAmount + $addAmount > $maxLimit) {
                    $addAmount = $maxLimit - $totalAmount;
                }
                
                $accumulateAmount += $addAmount;
                $totalAmount = $baseAmount + $accumulateAmount;
                $clickCount++;
                
                $addedAmount = $addAmount;
            }
            
            // 存储到Redis
            $redis->hMSet($redisKey, [
                'base_amount' => $baseAmount,
                'accumulate_amount' => $accumulateAmount,
                'total_amount' => $totalAmount,
                'click_count' => $clickCount,
                'task_id' => $taskId,
                'base_hour' => $baseHour,
                'base_start_hour' => isset($baseStartHour) ? $baseStartHour : ($clickData['base_start_hour'] ?? 0),
                'base_end_hour' => isset($baseEndHour) ? $baseEndHour : ($clickData['base_end_hour'] ?? 24),
                'is_new_user' => $isNewUser ? 1 : 0,
                'today_amount' => $todayAmount,
                'updatetime' => time()
            ]);
            $redis->expire($redisKey, self::REDIS_EXPIRE);
            
            $this->success('获取成功', [
                'amount' => $addedAmount,
                'base_amount' => $baseAmount,
                'accumulate_amount' => $accumulateAmount,
                'total_amount' => $totalAmount,
                'click_count' => $clickCount,
                'is_new_base' => $isNewBase,
                'max_limit' => $maxLimit,
                'reached_limit' => $totalAmount >= $maxLimit
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
                    'base_amount' => $baseAmount,
                    'accumulate_amount' => $accumulateAmount,
                    'click_count' => $clickCount,
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
     * @apiSuccess {Number} base_amount 基础金额
     * @apiSuccess {Number} accumulate_amount 累加金额
     * @apiSuccess {Number} total_amount 总金额
     * @apiSuccess {Number} click_count 点击次数
     * @apiSuccess {Number} max_limit 封顶额度
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
            
            $baseAmount = 0;
            $accumulateAmount = 0;
            $totalAmount = 0;
            $clickCount = 0;
            
            if ($clickData && isset($clickData['total_amount'])) {
                $baseAmount = intval($clickData['base_amount'] ?? 0);
                $accumulateAmount = intval($clickData['accumulate_amount'] ?? 0);
                $totalAmount = intval($clickData['total_amount'] ?? 0);
                $clickCount = intval($clickData['click_count'] ?? 0);
            }
            
            $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
            
            $this->success('获取成功', [
                'base_amount' => $baseAmount,
                'accumulate_amount' => $accumulateAmount,
                'total_amount' => $totalAmount,
                'click_count' => $clickCount,
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
