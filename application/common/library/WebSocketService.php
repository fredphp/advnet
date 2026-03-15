<?php

namespace app\common\library;

use think\Db;
use think\Log;

/**
 * WebSocket 服务类
 * 基于 Workerman 实现
 */
class WebSocketService
{
    // WebSocket 服务端口
    const WS_PORT = 3002;
    
    // 内部 API 端口
    const API_PORT = 3003;
    
    // API 密钥
    const API_KEY = 'your-secret-api-key';
    
    // 连接用户映射 [connection_id => user_id]
    private static $connections = [];
    
    // 用户连接映射 [user_id => connection_id]
    private static $userConnections = [];
    
    // 在线用户数
    private static $onlineCount = 0;
    
    /**
     * 启动 WebSocket 服务
     */
    public static function start($port = null, $apiPort = null)
    {
        $port = $port ?: self::WS_PORT;
        $apiPort = $apiPort ?: self::API_PORT;
        
        // 检查是否安装 Workerman
        if (!class_exists('Workerman\Worker')) {
            echo "请先安装 Workerman: composer require workerman/workerman\n";
            return;
        }
        
        // 创建 WebSocket 服务
        $wsWorker = new \Workerman\Worker("websocket://0.0.0.0:{$port}");
        $wsWorker->count = 1;
        $wsWorker->name = 'AdNetwork-WebSocket';
        
        // 创建内部 API 服务（用于后端推送消息）
        $apiWorker = new \Workerman\Worker("http://0.0.0.0:{$apiPort}");
        $apiWorker->count = 1;
        $apiWorker->name = 'AdNetwork-PushAPI';
        
        // WebSocket 连接事件
        $wsWorker->onConnect = function ($connection) {
            echo "新连接: {$connection->id}\n";
        };
        
        // WebSocket 消息事件
        $wsWorker->onMessage = function ($connection, $data) {
            $message = json_decode($data, true);
            
            if (!$message) {
                $connection->send(json_encode(['type' => 'error', 'msg' => '无效的消息格式']));
                return;
            }
            
            self::handleMessage($connection, $message);
        };
        
        // WebSocket 关闭事件
        $wsWorker->onClose = function ($connection) {
            if (isset(self::$connections[$connection->id])) {
                $userId = self::$connections[$connection->id];
                unset(self::$connections[$connection->id]);
                unset(self::$userConnections[$userId]);
                self::$onlineCount--;
                
                echo "用户 {$userId} 断开连接，当前在线: " . self::$onlineCount . "\n";
                
                // 广播在线人数更新
                self::broadcastOnlineCount();
            }
        };
        
        // API 消息事件
        $apiWorker->onMessage = function ($connection, $data) {
            // 验证 API Key
            $headers = $data->header;
            $apiKey = $headers['x-api-key'] ?? '';
            
            if ($apiKey !== self::API_KEY) {
                $connection->send(json_encode(['success' => false, 'error' => 'Unauthorized']));
                $connection->close();
                return;
            }
            
            $path = $data->path;
            $method = $data->method;
            $body = json_decode($data->rawBody(), true) ?: [];
            
            $result = self::handleApiRequest($path, $method, $body);
            
            $connection->send(json_encode($result, JSON_UNESCAPED_UNICODE));
            $connection->close();
        };
        
        echo "WebSocket 服务启动成功!\n";
        echo "WebSocket 端口: {$port}\n";
        echo "API 端口: {$apiPort}\n";
        echo "启动时间: " . date('Y-m-d H:i:s') . "\n";
        
        // 运行所有 Worker
        \Workerman\Worker::runAll();
    }
    
