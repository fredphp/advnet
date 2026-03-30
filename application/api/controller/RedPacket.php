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

/**
 * 红包任务接口
 * 
 * 使用 ThinkPHP Cache 统一缓存接口，支持 Redis / File 降级
 * Redis 可用时自动使用 Redis，不可用时降级到文件缓存
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    // 缓存键前缀
    const CACHE_CLICK_PREFIX = 'red_packet:click:';
    
    // 过期时间：1小时
    const CACHE_EXPIRE = 3600;
    
    protected $service = null;
    
    /**
     * @var RiskControlService
     */
    protected $riskService;
    
    /**
     * 是否使用 Redis（运行时检测）
     */
    private static $useRedis = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->service = new \app\common\library\RedPacketClickService();
    }
    
    /**
     * 获取缓存实例（自动降级）
     * 优先 Redis，不可用时降级到 File
     */
    private function getCache()
    {
        if (self::$useRedis === null) {
            try {
                $handler = Cache::store('redis')->handler();
                if ($handler && method_exists($handler, 'ping')) {
                    $handler->ping();
                }
                self::$useRedis = true;
                return Cache::store('redis');
            } catch (\Exception $e) {
                Log::warning('[RedPacket] Redis 不可用，降级到文件缓存: ' . $e->getMessage());
                self::$useRedis = false;
                return Cache::store('file');
            }
        }
        
        return self::$useRedis ? Cache::store('redis') : Cache::store('file');
    }
    
    /**
     * 读取红包点击数据
     */
    private function getClickData($userId)
    {
        $cache = $this->getCache();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        $data = $cache->get($key);
        
        if (!is_array($data)) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * 写入红包点击数据
     */
    private function setClickData($userId, $data)
    {
        $cache = $this->getCache();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        $cache->set($key, $data, self::CACHE_EXPIRE);
    }
    
    /**
     * 删除红包点击数据
     */
    private function delClickData($userId)
    {
        $cache = $this->getCache();
        $key = self::CACHE_CLICK_PREFIX . $userId;
        $cache->rm($key);
    }
    
    /**
     * 点击红包 - 生成/累加红包金额
     * 
     * 逻辑：
     * 1. 首次进入（reset=1）：清理旧数据，生成基础金额
     * 2. 正常点击：累加金额
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
        } catch (\Exception $e) {
            Log::error('红包点击风控服务调用失败: ' . $e->getMessage());
        }
        
        try {
            // 如果是重置模式，先清理旧数据
            if ($reset == 1) {
                $this->delClickData($userId);
                $clickData = [];
            } else {
                $clickData = $this->getClickData($userId);
            }
            
            // 获取当前小时
            $currentHour = intval(date('H'));
            
            // 获取今日已领取金额（从当月分表查询）
            $todayStart = strtotime(date('Y-m-d'));
            $todayAmount = 0;
            $tableName = CoinLog::getTableByMonth();
            if (CoinLog::tableExists($tableName)) {
                $todayAmount = Db::name($tableName)
                    ->where('user_id', $userId)
                    ->whereIn('type', ['red_packet_grab', 'red_packet_click', 'red_packet_reward'])
                    ->where('createtime', '>=', $todayStart)
                    ->sum('amount');
            }
            $todayAmount = intval($todayAmount);
            
            // 判断是否新用户（注册7天内）
            $user = Db::name('user')->field('jointime')->find($userId);
            $isNewUser = $user && (time() - $user['jointime'] < 7 * 86400);
            
            // 获取封顶额度
            $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
            
            // 获取已有的红包数据
            $currentAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            // 本次获得的金额
            $addAmount = 0;
            
            if ($currentAmount <= 0) {
                // 当前红包金额为0或不存在 → 生成基础金额
                $addAmount = RedPacketRewardConfig::generateBaseAmount($todayAmount, $currentHour, $isNewUser);
                
                // 存储新数据
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
                ]);
            }
            
            // 获取最新数据
            $latestData = $this->getClickData($userId);
            
            $this->success('获取成功', [
                'total_amount' => intval($latestData['total_amount'] ?? 0),
            ]);
            
        } catch (\Exception $e) {
            Log::error('红包点击失败: ' . $e->getMessage());
            $this->error('系统错误');
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
        } catch (\Exception $e) {
            Log::error('红包领取风控服务初始化失败: ' . $e->getMessage());
        }
        
        try {
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
                } catch (\Exception $e) {
                    Log::error('红包领取风控检查失败: ' . $e->getMessage());
                }
            }
            
            // 发放金币
            $coinService = new CoinService();
            $result = $coinService->addCoin(
                $userId,
                $totalAmount,
                'red_packet_click',
                'redpacket',                    // relationType
                $taskId ?: 0,                   // relationId
                '观看广告领取红包金币'              // description
            );
            
            if ($result['success']) {
                // 发放成功后删除缓存中的累计金额，开启新一轮红包点击累加
                $this->delClickData($userId);
                
                $this->success('领取成功', [
                    'amount' => $totalAmount,
                    'balance' => $result['balance']
                ]);
            } else {
                $this->error($result['message'] ?? '发放失败');
            }
            
        } catch (\Exception $e) {
            Log::error('红包领取失败: ' . $e->getMessage());
            $this->error('系统错误');
        }
    }
    
    /**
     * 重置红包 - 清理缓存
     * 
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
            // 获取当前红包金额（用于日志记录）
            $clickData = $this->getClickData($userId);
            $totalAmount = isset($clickData['total_amount']) ? intval($clickData['total_amount']) : 0;
            
            // 清理缓存
            $this->delClickData($userId);
            
            Log::info("红包已重置: 用户{$userId}, 原金额{$totalAmount}");
            
            $this->success('重置成功', [
                'success' => true,
                'cleared_amount' => $totalAmount
            ]);
            
        } catch (\Exception $e) {
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
            
        } catch (\Exception $e) {
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
