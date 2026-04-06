<?php

namespace app\common\library;

use think\Db;
use think\Log;

/**
 * Swoole WebSocket 服务类
 * 
 * 依赖：需要安装 Swoole 扩展 (>=4.5)
 * 安装：pecl install swoole
 * 
 * ★★★ 重要修复 ★★★
 * 使用 Swoole\Table 共享内存 + Swoole\Atomic 原子计数器
 * 解决多 Worker 进程之间连接状态不共享的核心问题
 * 
 * 之前的问题：Swoole 默认启动多个 Worker 进程，
 * PHP static 变量在进程间不共享，导致 Worker A 接收的前端连接
 * 在 Worker B 处理 TCP 推送时看不到，广播 0 条消息。
 * 
 * 修复后：所有连接信息存储在 Swoole\Table（共享内存）中，
 * 任何 Worker 都能读取到完整的连接列表。
 */
class WebSocketService
{
    // WebSocket 服务端口
    const WS_PORT = 3002;
    
    // 内部 API 端口
    const API_PORT = 3003;
    
    // API 密钥
    const API_KEY = 'tswbkpym4e0yW2YA6w85Zj2Eb9KjH3J9TyKiX4tnGymcFZ7pm0Ynnsz29mxW6hQWyKexTBScMnKhphYpXXjAiA9p9RXaPnMQb2yzYTtXmWBaAyWfXAayrNw00hPtyrKfH6C6MabNzFZ4AEH2YKw04H2Gt63yxee65eT5bDxeBzS02DeYKCrZcinhtSk9y2JkbQCemQ8B7ARQaeXFEre6M6axMN39ws2ZBAhkwrpkWMWpYa4c2JJsyw6k91bBBW0i';
    
    // Swoole Server 实例
    private static $server = null;
    
    /**
     * ★ 共享内存表：fd => userId 映射（所有 Worker 进程共享）
     */
    private static $connectionTable = null;
    
    /**
     * ★ 共享内存表：userId => fd 映射（所有 Worker 进程共享）
     */
    private static $userConnectionTable = null;
    
    /**
     * ★ 原子计数器：在线用户数（所有 Worker 进程共享，线程安全）
     */
    private static $onlineCountAtomic = null;
    
    /**
     * 初始化共享内存（必须在 server->start() 之前调用）
     */
    private static function initSharedMemory($maxConnections = 1024)
    {
        // fd => userId 表 (key=fd, value=userId, connect_time)
        self::$connectionTable = new \Swoole\Table($maxConnections);
        self::$connectionTable->column('user_id', \Swoole\Table::TYPE_STRING, 64);
        self::$connectionTable->column('connect_time', \Swoole\Table::TYPE_INT);
        self::$connectionTable->create();
        
        // userId => fd 表 (key=userId, value=fd, connect_time)
        self::$userConnectionTable = new \Swoole\Table($maxConnections);
        self::$userConnectionTable->column('fd', \Swoole\Table::TYPE_INT);
        self::$userConnectionTable->column('connect_time', \Swoole\Table::TYPE_INT);
        self::$userConnectionTable->create();
        
        // 原子计数器（替代 PHP static 变量）
        self::$onlineCountAtomic = new \Swoole\Atomic(0);
        
        echo "\033[36m[共享内存] 初始化完成\033[0m\n";
        echo "  - connectionTable: fd→userId (容量: {$maxConnections})\n";
        echo "  - userConnectionTable: userId→fd (容量: {$maxConnections})\n";
        echo "  - onlineCount: Atomic 原子计数器\n\n";
    }
    
    /**
     * 获取在线用户数（跨 Worker 安全）
     */
    private static function getOnlineCount()
    {
        return self::$onlineCountAtomic ? self::$onlineCountAtomic->get() : 0;
    }
    
    /**
     * 增加在线用户数
     */
    private static function incOnlineCount()
    {
        if (self::$onlineCountAtomic) {
            self::$onlineCountAtomic->add(1);
        }
    }
    
    /**
     * 减少在线用户数
     */
    private static function decOnlineCount()
    {
        if (self::$onlineCountAtomic) {
            $val = self::$onlineCountAtomic->get();
            if ($val > 0) {
                self::$onlineCountAtomic->sub(1);
            }
        }
    }
    
