<?php

namespace app\common\library;

use think\Db;
use think\Log;

/**
 * Swoole WebSocket 服务类
 * 
 * 依赖：需要安装 Swoole 扩展 (>=4.5)
 * 安装：pecl install swoole
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
    
    // 连接用户映射 [fd => user_id]
    private static $connections = [];
    
    // 用户连接映射 [user_id => fd]
    private static $userConnections = [];
    
    // 在线用户数
    private static $onlineCount = 0;
    
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
        echo "\033[32m========================================\033[0m\n\n";
        echo "\033[33mWebSocket 端口:\033[0m \033[36m{$port}\033[0m\n";
        echo "\033[33mAPI 端口:\033[0m \033[36m{$apiPort}\033[0m\n";
        echo "\033[33m守护进程:\033[0m \033[36m" . ($daemon ? '是' : '否') . "\033[0m\n";
        echo "\033[33m启动时间:\033[0m \033[36m" . date('Y-m-d H:i:s') . "\033[0m\n\n";
        
        // 创建 WebSocket 服务器
        self::$server = new \Swoole\WebSocket\Server('0.0.0.0', $port);
        
        // 守护进程模式
        if ($daemon) {
            self::$server->set([
                'daemonize' => true,
                'pid_file' => RUNTIME_PATH . 'websocket.pid',
                'log_file' => RUNTIME_PATH . 'log' . DS . 'websocket.log',
            ]);
        } else {
            self::$server->set([
                'pid_file' => RUNTIME_PATH . 'websocket.pid',
            ]);
        }
        
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
        
        // TCP 内部推送事件（替代原来的 HTTP API）
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
            
            echo "[TCP] 收到指令: {$action}, fd={$fd}\n";
            
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
                    $result = ['success' => true, 'count' => self::$onlineCount];
                    break;
                case 'connections':
                    $result = ['success' => true, 'count' => self::$onlineCount, 'users' => array_keys(self::$userConnections)];
                    break;
            }
            
            $server->send($fd, json_encode($result, JSON_UNESCAPED_UNICODE) . "\n");
            $server->close($fd);
        });
        
        // WebSocket 连接事件
        self::$server->on('open', function ($server, $request) {
            echo "新连接: fd={$request->fd}\n";
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
            if (isset(self::$connections[$fd])) {
                $userId = self::$connections[$fd];
                unset(self::$connections[$fd]);
                unset(self::$userConnections[$userId]);
                self::$onlineCount--;
                
                echo "用户 {$userId} 断开连接，当前在线: " . self::$onlineCount . "\n";
                
                // 广播在线人数更新
                self::broadcastOnlineCount();
            }
        });
        
        // HTTP API 已移除，全部通过 TCP 推送端口（3003）通信
        
        echo "\033[32mWebSocket 服务启动成功!\033[0m\n";
        
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
            echo "在线用户数: " . self::$onlineCount . "\n";
        } else {
            echo "\033[33m服务已停止 (PID 文件存在但进程不存在)\033[0m\n";
            unlink($pidFile);
        }
    }
    
    /**
     * 处理 WebSocket 消息
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
                    if (isset(self::$userConnections[$userId])) {
                        $oldFd = self::$userConnections[$userId];
                        unset(self::$connections[$oldFd]);
                    }
                    
                    self::$connections[$fd] = $userId;
                    self::$userConnections[$userId] = $fd;
                    self::$onlineCount++;
                    
                    self::send($fd, [
                        'type' => 'connected',
                        'userId' => $userId,
                        'onlineCount' => self::$onlineCount,
                    ]);
                    
                    echo "用户 {$userId} 认证成功，当前在线: " . self::$onlineCount . "\n";
                    
                    // 广播在线人数更新
                    self::broadcastOnlineCount();
                } else {
                    self::send($fd, ['type' => 'auth_failed', 'msg' => '认证失败']);
                }
                break;
                
            case 'ping':
                // 心跳
                self::send($fd, ['type' => 'pong']);
                break;
                
            case 'get_online_count':
                self::send($fd, ['type' => 'online_count', 'count' => self::$onlineCount]);
                break;
                
            default:
                self::send($fd, ['type' => 'error', 'msg' => '未知的消息类型']);
        }
    }
    

    /**
     * 推送红包任务（供 API 调用）
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

        // 如果有聊天相关字段，保留
        if (!empty($data['chat_content'])) {
            $message['chat_content'] = $data['chat_content'];
        }
        if (!empty($data['chat_duration'])) {
            $message['chat_duration'] = $data['chat_duration'];
        }

        self::broadcast($message);

        return ['success' => true, 'message' => '推送成功', 'online_count' => self::$onlineCount];
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
     * 广播消息给所有连接
     */
    public static function broadcast($message)
    {
        if (!self::$server) {
            echo "[广播] 失败: 服务未启动\n";
            return;
        }
        
        $connCount = count(self::$connections);
        echo "[广播] 开始广播，在线连接数: {$connCount}\n";
        
        $jsonMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
        
        $sentCount = 0;
        foreach (self::$connections as $fd => $userId) {
            if (self::$server->isEstablished($fd)) {
                $result = self::$server->push($fd, $jsonMessage);
                if ($result) {
                    $sentCount++;
                } else {
                    echo "[广播] fd={$fd} userId={$userId} 推送失败\n";
                }
            } else {
                echo "[广播] fd={$fd} userId={$userId} 连接未建立，跳过\n";
            }
        }
        
        echo "[广播] 完成，成功发送: {$sentCount}/{$connCount}\n";
    }
    
    /**
     * 发送消息给指定用户
     */
    private static function sendToUser($userId, $message)
    {
        if (!isset(self::$userConnections[$userId])) {
            return false;
        }
        
        $fd = self::$userConnections[$userId];
        
        if (self::$server && self::$server->isEstablished($fd)) {
            self::$server->push($fd, json_encode($message, JSON_UNESCAPED_UNICODE));
            return true;
        }
        
        return false;
    }
    
    /**
     * 广播在线人数
     */
    private static function broadcastOnlineCount()
    {
        self::broadcast(['type' => 'online_count', 'count' => self::$onlineCount]);
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
            // 获取 token 配置
            $tokenConfig = \think\Config::get('token');
            $encryptedToken = hash_hmac($tokenConfig['hashalgo'], $token, $tokenConfig['key']);
            
            // 从数据库验证加密后的 token
            $userToken = Db::name('user_token')
                ->where('user_id', $userId)
                ->where('token', $encryptedToken)  // 使用加密后的 token 查询
                ->where('expiretime', '>', time())
                ->find();
            
            return !empty($userToken);
        } catch (\Exception $e) {
            // 如果出错，记录日志
            \think\Log::error('WebSocket Token验证失败: ' . $e->getMessage());
            return false;
        }
    }
}
