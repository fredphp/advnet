<?php

namespace app\admin\controller\videoreward;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 视频收益规则管理
 */
class VideoRewardRule extends Backend
{
    /**
     * VideoRewardRule模型对象
     * @var \app\common\model\VideoRewardRule
     */
    protected $model = null;
    
    protected $noNeedLogin = [];
    protected $noNeedRight = [];
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoRewardRule;
        
        $this->view->assign('conditionTypeList', $this->model::$conditionTypeList);
        $this->view->assign('rewardTypeList', $this->model::$rewardTypeList);
        $this->view->assign('scopeTypeList', $this->model::$scopeTypeList);
        $this->view->assign('statusList', $this->model::$statusList);
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
                
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                
                $result = false;
                Db::startTrans();
                try {
                    // 是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace('\\model\\', '\\validate\\', get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    
                    // 处理时间
                    $params['start_time'] = $params['start_time'] ? strtotime($params['start_time']) : null;
                    $params['end_time'] = $params['end_time'] ? strtotime($params['end_time']) : null;
                    
                    // 验证规则配置
                    $this->validateRule($params);
                    
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
                    // 是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace('\\model\\', '\\validate\\', get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    
                    // 处理时间
                    $params['start_time'] = $params['start_time'] ? strtotime($params['start_time']) : null;
                    $params['end_time'] = $params['end_time'] ? strtotime($params['end_time']) : null;
                    
                    // 验证规则配置
                    $this->validateRule($params);
                    
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
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 验证规则配置
     */
    protected function validateRule($params)
    {
        // 时长领取必须设置时长
        if ($params['condition_type'] == 'duration') {
            if (empty($params['watch_duration']) && empty($params['watch_duration_ratio'])) {
                throw new ValidateException('时长领取必须设置观看时长或时长占比');
            }
        }
        
        // 集数领取必须设置集数
        if ($params['condition_type'] == 'count') {
            if (empty($params['watch_count'])) {
                throw new ValidateException('集数领取必须设置观看集数');
            }
        }
        
        // 随机奖励必须设置范围
        if ($params['reward_type'] == 'random') {
            if (empty($params['reward_min']) || empty($params['reward_max'])) {
                throw new ValidateException('随机奖励必须设置最小和最大金额');
            }
            if ($params['reward_min'] >= $params['reward_max']) {
                throw new ValidateException('最小金额必须小于最大金额');
            }
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
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
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
    
    /**
     * 获取分类列表(Selectpage)
     */
    public function category()
    {
        $list = Db::name('category')->where('status', 1)->field('id,name')->select();
        return json(['list' => $list]);
    }
    
    /**
     * 获取视频列表(Selectpage)
     */
    public function video()
    {
        $keyword = $this->request->get('keyword');
        $query = Db::name('video')->where('status', 1);
        
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        }
        
        $list = $query->field('id,title')->limit(20)->select();
        return json(['list' => $list]);
    }
    
    /**
     * 获取合集列表(Selectpage)
     */
    public function collection()
    {
        $keyword = $this->request->get('keyword');
        $query = Db::name('video_collection')->where('status', 1);
        
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        }
        
        $list = $query->field('id,title')->limit(20)->select();
        return json(['list' => $list]);
    }
}
