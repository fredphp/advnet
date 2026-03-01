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

            // 转换为数组并添加统计信息
            $data = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                $row['grabbed_count'] = Db::name('task_participation')
                    ->where('task_id', $row['id'])
                    ->count();
                $row['total_grabbed_amount'] = Db::name('task_participation')
                    ->where('task_id', $row['id'])
                    ->sum('reward_coin');
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
     * 删除红包任务
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
            'total_amount' => array_sum(array_column($participations, 'reward_coin')),
            'avg_amount' => count($participations) > 0 ? array_sum(array_column($participations, 'reward_coin')) / count($participations) : 0,
        ];

        $this->view->assign('task', $task);
        $this->view->assign('participations', $participations);
        $this->view->assign('stats', $stats);
        return $this->view->fetch();
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
