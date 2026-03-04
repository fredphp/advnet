<?php

namespace app\api\middleware;

use app\common\library\RiskControlService;
use think\Response;

/**
 * 风控中间件
 * 
 * 功能：
 * 1. 请求前置检查
 * 2. 设备指纹验证
 * 3. IP风险检测
 * 4. 行为记录
 * 5. 响应后处理
 */
class RiskControlMiddleware
{
    /**
     * @var array 需要进行风控检查的路由
     */
    protected $watchRoutes = [
        'api/video.reward/watch',
        'api/video.reward/complete',
        'api/redpacket/grab',
        'api/task/complete',
        'api/withdraw/apply',
        'api/invite/bind',
    ];
    
    /**
     * @var array 高风险路由(需要更严格的检查)
     */
    protected $highRiskRoutes = [
        'api/withdraw/apply',
        'api/redpacket/grab',
    ];
    
    /**
     * @var array 免检查路由
     */
    protected $exemptRoutes = [
        'api/user/login',
        'api/user/register',
        'api/token/refresh',
        'api/config/index',
    ];
    
    /**
     * @var RiskControlService 风控服务
     */
    protected $riskService;
    
    /**
     * @var float 请求开始时间
     */
    protected $startTime;
    
    /**
     * 处理请求
     * 
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $this->startTime = microtime(true);
        
        // 获取请求信息
        $route = $request->path();
        $userId = $this->getUserId($request);
        $deviceId = $request->header('X-Device-Id', '');
        $ip = $request->ip();
        $userAgent = $request->header('user-agent', '');
        
        // 初始化服务
        $this->riskService = new RiskControlService();
        $this->riskService->init($userId, $deviceId, $ip, $userAgent);
        $this->riskService->setRequestData($request->param());
        
        // 检查是否免检查路由
        if ($this->isExemptRoute($route)) {
            return $next($request);
        }
        
        // 1. IP基础检查
        $ipCheckResult = $this->checkIp($ip);
        if (!$ipCheckResult['passed']) {
            return $this->errorResponse($ipCheckResult['message'], 403);
        }
        
        // 2. 设备指纹检查
        if (!empty($deviceId)) {
            $deviceCheckResult = $this->checkDevice($deviceId, $userId);
            if (!$deviceCheckResult['passed']) {
                return $this->errorResponse($deviceCheckResult['message'], 403);
            }
        }
        
        // 3. 用户状态检查(已登录用户)
        if ($userId > 0) {
            $userCheckResult = $this->checkUserStatus($userId);
            if (!$userCheckResult['passed']) {
                return $this->errorResponse($userCheckResult['message'], 403);
            }
        }
        
        // 4. 路由级别风控检查
        if ($this->shouldCheckRoute($route) && $userId > 0) {
            $routeCheckResult = $this->checkRouteRisk($route, $request);
            if (!$routeCheckResult['passed']) {
                return $this->errorResponse($routeCheckResult['message'], 429);
            }
        }
        
        // 5. 请求频率限制
        $rateLimitResult = $this->checkRateLimit($ip, $userId, $route);
        if (!$rateLimitResult['passed']) {
            return $this->errorResponse($rateLimitResult['message'], 429, [
                'retry_after' => $rateLimitResult['retry_after'] ?? 60,
            ]);
        }
        
        // 执行请求
        $response = $next($request);
        
        // 6. 记录行为日志
        $this->recordBehavior($request, $response, $userId);
        
        // 7. 更新请求统计
        $this->updateRequestStats($ip, $deviceId);
        
        // 8. 添加风控响应头
        $this->addRiskHeaders($response);
        
        return $response;
    }
    
    /**
     * 检查IP
     */
    protected function checkIp($ip)
    {
        // IP格式验证
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return [
                'passed' => false,
                'message' => '无效的IP地址',
            ];
        }
        
        // 检查IP黑名单
        $blacklist = \app\common\model\RiskBlacklist::where('type', 'ip')
            ->where('value', $ip)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        if ($blacklist) {
            return [
                'passed' => false,
                'message' => '当前网络环境异常，请更换网络后重试',
            ];
        }
        
        // 检查IP风险评分
        $ipRisk = \app\common\model\IpRisk::where('ip', $ip)->find();
        if ($ipRisk && $ipRisk['risk_level'] == 'blacklist') {
            return [
                'passed' => false,
                'message' => '当前IP已被限制访问',
            ];
        }
        
