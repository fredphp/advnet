<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\TaskMessage;
use app\common\service\PushService;
use think\Db;
use think\Exception;

/**
 * 红包任务管理
 */
class Task extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RedPacketTask();
        
        // 传递类型列表到视图
        $this->view->assign('typeList', \app\common\model\RedPacketResource::getTypeList());
    }

    /**
     * 红包任务列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $data = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                $row['grabbed_count'] = Db::name('task_participation')
                    ->where('task_id', $row['id'])
                    ->count();
                $row['total_grabbed_amount'] = Db::name('task_participation')
                    ->where('task_id', $row['id'])
                    ->sum('reward_coin');
                $row['push_status'] = TaskMessage::where('task_id', $row['id'])->count() > 0 ? 1 : 0;
                $data[] = $row;
            }

            $result = ['total' => $total, 'rows' => $data];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加红包任务
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            if ($params['total_amount'] <= 0) {
                $this->error('红包总金额必须大于0');
            }
            if ($params['total_count'] <= 0) {
                $this->error('红包数量必须大于0');
            }
            
            if (!empty($params['resource_id'])) {
                $resource = \app\common\model\RedPacketResource::get($params['resource_id']);
                if ($resource) {
                    $params['task_url'] = $resource->url;
                    $params['task_params'] = json_encode([
                        'resource_id' => $resource->id,
                        'resource_type' => $resource->type,
                        'name' => $resource->name,
                        'description' => $resource->description,
                        'logo' => $resource->logo,
                        'package_name' => $resource->package_name,
                        'app_id' => $resource->app_id,
                        'video_id' => $resource->video_id,
                    ]);
                    $params['icon'] = $resource->logo;
                }
            }

            $params['remain_amount'] = $params['total_amount'];
            $params['remain_count'] = $params['total_count'];
            $params['createtime'] = time();
            $params['updatetime'] = time();

            Db::startTrans();
            try {
                $result = $this->model->allowField(true)->save($params);
                Db::commit();
                $this->success();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑红包任务
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }
            
            if (!empty($params['resource_id'])) {
                $resource = \app\common\model\RedPacketResource::get($params['resource_id']);
                if ($resource) {
                    $params['task_url'] = $resource->url;
                    $params['task_params'] = json_encode([
                        'resource_id' => $resource->id,
                        'resource_type' => $resource->type,
                        'name' => $resource->name,
                        'description' => $resource->description,
                        'logo' => $resource->logo,
                        'package_name' => $resource->package_name,
                        'app_id' => $resource->app_id,
                        'video_id' => $resource->video_id,
                    ]);
                    $params['icon'] = $resource->logo;
                }
            }

            $params['updatetime'] = time();

            Db::startTrans();
            try {
                $result = $row->allowField(true)->save($params);
                Db::commit();
                $this->success();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        $resourceInfo = null;
        if ($row['task_params']) {
            $params = json_decode($row['task_params'], true);
            if (isset($params['resource_id'])) {
                $resourceInfo = \app\common\model\RedPacketResource::get($params['resource_id']);
            }
        }
        
        $row['start_time'] = $row['start_time'] ? date('Y-m-d H:i:s', $row['start_time']) : '';
        $row['end_time'] = $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '';

        $this->view->assign('row', $row);
        $this->view->assign('resourceInfo', $resourceInfo);
        return $this->view->fetch();
    }

    /**
     * 推送任务到客户端（WebSocket 实时推送）
     */
    public function push($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($row->status != 1) {
            $this->error('只能推送已启用的任务');
        }

        // 检查是否已经推送过
        $exists = TaskMessage::where('task_id', $ids)
            ->where('status', '<>', TaskMessage::STATUS_EXPIRED)
            ->find();
        if ($exists) {
            $this->error('该任务已推送，请勿重复推送');
        }

        // 获取任务类型名称
        $typeList = \app\common\model\RedPacketResource::getTypeList();
        $taskTypeName = $typeList[$row->task_type] ?? $row->task_type;

        // 解析任务参数
        $taskParams = json_decode($row->task_params, true) ?: [];

        // 1. 保存推送记录到数据库
        $messageData = [
            'task_id' => $row->id,
            'task_type' => $row->task_type,
            'task_type_name' => $taskTypeName,
            'name' => $row->name,
            'description' => $row->description,
            'icon' => $row->icon ?: ($taskParams['logo'] ?? '/assets/img/avatar.png'),
            'total_amount' => $row->total_amount,
            'single_amount' => $row->single_amount,
            'total_count' => $row->total_count,
            'remain_count' => $row->remain_count,
            'start_time' => $row->start_time,
            'end_time' => $row->end_time,
            'task_url' => $row->task_url,
            'resource' => $taskParams,
        ];

        $message = new TaskMessage();
        $message->task_id = $ids;
        $message->type = TaskMessage::TYPE_TASK_PUSH;
        $message->title = '🎉 新任务推送：' . $row->name;
        $message->content = sprintf(
            '【%s】%s，完成可获得 %.2f 金币奖励！剩余%d个名额',
            $taskTypeName,
            $row->name,
            $row->amount_type == 'fixed' ? $row->single_amount : $row->max_amount,
            $row->remain_count
        );
        $message->message_data = json_encode($messageData);
        $message->status = TaskMessage::STATUS_SENT;
        $message->target_users = json_encode([]);
        $message->send_time = time();
        $message->expire_time = $row->end_time ?: (time() + 86400 * 7);
        $message->save();

        // 2. 通过 WebSocket 实时推送到客户端
        $pushResult = PushService::pushTask([
            'id' => $row->id,
            'name' => $row->name,
            'task_type' => $row->task_type,
            'task_type_text' => $taskTypeName,
            'single_amount' => $row->amount_type == 'fixed' ? $row->single_amount : $row->max_amount,
            'icon' => $row->icon ?: ($taskParams['logo'] ?? ''),
            'total_count' => $row->total_count,
            'remain_count' => $row->remain_count,
        ]);

        if (isset($pushResult['success']) && $pushResult['success']) {
            $onlineCount = $pushResult['delivered'] ?? 0;
            $this->success("推送成功！已实时推送到 {$onlineCount} 个在线用户");
        } else {
            // WebSocket 推送失败，但数据库记录已保存，客户端轮询时仍可获取
            $this->success('推送记录已保存，客户端将在下次刷新时收到通知');
        }
    }

    /**
     * 发送自定义消息（WebSocket 实时推送）
     */
    public function sendMessage($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($this->request->isPost()) {
            $title = $this->request->post('title');
            $content = $this->request->post('content');
            
            if (empty($title) || empty($content)) {
                $this->error('标题和内容不能为空');
            }

            $taskParams = json_decode($row->task_params, true) ?: [];

            // 保存到数据库
            $messageData = [
                'task_id' => $row->id,
                'task_type' => $row->task_type,
                'name' => $row->name,
                'icon' => $row->icon ?: ($taskParams['logo'] ?? '/assets/img/avatar.png'),
                'task_url' => $row->task_url,
            ];

            $message = new TaskMessage();
            $message->task_id = $ids;
            $message->type = TaskMessage::TYPE_SYSTEM_NOTICE;
            $message->title = $title;
            $message->content = $content;
            $message->message_data = json_encode($messageData);
            $message->status = TaskMessage::STATUS_SENT;
            $message->target_users = json_encode([]);
            $message->send_time = time();
            $message->expire_time = $row->end_time ?: (time() + 86400 * 7);
            $message->save();

            // WebSocket 实时推送
            $pushResult = PushService::sendSystemMessage($title, $content, 'info');

            if (isset($pushResult['success']) && $pushResult['success']) {
                $this->success('消息发送成功，已实时推送到客户端');
            } else {
                $this->success('消息已保存，客户端将在下次刷新时收到');
            }
        }

        $this->view->assign('task', $row);
        return $this->view->fetch();
    }

    /**
     * 删除任务
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('参数错误'));
        }
        $ids = $ids ? $ids : $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }
        $pk = $this->model->getPk();
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('删除失败'));
        }
    }

    /**
     * 发布任务
     */
    public function publish($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($row->status != 0) {
            $this->error('该任务已发布');
        }

        $row->status = 1;
        $row->updatetime = time();
        $row->save();

        $this->success('发布成功');
    }

    /**
     * 任务详情
     */
    public function detail($ids = null)
    {
        $task = $this->model->get($ids);
        if (!$task) {
            $this->error('任务不存在');
        }

        $participations = Db::name('task_participation tp')
            ->join('user u', 'u.id = tp.user_id', 'LEFT')
            ->field('tp.*, u.username, u.nickname, u.avatar')
            ->where('tp.task_id', $ids)
            ->order('tp.createtime', 'desc')
            ->limit(100)
            ->select();

        $stats = [
            'total_grabbed' => count($participations),
            'total_amount' => array_sum(array_column($participations, 'reward_coin')),
            'avg_amount' => count($participations) > 0 ? array_sum(array_column($participations, 'reward_coin')) / count($participations) : 0,
        ];

        $this->view->assign('task', $task);
        $this->view->assign('participations', $participations);
        $this->view->assign('stats', $stats);
        return $this->view->fetch();
    }
}
