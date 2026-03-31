<?php

namespace app\common\library;

use think\Db;
use think\Log;
use app\common\model\RedPacketResource;
use app\common\model\RedPacketTaskSplit;

/**
 * 自动消息推送服务
 * 
 * 功能：
 * 1. 普通消息循环：每隔2-5秒推送聊天/下载/广告消息
 *    - 每发2-5条聊天消息后必发一条下载App或广告时长任务
 *    - 这些消息不计入红包任务数据库
 * 2. 红包任务循环（可开关）：每隔4-10秒推送红包(小程序游戏)任务
 *    - 开关由 advn_config 表的 auto_redpacket_enabled 配置控制
 *    - 红包任务会记录在 red_packet_task_YYYYMM 分表中
 * 
 * 推送方式：通过 TCP 连接 WebSocketService 内部API端口(3003)
 */
class AutoPushService
{
    // TCP API 配置
    const TCP_HOST = '127.0.0.1';
    const TCP_PORT = 3003;
    
    // PID 文件
    const PID_FILE = 'autopush.pid';

    // ==================== 聊天消息模板 ====================
    
    private $chatTemplates = [
        '今天天气真不错啊~',
        '有人一起玩吗？',
        '刚领了个红包，运气不错！',
        '这个活动挺有意思的',
        '每天来群里领红包，已经养成习惯了',
        '加油！争取多领点金币',
        '大家都在玩什么游戏啊？',
        '今天收益怎么样？',
        '这个平台还挺靠谱的',
        '有没有人知道怎么快速赚金币？',
        '领了好几天了，越来越熟练了',
        '昨天提现到账了，速度很快',
        '大家多多分享，人越多红包越大',
        '新来的小伙伴多多参与哦',
        '群里的氛围真好',
        '感觉金币越来越多了，开心',
        '有没有一起组队的？',
        '这个红包群真的能赚钱',
        '每天几分钟，轻松赚零花钱',
        '加油加油，再领一个大的！',
        '今天群里好热闹啊',
        '第一次来，请多关照',
        '快到提现门槛了，加油',
        '你们都领了多少金币了？',
        '我又领到红包了，太开心了',
        '最近活动力度很大，抓紧参与',
        '大家注意看，新红包来了',
        '感觉今天运气特别好',
        '有没有大佬带带我',
        '每天签到+领红包，稳稳的收益',
    ];

    // ==================== 模拟发送者 ====================
    
    private $mockSenders = [
        ['nickname' => '小明', 'avatar' => ''],
        ['nickname' => '小红', 'avatar' => ''],
        ['nickname' => '阿强', 'avatar' => ''],
        ['nickname' => '美美', 'avatar' => ''],
        ['nickname' => '大伟', 'avatar' => ''],
        ['nickname' => '小丽', 'avatar' => ''],
        ['nickname' => '阿杰', 'avatar' => ''],
        ['nickname' => '花花', 'avatar' => ''],
        ['nickname' => '小王', 'avatar' => ''],
        ['nickname' => '丽丽', 'avatar' => ''],
        ['nickname' => '志强', 'avatar' => ''],
        ['nickname' => '晓雯', 'avatar' => ''],
        ['nickname' => '大飞', 'avatar' => ''],
        ['nickname' => '甜甜', 'avatar' => ''],
        ['nickname' => '小陈', 'avatar' => ''],
    ];

    // 下载App 模拟数据
    private $downloadTemplates = [
        ['name' => '下载拼多多领红包', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载淘宝特价版', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载抖音赚金币', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载快手领奖励', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载京东赚金币', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载美团领红包', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载高德地图', 'description' => '下载注册即可领取金币奖励'],
        ['name' => '下载WiFi万能钥匙', 'description' => '下载注册即可领取金币奖励'],
    ];

    // 广告时长 模拟数据
    private $advTemplates = [
        ['name' => '观看30秒广告领金币', 'description' => '观看完整广告即可领取金币奖励', 'adv_duration' => 30],
        ['name' => '看视频赚金币', 'description' => '观看15秒视频即可领取奖励', 'adv_duration' => 15],
        ['name' => '广告奖励任务', 'description' => '观看广告即可领取金币奖励', 'adv_duration' => 20],
        ['name' => '观看广告赢大奖', 'description' => '完成广告观看赢取金币', 'adv_duration' => 30],
        ['name' => '趣味广告挑战', 'description' => '观看趣味广告领取奖励', 'adv_duration' => 25],
    ];