    /**
     * 获取所有已认证的连接（从共享内存表读取，跨 Worker 可见）
     * @return array [fd => userId]
     */
    private static function getAllConnections()
    {
        $connections = [];
        if (self::$connectionTable) {
            foreach (self::$connectionTable as $fd => $row) {
                $connections[$fd] = $row['user_id'];
            }
        }
        return $connections;
    }
    
    /**
     * 启动 WebSocket 服务
     */
    public static function start($port = null, $apiPort = null, $daemon = false)
    {
        $port = $port ?: self::WS_PORT;
        $apiPort = $apiPort ?: self::API_PORT;
        
        // 检查 Swoole 扩展
        if (!extension_loaded('swoole')) {
            echo "\033[31m错误: 未安装 Swoole 扩展\033[0m\n";
            echo "安装方法:\n";
            echo "  pecl install swoole\n";
            echo "  或查看文档: https://wiki.swoole.com/#/environment\n";
            return;
        }
        
        echo "\033[32m========================================\033[0m\n";
        echo "\033[32m   广告网络管理系统 - WebSocket 服务\033[0m\n";
        echo "\033[32m   (Swoole\Table 共享内存版)\033[0m\n";
        echo "\033[32m========================================\033[0m\n\n";
        echo "\033[33mWebSocket 端口:\033[0m \033[36m{$port}\033[0m\n";
        echo "\033[33mAPI 端口 (TCP):\033[0m \033[36m{$apiPort}\033[0m\n";
        echo "\033[33m守护进程:\033[0m \033[36m" . ($daemon ? '是' : '否') . "\033[0m\n";
        echo "\033[33m启动时间:\033[0m \033[36m" . date('Y-m-d H:i:s') . "\033[0m\n\n";
        
        // ★★★ 第一步：初始化共享内存（必须在 start() 之前） ★★★
        self::initSharedMemory(1024);
        
        // 创建 WebSocket 服务器
        self::$server = new \Swoole\WebSocket\Server('0.0.0.0', $port);
        
        // 服务器配置
        $serverConfig = [
            'pid_file' => RUNTIME_PATH . 'websocket.pid',
            'worker_num' => 2,  // ★ 多 Worker 进程，通过共享内存表通信
        ];
        
        if ($daemon) {
            $serverConfig['daemonize'] = true;
            $serverConfig['log_file'] = RUNTIME_PATH . 'log' . DS . 'websocket.log';
        }
        
        self::$server->set($serverConfig);
        
        // 添加内部 TCP 推送端口（纯 TCP，不走 HTTP）
        // PHP-FPM 通过 fsockopen 直连此端口发送推送指令
        $apiServer = self::$server->addListener('0.0.0.0', $apiPort, SWOOLE_SOCK_TCP);
        $apiServer->set([
            'open_http_protocol' => false,
        ]);
        
        // TCP 内部连接事件
        $apiServer->on('connect', function ($server, $fd) {
            echo "[TCP] 内部连接建立: fd={$fd}\n";
        });
        
        $apiServer->on('close', function ($server, $fd) {
            echo "[TCP] 内部连接断开: fd={$fd}\n";
        });
        
        // TCP 内部推送事件
        $apiServer->on('receive', function ($server, $fd, $reactor_id, $data) {
            $body = trim($data);
            if (empty($body)) {
                $server->send($fd, json_encode(['success' => false, 'error' => '空数据'], JSON_UNESCAPED_UNICODE) . "\n");
                $server->close($fd);
                return;
            }
            
            $request = json_decode($body, true);
            if (!$request) {
                $server->send($fd, json_encode(['success' => false, 'error' => '无效的JSON'], JSON_UNESCAPED_UNICODE) . "\n");
                $server->close($fd);
                return;
            }
            
            // 验证密钥
            $apiKey = $request['api_key'] ?? '';
            if ($apiKey !== self::API_KEY) {
                echo "[TCP] 认证失败: fd={$fd}\n";
                $server->send($fd, json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE) . "\n");
                $server->close($fd);
                return;
            }
            
            $action = $request['action'] ?? '';
            $payload = $request['data'] ?? [];
            
            // ★ 打印当前 Worker ID 和在线连接数，方便调试
            $workerId = $server->worker_id;
            $onlineCount = self::getOnlineCount();
            $tableCount = count(self::getAllConnections());
            echo "[TCP] 收到指令: {$action}, worker_id={$workerId}, 在线人数={$onlineCount}, 共享表连接数={$tableCount}\n";
            
            $result = ['success' => false, 'error' => '未知操作: ' . $action];
            
            switch ($action) {
                case 'push_task':
                    $result = self::apiPushTask($payload);
                    break;
                case 'system_message':
                    $result = self::apiSystemMessage($payload);
                    break;
                case 'broadcast':
                    $result = self::apiBroadcast($payload);
                    break;
                case 'online_count':
                    $result = ['success' => true, 'count' => self::getOnlineCount()];
                    break;
                case 'connections':
                    $connections = self::getAllConnections();
                    $result = [
                        'success' => true,
                        'count' => self::getOnlineCount(),
                        'users' => array_values($connections),
                        'worker_id' => $workerId,
                        'table_connections' => $tableCount,
                    ];
                    break;
            }
            
            $server->send($fd, json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
            $server->close($fd);
        });
        
        // WebSocket 连接事件
        self::$server->on('open', function ($server, $request) {
            echo "[WS] 新连接: fd={$request->fd}, worker_id=" . $server->worker_id . "\n";
        });
        
        // WebSocket 消息事件
        self::$server->on('message', function ($server, $frame) {
            $message = json_decode($frame->data, true);
            
            if (!$message) {
                $server->push($frame->fd, json_encode(['type' => 'error', 'msg' => '无效的消息格式']));
                return;
            }
            
            self::handleMessage($frame->fd, $message);
        });
        
        // WebSocket 关闭事件
        self::$server->on('close', function ($server, $fd) {
            echo "[WS] 连接关闭: fd={$fd}, worker_id=" . $server->worker_id . "\n";
            
            // ★ 从共享内存表中查找并清理
            if (self::$connectionTable && self::$connectionTable->exists($fd)) {
                $row = self::$connectionTable->get($fd);
                $userId = $row ? $row['user_id'] : null;
                
                if ($userId !== null) {
                    self::$connectionTable->del($fd);
                    self::$userConnectionTable->del($userId);
                    self::decOnlineCount();
                    
                    echo "[WS] 用户 {$userId} 断开连接，当前在线: " . self::getOnlineCount() . "\n";
                    
                    // 广播在线人数更新
                    self::broadcastOnlineCount();
                }
            }
        });
        
        echo "\033[32mWebSocket 服务启动成功! (worker_num=" . $serverConfig['worker_num'] . ")\033[0m\n\n";
        
        // 启动服务器
        self::$server->start();
    }
    
