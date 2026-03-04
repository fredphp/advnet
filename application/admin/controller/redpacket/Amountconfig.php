<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketAmountConfig as RedPacketAmountConfigModel;
use think\Db;
use think\Exception;

/**
 * 红包金额配置管理
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

            // 默认按ID排序
            if ($sort == 'sort') {
                $sort = 'weigh';
                $order = 'desc';
            }

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->order('weigh', 'desc')
                ->paginate($limit);

            // 添加类型文本
            foreach ($list as $row) {
                $row->config_type_text = $row->config_type_text;
                $row->status_text = $row->status_text;

                // 格式化今日金额区间显示
                if ($row->config_type == 'new_user') {
                    $row->today_range_text = '-';
                } else {
                    $minAmount = number_format($row->min_today_amount);
                    if ($row->max_today_amount > 0) {
                        $maxAmount = number_format($row->max_today_amount);
                        $row->today_range_text = "{$minAmount} - {$maxAmount}";
                    } else {
                        $row->today_range_text = "{$minAmount}以上";
                    }
                }

                // 格式化奖励金额显示
                $minReward = number_format($row->min_reward);
                $maxReward = number_format($row->max_reward);
                $row->reward_range_text = "{$minReward} - {$maxReward}";
            }

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
                    if ($params['min_reward'] > $params['max_reward']) {
                        $this->error('奖励金额下限不能大于上限');
                    }
                    if (isset($params['min_today_amount']) && isset($params['max_today_amount'])) {
                        if ($params['max_today_amount'] > 0 && $params['min_today_amount'] > $params['max_today_amount']) {
                            $this->error('今日领取金额下限不能大于上限');
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
                    // 验证
                    if (isset($params['min_reward']) && isset($params['max_reward'])) {
                        if ($params['min_reward'] > $params['max_reward']) {
                            $this->error('奖励金额下限不能大于上限');
                        }
                    }
                    if (isset($params['min_today_amount']) && isset($params['max_today_amount'])) {
                        if ($params['max_today_amount'] > 0 && $params['min_today_amount'] > $params['max_today_amount']) {
                            $this->error('今日领取金额下限不能大于上限');
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
}
