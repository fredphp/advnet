<?php

namespace app\common\service;

/**
 * WebSocket 推送服务
 * 调用 mini-service/push-service 进行实时推送
 */
class PushService
{
    // 推送服务 API 地址
    const PUSH_API_URL = 'http://localhost:3003';
    
    // API 密钥
    const API_KEY = 'your-secret-api-key';
    
    /**
     * 发送 HTTP 请求
     */
    private static function request($method, $endpoint, $data = [])
    {
        $url = self::PUSH_API_URL . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . self::API_KEY,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        return json_decode($response, true) ?: ['success' => false, 'error' => 'Invalid response'];
    }
    
    /**
     * 推送任务通知到所有用户
     */
    public static function pushTask($taskData)
    {
        $data = [
            'taskId' => $taskData['id'] ?? 0,
            'taskName' => $taskData['name'] ?? '',
            'taskType' => $taskData['type'] ?? '',
            'reward' => $taskData['single_amount'] ?? 0,
            'content' => sprintf(
                '【%s】%s，完成可获得 %.2f 金币奖励！',
                $taskData['type_text'] ?? '',
                $taskData['name'] ?? '',
                $taskData['single_amount'] ?? 0
            ),
        ];
        
        return self::request('POST', '/api/push-task', $data);
    }
    
    /**
     * 发送系统消息
     */
    public static function sendSystemMessage($title, $content, $level = 'info', $targetUsers = null)
    {
        $data = [
            'title' => $title,
            'content' => $content,
            'level' => $level,
        ];
        
        if ($targetUsers !== null) {
            $data['targetUsers'] = $targetUsers;
        }
        
        return self::request('POST', '/api/system-message', $data);
    }
    
    /**
     * 广播消息到所有用户
     */
    public static function broadcast($event, $data)
    {
        return self::request('POST', '/api/broadcast', [
            'event' => $event,
            'data' => $data,
        ]);
    }
    
    /**
     * 获取在线用户数
     */
    public static function getOnlineCount()
    {
        return self::request('GET', '/api/online-count');
    }
}