    /**
     * 处理 WebSocket 消息
     */
    private static function handleMessage($connection, $message)
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
                        $oldConnId = self::$userConnections[$userId];
                        unset(self::$connections[$oldConnId]);
                    }
                    
                    self::$connections[$connection->id] = $userId;
                    self::$userConnections[$userId] = $connection->id;
                    self::$onlineCount++;
                    
                    $connection->send(json_encode([
                        'type' => 'connected',
                        'userId' => $userId,
                        'onlineCount' => self::$onlineCount,
                    ]));
                    
                    echo "用户 {$userId} 认证成功，当前在线: " . self::$onlineCount . "\n";
                    
                    // 广播在线人数更新
                    self::broadcastOnlineCount();
                } else {
                    $connection->send(json_encode(['type' => 'auth_failed', 'msg' => '认证失败']));
                }
                break;
                
            case 'ping':
                // 心跳
                $connection->send(json_encode(['type' => 'pong']));
                break;
                
            case 'get_online_count':
                $connection->send(json_encode(['type' => 'online_count', 'count' => self::$onlineCount]));
                break;
                
            default:
                $connection->send(json_encode(['type' => 'error', 'msg' => '未知的消息类型']));
        }
    }
    
    /**
     * 处理 API 请求
     */
    private static function handleApiRequest($path, $method, $body)
    {
        switch ($path) {
            case '/api/push-task':
                return self::apiPushTask($body);
                
            case '/api/system-message':
                return self::apiSystemMessage($body);
                
            case '/api/broadcast':
                return self::apiBroadcast($body);
                
            case '/api/online-count':
                return ['success' => true, 'count' => self::$onlineCount];
                
            case '/api/connections':
                return ['success' => true, 'count' => self::$onlineCount, 'users' => array_keys(self::$userConnections)];
                
            default:
                return ['success' => false, 'error' => '未知的接口'];
        }
    }
    
    /**
     * 推送红包任务
     */
    private static function apiPushTask($data)
    {
        $taskId = $data['taskId'] ?? 0;
        $taskName = $data['taskName'] ?? '';
        $taskType = $data['taskType'] ?? '';
        $reward = $data['reward'] ?? 0;
        $content = $data['content'] ?? '';
        
        $message = [
            'type' => 'task_notification',
            'taskId' => $taskId,
            'taskName' => $taskName,
            'taskType' => $taskType,
            'reward' => $reward,
            'content' => $content,
            'time' => time(),
        ];
        
        self::broadcast($message);
        
        return ['success' => true, 'message' => '推送成功'];
    }
    
    /**
     * 发送系统消息
     */
    private static function apiSystemMessage($data)
    {
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $level = $data['level'] ?? 'info';
        $targetUsers = $data['targetUsers'] ?? null;
        
        $message = [
            'type' => 'system_message',
            'title' => $title,
            'content' => $content,
            'level' => $level,
            'time' => time(),
        ];
        
        if ($targetUsers && is_array($targetUsers)) {
            // 发送给指定用户
            foreach ($targetUsers as $userId) {
                self::sendToUser($userId, $message);
            }
        } else {
            // 广播给所有用户
            self::broadcast($message);
        }
        
        return ['success' => true, 'message' => '发送成功'];
    }
    
    /**
     * 广播消息
     */
    private static function apiBroadcast($data)
    {
        $event = $data['event'] ?? '';
        $messageData = $data['data'] ?? [];
        
        $message = [
            'type' => $event,
            'data' => $messageData,
            'time' => time(),
        ];
        
        self::broadcast($message);
        
        return ['success' => true, 'message' => '广播成功'];
    }
    
    /**
     * 广播消息给所有连接
     */
    private static function broadcast($message)
    {
        global $wsWorker;
        
        if (!isset($wsWorker)) {
            return;
        }
        
        $jsonMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
        
        foreach ($wsWorker->connections as $connection) {
            $connection->send($jsonMessage);
        }
    }
    
    /**
     * 发送消息给指定用户
     */
    private static function sendToUser($userId, $message)
    {
        if (!isset(self::$userConnections[$userId])) {
            return false;
        }
        
        global $wsWorker;
        
        if (!isset($wsWorker)) {
            return false;
        }
        
        $connectionId = self::$userConnections[$userId];
        
        if (isset($wsWorker->connections[$connectionId])) {
            $wsWorker->connections[$connectionId]->send(json_encode($message, JSON_UNESCAPED_UNICODE));
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
            // 从数据库验证 token
            $userToken = Db::name('user_token')
                ->where('user_id', $userId)
                ->where('token', $token)
                ->where('expiretime', '>', time())
                ->find();
            
            return !empty($userToken);
        } catch (\Exception $e) {
            // 如果表不存在，简单验证
            return strlen($token) > 10;
        }
    }
    
    /**
     * 获取在线用户数
     */
    public static function getOnlineCount()
    {
        return self::$onlineCount;
    }
    
    /**
     * 获取所有在线用户
     */
    public static function getOnlineUsers()
    {
        return array_keys(self::$userConnections);
    }
}
