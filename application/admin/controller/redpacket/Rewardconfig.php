<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketRewardConfig as RedPacketRewardConfigModel;
use think\Db;
use think\Exception;

/**
 * 红包奖励配置管理
 * 统一配置：时间段 + 今日金额限制 + 奖励金额
 */
class Rewardconfig extends Backend
{
    /**
     * RedPacketRewardConfig模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketRewardConfigModel;

        // 状态列表
        $statusList = RedPacketRewardConfigModel::$statusList;
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

            // 默认按权重排序
            if ($sort == 'sort') {
                $sort = 'weigh';
                $order = 'desc';
            }

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }

        // 获取最高金额限制配置
        $maxRewardLimit = RedPacketRewardConfigModel::getMaxRewardLimit();
        $this->view->assign('maxRewardLimit', $maxRewardLimit);

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
                    // 验证配置名称
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

                    // 验证今日金额限制
                    $minTodayAmount = intval($params['min_today_amount'] ?? 0);
                    $maxTodayAmount = intval($params['max_today_amount'] ?? 0);
                    if ($maxTodayAmount > 0 && $minTodayAmount > $maxTodayAmount) {
                        $this->error('今日领取金额下限不能大于上限');
                    }

                    // 获取最高金额限制
                    $maxLimit = RedPacketRewardConfigModel::getMaxRewardLimit();

                    // 验证老用户基础奖励
                    if (!isset($params['base_min_reward']) || !isset($params['base_max_reward'])) {
                        $this->error('老用户基础奖励不能为空');
                    }
                    if ($params['base_min_reward'] > $params['base_max_reward']) {
                        $this->error('老用户基础奖励下限不能大于上限');
                    }
                    if ($params['base_max_reward'] > $maxLimit) {
                        $this->error('老用户基础奖励上限不能超过' . number_format($maxLimit));
                    }

                    // 验证老用户累加奖励
                    if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                        if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                            $this->error('老用户累加奖励下限不能大于上限');
                        }
                        if ($params['accumulate_max_reward'] > $maxLimit) {
                            $this->error('老用户累加奖励上限不能超过' . number_format($maxLimit));
                        }
                    }

                    // 验证新用户基础奖励
                    if (isset($params['new_user_base_min']) && isset($params['new_user_base_max'])) {
                        if ($params['new_user_base_min'] > $params['new_user_base_max']) {
                            $this->error('新用户基础奖励下限不能大于上限');
                        }
                        if ($params['new_user_base_max'] > $maxLimit) {
                            $this->error('新用户基础奖励上限不能超过' . number_format($maxLimit));
                        }
                    }

                    // 验证新用户累加奖励
                    if (isset($params['new_user_accumulate_min']) && isset($params['new_user_accumulate_max'])) {
                        if ($params['new_user_accumulate_min'] > $params['new_user_accumulate_max']) {
                            $this->error('新用户累加奖励下限不能大于上限');
                        }
                        if ($params['new_user_accumulate_max'] > $maxLimit) {
                            $this->error('新用户累加奖励上限不能超过' . number_format($maxLimit));
                        }
                    }

                    $result = $this->model->allowField(true)->save($params);
                    
                    // 刷新配置缓存
                    RedPacketRewardConfigModel::refreshCache();
                    
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

        // 获取最高金额限制配置
        $maxRewardLimit = RedPacketRewardConfigModel::getMaxRewardLimit();
        $this->view->assign('maxRewardLimit', $maxRewardLimit);

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
                    }

                    // 验证今日金额限制
                    $minTodayAmount = intval($params['min_today_amount'] ?? $row->min_today_amount);
                    $maxTodayAmount = intval($params['max_today_amount'] ?? $row->max_today_amount);
                    if ($maxTodayAmount > 0 && $minTodayAmount > $maxTodayAmount) {
                        $this->error('今日领取金额下限不能大于上限');
                    }

                    // 获取最高金额限制
                    $maxLimit = RedPacketRewardConfigModel::getMaxRewardLimit();

                    // 验证各项奖励金额
                    if (isset($params['base_min_reward']) && isset($params['base_max_reward'])) {
                        if ($params['base_min_reward'] > $params['base_max_reward']) {
                            $this->error('老用户基础奖励下限不能大于上限');
                        }
                        if ($params['base_max_reward'] > $maxLimit) {
                            $this->error('老用户基础奖励上限不能超过' . number_format($maxLimit));
                        }
                    }

                    if (isset($params['accumulate_min_reward']) && isset($params['accumulate_max_reward'])) {
                        if ($params['accumulate_min_reward'] > $params['accumulate_max_reward']) {
                            $this->error('老用户累加奖励下限不能大于上限');
                        }
                    }

                    if (isset($params['new_user_base_min']) && isset($params['new_user_base_max'])) {
                        if ($params['new_user_base_min'] > $params['new_user_base_max']) {
                            $this->error('新用户基础奖励下限不能大于上限');
                        }
                    }

                    if (isset($params['new_user_accumulate_min']) && isset($params['new_user_accumulate_max'])) {
                        if ($params['new_user_accumulate_min'] > $params['new_user_accumulate_max']) {
                            $this->error('新用户累加奖励下限不能大于上限');
                        }
                    }

                    $result = $row->allowField(true)->save($params);
                    
                    // 刷新配置缓存
                    RedPacketRewardConfigModel::refreshCache();
                    
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

        // 获取最高金额限制配置
        $maxRewardLimit = RedPacketRewardConfigModel::getMaxRewardLimit();
        $this->view->assign('maxRewardLimit', $maxRewardLimit);

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
                
                // 刷新配置缓存
                RedPacketRewardConfigModel::refreshCache();
                
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
     * 手动刷新缓存
     */
    public function refreshCache()
    {
        $result = RedPacketRewardConfigModel::refreshCache();
        
        if ($result) {
            $this->success('缓存刷新成功');
        } else {
            $this->error('缓存刷新失败');
        }
    }

    /**
     * 获取当前配置（API接口）
     */
    public function current()
    {
        $todayAmount = $this->request->get('today_amount', 0);
        $isNewUser = $this->request->get('is_new_user', 0);

        $config = RedPacketRewardConfigModel::getFullConfig($todayAmount, null, $isNewUser);

        $this->success('获取成功', null, $config);
    }
}
