<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\RedPacketClickService;
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
        $this->service = new RedPacketClickService();
    }

    /**
     * 获取任务列表
     * @api {get} /api/redpacket/tasks 获取任务列表
     * @apiName GetTaskList
     * @apiGroup 红包任务
     * @apiParam {String} [type] 任务类型筛选
     * @apiParam {Number} [page=1] 页码
     * @apiParam {Number} [limit=20] 每页数量
     */
    public function tasks()
    {
        $filters = [
            'page' => $this->request->get('page/d', 1),
            'limit' => $this->request->get('limit/d', 20),
            'type' => $this->request->get('type/s', ''),
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
     * 点击红包
     * @api {post} /api/redpacket/click 点击红包
     * @apiName ClickRedPacket
     * @apiGroup 红包任务
     * @apiParam {Number} task_id 任务ID
     * @apiDescription
     * 第一次点击：生成基础金额（新用户使用新用户红包金额，老用户根据今日领取金额确定基础额度）
     * 后续点击：在现有金额上累加随机金额
     */
    public function click()
    {
        $taskId = $this->request->post('task_id/d');

        if (!$taskId) {
            $this->error('任务ID不能为空');
        }

        $options = [
            'ip' => $this->request->ip(),
            'device_id' => $this->request->header('x-device-id', ''),
            'platform' => $this->request->header('x-platform', ''),
        ];

        $result = $this->service->clickRedPacket($this->auth->id, $taskId, $options);

        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }

    /**
     * 领取红包
     * @api {post} /api/redpacket/collect 领取红包
     * @apiName CollectRedPacket
     * @apiGroup 红包任务
     * @apiParam {Number} task_id 任务ID
     * @apiDescription 领取累计的红包金额，金币将发放到用户账户
     */
    public function collect()
    {
        $taskId = $this->request->post('task_id/d');

        if (!$taskId) {
            $this->error('任务ID不能为空');
        }

        $result = $this->service->collectRedPacket($this->auth->id, $taskId);

        if ($result['success']) {
            $this->success($result['message'], $result['data']);
        } else {
            $this->error($result['message']);
        }
    }

    /**
     * 获取用户红包状态
     * @api {get} /api/redpacket/status 获取用户红包状态
     * @apiName GetRedPacketStatus
     * @apiGroup 红包任务
     * @apiParam {Number} task_id 任务ID
     * @apiDescription 获取用户对某个任务的红包累计状态
     */
    public function status()
    {
        $taskId = $this->request->get('task_id/d');

        if (!$taskId) {
            $this->error('任务ID不能为空');
        }

        $result = $this->service->getUserRedPacketStatus($this->auth->id, $taskId);

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
        // 获取今日已领取金额
        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $this->auth->id)
            ->where('type', 'red_packet_click')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        // 获取今日点击次数
        $todayClickCount = Db::name('user_red_packet_accumulate')
            ->where('user_id', $this->auth->id)
            ->where('createtime', '>=', $todayStart)
            ->count();

        $this->success('获取成功', [
            'today_amount' => intval($todayAmount),
            'today_click_count' => $todayClickCount,
        ]);
    }

    /**
     * 获取我的参与记录
     * @api {get} /api/redpacket/records 获取我的参与记录
     * @apiName GetRecords
     * @apiGroup 红包任务
     * @apiParam {Number} [page=1] 页码
     * @apiParam {Number} [limit=20] 每页数量
     */
    public function records()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);

        $result = $this->service->getUserRecords($this->auth->id, $page, $limit);

        $this->success('获取成功', $result);
    }
}