    /**
     * 停止服务
     */
    public static function stop()
    {
        $pidFile = RUNTIME_PATH . 'websocket.pid';
        
        if (!file_exists($pidFile)) {
            echo "\033[31m服务未运行或 PID 文件不存在\033[0m\n";
            return false;
        }
        
        $pid = intval(file_get_contents($pidFile));
        
        if ($pid > 0) {
            \Swoole\Process::kill($pid, SIGTERM);
            unlink($pidFile);
            echo "\033[32m服务已停止\033[0m\n";
            return true;
        }
        
        return false;
    }
    
    /**
     * 重启服务
     */
    public static function restart($port = null, $apiPort = null)
    {
        self::stop();
        sleep(1);
        self::start($port, $apiPort, true);
    }
    
    /**
     * 查看状态
     */
    public static function status()
    {
        $pidFile = RUNTIME_PATH . 'websocket.pid';
        
        if (!file_exists($pidFile)) {
            echo "\033[33m服务未运行\033[0m\n";
            return;
        }
        
        $pid = intval(file_get_contents($pidFile));
        
        if ($pid > 0 && \Swoole\Process::kill($pid, 0)) {
            echo "\033[32m服务运行中\033[0m (PID: {$pid})\n";
            echo "在线用户数: " . self::getOnlineCount() . "\n";
        } else {
            echo "\033[33m服务已停止 (PID 文件存在但进程不存在)\033[0m\n";
            unlink($pidFile);
        }
    }
    
