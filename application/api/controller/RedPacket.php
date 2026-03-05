<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\RedPacketClickService;
use app\common\library\CoinService;
use think\facade\Db;
use think\facade\Cache;

/**
 * 红包任务接口
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    // Redis键前缀 - 用于存储用户点击累计金额
    const REDIS_CLICK_AMOUNT_PREFIX = 'red_packet:click_amount:';
    
    protected $service = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->service = new RedPacketClickService();
    }
    
    /**
     * 点击红包 - 生成红包金额并累加到Redis
     * 不写数据库，只用Redis存储累计金额
     * @api {post} /api/redpacket/click 点击红包
     * @apiName ClickRedPacket
     * @apiGroup 红包任务
     * @apiParam {Number} [min_amount=1] 最小金额(金币)
     * @apiParam {Number} [max_amount=10] 最大金额(金币)
     * @apiParam {Number} [task_id] 任务ID(可选)
     * @apiSuccess {Number} amount 本次点击获得的金额
     * @apiSuccess {Number} total_amount 累计金额
     */
    public function click()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        // 获取配置参数
        $minAmount = $this->request->post('min_amount/d', 1);
        $maxAmount = $this->request->post('max_amount/d', 10);
        $taskId = $this->request->post('task_id/d', 0);
        
        // 参数校验
        if ($minAmount < 1) {
            $minAmount = 1;
        }
        if ($maxAmount < $minAmount) {
            $maxAmount = $minAmount;
        }
        
        // 生成随机金额
        $amount = mt_rand($minAmount * 100, $maxAmount * 100) / 100;
        
        // Redis键
        $redisKey = self::REDIS_CLICK_AMOUNT_PREFIX . $userId;
        
        try {
            // 获取Redis实例
            $redis = Cache::store('redis')->handler();
            
            // 累加金额到Redis
            $totalAmount = $redis->incrByFloat($redisKey, $amount);
            
            // 设置过期时间（24小时）
            $redis->expire($redisKey, 86400);
            
            $this->success('获取成功', [
                'amount' => $amount,
                'total_amount' => round($totalAmount, 2),
                'task_id' => $taskId
            ]);
            
        } catch (\Exception $e) {
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 领取红包金币 - 看完广告后领取，重置Redis累计金额
     * @api {post} /api/redpacket/claim 领取红包金币
     * @apiName ClaimRedPacket
     * @apiGroup 红包任务
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
        
        // Redis键
        $redisKey = self::REDIS_CLICK_AMOUNT_PREFIX . $userId;
        
        try {
            $redis = Cache::store('redis')->handler();
            
            // 获取累计金额
            $totalAmount = $redis->get($redisKey);
            
            if (!$totalAmount || $totalAmount <= 0) {
                $this->error('没有可领取的金币');
            }
            
            $totalAmount = floatval($totalAmount);
            
            // 删除或重置Redis中的累计金额
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
                    'description' => '观看广告领取红包金币'
                ]
            );
            
            if ($result['success']) {
                $this->success('领取成功', [
                    'amount' => round($totalAmount, 2),
                    'balance' => $result['balance']
                ]);
            } else {
                // 如果发放失败，恢复Redis中的金额
                $redis->set($redisKey, $totalAmount, 86400);
                $this->error($result['message'] ?? '发放失败');
            }
            
        } catch (\Exception $e) {
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取当前累计金额
     * @api {get} /api/redpacket/amount 获取累计金额
     * @apiName GetRedPacketAmount
     * @apiGroup 红包任务
     * @apiSuccess {Number} total_amount 累计金额
     */
    public function amount()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        // Redis键
        $redisKey = self::REDIS_CLICK_AMOUNT_PREFIX . $userId;
        
        try {
            $redis = Cache::store('redis')->handler();
            $totalAmount = $redis->get($redisKey);
            
            $this->success('获取成功', [
                'total_amount' => round(floatval($totalAmount ?: 0), 2)
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
        // 获取今日已领取金额
        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $this->auth->id)
            ->where('type', 'red_packet_grab')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        // 获取今日抢到的红包数
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
