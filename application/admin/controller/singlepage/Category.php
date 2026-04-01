<?php

namespace app\admin\controller\singlepage;

use app\common\controller\Backend;
use app\common\model\SinglepageCategory;
use think\Exception;
use think\Db;

/**
 * 单页分类管理
 *
 * @icon fa fa-folder-open
 * @remark 管理单页内容的分类
 */
class Category extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new SinglepageCategory();
    }

    /**
     * 分类列表（带分页）
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

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加分类
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }
            if (empty($params['name'])) {
                $this->error('分类名称不能为空');
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
     * 编辑分类
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }
            if (empty($params['name'])) {
                $this->error('分类名称不能为空');
            }

            $params['updatetime'] = time();

            try {
                $result = $row->allowField(true)->save($params);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($row->getError());
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除分类
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

        // 检查分类下是否还有单页
        $count = Db::name('singlepage')->where('category_id', 'in', $ids)->count();
        if ($count > 0) {
            $this->error('该分类下还有 ' . $count . ' 个单页，请先删除或移动相关单页');
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
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        } else {
            $this->error(__('删除失败'));
        }
    }
}
