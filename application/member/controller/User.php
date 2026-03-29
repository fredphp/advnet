<?php

namespace app\member\controller;

use app\common\controller\Backend;
use app\member\model\User as UserModel;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 会员管理
 */
class User extends Backend
{
    /**
     * User模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new UserModel();
    }

    /**
     * 会员统计
     */
    public function statistics()
    {
        $start_date = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $end_date = $this->request->get('end_date', date('Y-m-d'));

        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }

        // 统计数据
        $startTime = strtotime($start_date);
        $endTime = strtotime($end_date . ' 23:59:59');

        // 总会员数
        $totalUsers = $this->model->count();

        // 新增会员数
        $newUsers = $this->model->where('createtime', 'between', [$startTime, $endTime])->count();

        // 活跃会员数
        $activeUsers = $this->model->where('logintime', 'between', [$startTime, $endTime])->count();

        // 按日期统计新增会员
        $dailyStats = $this->model
            ->where('createtime', 'between', [$startTime, $endTime])
            ->field("FROM_UNIXTIME(createtime, '%Y-%m-%d') as date, COUNT(*) as count")
            ->group('date')
            ->select();

        $result = [
            'total' => [
                'total_users' => $totalUsers,
                'new_users' => $newUsers,
                'active_users' => $activeUsers,
            ],
            'daily' => $dailyStats,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        return json($result);
    }

    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
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
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 删除
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
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
        } catch (PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }
}
