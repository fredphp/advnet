<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 红包领取记录
 * 使用用户红包累计记录表（单人抢红包模式）
 */
class Participation extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\UserRedPacketAccumulate;

        // 领取状态列表
        $this->view->assign('collectStatusList', [
            0 => '待领取',
            1 => '已领取'
        ]);

        // 是否新用户
        $this->view->assign('newUserList', [
            0 => '老用户',
            1 => '新用户'
        ]);
    }

    /**
     * 查看列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['task', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            // 处理数据
            foreach ($list as &$row) {
                $row->task_name = $row->task ? $row->task->name : '';
                $row->username = $row->user ? $row->user->username : '';
                $row->nickname = $row->user ? $row->user->nickname : '';
                $row->collect_status_text = $row->is_collected ? '已领取' : '待领取';
                $row->new_user_text = $row->is_new_user ? '新用户' : '老用户';
            }

            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['task', 'user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        // 获取关联任务的详细信息
        $taskData = null;
        if ($row->task) {
            $taskData = $row->task->toArray();
            $taskData['type_text'] = $row->task->type_text ?? '';
            $taskData['status_text'] = $row->task->status_text ?? '';
        }

        $this->view->assign('row', $row);
        $this->view->assign('task', $taskData);
        $this->view->assign('userData', $row->user ? $row->user->toArray() : null);

        return $this->view->fetch();
    }

    /**
     * 统计数据
     */
    public function stat()
    {
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // 总领取人数
            $totalUsers = $this->model->count();

            // 总领取金额
            $totalAmount = $this->model->where('is_collected', 1)->sum('total_amount');

            // 待领取金额
            $pendingAmount = $this->model->where('is_collected', 0)->sum('total_amount');

            // 今日新增
            $todayStart = strtotime(date('Y-m-d'));
            $todayCount = $this->model->where('createtime', '>=', $todayStart)->count();
            $todayAmount = $this->model->where('createtime', '>=', $todayStart)
                ->where('is_collected', 1)
                ->sum('total_amount');

            // 新用户统计
            $newUserCount = $this->model->where('is_new_user', 1)->count();
            $newUserAmount = $this->model->where('is_new_user', 1)
                ->where('is_collected', 1)
                ->sum('total_amount');

            // 平均点击次数
            $avgClicks = $this->model->avg('click_count');

            return json([
                'code' => 1,
                'data' => [
                    'total_users' => $totalUsers,
                    'total_amount' => number_format($totalAmount, 0, '', ','),
                    'pending_amount' => number_format($pendingAmount, 0, '', ','),
                    'today_count' => $todayCount,
                    'today_amount' => number_format($todayAmount, 0, '', ','),
                    'new_user_count' => $newUserCount,
                    'new_user_amount' => number_format($newUserAmount, 0, '', ','),
                    'avg_clicks' => round($avgClicks, 1)
                ]
            ]);
        }

        return $this->view->fetch();
    }
}