    /**
     * 处理 WebSocket 消息
     * ★ 使用共享内存表存储连接信息（跨 Worker 可见）
     */
    private static function handleMessage($fd, $message)
    {
        $type = $message['type'] ?? '';
        
        switch ($type) {
            case 'auth':
                // 用户认证
                $userId = $message['userId'] ?? '';
                $token = $message['token'] ?? '';
                
                // 验证 token
                $isValid = self::verifyToken($userId, $token);
                
                if ($isValid) {
                    // 如果用户已有连接，先断开旧连接
                    if (self::$userConnectionTable->exists((string)$userId)) {
                        $oldRow = self::$userConnectionTable->get((string)$userId);
                        if ($oldRow) {
                            $oldFd = $oldRow['fd'];
                            if (self::$server->isEstablished($oldFd)) {
                                self::$server->close($oldFd);
                            }
                            self::$connectionTable->del($oldFd);
                            self::$userConnectionTable->del((string)$userId);
                            self::decOnlineCount();
                            echo "[WS] 用户 {$userId} 旧连接 fd={$oldFd} 已断开\n";
                        }
                    }
                    
                    // ★ 写入共享内存表（所有 Worker 进程都能读到） ★
                    self::$connectionTable->set((string)$fd, [
                        'user_id' => (string)$userId,
                        'connect_time' => time(),
                    ]);
                    self::$userConnectionTable->set((string)$userId, [
                        'fd' => (int)$fd,
                        'connect_time' => time(),
                    ]);
                    self::incOnlineCount();
                    
                    self::send($fd, [
                        'type' => 'connected',
                        'userId' => $userId,
                        'onlineCount' => self::getOnlineCount(),
                    ]);
                    
                    echo "[WS] 用户 {$userId} 认证成功, fd={$fd}, 在线: " . self::getOnlineCount() . "\n";
                    
                    // 广播在线人数更新
                    self::broadcastOnlineCount();
                } else {
                    self::send($fd, ['type' => 'auth_failed', 'msg' => '认证失败']);
                }
                break;
                
            case 'ping':
                self::send($fd, ['type' => 'pong']);
                break;
                
            case 'get_online_count':
                self::send($fd, ['type' => 'online_count', 'count' => self::getOnlineCount()]);
                break;
                
            default:
                self::send($fd, ['type' => 'error', 'msg' => '未知的消息类型']);
        }
    }
    

    /**
     * 推送红包任务（供 TCP API 调用）
     * ★ 关键：通过 Swoole\Table 读取所有连接，确保跨 Worker 广播
     */
    public static function apiPushTask($data)
    {
        // 兼容 snake_case (buildPushData) 和 camelCase 两种格式
        $message = [
            'type' => 'task_notification',
            'taskId'              => $data['taskId'] ?? $data['task_id'] ?? 0,
            'taskName'            => $data['taskName'] ?? $data['task_name'] ?? '',
            'taskType'            => $data['taskType'] ?? $data['type'] ?? '',
            'description'         => $data['description'] ?? '',
            'display_title'       => $data['display_title'] ?? ($data['taskName'] ?? $data['task_name'] ?? ''),
            'display_description' => $data['display_description'] ?? ($data['description'] ?? ''),
            'show_red_packet'     => $data['show_red_packet'] ?? false,
            'background_image'    => $data['background_image'] ?? '',
            'jump_url'            => $data['jump_url'] ?? '',
            'status'              => $data['status'] ?? '',
            'sender_name'         => $data['sender_name'] ?? '',
            'sender_avatar'       => $data['sender_avatar'] ?? '',
            'resource'            => $data['resource'] ?? null,
            'reward'              => $data['reward'] ?? 0,
            'content'             => $data['content'] ?? ($data['description'] ?? ''),
            'time'                => $data['timestamp'] ?? time(),
        ];

        if (!empty($data['chat_content'])) {
            $message['chat_content'] = $data['chat_content'];
        }
        if (!empty($data['chat_duration'])) {
            $message['chat_duration'] = $data['chat_duration'];
        }

        self::broadcast($message);

        return ['success' => true, 'message' => '推送成功', 'online_count' => self::getOnlineCount()];
    }
    
    /**
     * 发送系统消息
     */
    private static function apiSystemMessage($data)
    {
        $message = [
            'type' => 'system_message',
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'level' => $data['level'] ?? 'info',
            'time' => time(),
        ];
        
        $targetUsers = $data['targetUsers'] ?? null;
        
        if ($targetUsers && is_array($targetUsers)) {
            foreach ($targetUsers as $userId) {
                self::sendToUser($userId, $message);
            }
        } else {
            self::broadcast($message);
        }
        
        return ['success' => true, 'message' => '发送成功'];
    }
    