    // ==================== 运行时状态 ====================
    
    /** @var bool 是否运行中 */
    private $running = false;
    
    /** @var int 已发送的聊天消息计数 */
    private $chatCounter = 0;
    
    /** @var int 本轮需要发送的聊天消息目标数（2~5） */
    private $chatTarget = 0;

    /** @var array 配置缓存 */
    private $configCache = [];
    
    /** @var array 资源缓存（按类型） */
    private $resourcesCache = [];
    
    /** @var array 系统用户缓存 */
    private $usersCache = [];
    
    /** @var int 上次配置刷新时间 */
    private $lastConfigCheck = 0;

    /** @var bool 红包定时器是否活跃 */
    private $redpacketActive = false;

    /** @var string CDN域名（CLI模式下手动从数据库读取） */
    private $cdnUrl = '';

    /** @var int 已推送统计 */
    private $stats = [
        'chat' => 0,
        'download' => 0,
        'adv' => 0,
        'redpacket' => 0,
        'total' => 0,
        'start_time' => 0,
    ];

    // ==================== 生命周期 ====================

    public function __construct()
    {
        $this->resetChatCounter();
        $this->stats['start_time'] = time();
    }

    /**
     * 启动服务（阻塞式主循环）
     */
    public function run()
    {
        $this->running = true;
        
        echo "\033[32m========================================\033[0m\n";
        echo "\033[32m   自动消息推送服务\033[0m\n";
        echo "\033[32m========================================\033[0m\n\n";
        
        // 注册信号处理
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGUSR1, [$this, 'printStats']);
        }
        
        // 初始化
        $this->loadCdnUrl();
        $this->loadSystemUsers();
        $this->loadAllResources();
        $this->refreshConfig();
        
        // 计算下次推送时间
        $nextNormalTime = microtime(true) + $this->randomSeconds(2, 5);
        $nextRedpacketTime = microtime(true) + $this->randomSeconds(4, 10);
        $lastConfigCheck = microtime(true);
        $lastStatsPrint = microtime(true);
        $lastDbReconnect = microtime(true);
        
        echo "\033[36m[AutoPush] 服务已启动\033[0m\n";
        echo "\033[36m[AutoPush] 普通消息间隔: 2~5秒\033[0m\n";
        $rpEnabled = $this->isRedpacketEnabled();
        echo "\033[36m[AutoPush] 红包自动推送: " . ($rpEnabled ? '已开启 (4~10秒)' : '已关闭') . "\033[0m\n";
        echo "\033[36m[AutoPush] 聊天目标: {$this->chatTarget} 条后强制下发下载/广告\033[0m\n\n";

        // ==================== 主循环 ====================
        while ($this->running) {
            $now = microtime(true);
            
            // 1. 推送普通消息（聊天/下载/广告）
            if ($now >= $nextNormalTime) {
                try {
                    $this->pushNormalMessage();
                } catch (\Exception $e) {
                    echo "\033[31m[AutoPush] 推送普通消息异常: {$e->getMessage()}\033[0m\n";
                    Log::error('[AutoPush] 推送普通消息异常: ' . $e->getMessage());
                }
                $nextNormalTime = $now + $this->randomSeconds(2, 5);
            }
            
            // 2. 推送红包消息（如果启用）
            $redpacketEnabled = $this->isRedpacketEnabled();
            if ($redpacketEnabled && $now >= $nextRedpacketTime) {
                try {
                    $this->pushRedpacketMessage();
                } catch (\Exception $e) {
                    echo "\033[31m[AutoPush] 推送红包消息异常: {$e->getMessage()}\033[0m\n";
                    Log::error('[AutoPush] 推送红包消息异常: ' . $e->getMessage());
                }
                $nextRedpacketTime = $now + $this->randomSeconds(4, 10);
            }
            
            // 3. 每10秒刷新配置和资源缓存
            if ($now - $lastConfigCheck > 10) {
                $oldRedpacketState = $this->isRedpacketEnabled();
                $this->refreshConfig();
                $this->loadAllResources();
                $this->loadSystemUsers();
                $lastConfigCheck = $now;
                
                // 检测红包开关变化
                $newRedpacketState = $this->isRedpacketEnabled();
                if ($newRedpacketState && !$oldRedpacketState) {
                    echo "\033[33m[AutoPush] 红包自动推送已开启\033[0m\n";
                    $nextRedpacketTime = $now + $this->randomSeconds(4, 10);
                } elseif (!$newRedpacketState && $oldRedpacketState) {
                    echo "\033[33m[AutoPush] 红包自动推送已关闭\033[0m\n";
                }
            }
            
            // 4. 每30秒打印统计
            if ($now - $lastStatsPrint > 30) {
                $this->printStats();
                $lastStatsPrint = $now;
            }
            
            // 5. 每5分钟重连数据库（防止连接超时）
            if ($now - $lastDbReconnect > 300) {
                try {
                    Db::close();
                    $this->resourcesCache = [];
                } catch (\Exception $e) {
                    // 忽略
                }
                $lastDbReconnect = $now;
            }
            
            // 休眠100ms防止CPU空转
            usleep(100000);
        }
        
        echo "\n\033[33m[AutoPush] 服务正在停止...\033[0m\n";
        $this->printStats();
    }

    /**
     * 信号处理
     */
    public function handleSignal($signo)
    {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
                echo "\n\033[33m[AutoPush] 收到停止信号 ({$signo})\033[0m\n";
                $this->running = false;
                break;
            case SIGUSR1:
                $this->printStats();
                break;
        }
    }

    // ==================== 普通消息推送 ====================

    /**
     * 推送普通消息
     * 规则：每发2-5条聊天后必发一条下载App或广告时长
     */
    private function pushNormalMessage()
    {
        if ($this->chatCounter < $this->chatTarget) {
            // 还没达到目标，发送聊天消息
            $this->doPushChat();
            $this->chatCounter++;
        } else {
            // 达到目标，必须发送下载App或广告时长
            $this->doPushForced();
            $this->resetChatCounter();
        }
        
        $this->stats['total']++;
    }

    /**
     * 发送聊天消息
     */
    private function doPushChat()
    {
        $sender = $this->getRandomSender();
        
        // 优先从资源库获取聊天内容
        $resource = $this->getRandomResource('chat');
        $content = '';
        if ($resource) {
            $content = $resource['description'] ?: $resource['name'] ?: '';
        }
        if (empty($content)) {
            $content = $this->chatTemplates[array_rand($this->chatTemplates)];
        }

        $pushData = $this->buildBasePushData($sender, 'chat', [
            'task_id' => 0,
            'task_name' => '聊天消息',
            'type' => 'chat',
            'display_title' => '',
            'display_description' => $content,
            'description' => $content,
            'resource' => $resource ? $this->buildResourceData($resource) : null,
        ]);

        $this->sendToWebSocket($pushData);
        $this->stats['chat']++;
        echo "[AutoPush] \033[36m聊天\033[0m | {$sender['nickname']}: " . mb_substr($content, 0, 30) . "\n";
    }

    /**
     * 发送强制消息（下载App或广告时长）
     */
    private function doPushForced()
    {
        // 随机选择下载App或广告时长
        $type = (mt_rand(1, 2) === 1) ? 'download' : 'adv';
        
        $sender = $this->getRandomSender();
        $resource = $this->getRandomResource($type);
        
        if ($type === 'download') {
            $this->doPushDownload($sender, $resource);
            $this->stats['download']++;
        } else {
            $this->doPushAdv($sender, $resource);
            $this->stats['adv']++;
        }
    }

    /**
     * 发送下载App消息
     */
    private function doPushDownload($sender, $resource)
    {
        $title = '';
        $description = '';
        $jumpUrl = '';

        if ($resource) {
            $title = $resource['name'] ?: '下载App领取奖励';
            $description = $resource['description'] ?: '下载注册即可领取金币奖励';
            $jumpUrl = $resource['url'] ?: ($resource['download_url'] ?: '');
        } else {
            $tpl = $this->downloadTemplates[array_rand($this->downloadTemplates)];
            $title = $tpl['name'];
            $description = $tpl['description'];
        }

        $pushData = $this->buildBasePushData($sender, 'download', [
            'task_id' => $resource ? intval($resource['id']) : 0,
            'task_name' => $title,
            'type' => 'download',
            'display_title' => $title,
            'display_description' => $description,
            'description' => $description,
            'jump_url' => $jumpUrl ? $this->fullUrl($jumpUrl) : '',
            'resource' => $resource ? $this->buildResourceData($resource, 'download') : null,
        ]);

        $this->sendToWebSocket($pushData);
        echo "[AutoPush] \033[33m下载\033[0m | {$sender['nickname']}: {$title}\n";
    }

    /**
     * 发送广告时长消息
     */
    private function doPushAdv($sender, $resource)
    {
        $title = '';
        $description = '';
        $advDuration = 30;

        if ($resource) {
            $title = $resource['name'] ?: '观看广告领金币';
            $description = $resource['description'] ?: '观看广告即可领取金币奖励';
            $advDuration = intval($resource['adv_duration'] ?? 0) ?: 30;
        } else {
            $tpl = $this->advTemplates[array_rand($this->advTemplates)];
            $title = $tpl['name'];
            $description = $tpl['description'];
            $advDuration = $tpl['adv_duration'];
        }

        $pushData = $this->buildBasePushData($sender, 'adv', [
            'task_id' => $resource ? intval($resource['id']) : 0,
            'task_name' => $title,
            'type' => 'adv',
            'display_title' => $title,
            'display_description' => $description,
            'description' => $description,
            'resource' => $resource ? $this->buildResourceData($resource, 'adv') : null,
            'adv_duration' => $advDuration,
        ]);

        $this->sendToWebSocket($pushData);
        echo "[AutoPush] \033[35m广告\033[0m | {$sender['nickname']}: {$title} ({$advDuration}s)\n";
    }

    // ==================== 红包任务推送 ====================

    /**
     * 推送红包消息（小程序游戏类型）
     * 需要记录在 red_packet_task_YYYYMM 分表中
     */
    private function pushRedpacketMessage()
    {
        $sender = $this->getRandomSender();
        $resource = $this->getRandomResource('miniapp');

        // ★ 创建红包任务记录到数据库
        $taskId = $this->createRedpacketTask($sender, $resource);
        if (!$taskId) {
            echo "\033[31m[AutoPush] 创建红包任务记录失败，跳过推送\033[0m\n";
            return;
        }

        $title = '恭喜发财，大吉大利！';
        $backgroundImage = '';

        if ($resource) {
            $title = $resource['name'] ?: '恭喜发财，大吉大利！';
            $backgroundImage = $resource['logo'] ? $this->fullUrl($resource['logo']) : '';
        }

        $pushData = $this->buildBasePushData($sender, 'miniapp', [
            'task_id' => $taskId,
            'task_name' => $title,
            'type' => 'miniapp',
            'display_title' => $title,
            'display_description' => '拆红包赢金币',
            'description' => '拆红包赢金币',
            'show_red_packet' => true,
            'background_image' => $backgroundImage,
            'jump_url' => $resource ? ($resource['url'] ? $this->fullUrl($resource['url']) : '') : '',
            'resource' => $resource ? $this->buildResourceData($resource, 'miniapp') : null,
        ]);

        $this->sendToWebSocket($pushData);
        $this->stats['redpacket']++;
        $this->stats['total']++;
        echo "[AutoPush] \033[31m红包\033[0m | {$sender['nickname']}: {$title} (taskId={$taskId})\n";
    }

    /**
     * 创建红包任务记录（写入月度分表）
     * @return int 任务ID，失败返回0
     */
    private function createRedpacketTask($sender, $resource)
    {
        try {
            $model = new RedPacketTaskSplit();
            $tableName = $model->getTableName(time());
            $model->ensureTableExists($tableName);

            $data = [
                'name'            => '恭喜发财',
                'display_title'   => $resource ? ($resource['name'] ?: '恭喜发财，大吉大利！') : '恭喜发财，大吉大利！',
                'display_description' => '拆红包赢金币',
                'description'     => '拆红包赢金币',
                'type'            => 'miniapp',
                'show_red_packet' => 1,
                'total_amount'    => 0,
                'total_count'     => 0,
                'reward'          => 0,
                'resource_id'     => $resource ? intval($resource['id']) : 0,
                'status'          => 'normal',
                'start_time'      => time(),
                'end_time'        => time() + 86400,
                'sender_id'       => intval($sender['id'] ?? 0),
                'sender_name'     => $sender['nickname'] ?: '系统',
                'sender_avatar'   => $sender['avatar'] ?: '',
                'push_status'     => 1,
                'push_time'       => time(),
                'createtime'      => time(),
                'updatetime'      => time(),
            ];

            // 使用 Db::name() 插入（已包含表前缀）
            $id = Db::name($tableName)->insertGetId($data);

            return intval($id);
        } catch (\Exception $e) {
            Log::error('[AutoPush] 创建红包任务失败: ' . $e->getMessage());
            echo "\033[31m[AutoPush] 创建红包任务失败: {$e->getMessage()}\033[0m\n";
            return 0;
        }
    }

    // ==================== 推送数据构建 ====================

    /**
     * 构建基础推送数据
     */
    private function buildBasePushData($sender, $type, array $overrides = [])
    {
        return array_merge([
            'task_id'             => 0,
            'task_name'           => '',
            'type'                => $type,
            'description'         => '',
            'display_title'       => $sender['nickname'] ?: '系统',
            'display_description' => '',
            'show_red_packet'     => ($type === 'miniapp'),
            'background_image'    => '',
            'jump_url'            => '',
            'status'              => 'normal',
            'sender_id'           => intval($sender['id'] ?? 0),
            'sender_name'         => $sender['nickname'] ?: '系统',
            'sender_avatar'       => $sender['avatar'] ? $this->fullUrl($sender['avatar']) : '',
            'resource'            => null,
            'reward'              => 0,
            'timestamp'           => time(),
        ], $overrides);
    }

    /**
     * 构建资源数据
     */
    private function buildResourceData($resource, $type = '')
    {
        if (empty($resource)) return null;

        $data = [
            'id'          => intval($resource['id'] ?? 0),
            'name'        => $resource['name'] ?? '',
            'description' => $resource['description'] ?? '',
            'logo'        => ($resource['logo'] ?? '') ? $this->fullUrl($resource['logo']) : '',
            'type'        => $type ?: ($resource['type'] ?? ''),
        ];

        // 根据资源类型补充字段
        switch ($type) {
            case 'chat':
                $data['chat_duration'] = intval($resource['chat_duration'] ?? 0) ?: 30;
                $data['chat_requirement'] = $resource['chat_requirement'] ?? '';
                break;
            case 'download':
                $data['download_url'] = ($resource['download_url'] ?? '') ? $this->fullUrl($resource['download_url']) : '';
                $data['download_type'] = $resource['download_type'] ?? '';
                $data['package_name'] = $resource['package_name'] ?? '';
                break;
            case 'miniapp':
                $data['miniapp_id'] = $resource['miniapp_id'] ?? '';
                $data['miniapp_path'] = $resource['miniapp_path'] ?? '';
                $data['miniapp_duration'] = intval($resource['miniapp_duration'] ?? 0) ?: 0;
                break;
            case 'adv':
                $data['adv_id'] = $resource['adv_id'] ?? '';
                $data['adv_platform'] = $resource['adv_platform'] ?? '';
                $data['adv_duration'] = intval($resource['adv_duration'] ?? 0) ?: 30;
                break;
            case 'video':
                $data['video_url'] = ($resource['video_url'] ?? '') ? $this->fullUrl($resource['video_url']) : '';
                $data['video_duration'] = intval($resource['video_duration'] ?? 0) ?: 0;
                break;
        }

        return $data;
    }

    // ==================== WebSocket 通信 ====================

    /**
     * 发送数据到 WebSocket 服务（通过 TCP API）
     */
    private function sendToWebSocket($data)
    {
        $apiKey = WebSocketService::API_KEY;
        $payload = json_encode([
            'action'  => 'push_task',
            'api_key' => $apiKey,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE) . "\n";

        $fp = @fsockopen(self::TCP_HOST, self::TCP_PORT, $errno, $errstr, 5);
        if (!$fp) {
            echo "\033[31m[AutoPush] 无法连接 WebSocket TCP ({self::TCP_HOST}:" . self::TCP_PORT . "): {$errstr}\033[0m\n";
            Log::error('[AutoPush] TCP连接失败: ' . $errstr);
            return false;
        }

        stream_set_timeout($fp, 5);
        fwrite($fp, $payload);
        
        // 读取响应
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 65536);
            if ($line === false) break;
            $response .= $line;
            if (strlen(trim($line)) > 0) break;
        }
        fclose($fp);

        $result = json_decode(trim($response), true);
        if (!$result || empty($result['success'])) {
            $errMsg = $result['message'] ?? ($result['error'] ?? '未知错误');
            Log::warning('[AutoPush] 推送响应异常: ' . $errMsg);
        }

        return true;
    }

    // ==================== 数据源 ====================

    /**
     * 加载CDN域名（CLI模式下request()->domain()为空）
     */
    private function loadCdnUrl()
    {
        $cdn = '';

        // 方式1: 从 ThinkPHP Config 读取（可能需要先加载缓存文件）
        try {
            // CLI 模式 ThinkPHP5 不会自动加载 config/extra/site.php
            $cacheFile = RUNTIME_PATH . 'cache' . DS . 'config' . DS . 'site.php';
            if (file_exists($cacheFile)) {
                $siteConfig = include $cacheFile;
                if (is_array($siteConfig) && !empty($siteConfig['upload']['cdnurl'])) {
                    $cdn = $siteConfig['upload']['cdnurl'];
                }
            }
        } catch (\Exception $e) {}

        // 方式2: 直接查数据库，宽松匹配
        if (empty($cdn) || strpos($cdn, 'http') !== 0) {
            try {
                // 先尝试 name=upload, group=basic
                $row = Db::name('config')
                    ->where('name', 'upload')
                    ->value('value');
                if ($row) {
                    $cfg = is_array($row) ? $row : json_decode($row, true);
                    if (!empty($cfg['cdnurl']) && strpos($cfg['cdnurl'], 'http') === 0) {
                        $cdn = $cfg['cdnurl'];
                    }
                }
            } catch (\Exception $e) {}
        }

        // 方式3: 查 site 配置获取网站域名
        if (empty($cdn) || strpos($cdn, 'http') !== 0) {
            try {
                $row = Db::name('config')
                    ->where('name', 'site')
                    ->value('value');
                if ($row) {
                    $cfg = is_array($row) ? $row : json_decode($row, true);
                    if (!empty($cfg['siteurl']) && strpos($cfg['siteurl'], 'http') === 0) {
                        $cdn = $cfg['siteurl'];
                    }
                }
            } catch (\Exception $e) {}
        }

        $this->cdnUrl = (!empty($cdn) && strpos($cdn, 'http') === 0) ? rtrim($cdn, '/') : '';

        echo "[AutoPush] CDN域名: " . ($this->cdnUrl ?: '(未配置，URL将使用相对路径)') . "\n";
    }

    /**
     * 构建完整URL（替代cdnurl，CLI模式下不会产生http:///错误）
     */
    private function fullUrl($path)
    {
        if (empty($path)) return '';
        // 已经是完整URL，直接返回
        if (preg_match('/^((?:[a-z]+:)?\/\/|data:image\/)/i', $path)) {
            return $path;
        }
        $path = '/' . ltrim(str_replace(['\\', '//'], '/', $path), '/');
        if (!empty($this->cdnUrl)) {
            return $this->cdnUrl . $path;
        }
        // cdnurl为空，返回相对路径（前端自动补全域名）
        return $path;
    }

    /**
     * 获取随机发送者
     */
    private function getRandomSender()
    {
        if (!empty($this->usersCache)) {
            $user = $this->usersCache[array_rand($this->usersCache)];
            return [
                'id'       => $user['id'],
                'nickname' => $user['nickname'] ?: ('用户' . $user['id']),
                'avatar'   => $user['avatar'] ?: '',
            ];
        }
        // 使用 mock 发送者
        $mock = $this->mockSenders[array_rand($this->mockSenders)];
        return [
            'id'       => 0,
            'nickname' => $mock['nickname'],
            'avatar'   => $mock['avatar'],
        ];
    }

    /**
     * 获取指定类型的随机资源
     */
    private function getRandomResource($type)
    {
        $resources = $this->resourcesCache[$type] ?? [];
        if (empty($resources)) return null;
        return $resources[array_rand($resources)];
    }

    /**
     * 加载系统用户（模拟发送者）
     */
    private function loadSystemUsers()
    {
        try {
            $users = Db::name('user')
                ->where('user_type', 1)
                ->where('status', 'normal')
                ->field('id, nickname, avatar')
                ->limit(50)
                ->select();
            
            if ($users && count($users) > 0) {
                $this->usersCache = is_array($users) ? $users : $users->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('[AutoPush] 加载系统用户失败: ' . $e->getMessage());
        }
    }

    /**
     * 加载所有类型的资源到缓存
     */
    private function loadAllResources()
    {
        $types = ['chat', 'download', 'adv', 'miniapp', 'video'];
        foreach ($types as $type) {
            try {
                $list = Db::name('red_packet_resource')
                    ->where('type', $type)
                    ->where('status', 'normal')
                    ->field('id, name, description, logo, url, type, miniapp_id, miniapp_path, miniapp_duration, download_url, download_type, package_name, video_url, video_duration, adv_id, adv_platform, adv_duration, chat_duration, chat_requirement')
                    ->limit(30)
                    ->select();
                
                $this->resourcesCache[$type] = ($list && count($list) > 0) 
                    ? (is_array($list) ? $list : $list->toArray()) 
                    : [];
            } catch (\Exception $e) {
                $this->resourcesCache[$type] = [];
            }
        }
    }

    // ==================== 配置管理 ====================

    /**
     * 检查红包自动推送是否启用
     */
    private function isRedpacketEnabled()
    {
        $val = $this->getConfig('auto_redpacket_enabled');
        return $val === '1' || $val === 1 || $val === true;
    }

    /**
     * 获取配置值
     */
    private function getConfig($name)
    {
        if (isset($this->configCache[$name])) {
            return $this->configCache[$name];
        }
        
        try {
            $row = Db::name('config')
                ->where('name', $name)
                ->field('value')
                ->find();
            
            $this->configCache[$name] = $row ? $row['value'] : '';
        } catch (\Exception $e) {
            $this->configCache[$name] = '';
        }
        
        return $this->configCache[$name];
    }

    /**
     * 刷新配置缓存
     */
    private function refreshConfig()
    {
        $this->configCache = [];
        $this->getConfig('auto_redpacket_enabled');
    }

    // ==================== 工具方法 ====================

    /**
     * 重置聊天计数器
     */
    private function resetChatCounter()
    {
        $this->chatCounter = 0;
        $this->chatTarget = mt_rand(2, 5);
    }

    /**
     * 生成随机秒数（浮点）
     */
    private function randomSeconds($min, $max)
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    /**
     * 打印统计信息
     */
    public function printStats()
    {
        $elapsed = time() - $this->stats['start_time'];
        $minutes = max(1, intval($elapsed / 60));
        
        echo "\n\033[33m========== AutoPush 统计 ==========\033[0m\n";
        echo "  运行时间: " . $this->formatDuration($elapsed) . "\n";
        echo "  总推送: {$this->stats['total']} 条\n";
        echo "  聊天消息: {$this->stats['chat']} 条\n";
        echo "  下载App: {$this->stats['download']} 条\n";
        echo "  广告时长: {$this->stats['adv']} 条\n";
        echo "  红包任务: {$this->stats['redpacket']} 条\n";
        echo "  平均速率: " . round($this->stats['total'] / $minutes, 1) . " 条/分钟\n";
        echo "  红包开关: " . ($this->isRedpacketEnabled() ? '已开启' : '已关闭') . "\n";
        echo "\033[33m====================================\033[0m\n\n";
    }

    /**
     * 格式化运行时间
     */
    private function formatDuration($seconds)
    {
        $h = intval($seconds / 3600);
        $m = intval(($seconds % 3600) / 60);
        $s = $seconds % 60;
        if ($h > 0) return "{$h}小时{$m}分{$s}秒";
        if ($m > 0) return "{$m}分{$s}秒";
        return "{$s}秒";
    }
}
