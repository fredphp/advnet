<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\TaskMessage;
use think\Db;

/**
 * 消息接口
 * 客户端调用此接口获取推送的任务消息
 */
class Message extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 获取消息列表
     * @method GET
     * @param int $page 页码
     * @param int $pagesize 每页数量
     * @param string $type 消息类型
     */
    public function list()
    {
        $page = $this->request->get('page', 1, 'intval');
        $pagesize = $this->request->get('pagesize', 20, 'intval');
        $type = $this->request->get('type', '');
        
        $userId = $this->auth->id;
        
        $query = TaskMessage::where('status', TaskMessage::STATUS_SENT)
            ->where('expire_time', '>', time())
            ->where(function($q) use ($userId) {
                $q->whereRaw("target_users = '[]' OR target_users = '' OR target_users IS NULL")
                  ->whereOrRaw("JSON_CONTAINS(target_users, '\"$userId\"')");
            });
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $total = $query->count();
        
        $list = $query->order('send_time', 'desc')
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();
        
        $data = [];
        foreach ($list as $item) {
            $msgData = $item->getFormattedData();
            
            // 检查是否已读
            $readStatus = Db::name('user_message')
                ->where('user_id', $userId)
                ->where('message_id', $item->id)
                ->find();
            
            $msgData['is_read'] = $readStatus ? $readStatus['is_read'] : 0;
            $msgData['read_time'] = $readStatus ? $readStatus['read_time'] : 0;
            
            $data[] = $msgData;
        }
        
        $this->success('获取成功', [
            'total' => $total,
            'page' => $page,
            'pagesize' => $pagesize,
            'list' => $data,
        ]);
    }

    /**
     * 获取未读消息数量
     * @method GET
     */
    public function unreadCount()
    {
        $userId = $this->auth->id;
        
        // 获取所有已发送的消息
        $totalMessages = TaskMessage::where('status', TaskMessage::STATUS_SENT)
            ->where('expire_time', '>', time())
            ->where(function($q) use ($userId) {
                $q->whereRaw("target_users = '[]' OR target_users = '' OR target_users IS NULL")
                  ->whereOrRaw("JSON_CONTAINS(target_users, '\"$userId\"')");
            })
            ->column('id');
        
        // 获取已读消息
        $readMessages = Db::name('user_message')
            ->where('user_id', $userId)
            ->where('is_read', 1)
            ->column('message_id');
        
        $unreadCount = count($totalMessages) - count(array_intersect($totalMessages, $readMessages));
        
        $this->success('获取成功', [
            'unread_count' => max(0, $unreadCount),
            'total_count' => count($totalMessages),
        ]);
    }

    /**
     * 标记消息已读
     * @method POST
     * @param int $message_id 消息ID，0表示标记全部已读
     */
    public function markRead()
    {
        $messageId = $this->request->post('message_id', 0, 'intval');
        $userId = $this->auth->id;
        
        if ($messageId > 0) {
            // 标记单条消息已读
            $exists = Db::name('user_message')
                ->where('user_id', $userId)
                ->where('message_id', $messageId)
                ->find();
            
            if ($exists) {
                Db::name('user_message')
                    ->where('id', $exists['id'])
                    ->update([
                        'is_read' => 1,
                        'read_time' => time(),
                    ]);
            } else {
                Db::name('user_message')->insert([
                    'user_id' => $userId,
                    'message_id' => $messageId,
                    'is_read' => 1,
                    'read_time' => time(),
                    'createtime' => time(),
                ]);
            }
        } else {
            // 标记全部已读
            $messages = TaskMessage::where('status', TaskMessage::STATUS_SENT)
                ->where('expire_time', '>', time())
                ->column('id');
            
            foreach ($messages as $mid) {
                $exists = Db::name('user_message')
                    ->where('user_id', $userId)
                    ->where('message_id', $mid)
                    ->find();
                
                if ($exists) {
                    Db::name('user_message')
                        ->where('id', $exists['id'])
                        ->update([
                            'is_read' => 1,
                            'read_time' => time(),
                        ]);
                } else {
                    Db::name('user_message')->insert([
                        'user_id' => $userId,
                        'message_id' => $mid,
                        'is_read' => 1,
                        'read_time' => time(),
                        'createtime' => time(),
                    ]);
                }
            }
        }
        
        $this->success('标记成功');
    }

    /**
     * 获取消息详情
     * @method GET
     * @param int $id 消息ID
     */
    public function detail()
    {
        $id = $this->request->get('id', 0, 'intval');
        $userId = $this->auth->id;
        
        $message = TaskMessage::get($id);
        if (!$message) {
            $this->error('消息不存在');
        }
        
        // 自动标记已读
        $exists = Db::name('user_message')
            ->where('user_id', $userId)
            ->where('message_id', $id)
            ->find();
        
        if ($exists) {
            Db::name('user_message')
                ->where('id', $exists['id'])
                ->update([
                    'is_read' => 1,
                    'read_time' => time(),
                ]);
        } else {
            Db::name('user_message')->insert([
                'user_id' => $userId,
                'message_id' => $id,
                'is_read' => 1,
                'read_time' => time(),
                'createtime' => time(),
            ]);
        }
        
        $this->success('获取成功', $message->getFormattedData());
    }

    /**
     * 获取任务推送（专门用于任务列表）
     * @method GET
     */
    public function taskPushList()
    {
        $page = $this->request->get('page', 1, 'intval');
        $pagesize = $this->request->get('pagesize', 10, 'intval');
        $userId = $this->auth->id;
        
        $query = TaskMessage::where('status', TaskMessage::STATUS_SENT)
            ->where('type', TaskMessage::TYPE_TASK_PUSH)
            ->where('expire_time', '>', time());
        
        $total = $query->count();
        
        $list = $query->order('send_time', 'desc')
            ->page($page, $pagesize)
            ->select();
        
        $data = [];
        foreach ($list as $item) {
            $msgData = json_decode($item->message_data, true) ?: [];
            $msgData['message_id'] = $item->id;
            $msgData['push_time'] = $item->send_time;
            $msgData['push_time_text'] = date('Y-m-d H:i', $item->send_time);
            $msgData['expire_time'] = $item->expire_time;
            $data[] = $msgData;
        }
        
        $this->success('获取成功', [
            'total' => $total,
            'list' => $data,
        ]);
    }
}
