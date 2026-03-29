<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 视频奖励规则
 */
class Rewardrule extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoRewardRule();
    }

    /**
     * 规则列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 处理排序字段映射：weigh -> priority
            if ($sort === 'weigh') {
                $sort = 'priority';
            }

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->field('id,name,code,description,scope_type,scope_id,reward_type,reward_coin,reward_min,reward_max,condition_type,watch_progress,watch_duration,daily_limit,priority,status,start_time,end_time,createtime')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 转换为数组并添加文本字段
            $result = [];
            foreach ($list as $item) {
                $data = $item->toArray();
                $result[] = $data;
            }

            return json(['total' => $total, 'rows' => $result]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加规则
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            // 处理时间
            if (!empty($params['start_time'])) {
                $params['start_time'] = strtotime($params['start_time']);
            }
            if (!empty($params['end_time'])) {
                $params['end_time'] = strtotime($params['end_time']);
            }

            $params['createtime'] = time();
            $params['updatetime'] = time();

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑规则
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

            // 处理时间
            if (!empty($params['start_time'])) {
                $params['start_time'] = strtotime($params['start_time']);
            }
            if (!empty($params['end_time'])) {
                $params['end_time'] = strtotime($params['end_time']);
            }

            $params['updatetime'] = time();
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        // 格式化时间显示
        $row['start_time'] = $row['start_time'] ? date('Y-m-d H:i:s', $row['start_time']) : '';
        $row['end_time'] = $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '';

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除规则
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
     * 启用/禁用规则
     */
    public function toggle($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $row->status = $row->status == 1 ? 0 : 1;
        $row->updatetime = time();
        $row->save();

        $this->success();
    }
}
