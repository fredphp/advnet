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
 * ★ 核心改动：缓存键从 red_packet:click:{userId} 改为 red_packet:click:{userId}:{taskId}
 *   解决多个红包任务共享同一缓存导致金额互相覆盖的问题
 *   使用 raw Redis handler（与 RedPacketRewardConfig 一致），避免 ThinkPHP Cache 序列化问题
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    // 缓存键前缀（raw Redis key，不含 ThinkPHP prefix）
    const CACHE_CLICK_PREFIX = 'red_packet:click:';
    
    // 过期时间：1小时
    const CACHE_EXPIRE = 3600;
    
    protected $service = null;
    
    /**
     * @var RiskControlService
     */
    protected $riskService;
    
    /**
     * Redis 连接（与 RedPacketRewardConfig 使用相同模式）
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
     * 因为 $this->success() / $this->error() 内部通过抛出 HttpResponseException 来中断执行并返回 JSON
     * 如果被 catch 吞掉，正常响应就会被替换为错误响应
     */
    private function rethrowHttpResponseException(\Throwable $e)
    {
        if ($e instanceof HttpResponseException) {
            throw $e;
        }
    }
    
    /**
     * 获取 Redis 连接（与 RedPacketRewardConfig::getRedis() 完全一致的模式）
     * 优先通过 ThinkPHP Cache store 获取，失败则直连
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
     * 读取红包点击数据（按 taskId 隔离）
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @return array
     */
    private function getClickData($userId, $taskId = 0)
    {
        $redis = $this->getRedis();
        
        if ($redis) {
            try {
                $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
                $cached = $redis->get($key);
                if ($cached !== false && $cached !== null) {
                    $data = json_decode($cached, true);
                    if (is_array($data)) {
                        return $data;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[RedPacket] getClickData Redis读取失败: ' . $e->getMessage());
            }
        }
        
        // 降级到文件缓存
        try {
            $cache = Cache::store('file');
            $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
            $data = $cache->get($key);
            if (is_array($data)) {
                return $data;
            }
        } catch (\Exception $e) {
            Log::warning('[RedPacket] getClickData 文件缓存读取失败: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * 写入红包点击数据（按 taskId 隔离）
     * @param int $userId 用户ID
     * @param array $data 点击数据
     * @param int $taskId 任务ID
     */
    private function setClickData($userId, $data, $taskId = 0)
    {
        $redis = $this->getRedis();
        
        if ($redis) {
            try {
                $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
                $redis->set($key, json_encode($data), self::CACHE_EXPIRE);
                Log::info("[RedPacket] setClickData OK: userId={$userId}, taskId={$taskId}, total=" . ($data['total_amount'] ?? 0) . ", clicks=" . ($data['click_count'] ?? 0));
                return;
            } catch (\Exception $e) {
                Log::warning('[RedPacket] setClickData Redis写入失败: ' . $e->getMessage());
            }
        }
        
        // 降级到文件缓存
        try {
            $cache = Cache::store('file');
            $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
            $cache->set($key, $data, self::CACHE_EXPIRE);
        } catch (\Exception $e) {
            Log::error('[RedPacket] setClickData 文件缓存写入失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 删除红包点击数据（按 taskId 隔离）
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     */
    private function delClickData($userId, $taskId = 0)
    {
        $redis = $this->getRedis();
        
        if ($redis) {
            try {
                $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
                $redis->del($key);
                Log::info("[RedPacket] delClickData OK: userId={$userId}, taskId={$taskId}");
                return;
            } catch (\Exception $e) {
                Log::warning('[RedPacket] delClickData Redis删除失败: ' . $e->getMessage());
            }
        }
        
        // 降级到文件缓存
        try {
            $cache = Cache::store('file');
            $key = self::CACHE_CLICK_PREFIX . $userId . ':' . $taskId;
            $cache->rm($key);
        } catch (\Exception $e) {
            // 静默处理
        }
    }
    
    /**
     * 删除用户所有红包点击数据（用于页面离开清理）
     * @param int $userId 用户ID
     */
    private function delAllClickData($userId)
    {
        $redis = $this->getRedis();
        
        if ($redis) {
            try {
                $pattern = self::CACHE_CLICK_PREFIX . $userId . ':*';
                $iterator = null;
                $count = 0;
                do {
                    $keys = $redis->scan($iterator, $pattern, 100);
                    if ($keys !== false && is_array($keys)) {
                        foreach ($keys as $key) {
                            $redis->del($key);
                            $count++;
                        }
                    }
                } while ($iterator > 0);
                Log::info("[RedPacket] delAllClickData OK: userId={$userId}, deleted={$count}");
                return;
            } catch (\Exception $e) {
                Log::warning('[RedPacket] delAllClickData SCAN失败: ' . $e->getMessage());
            }
        }
        
        // 文件缓存无法按模式删除，各缓存将通过 TTL 自然过期
    }
    
    /**
     * 点击红包 - 生成/累加红包金额
     * 
     * ★ 按 taskId 隔离缓存：每个红包任务的累加独立，互不干扰
     * reset=1 → 清除该 taskId 的缓存，重新生成基础金额
     * reset=0 → 在该 taskId 的已有基础上累加
     * 
     * @api {post} /api/redpacket/click 点击红包
     * @apiParam {Number} [task_id] 任务ID(可选)
     * @apiParam {Number} [reset] 是否重置：1=重置并生成新红包，0=正常累加
     * @apiSuccess {Number} total_amount 当前红包总金额
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
            // ★ 关键修复：按 taskId 隔离缓存
            // 如果是重置模式，只清理该 taskId 的旧数据（不影响其他任务）
            if ($reset == 1) {
                $this->delClickData($userId, $taskId);
                $clickData = [];
            } else {
                $clickData = $this->getClickData($userId, $taskId);
            }
            
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
            
            // ★ 金额生成始终使用 todayAmount=0（默认配置区间）
            // 这样确保：基础额度始终是配置的最大区间，累加额度始终稳定
            // 上限由 red_packet_max_reward 配置控制（默认10000）
            // 不使用实际 todayAmount，避免"领得越多区间越小"的问题
            
            // 获取已有的红包数据（该 taskId 的独立数据）
            $currentAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            // 本次获得的金额
            $addAmount = 0;
            
            if ($currentAmount <= 0) {
                // 当前红包金额为0或不存在 → 生成基础金额（用默认配置，今日金额传0）
                $addAmount = RedPacketRewardConfig::generateBaseAmount(0, $currentHour, $isNewUser);
                
                // 存储新数据（按 taskId 隔离）
                $this->setClickData($userId, [
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
                ], $taskId);
            } else {
                // 当前红包金额不为0 → 生成累加金额（同样用默认配置，今日金额传0）
                $addAmount = RedPacketRewardConfig::generateAccumulateAmount(0, $currentHour, $isNewUser);
                
                // 获取封顶额度
                $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
                
                // 检查封顶
                if ($currentAmount >= $maxLimit) {
                    $addAmount = 0;
                } elseif ($currentAmount + $addAmount > $maxLimit) {
                    $addAmount = $maxLimit - $currentAmount;
                }
                
                // 更新数据（按 taskId 隔离）
                $newTotal = $currentAmount + $addAmount;
                $this->setClickData($userId, [
                    'base_amount'      => $clickData['base_amount'] ?? 0,
                    'accumulate_amount' => intval($clickData['accumulate_amount'] ?? 0) + $addAmount,
                    'total_amount'      => $newTotal,
                    'click_count'       => intval($clickData['click_count'] ?? 0) + 1,
                    'task_id'           => $taskId,
                    'base_hour'         => $clickData['base_hour'] ?? $currentHour,
                    'is_new_user'       => $clickData['is_new_user'] ?? 0,
                    'today_amount'      => $todayAmount,
                    'createtime'        => $clickData['createtime'] ?? time(),
                    'updatetime'        => time()
                ], $taskId);
            }
            
            // 获取最新数据（按 taskId）
            $latestData = $this->getClickData($userId, $taskId);
            
            $this->success('获取成功', [
                'total_amount' => intval($latestData['total_amount'] ?? 0),
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
            // ★ 修复：按 taskId 读取对应任务的累加数据
            $clickData = $this->getClickData($userId, $taskId);
            
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
                // ★ 修复：领取后只清除该 taskId 的缓存（不影响其他任务的累加）
                $this->delClickData($userId, $taskId);
                
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
     * 重置红包 - 清理缓存
     * @api {post} /api/redpacket/reset 重置红包
     * @apiParam {Number} [task_id] 任务ID(可选，不传则清除所有)
     * @apiSuccess {Boolean} success 是否成功
     */
    public function reset()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $taskId = $this->request->post('task_id/d', 0);
        
        try {
            if ($taskId > 0) {
                // 清理指定任务的缓存
                $clickData = $this->getClickData($userId, $taskId);
                $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
                $this->delClickData($userId, $taskId);
                Log::info("红包已重置: 用户{$userId}, 任务{$taskId}, 原金额{$totalAmount}");
            } else {
                // 清理所有任务的缓存
                $this->delAllClickData($userId);
                Log::info("红包已全部重置: 用户{$userId}");
            }
            
            $this->success('重置成功', [
                'success' => true
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
     * @apiParam {Number} [task_id] 任务ID(可选)
     */
    public function amount()
    {
        $userId = $this->auth->id;
        if (!$userId) {
            $this->error('请先登录');
        }
        
        $taskId = $this->request->get('task_id/d', 0);
        
        try {
            $clickData = $this->getClickData($userId, $taskId);
            
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
