<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
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

            foreach ($list as &$item) {
                $item['grabbed_count'] = Db::name('task_participation')
                    ->where('task_id', $item['id'])
                    ->count();
                $item['total_grabbed_amount'] = Db::name('task_participation')
                    ->where('task_id', $item['id'])
                    ->sum('coin_reward');
            }

            $result = ['total' => $total, 'rows' => $list];
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

            // 验证参数
            if ($params['total_amount'] <= 0) {
                $this->error('红包总金额必须大于0');
            }
            if ($params['total_count'] <= 0) {
                $this->error('红包数量必须大于0');
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

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 发布红包
     */
    public function publish($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($row->status != 'pending') {
            $this->error('该红包已发布');
        }

        $row->status = 'active';
        $row->publish_time = time();
        $row->updatetime = time();
        $row->save();

        $this->success('发布成功');
    }

    /**
     * 撤回红包
     */
    public function revoke($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($row->status != 'active') {
            $this->error('只能撤回进行中的红包');
        }

        Db::startTrans();
        try {
            $row->status = 'revoked';
            $row->updatetime = time();
            $row->save();

            // 退还剩余金额给发布者（如果有）
            // 这里可以根据业务逻辑处理

            Db::commit();
            $this->success('撤回成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 红包详情
     */
    public function detail($ids = null)
    {
        $task = $this->model->get($ids);
        if (!$task) {
            $this->error('红包不存在');
        }

        // 领取记录
        $participations = Db::name('task_participation tp')
            ->join('user u', 'u.id = tp.user_id', 'LEFT')
            ->field('tp.*, u.username, u.nickname, u.avatar')
            ->where('tp.task_id', $ids)
            ->order('tp.createtime', 'desc')
            ->limit(100)
            ->select();

        // 统计信息
        $stats = [
            'total_grabbed' => count($participations),
            'total_amount' => array_sum(array_column($participations, 'coin_reward')),
            'avg_amount' => count($participations) > 0 ? array_sum(array_column($participations, 'coin_reward')) / count($participations) : 0,
        ];

        $this->success('', [
            'task' => $task,
            'participations' => $participations,
            'stats' => $stats,
        ]);
    }

    /**
     * 批量发布
     */
    public function batchPublish()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $this->model->where('id', 'in', $ids)
            ->where('status', 'pending')
            ->update([
                'status' => 'active',
                'publish_time' => time(),
                'updatetime' => time()
            ]);

        $this->success();
    }
}

/**
 * 红包领取记录
 */
class Participation extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\TaskParticipation();
    }

    /**
     * 领取记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('tp')
                ->join('user u', 'u.id = tp.user_id', 'LEFT')
                ->join('red_packet_task rpt', 'rpt.id = tp.task_id', 'LEFT')
                ->field('tp.*, u.username, u.nickname, rpt.title as task_title')
                ->where($where)
                ->order("tp.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 领取统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 总体统计
        $totalStats = Db::name('task_participation')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_count, COUNT(DISTINCT user_id) as unique_users,
                     SUM(coin_reward) as total_coin, AVG(coin_reward) as avg_coin')
            ->find();

        // 每日统计
        $dailyStats = Db::name('task_participation')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as count, SUM(coin_reward) as coin')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 用户领取排行
        $topUsers = Db::name('task_participation')
            ->alias('tp')
            ->join('user u', 'u.id = tp.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, COUNT(*) as grab_count, SUM(tp.coin_reward) as total_coin')
            ->where('tp.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('tp.user_id')
            ->order('total_coin', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'daily_stats' => $dailyStats,
            'top_users' => $topUsers,
        ]);
    }
}

/**
 * 红包分类管理
 */
class Category extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\TaskCategory();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}

/**
 * 红包统计
 */
class Stat extends Backend
{
    /**
     * 红包统计概览
     */
    public function index()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 发放统计
        $sendStats = Db::name('red_packet_task')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_tasks, SUM(total_amount) as total_amount,
                     SUM(total_count) as total_count, SUM(remain_amount) as remain_amount')
            ->find();

        // 领取统计
        $grabStats = Db::name('task_participation')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_grab, SUM(coin_reward) as total_coin')
            ->find();

        // 每日发放趋势
        $dailySend = Db::name('red_packet_task')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as tasks, SUM(total_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 每日领取趋势
        $dailyGrab = Db::name('task_participation')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as grabs, SUM(coin_reward) as coin')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 红包类型分布
        $typeDistribution = Db::name('red_packet_task')
            ->field('type, COUNT(*) as count, SUM(total_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('type')
            ->select();

        $this->success('', [
            'send_stats' => $sendStats,
            'grab_stats' => $grabStats,
            'daily_send' => $dailySend,
            'daily_grab' => $dailyGrab,
            'type_distribution' => $typeDistribution,
        ]);
    }
}
