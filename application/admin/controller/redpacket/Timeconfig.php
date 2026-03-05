<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketTimeConfig as RedPacketTimeConfigModel;
use think\Db;
use think\Exception;

/**
 * 红包时间段配置管理
 * 用于配置不同时间段的红包奖励金额
 */
class Timeconfig extends Backend
{
    /**
     * RedPacketTimeConfig模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketTimeConfigModel;

        // 状态列表
        $statusList = RedPacketTimeConfigModel::$statusList;
        $this->view->assign('statusList', $statusList);

        // 小时选项（0-24）
        $hourOptions = [];
        for ($i = 0; $i <= 24; $i++) {
            $hourOptions[$i] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
        }
        $this->view->assign('hourOptions', $hourOptions);
    }

    /**
     * 查看
     */
    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 默认按开始时间排序
            if ($sort == 'sort' || $sort == 'weigh') {
                $sort = 'start_hour';
                $order = 'asc';
            }

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->order('weigh', 'desc')
                ->paginate($limit);

            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }

        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                $result = false;
                Db::startTrans();
                try {
                    // 验证
                    if (empty($params['name'])) {
                        $this->error('配置名称不能为空');
                    }

                    // 验证时间段
                    $startHour = intval($params['start_hour'] ?? 0);
                    $endHour = intval($params['end_hour'] ?? 24);
                    
                    if ($startHour < 0 || $startHour > 24) {
                        $this->error('开始时间必须在0-24之间');
                    }
                    if ($endHour < 0 || $endHour > 24) {
                        $this->error('结束时间必须在0-24之间');
                    }
                    if ($startHour >= $endHour) {
                        $this->error('开始时间必须小于结束时间');
                    }

                    // 检查时间段是否重叠
                    if (RedPacketTimeConfigModel::hasOverlap($startHour, $endHour)) {
                        $this->error('该时间段与已有配置重叠，请调整时间段');
                    }

                    // 验证基础奖励金额
                    if (!isset($params['base_min_reward']) || !isset($params['base_max_reward'])) {
                        $this->error('基础奖励金额不能为空');
                    }
                    if ($params['base_min_reward'] > $params['base_max_reward']) {
                        $this->error('基础奖励金额下限不能大于上限');
                    }

                    // 验证累加奖励金额
                    if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                        if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                            $this->error('累加奖励金额下限不能大于上限');
                        }
                    }

                    // 验证新用户奖励金额
                    if (isset($params['new_user_base_min']) && isset($params['new_user_base_max'])) {
                        if ($params['new_user_base_min'] > $params['new_user_base_max']) {
                            $this->error('新用户基础奖励金额下限不能大于上限');
                        }
                    }
                    if (isset($params['new_user_accumulate_min']) && isset($params['new_user_accumulate_max'])) {
                        if ($params['new_user_accumulate_min'] > $params['new_user_accumulate_max']) {
                            $this->error('新用户累加奖励金额下限不能大于上限');
                        }
                    }

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
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
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    // 验证时间段
                    if (isset($params['start_hour']) || isset($params['end_hour'])) {
                        $startHour = intval($params['start_hour'] ?? $row->start_hour);
                        $endHour = intval($params['end_hour'] ?? $row->end_hour);
                        
                        if ($startHour < 0 || $startHour > 24) {
                            $this->error('开始时间必须在0-24之间');
                        }
                        if ($endHour < 0 || $endHour > 24) {
                            $this->error('结束时间必须在0-24之间');
                        }
                        if ($startHour >= $endHour) {
                            $this->error('开始时间必须小于结束时间');
                        }

                        // 检查时间段是否重叠
                        if (RedPacketTimeConfigModel::hasOverlap($startHour, $endHour, $ids)) {
                            $this->error('该时间段与已有配置重叠，请调整时间段');
                        }
                    }

                    // 验证基础奖励金额
                    if (isset($params['base_min_reward']) && isset($params['base_max_reward'])) {
                        if ($params['base_min_reward'] > $params['base_max_reward']) {
                            $this->error('基础奖励金额下限不能大于上限');
                        }
                    }

                    // 验证累加奖励金额
                    if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                        if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                            $this->error('累加奖励金额下限不能大于上限');
                        }
                    }

                    // 验证新用户奖励金额
                    if (isset($params['new_user_base_min']) && isset($params['new_user_base_max'])) {
                        if ($params['new_user_base_min'] > $params['new_user_base_max']) {
                            $this->error('新用户基础奖励金额下限不能大于上限');
                        }
                    }
                    if (isset($params['new_user_accumulate_min']) && isset($params['new_user_accumulate_max'])) {
                        if ($params['new_user_accumulate_min'] > $params['new_user_accumulate_max']) {
                            $this->error('新用户累加奖励金额下限不能大于上限');
                        }
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ? $ids : $this->request->post('ids');
        if ($ids) {
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
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

            if ($count) {
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 获取当前时段配置（API接口）
     */
    public function current()
    {
        $hour = intval(date('H'));
        $config = RedPacketTimeConfigModel::getConfigByHour($hour);

        if (!$config) {
            $this->error('未找到当前时段的配置');
        }

        $this->success('获取成功', null, $config->toArray());
    }
}
