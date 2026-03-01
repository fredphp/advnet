<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 红包任务管理
 */
class Task extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RedPacketTask;
        
        $this->view->assign('taskTypeList', $this->model::$taskTypeList);
        $this->view->assign('amountTypeList', $this->model::$amountTypeList);
        $this->view->assign('verifyMethodList', $this->model::$verifyMethodList);
        $this->view->assign('auditTypeList', $this->model::$auditTypeList);
        $this->view->assign('statusList', $this->model::$statusList);
        
        // 获取分类列表
        $categories = Db::name('task_category')->where('status', 1)->column('name', 'id');
        $this->view->assign('categories', $categories);
    }
    
    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
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
                
                $result = false;
                Db::startTrans();
                try {
                    // 处理时间
                    $params['start_time'] = $params['start_time'] ? strtotime($params['start_time']) : null;
                    $params['end_time'] = $params['end_time'] ? strtotime($params['end_time']) : null;
                    
                    // 处理JSON字段
                    if (isset($params['task_params']) && is_array($params['task_params'])) {
                        $params['task_params'] = json_encode($params['task_params']);
                    }
                    if (isset($params['images']) && is_array($params['images'])) {
                        $params['images'] = json_encode($params['images']);
                    }
                    if (isset($params['verify_params']) && is_array($params['verify_params'])) {
                        $params['verify_params'] = json_encode($params['verify_params']);
                    }
                    
                    // 初始化剩余数量
                    $params['remain_count'] = $params['total_count'] ?? 0;
                    $params['remain_amount'] = $params['total_amount'] ?? 0;
                    
                    // 验证
                    $this->validateParams($params);
                    
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
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
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $params = $this->preExcludeFields($params);
                
                $result = false;
                Db::startTrans();
                try {
                    // 处理时间
                    $params['start_time'] = $params['start_time'] ? strtotime($params['start_time']) : null;
                    $params['end_time'] = $params['end_time'] ? strtotime($params['end_time']) : null;
                    
                    // 处理JSON字段
                    if (isset($params['task_params']) && is_array($params['task_params'])) {
                        $params['task_params'] = json_encode($params['task_params']);
                    }
                    if (isset($params['images']) && is_array($params['images'])) {
                        $params['images'] = json_encode($params['images']);
                    }
                    if (isset($params['verify_params']) && is_array($params['verify_params'])) {
                        $params['verify_params'] = json_encode($params['verify_params']);
                    }
                    
                    // 验证
                    $this->validateParams($params);
                    
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
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
        
        // 处理显示数据
        $row['start_time'] = $row['start_time'] ? date('Y-m-d H:i:s', $row['start_time']) : '';
        $row['end_time'] = $row['end_time'] ? date('Y-m-d H:i:s', $row['end_time']) : '';
        $row['task_params'] = json_decode($row['task_params'], true) ?: [];
        $row['images'] = json_decode($row['images'], true) ?: [];
        $row['verify_params'] = json_decode($row['verify_params'], true) ?: [];
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 验证参数
     */
    protected function validateParams($params)
    {
        // 金额验证
        if ($params['amount_type'] == 'random') {
            if (empty($params['min_amount']) || empty($params['max_amount'])) {
                throw new ValidateException('随机金额必须设置最小和最大金额');
            }
            if ($params['min_amount'] >= $params['max_amount']) {
                throw new ValidateException('最小金额必须小于最大金额');
            }
        } else {
            if (empty($params['single_amount'])) {
                throw new ValidateException('固定金额不能为空');
            }
        }
        
        // 时长要求验证
        if (in_array($params['task_type'], ['play_game', 'watch_video']) && empty($params['required_duration'])) {
            throw new ValidateException('该任务类型必须设置要求时长');
        }
        
        // 任务链接验证
        if (in_array($params['task_type'], ['download_app', 'mini_program']) && empty($params['task_url']) && empty($params['task_params'])) {
            throw new ValidateException('该任务类型必须设置任务链接或参数');
        }
        
        return true;
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
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
