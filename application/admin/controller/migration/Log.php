<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移日志
 */
class Log extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 日志列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');

            $total = Db::name('migration_log')->count();
            $list = Db::name('migration_log')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 日志详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('migration_log')->where('id', $ids)->find();
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $row['params'] = json_decode($row['params'], true);
        $row['result'] = json_decode($row['result'], true);

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
