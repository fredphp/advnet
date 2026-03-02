<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 红包任务资源管理
 */
class Resource extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RedPacketResource();
    }

    /**
     * 资源列表
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

            // 转换为数组
            $data = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                $row['type_text'] = \app\common\model\RedPacketResource::$typeList[$row['type']] ?? '';
                $data[] = $row;
            }

            return json(['total' => $total, 'rows' => $data]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加资源
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            // 处理图片数组
            if (isset($params['images']) && is_array($params['images'])) {
                $params['images'] = json_encode($params['images']);
            }

            // 处理扩展参数
            if (isset($params['params']) && is_array($params['params'])) {
                $params['params'] = json_encode($params['params']);
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

        // 获取资源类型列表
        $this->view->assign('typeList', \app\common\model\RedPacketResource::$typeList);
        return $this->view->fetch();
    }

    /**
     * 编辑资源
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

            // 处理图片数组
            if (isset($params['images']) && is_array($params['images'])) {
                $params['images'] = json_encode($params['images']);
            }

            // 处理扩展参数
            if (isset($params['params']) && is_array($params['params'])) {
                $params['params'] = json_encode($params['params']);
            }

            $params['updatetime'] = time();

            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        // 处理图片和参数显示
        $row['images'] = $row['images'] ? json_decode($row['images'], true) : [];
        $row['params'] = $row['params'] ? json_decode($row['params'], true) : [];

        $this->view->assign('row', $row);
        $this->view->assign('typeList', \app\common\model\RedPacketResource::$typeList);
        return $this->view->fetch();
    }

    /**
     * 删除资源
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
     * 根据类型获取资源列表（供选择器使用）
     */
    public function select()
    {
        $type = $this->request->get('type');
        if (!$type) {
            $this->error('请指定资源类型');
        }

        if ($this->request->isAjax()) {
            $list = $this->model->where('type', $type)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'desc')
                ->select();

            $data = [];
            foreach ($list as $item) {
                $data[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'logo' => $item->logo,
                    'url' => $item->url,
                    'package_name' => $item->package_name,
                    'app_id' => $item->app_id,
                    'video_id' => $item->video_id,
                ];
            }

            return json(['list' => $data, 'total' => count($data)]);
        }

        $this->view->assign('type', $type);
        return $this->view->fetch();
    }

    /**
     * 获取资源详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $data = $row->getFormattedData();
        $this->success('获取成功', null, $data);
    }
}
