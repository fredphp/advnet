<?php

namespace app\admin\controller\videoreward;

use app\common\controller\Backend;
use think\Db;

/**
 * 观看记录管理
 */
class VideoWatchRecord extends Backend
{
    /**
     * VideoWatchRecord模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoWatchRecord;
        
        $this->view->assign('isCompletedList', $this->model::$isCompletedList);
        $this->view->assign('rewardStatusList', $this->model::$rewardStatusList);
    }
    
    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->with(['user', 'video'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            foreach ($list as $row) {
                $row->visible(['id', 'user_id', 'video_id', 'watch_duration', 'watch_progress', 
                    'is_completed', 'reward_status', 'reward_coin', 'createtime']);
                $row->visible(['user']);
                $row->visible(['video']);
            }
            
            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->with(['user', 'video', 'rule'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        if ($this->request->isAjax()) {
            $this->success('', null, $row);
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
