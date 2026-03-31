<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\CoinService;
use app\common\library\RiskControlService;
use app\common\model\RedPacketRewardConfig;
use app\common\model\CoinLog;
use think\Db;
use think\Cache;
use think\Log;
use think\exception\HttpResponseException;

/**
 * 红包任务接口
 * 
 * ★ 核心设计：缓存按「用户」隔离（per-user），不按 taskId 隔离
 *   同一个用户点击不同红包 → 金额持续累加（基础+累加+累加...）
 *   只有用户「领取」之后 → 才清零，下一轮从基础金额重新开始
 *   使用 raw Redis handler + json，避免 ThinkPHP Cache 序列化丢失问题
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    // 缓存键（raw Redis key，per-user 共享）
    const CACHE_CLICK_PREFIX = 'red_packet:click:';
    
    // 过期时间：2小时（防止用户长时间不领取占内存）
    const CACHE_EXPIRE = 7200;
    
    protected $service = null;
    
    /**
     * @var RiskControlService
     */
    protected $riskService;
    
    /**
     * Redis 连接
     */
    private static $redis = null;
    private static $redisChecked = false;

    public function _initialize()
    {
        parent::_initialize();
        try {
            $this->service = new \app\common\library\RedPacketClickService();
        } catch (\Throwable $e) {
            Log::error('RedPacketClickService 初始化失败: ' . $e->getMessage());
            $this->service = null;
        }
    }

    /**
     * 在 catch(\Throwable) 中必须重新抛出 HttpResponseException
     */
    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
    }
    
    /**
     * 获取 Redis 连接（与 RedPacketRewardConfig::getRedis() 一致）
     */
    private function getRedis()
    {
        if (self::$redisChecked) {
            return self::$redis;
        }
        
        self::$redisChecked = true;
        
        try {
            $handler = Cache::store('redis');
            if ($handler) {
                $redis = $handler->handler();
                if ($redis instanceof \Redis) {
                    self::$redis = $redis;
                    return $redis;
                }
            }
        } catch (\Exception $e) {
            Log::warning('[RedPacket] Cache::store(redis) 获取失败: ' . $e->getMessage());
        }
        
        try {
            $redis = new \Redis();
            if ($redis->connect('127.0.0.1', 6379, 3)) {
                self::$redis = $redis;
                return $redis;
            }
        } catch (\Exception $e) {
            Log::warning('[RedPacket] Redis 直连失败: ' . $e->getMessage());
        }
        
        self::$redis = null;
        return null;
    }
    
    /**
     * 读取用户红包点击数据（per-user 共享）
     * @param int $userId
     * @return array
     */
    private function getClickData($userId)
    {
        $redis = $this->getRedis();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        
        if ($redis) {
            try {
                $cached = $redis->get($key);
                if ($cached !== false && $cached !== null) {
                    $data = json_decode($cached, true);
                    if (is_array($data)) {
                        return $data;
                    }
                }
                return [];
            } catch (\Exception $e) {
                Log::warning('[RedPacket] Redis get 失败: ' . $e->getMessage());
            }
        }
        
        // 降级到文件缓存
        try {
            $cache = Cache::store('file');
            $data = $cache->get($key);
            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 写入用户红包点击数据
     * @param int $userId
     * @param array $data
     */
    private function setClickData($userId, $data)
    {
        $redis = $this->getRedis();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        $json = json_encode($data);
        
        if ($redis) {
            try {
                $redis->set($key, $json, self::CACHE_EXPIRE);
                Log::info("[RedPacket] setClickData OK: userId={$userId}, total=" . ($data['total_amount'] ?? 0) . ", clicks=" . ($data['click_count'] ?? 0));
                return;
            } catch (\Exception $e) {
                Log::warning('[RedPacket] Redis set 失败: ' . $e->getMessage());
            }
        }
        
        // 降级到文件缓存
        try {
            $cache = Cache::store('file');
            $cache->set($key, $data, self::CACHE_EXPIRE);
        } catch (\Exception $e) {
            Log::error('[RedPacket] 文件缓存写入失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除用户红包点击数据
     * @param int $userId
     */
    private function delClickData($userId)
    {
        $redis = $this->getRedis();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        
        if ($redis) {
            try {
                $redis->del($key);
                Log::info("[RedPacket] delClickData OK: userId={$userId}");
                return;
            } catch (\Exception $e) {
                Log::warning('[RedPacket] Redis del 失败: ' . $e->getMessage());
            }
        }
        
        try {
            $cache = Cache::store('file');
            $cache->rm($key);
        } catch (\Exception $e) {
        }
    }
    
    /**
     * 点击红包 - 生成/累加红包金额
     * 
     * ★ per-user 共享累加：
     *   - 第一次点击任意红包 → 生成基础金额（base_amount）
     *   - 之后每次点击任意红包 → 生成累加金额（accumulate_amount）加到 total 上
     *   - 点击「领取」→ 发放 total_amount 金币 → 清零
     *   - 清零后下一次点击 → 从新的基础金额开始
     *   - reset=1（前端首次点击新红包时发送）→ 仅当已有累加数据时保留，否则正常生成
     * 
     * @api {post} /api/redpacket/click 点击红包
     * @apiParam {Number} [task_id] 任务ID(可选，记录用)
     * @apiParam {Number} [reset] 1=前端认为这是新红包首次点击（不影响累加逻辑）
     * @apiSuccess {Number} total_amount 当前红包总金额（所有点击累加）
     * @apiSuccess {Number} click_count 已点击次数
     */
    public function click()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $taskId = $this->request->post('task_id/d', 0);
        $reset = $this->request->post('reset/d', 0);
        
        // 调用统一风控服务（失败不影响主流程）
        try {
            $this->riskService = new RiskControlService();
            $this->riskService->init(
                $userId,
                $this->request->header('X-Device-Id', ''),
                $this->request->ip(),
                $this->request->header('user-agent', '')
            );
            
            $riskResult = $this->riskService->check('redpacket', 'click', [
                'task_id' => $taskId,
                'reset' => $reset,
            ]);
            
            if (!$riskResult['passed']) {
                $this->error($riskResult['message'] ?: '风控检测未通过');
            }
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包点击风控服务调用失败: ' . $e->getMessage());
        }
        
        try {
            // ★ 读取该用户当前的红包累加数据（per-user 共享）
            $clickData = $this->getClickData($userId);
            
            // 获取当前小时
            $currentHour = intval(date('H'));
            
            // 获取今日已领取金额（从当月分表查询）
            $todayStart = strtotime(date('Y-m-d'));
            $todayAmount = 0;
            try {
                $tableName = CoinLog::getTableByMonth();
                if (CoinLog::tableExists($tableName)) {
                    $todayAmount = Db::name($tableName)
                        ->where('user_id', $userId)
                        ->whereIn('type', ['red_packet_grab', 'red_packet_click', 'red_packet_reward'])
                        ->where('createtime', '>=', $todayStart)
                        ->sum('amount');
                }
                $todayAmount = intval($todayAmount);
            } catch (\Throwable $e) {
                $this->rethrowHttpResponseException($e);
                Log::warning('获取今日红包金额失败: ' . $e->getMessage());
                $todayAmount = 0;
            }
            
            // 判断是否新用户（注册7天内）
            $isNewUser = false;
            try {
                $user = Db::name('user')->field('jointime')->find($userId);
                $isNewUser = $user && (time() - intval($user['jointime']) < 7 * 86400);
            } catch (\Throwable $e) {
                $this->rethrowHttpResponseException($e);
                Log::warning('获取用户注册时间失败: ' . $e->getMessage());
            }
            
            // 获取已有的红包数据
            $currentAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            $currentClickCount = isset($clickData['click_count']) ? intval($clickData['click_count']) : 0;
            
            // 本次获得的金额
            $addAmount = 0;
            
            if ($currentAmount <= 0 || $currentClickCount <= 0) {
                // ★ 没有累加数据 → 生成基础金额（新一轮开始）
                $addAmount = RedPacketRewardConfig::generateBaseAmount(0, $currentHour, $isNewUser);
                
                // 存储新数据
                $newData = [
                    'base_amount'      => $addAmount,
                    'accumulate_amount' => 0,
                    'total_amount'      => $addAmount,
                    'click_count'       => 1,
                    'task_id'           => $taskId,
                    'base_hour'         => $currentHour,
                    'is_new_user'       => $isNewUser ? 1 : 0,
                    'today_amount'      => $todayAmount,
                    'createtime'        => time(),
                    'updatetime'        => time()
                ];
                $this->setClickData($userId, $newData);
                $clickData = $newData;
            } else {
                // ★ 已有累加数据 → 生成累加金额，加到 total 上
                $addAmount = RedPacketRewardConfig::generateAccumulateAmount(0, $currentHour, $isNewUser);
                
                // 获取封顶额度
                $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
                
                // 检查封顶
                if ($currentAmount >= $maxLimit) {
                    $addAmount = 0;
                } elseif ($currentAmount + $addAmount > $maxLimit) {
                    $addAmount = $maxLimit - $currentAmount;
                }
                
                // 更新数据
                $newTotal = $currentAmount + $addAmount;
                $clickData = [
                    'base_amount'      => $clickData['base_amount'] ?? 0,
                    'accumulate_amount' => intval($clickData['accumulate_amount'] ?? 0) + $addAmount,
                    'total_amount'      => $newTotal,
                    'click_count'       => $currentClickCount + 1,
                    'task_id'           => $taskId,
                    'base_hour'         => $clickData['base_hour'] ?? $currentHour,
                    'is_new_user'       => $clickData['is_new_user'] ?? 0,
                    'today_amount'      => $todayAmount,
                    'createtime'        => $clickData['createtime'] ?? time(),
                    'updatetime'        => time()
                ];
                $this->setClickData($userId, $clickData);
            }
            
            $this->success('获取成功', [
                'total_amount' => intval($clickData['total_amount'] ?? 0),
                'click_count'  => intval($clickData['click_count'] ?? 0),
            ]);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包点击失败: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 领取红包金币 - 看完广告后领取
     * @api {post} /api/redpacket/claim 领取红包金币
     * @apiParam {Number} [task_id] 任务ID(可选，记录用)
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
        
        // 调用统一风控服务（失败不影响主流程）
        try {
            $this->riskService = new RiskControlService();
            $this->riskService->init(
                $userId,
                $this->request->header('X-Device-Id', ''),
                $this->request->ip(),
                $this->request->header('user-agent', '')
            );
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包领取风控服务初始化失败: ' . $e->getMessage());
        }
        
        try {
            // ★ 读取用户共享累加数据
            $clickData = $this->getClickData($userId);
            
            if (empty($clickData) || !isset($clickData['total_amount']) || $clickData['total_amount'] <= 0) {
                $this->error('没有可领取的金币');
            }
            
            $totalAmount = intval($clickData['total_amount']);
            $baseAmount = intval($clickData['base_amount'] ?? 0);
            $accumulateAmount = intval($clickData['accumulate_amount'] ?? 0);
            $clickCount = intval($clickData['click_count'] ?? 0);
            
            // 执行风控检查
            if ($this->riskService) {
                try {
                    $riskResult = $this->riskService->check('redpacket', 'claim', [
                        'task_id' => $taskId,
                        'total_amount' => $totalAmount,
                        'base_amount' => $baseAmount,
                        'accumulate_amount' => $accumulateAmount,
                        'click_count' => $clickCount,
                    ]);
                    
                    if (!$riskResult['passed']) {
                        $this->error($riskResult['message'] ?: '风控检测未通过');
                    }
                } catch (\Throwable $e) {
                    $this->rethrowHttpResponseException($e);
                    Log::error('红包领取风控检查失败: ' . $e->getMessage());
                }
            }
            
            // 发放金币
            $coinService = new CoinService();
            $result = $coinService->addCoin(
                $userId,
                $totalAmount,
                'red_packet_click',
                'redpacket',
                $taskId ?: 0,
                '观看广告领取红包金币'
            );
            
            if ($result['success']) {
                // ★ 领取成功后清零，下一轮从基础金额重新累加
                $this->delClickData($userId);
                
                $this->success('领取成功', [
                    'amount' => $totalAmount,
                    'balance' => $result['balance']
                ]);
            } else {
                $this->error($result['message'] ?? '发放失败');
            }
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包领取失败: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->error('系统错误: ' . $e->getMessage());
        }
    }
    
    /**
     * 重置红包 - 清理用户累加数据
     * @api {post} /api/redpacket/reset 重置红包
     * @apiSuccess {Boolean} success 是否成功
     */
    public function reset()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        try {
            $clickData = $this->getClickData($userId);
            $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            $this->delClickData($userId);
            
            Log::info("红包已重置: 用户{$userId}, 原金额{$totalAmount}");
            
            $this->success('重置成功', [
                'success' => true,
                'cleared_amount' => $totalAmount
            ]);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包重置失败: ' . $e->getMessage());
            $this->error('系统错误');
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
        
        try {
            $clickData = $this->getClickData($userId);
            
            $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            $this->success('获取成功', [
                'total_amount' => $totalAmount,
            ]);
            
        } catch (\Throwable $e) {
            $this->rethrowHttpResponseException($e);
            Log::error('红包金额获取失败: ' . $e->getMessage());
            $this->error('系统错误');
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
        $todayAmount = 0;
        $tableName = CoinLog::getTableByMonth();
        if (CoinLog::tableExists($tableName)) {
            $todayAmount = Db::name($tableName)
                ->where('user_id', $this->auth->id)
                ->where('type', 'red_packet_grab')
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');
        }

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
