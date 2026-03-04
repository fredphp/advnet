<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketAmountConfig as RedPacketAmountConfigModel;
use think\Db;
use think\Exception;

/**
 * 红包金额配置管理
 * 用于配置新用户红包和不同领取金额区间的奖励额度
 */
class Amountconfig extends Backend
{
    /**
     * RedPacketAmountConfig模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketAmountConfigModel;

        // 配置类型列表
        $configTypeList = RedPacketAmountConfigModel::$configTypeList;
        $this->view->assign('configTypeList', $configTypeList);

        // 状态列表
        $statusList = RedPacketAmountConfigModel::$statusList;
        $this->view->assign('statusList', $statusList);
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

            // 默认按权重排序
            if ($sort == 'sort' || $sort == 'weigh') {
                $sort = 'weigh';
                $order = 'desc';
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

                    // 验证基础奖励金额
                    if (!isset($params['base_min_reward']) || !isset($params['base_max_reward'])) {
                        $this->error('基础奖励金额不能为空');
                    }
                    if ($params['base_min_reward'] > $params['base_max_reward']) {
                        $this->error('基础奖励金额下限不能大于上限');
                    }

                    // 阶梯配置需要验证今日金额区间和累加奖励
                    if ($params['config_type'] === 'tier') {
                        // 验证今日金额区间
                        if (isset($params['min_today_amount']) && isset($params['max_today_amount'])) {
                            if ($params['max_today_amount'] > 0 && $params['min_today_amount'] > $params['max_today_amount']) {
                                $this->error('今日领取金额下限不能大于上限');
                            }
                        }
                        // 验证累加奖励金额
                        if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                            if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                                $this->error('累加奖励金额下限不能大于上限');
                            }
                        }
                    } else {
                        // 新用户红包不需要今日金额区间
                        $params['min_today_amount'] = 0;
                        $params['max_today_amount'] = 0;
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
                    // 验证基础奖励金额区间
                    if (isset($params['base_min_reward']) && isset($params['base_max_reward'])) {
                        if ($params['base_min_reward'] > $params['base_max_reward']) {
                            $this->error('基础奖励金额下限不能大于上限');
                        }
                    }

                    // 阶梯配置验证
                    $configType = isset($params['config_type']) ? $params['config_type'] : $row->config_type;
                    if ($configType === 'tier') {
                        // 验证今日金额区间
                        if (isset($params['min_today_amount']) && isset($params['max_today_amount'])) {
                            if ($params['max_today_amount'] > 0 && $params['min_today_amount'] > $params['max_today_amount']) {
                                $this->error('今日领取金额下限不能大于上限');
                            }
                        }
                        // 验证累加奖励金额
                        if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                            if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                                $this->error('累加奖励金额下限不能大于上限');
                            }
                        }
                    } else {
                        // 新用户红包不需要今日金额区间
                        $params['min_today_amount'] = 0;
                        $params['max_today_amount'] = 0;
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
}
