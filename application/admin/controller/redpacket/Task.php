<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketTask as RedPacketTaskModel;
use app\common\model\RedPacketResource;
use think\Db;
use think\Exception;

/**
 * 红包任务管理
 */
class Task extends Backend
{
    /**
     * RedPacketTask模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketTaskModel;
        
        // 类型列表
        $typeList = RedPacketTaskModel::$typeList;
        $this->view->assign('typeList', $typeList);
        
        // 状态列表
        $statusList = RedPacketTaskModel::$statusList;
        $this->view->assign('statusList', $statusList);
        
        // 资源列表
        $resourceList = RedPacketResource::where('status', 'normal')->column('id,name,type');
        $this->view->assign('resourceList', $resourceList);
    }
    
    /**
     * 查看
     */
    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 默认按ID排序，避免sort字段不存在的问题
            if ($sort == 'sort') {
                $sort = 'id';
            }

            $list = $this->model
                ->with(['resource'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            // 确保返回 type_text
            foreach ($list as $row) {
                $row->type_text = $row->type_text ?? '';
            }

            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                $result = false;
                Db::startTrans();
                try {
                    // 计算单个红包金额
                    $params['remain_count'] = $params['total_count'];
                    $params['remain_amount'] = $params['total_amount'];
                    $params['reward'] = round($params['total_amount'] / $params['total_count'], 2);
                    
                    // 设置发送者信息
                    $params['sender_id'] = $this->auth->id;
                    $params['sender_name'] = $this->auth->nickname;
                    $params['sender_avatar'] = $this->auth->avatar;
                    
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 检查是否已发送，已发送的不允许修改
        if ($row->push_status == 1) {
            $this->error('该任务已发送，不允许修改');
        }
        
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 发送预览页面
     */
    public function send($ids = null)
    {
        $row = $this->model->with(['resource'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 已发送的任务不能再发送
        if ($row->push_status == 1) {
            $this->error('该任务已发送，请勿重复发送');
        }
        
        // 获取发送数据预览
        $sendData = $row->getPushData();
        
        // 根据任务类型获取聊天内容
        $chatContent = '';
        if ($row->type === 'chat') {
            if ($row->resource) {
                // 有关联资源，使用资源的聊天要求
                $chatContent = $row->resource->chat_requirement ?: $row->description;
            } else {
                // 无关联资源，使用任务描述作为聊天内容
                $chatContent = $row->description ?: '';
            }
        }
        
        $this->view->assign('row', $row);
        $this->view->assign('sendData', $sendData);
        $this->view->assign('chatContent', $chatContent);
        
        return $this->view->fetch();
    }
    
    /**
     * 执行发送
     */
    public function doSend($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 已发送的任务不能再发送
        if ($row->push_status == 1) {
            $this->error('该任务已发送，请勿重复发送');
        }
        
        if ($row->remain_count <= 0) {
            $this->error('红包数量已用完');
        }
        
        try {
            // 调用推送服务
            $pushData = $row->getPushData();
            $pushResult = $this->sendPushNotification($pushData);
            
            if ($pushResult['success']) {
                // 更新推送状态
                $row->push_status = 1;
                $row->push_time = time();
                $row->status = 'normal';
                $row->save();
                
                $this->success('发送成功', null, $pushResult);
            } else {
                $this->error('发送失败: ' . ($pushResult['message'] ?? '未知错误'));
            }
        } catch (Exception $e) {
            $this->error('发送失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 推送红包
     */
    public function push($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        if ($row->status != 'normal' && $row->status != 'pending') {
            $this->error('该红包任务状态不允许推送');
        }
        
        if ($row->remain_count <= 0) {
            $this->error('红包已抢完');
        }
        
        try {
            // 调用推送服务
            $pushData = $row->getPushData();
            $pushResult = $this->sendPushNotification($pushData);
            
            if ($pushResult['success']) {
                // 更新推送状态
                $row->push_status = 1;
                $row->push_time = time();
                $row->status = 'normal';
                $row->save();
                
                $this->success('推送成功', null, $pushResult);
            } else {
                $this->error('推送失败: ' . ($pushResult['message'] ?? '未知错误'));
            }
        } catch (Exception $e) {
            $this->error('推送失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送推送通知
     */
    protected function sendPushNotification($data)
    {
        // 推送服务配置 - 使用网关访问
        $pushServiceUrl = env('push.service_url', 'http://localhost:3003/api/push-task?XTransformPort=3003');
        $pushApiKey = env('push.api_key', 'redpacket-push-secret-key-2024');
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pushServiceUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . $pushApiKey
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                return json_decode($response, true);
            } else {
                return ['success' => false, 'message' => 'HTTP Error: ' . $httpCode];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 删除
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        
        $ids = $ids ? $ids : $this->request->post('ids');
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            
            $list = $this->model->where($pk, 'in', $ids)->select();
            
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $item) {
                    $count += $item->delete();
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            
            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 任务详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['resource'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        // 获取领取记录
        $records = \app\common\model\RedPacketRecord::where('task_id', $ids)
            ->with(['user'])
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        // 统计信息
        $stats = [
            'total_records' => \app\common\model\RedPacketRecord::where('task_id', $ids)->count(),
            'total_amount_sent' => \app\common\model\RedPacketRecord::where('task_id', $ids)->sum('amount'),
        ];

        $this->view->assign('row', $row);
        $this->view->assign('records', $records);
        $this->view->assign('stats', $stats);

        return $this->view->fetch();
    }
}
