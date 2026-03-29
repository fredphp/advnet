<?php

namespace app\admin\controller\videoreward;

use app\common\controller\Backend;
use think\Db;

/**
 * 视频合集管理
 */
class VideoCollection extends Backend
{
    /**
     * VideoCollection模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoCollection;
        
        $this->view->assign('statusList', $this->model::$statusList);
        $this->view->assign('rewardTypeList', $this->model::$rewardTypeList);
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
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
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
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
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
    
    /**
     * 管理合集视频
     */
    public function videos($ids = null)
    {
        $collection = $this->model->get($ids);
        if (!$collection) {
            $this->error('合集不存在');
        }
        
        if ($this->request->isPost()) {
            $videoIds = $this->request->post('video_ids/a', []);
            
            Db::startTrans();
            try {
                // 删除旧关联
                Db::name('video_collection_item')->where('collection_id', $ids)->delete();
                
                // 添加新关联
                foreach ($videoIds as $index => $videoId) {
                    Db::name('video_collection_item')->insert([
                        'collection_id' => $ids,
                        'video_id' => $videoId,
                        'episode' => $index + 1,
                        'sort' => $index,
                        'createtime' => time(),
                    ]);
                }
                
                // 更新合集统计
                $collection->updateStats();
                
                Db::commit();
                $this->success();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        // 获取已关联的视频
        $videos = Db::name('video_collection_item')
            ->alias('vci')
            ->join('video v', 'v.id = vci.video_id')
            ->where('vci.collection_id', $ids)
            ->field('v.id, v.title, v.duration, vci.episode')
            ->order('vci.episode', 'asc')
            ->select();
        
        $this->view->assign('collection', $collection);
        $this->view->assign('videos', $videos);
        return $this->view->fetch();
    }
}