    /**
     * 广播消息
     */
    private static function apiBroadcast($data)
    {
        $message = [
            'type' => $data['event'] ?? '',
            'data' => $data['data'] ?? [],
            'time' => time(),
        ];
        
        self::broadcast($message);
        
        return ['success' => true, 'message' => '广播成功'];
    }
    
    /**
     * 发送消息给指定连接
     */
    private static function send($fd, $message)
    {
        if (self::$server && self::$server->isEstablished($fd)) {
            self::$server->push($fd, json_encode($message, JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * ★★★ 核心方法：广播消息给所有已连接用户 ★★★
     * 
     * 使用 Swoole\Table 共享内存读取所有连接（跨 Worker 进程可见），
     * 然后 Swoole 引擎自动将 push 路由到拥有该 fd 的 Worker 进程。
     */
    public static function broadcast($message)
    {
        if (!self::$server) {
            echo "[广播] 失败: 服务未启动\n";
            return;
        }
        
        // ★ 从共享内存表获取所有连接（跨 Worker 可见） ★
        $allConnections = self::getAllConnections();
        $connCount = count($allConnections);
        $onlineCount = self::getOnlineCount();
        
        echo "\033[33m[广播] 开始\033[0m | 消息类型: " . ($message['type'] ?? 'unknown') 
             . " | 共享表连接数: {$connCount} | 在线人数: {$onlineCount}\n";
        
        if ($connCount === 0) {
            echo "\033[31m[广播] ⚠️ 没有已连接的用户，跳过广播！\033[0m\n";
            echo "\033[31m[广播] 请检查前端是否已连接并认证成功\033[0m\n\n";
            return;
        }
        
        $jsonMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
        
        $sentCount = 0;
        foreach ($allConnections as $fd => $userId) {
            if (self::$server->isEstablished($fd)) {
                $result = self::$server->push($fd, $jsonMessage);
                if ($result) {
                    $sentCount++;
                    echo "[广播] ✅ fd={$fd} userId={$userId}\n";
                } else {
                    echo "[广播] ❌ fd={$fd} userId={$userId} push失败\n";
                }
            } else {
                echo "[广播] ⚠️ fd={$fd} 连接无效，清理\n";
                self::$connectionTable->del((string)$fd);
                if ($userId) {
                    self::$userConnectionTable->del((string)$userId);
                }
            }
        }
        
        echo "\033[32m[广播] 完成: {$sentCount}/{$connCount} 条消息已发送\033[0m\n\n";
    }
    
    /**
     * 发送消息给指定用户（从共享内存表查找 fd）
     */
    private static function sendToUser($userId, $message)
    {
        if (!self::$userConnectionTable->exists((string)$userId)) {
            return false;
        }
        
        $row = self::$userConnectionTable->get((string)$userId);
        $fd = $row ? $row['fd'] : 0;
        
        if (self::$server && $fd > 0 && self::$server->isEstablished($fd)) {
            self::$server->push($fd, json_encode($message, JSON_UNESCAPED_UNICODE));
            return true;
        }
        
        // 清理无效连接
        if ($fd > 0) {
            self::$connectionTable->del((string)$fd);
        }
        self::$userConnectionTable->del((string)$userId);
        self::decOnlineCount();
        
        return false;
    }
    
    /**
     * 广播在线人数
     */
    private static function broadcastOnlineCount()
    {
        self::broadcast(['type' => 'online_count', 'count' => self::getOnlineCount()]);
    }
    
    /**
     * 验证用户 Token
     */
    private static function verifyToken($userId, $token)
    {
        if (empty($userId) || empty($token)) {
            return false;
        }
        
        try {
            $tokenConfig = \think\Config::get('token');
            $encryptedToken = hash_hmac($tokenConfig['hashalgo'], $token, $tokenConfig['key']);
            
            $userToken = Db::name('user_token')
                ->where('user_id', $userId)
                ->where('token', $encryptedToken)
                ->where('expiretime', '>', time())
                ->find();
            
            return !empty($userToken);
        } catch (\Exception $e) {
            \think\Log::error('WebSocket Token验证失败: ' . $e->getMessage());
            return false;
        }
    }
}
