<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\RedPacketService;
use think\facade\Db;

/**
 * 红包任务接口
 */
class RedPacket extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    protected $service = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->service = new RedPacketService();
    }
    
    /**
     * 获取任务列表
     * @api {get} /api/redpacket/tasks 获取任务列表
     * @apiName GetTaskList
     * @apiGroup 红包任务
     * @apiParam {String} [type] 任务类型筛选
     * @apiParam {Number} [category_id] 分类ID筛选
     * @apiParam {Number} [page=1] 页码
     * @apiParam {Number} [limit=20] 每页数量
     */
    public function tasks()
    {
        $filters = [
            'page' => $this->request->get('page/d', 1),
            'limit' => $this->request->get('limit/d', 20),
            'type' => $this->request->get('type/s', ''),
            'category_id' => $this->request->get('category_id/d', 0)
        ];
        
        $result = $this->service->getTaskList($this->auth->id, $filters);
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取任务详情
     * @api {get} /api/redpacket/detail 获取任务详情
     * @apiName GetTaskDetail
     * @apiGroup 红包任务
     * @apiParam {Number} task_id 任务ID
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
     * 获取任务分类
     * @api {get} /api/redpacket/categories 获取任务分类
     * @apiName GetCategories
     * @apiGroup 红包任务
     */
    public function categories()
    {
        $list = \app\common\model\TaskCategory::getActiveCategories();
        
        $this->success('获取成功', ['list' => $list]);
    }
    
    /**
     * 领取任务
     * @api {post} /api/redpacket/receive 领取任务
     * @apiName ReceiveTask
     * @apiGroup 红包任务
     * @apiParam {Number} task_id 任务ID
     */
    public function receive()
    {
        $taskId = $this->request->post('task_id/d');
        
        if (!$taskId) {
            $this->error('任务ID不能为空');
        }
        
        $options = [
            'ip' => $this->request->ip(),
            'device_id' => $this->request->header('x-device-id', ''),
            'platform' => $this->request->header('x-platform', ''),
            'app_version' => $this->request->header('x-app-version', ''),
            'device_info' => json_decode($this->request->post('device_info', '{}'), true)
        ];
        
        $result = $this->service->receiveTask($this->auth->id, $taskId, $options);
        
        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 提交任务完成
     * @api {post} /api/redpacket/submit 提交任务完成
     * @apiName SubmitTask
     * @apiGroup 红包任务
     * @apiParam {String} order_no 订单号
     * @apiParam {Number} [duration] 实际耗时(秒)
     * @apiParam {Number} [progress] 完成进度(%)
     * @apiParam {String[]} [screenshots] 截图URL数组
     * @apiParam {Object} [proof_data] 证明数据
     */
    public function submit()
    {
        $orderNo = $this->request->post('order_no/s');
        
        if (!$orderNo) {
            $this->error('订单号不能为空');
        }
        
        $data = [
            'duration' => $this->request->post('duration/d', 0),
            'progress' => $this->request->post('progress/d', 100),
            'screenshots' => json_decode($this->request->post('screenshots', '[]'), true) ?: [],
            'proof_data' => json_decode($this->request->post('proof_data', '{}'), true) ?: [],
            'extra_data' => [
                'ip' => $this->request->ip(),
                'device_id' => $this->request->header('x-device-id', ''),
            ]
        ];
        
        $result = $this->service->submitTask($this->auth->id, $orderNo, $data);
        
        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 获取我的参与记录
     * @api {get} /api/redpacket/records 获取我的参与记录
     * @apiName GetRecords
     * @apiGroup 红包任务
     * @apiParam {Number} [status] 状态筛选
     * @apiParam {Number} [page=1] 页码
     * @apiParam {Number} [limit=20] 每页数量
     */
    public function records()
    {
        $status = $this->request->get('status');
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);
        
        if ($status !== null && $status !== '') {
            $status = intval($status);
        } else {
            $status = null;
        }
        
        $result = $this->service->getUserParticipations($this->auth->id, $status, $page, $limit);
        
        $this->success('获取成功', $result);
    }
    
    /**
     * 获取今日统计
     * @api {get} /api/redpacket/today 获取今日统计
     * @apiName GetTodayStat
     * @apiGroup 红包任务
     */
    public function today()
    {
        $stat = \app\common\model\UserTaskStat::getToday($this->auth->id);
        
        $this->success('获取成功', [
            'receive_count' => $stat->receive_count,
            'complete_count' => $stat->complete_count,
            'reward_count' => $stat->reward_count,
            'reward_coin' => $stat->reward_coin
        ]);
    }
    
    /**
     * 取消任务
     * @api {post} /api/redpacket/cancel 取消任务
     * @apiName CancelTask
     * @apiGroup 红包任务
     * @apiParam {String} order_no 订单号
     */
    public function cancel()
    {
        $orderNo = $this->request->post('order_no/s');
        
        if (!$orderNo) {
            $this->error('订单号不能为空');
        }
        
        $participation = \app\common\model\TaskParticipation::where('order_no', $orderNo)
            ->where('user_id', $this->auth->id)
            ->find();
        
        if (!$participation) {
            $this->error('参与记录不存在');
        }
        
        if ($participation->status != \app\common\model\TaskParticipation::STATUS_RECEIVED) {
            $this->error('任务状态不可取消');
        }
        
        $participation->status = \app\common\model\TaskParticipation::STATUS_CANCELLED;
        $participation->save();
        
        $this->success('已取消');
    }
}