        // 检查IP代理
        if ($ipRisk && $ipRisk['proxy_detected'] && $ipRisk['risk_score'] > 50) {
            return [
                'passed' => false,
                'message' => '检测到代理访问，请使用正常网络',
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查设备
     */
    protected function checkDevice($deviceId, $userId)
    {
        $device = \app\common\model\DeviceFingerprint::where('device_id', $deviceId)->find();
        
        if (!$device) {
            return ['passed' => true];
        }
        
        // 检查设备黑名单
        $blacklist = \app\common\model\RiskBlacklist::where('type', 'device')
            ->where('value', $deviceId)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        if ($blacklist) {
            return [
                'passed' => false,
                'message' => '当前设备已被限制访问',
            ];
        }
        
        // 检查模拟器
        if ($device['emulator_detected']) {
            return [
                'passed' => false,
                'message' => '不支持模拟器访问',
            ];
        }
        
        // 检查Hook框架
        if ($device['hook_detected']) {
            return [
                'passed' => false,
                'message' => '检测到异常环境，请使用正常设备',
            ];
        }
        
        // 检查设备风险等级
        if ($device['risk_level'] == 'blacklist') {
            return [
                'passed' => false,
                'message' => '当前设备已被限制访问',
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查用户状态
     */
    protected function checkUserStatus($userId)
    {
        $userScore = \app\common\model\UserRiskScore::where('user_id', $userId)->find();
        
        if (!$userScore) {
            return ['passed' => true];
        }
        
        // 检查封禁状态
        if ($userScore['status'] == 'banned') {
            if ($userScore['ban_expire_time'] === null) {
                return [
                    'passed' => false,
                    'message' => '您的账户已被永久封禁',
                ];
            }
            if ($userScore['ban_expire_time'] > time()) {
                $remaining = ceil(($userScore['ban_expire_time'] - time()) / 86400);
                return [
                    'passed' => false,
                    'message' => "您的账户已被封禁，{$remaining}天后自动解封",
                ];
            }
        }
        
        // 检查冻结状态
        if ($userScore['status'] == 'frozen') {
            if ($userScore['freeze_expire_time'] > time()) {
                $remaining = ceil(($userScore['freeze_expire_time'] - time()) / 3600);
                return [
                    'passed' => false,
                    'message' => "您的账户已被冻结，{$remaining}小时后自动解冻",
                ];
            }
        }
        
        // 检查用户账户状态
        $user = \think\Db::name('user')->where('id', $userId)->find();
        if ($user && $user['status'] != 'normal') {
            return [
                'passed' => false,
                'message' => '账户状态异常，请联系客服',
            ];
        }
        
        return ['passed' => true];
    }
    
    /**
     * 检查路由风控
     */
    protected function checkRouteRisk($route, $request)
    {
        // 获取规则类型
        $ruleType = $this->getRuleType($route);
        
        if (!$ruleType) {
            return ['passed' => true];
        }
        
        // 构建上下文
        $context = $this->buildContext($route, $request);
        
        // 执行风控检查
        $result = $this->riskService->check($ruleType, $route, $context);
        
        return $result;
    }
    
    /**
     * 检查请求频率限制
     */
    protected function checkRateLimit($ip, $userId, $route)
    {
        $cache = \think\Cache::getInstance();
        
        // IP级别限制 (每分钟60次)
        $ipKey = 'rate_limit:ip:' . $ip;
        $ipCount = $cache->get($ipKey, 0);
        if ($ipCount >= 60) {
            return [
                'passed' => false,
                'message' => '请求过于频繁，请稍后再试',
                'retry_after' => 60,
            ];
        }
        $cache->set($ipKey, $ipCount + 1, 60);
        
        // 用户级别限制 (每分钟30次)
        if ($userId > 0) {
            $userKey = 'rate_limit:user:' . $userId;
            $userCount = $cache->get($userKey, 0);
            if ($userCount >= 30) {
                return [
                    'passed' => false,
                    'message' => '操作过于频繁，请稍后再试',
                    'retry_after' => 60,
                ];
            }
            $cache->set($userKey, $userCount + 1, 60);
        }
        
        // 高风险路由特殊限制
        if ($this->isHighRiskRoute($route)) {
            $highRiskKey = 'rate_limit:high:' . ($userId > 0 ? 'user:' . $userId : 'ip:' . $ip);
            $highRiskCount = $cache->get($highRiskKey, 0);
            if ($highRiskCount >= 5) {
                return [
                    'passed' => false,
                    'message' => '该操作过于频繁，请稍后再试',
                    'retry_after' => 300,
                ];
            }
            $cache->set($highRiskKey, $highRiskCount + 1, 300);
        }
        
        return ['passed' => true];
    }
    
    /**
     * 记录行为日志
     */
    protected function recordBehavior($request, $response, $userId)
    {
        if ($userId <= 0) {
            return;
        }
        
        $route = $request->path();
        $behaviorType = $this->getBehaviorType($route);
        
        if (!$behaviorType) {
            return;
        }
        
        try {
            $behavior = new \app\common\model\UserBehavior();
            $behavior->user_id = $userId;
            $behavior->behavior_type = $behaviorType;
            $behavior->behavior_action = $route;
            $behavior->device_id = $request->header('X-Device-Id', '');
            $behavior->ip = $request->ip();
            $behavior->user_agent = $request->header('user-agent', '');
            $behavior->duration = intval((microtime(true) - $this->startTime) * 1000);
            $behavior->extra_data = json_encode([
                'params' => $request->param(),
                'response_code' => $response->getCode(),
            ]);
            $behavior->save();
        } catch (\Exception $e) {
            // 行为记录失败不影响主流程
        }
    }
    
    /**
     * 更新请求统计
     */
    protected function updateRequestStats($ip, $deviceId)
    {
        try {
            // 更新IP请求统计
            $ipRisk = \app\common\model\IpRisk::where('ip', $ip)->find();
            if ($ipRisk) {
                $ipRisk->request_count = $ipRisk->request_count + 1;
                $ipRisk->last_request_time = time();
                $ipRisk->save();
            }
            
            // 更新今日统计
            $today = date('Y-m-d');
            $stat = \think\Db::name('risk_stat')
                ->where('stat_date', $today)
                ->find();
            
            if ($stat) {
                \think\Db::name('risk_stat')
                    ->where('id', $stat['id'])
                    ->inc('total_requests')
                    ->update();
            } else {
                \think\Db::name('risk_stat')->insert([
                    'stat_date' => $today,
                    'total_requests' => 1,
                    'createtime' => time(),
                ]);
            }
        } catch (\Exception $e) {
            // 统计更新失败不影响主流程
        }
    }
    
    /**
     * 添加风控响应头
     */
    protected function addRiskHeaders($response)
    {
        $response->header([
            'X-Risk-Score' => $this->riskService->calculateRiskScore(),
            'X-Risk-Level' => $this->riskService->getRiskLevel(
                $this->riskService->calculateRiskScore()
            ),
        ]);
    }
    
    /**
     * 判断是否免检查路由
     */
    protected function isExemptRoute($route)
    {
        foreach ($this->exemptRoutes as $exemptRoute) {
            if (strpos($route, $exemptRoute) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 判断是否需要检查的路由
     */
    protected function shouldCheckRoute($route)
    {
        foreach ($this->watchRoutes as $watchRoute) {
            if (strpos($route, $watchRoute) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 判断是否高风险路由
     */
    protected function isHighRiskRoute($route)
    {
        foreach ($this->highRiskRoutes as $highRiskRoute) {
            if (strpos($route, $highRiskRoute) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 获取规则类型
     */
    protected function getRuleType($route)
    {
        if (strpos($route, 'video') !== false) {
            return 'video';
        }
        if (strpos($route, 'task') !== false) {
            return 'task';
        }
        if (strpos($route, 'withdraw') !== false) {
            return 'withdraw';
        }
        if (strpos($route, 'redpacket') !== false) {
            return 'redpacket';
        }
        if (strpos($route, 'invite') !== false) {
            return 'invite';
        }
        return null;
    }
    
    /**
     * 获取行为类型
     */
    protected function getBehaviorType($route)
    {
        $map = [
            'video' => 'video_watch',
            'task' => 'task_complete',
            'withdraw' => 'withdraw',
            'redpacket' => 'redpacket_grab',
            'invite' => 'invite',
            'login' => 'login',
        ];
        
        foreach ($map as $key => $type) {
            if (strpos($route, $key) !== false) {
                return $type;
            }
        }
        
        return 'other';
    }
    
    /**
     * 构建上下文
     */
    protected function buildContext($route, $request)
    {
        $context = [];
        
        // 视频相关
        if (strpos($route, 'video') !== false) {
            $context['video_id'] = $request->param('video_id', 0);
            $context['watch_duration'] = $request->param('watch_duration', 0);
            $context['video_duration'] = $request->param('video_duration', 0);
            $context['coin_earned'] = $request->param('coin_earned', 0);
        }
        
        // 任务相关
        if (strpos($route, 'task') !== false) {
            $context['task_id'] = $request->param('task_id', 0);
            $context['task_duration'] = $request->param('task_duration', 0);
            $context['click_intervals'] = $request->param('click_intervals', []);
            $context['touch_positions'] = $request->param('touch_positions', []);
        }
        
        // 提现相关
        if (strpos($route, 'withdraw') !== false) {
            $context['amount'] = $request->param('amount', 0);
        }
        
        // 红包相关
        if (strpos($route, 'redpacket') !== false) {
            $context['redpacket_id'] = $request->param('redpacket_id', 0);
            $context['grab_time'] = $request->param('grab_time', 0);
        }
        
        // 邀请相关
        if (strpos($route, 'invite') !== false) {
            $context['invitee_id'] = $request->param('invitee_id', 0);
        }
        
        return $context;
    }
    
    /**
     * 获取用户ID
     */
    protected function getUserId($request)
    {
        return $request->userId ?? 0;
    }
    
    /**
     * 错误响应
     */
    protected function errorResponse($message, $code = 400, $data = [])
    {
        $result = [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'time' => time(),
        ];
        
        return Response::create($result, 'json', $code);
    }
}
