<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;

/**
 * 视频合集管理
 */
class Collection extends Backend
{
    protected $model = null;
    
    // 排序字段映射：weigh -> sort
    protected $sortFieldMapping = ['weigh' => 'sort'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoCollection();
    }

    /**
     * 合集列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as &$item) {
                $item['video_count'] = Db::name('video_collection_item')
                    ->where('collection_id', $item['id'])
                    ->count();
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加合集
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            $params['createtime'] = time();
            $params['updatetime'] = time();

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑合集
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            $params['updatetime'] = time();
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 删除合集
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('参数错误'));
        }
        $ids = $ids ? $ids : $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }
        $pk = $this->model->getPk();
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($list as $item) {
                // 删除合集关联的视频
                Db::name('video_collection_item')->where('collection_id', $item->id)->delete();
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
            $this->error(__('删除失败'));
        }
    }

    /**
     * 合集视频管理
     */
    public function videos($ids = null)
    {
        $collectionId = $ids ?: $this->request->get('id');
        
        if (!$collectionId) {
            $this->error('请指定合集ID');
        }

        $collection = $this->model->get($collectionId);
        if (!$collection) {
            $this->error('合集不存在');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            $total = Db::name('video_collection_item')
                ->where('collection_id', $collectionId)
                ->count();

            $list = Db::name('video_collection_item')
                ->alias('vci')
                ->join('video v', 'v.id = vci.video_id', 'LEFT')
                ->field('vci.*, v.title, v.cover, v.duration, v.reward_coin')
                ->where('vci.collection_id', $collectionId)
                ->order('vci.sort', 'asc')
                ->limit($offset, $limit)
                ->select();

            return json(['total' => $total, 'rows' => $list]);
        }

        $this->view->assign('collection', $collection);
        return $this->view->fetch();
    }

    /**
     * 添加视频到合集
     */
    public function addVideo()
    {
        $collectionId = $this->request->post('collection_id');
        $videoIds = $this->request->post('video_ids/a');

        if (!$collectionId || !$videoIds) {
            $this->error('参数错误');
        }

        $existsIds = Db::name('video_collection_item')
            ->where('collection_id', $collectionId)
            ->column('video_id');

        $addData = [];
        $sortOrder = Db::name('video_collection_item')
            ->where('collection_id', $collectionId)
            ->max('sort') ?: 0;

        foreach ($videoIds as $videoId) {
            if (!in_array($videoId, $existsIds)) {
                $sortOrder++;
                $addData[] = [
                    'collection_id' => $collectionId,
                    'video_id' => $videoId,
                    'sort' => $sortOrder,
                    'createtime' => time(),
                ];
            }
        }

        if ($addData) {
            Db::name('video_collection_item')->insertAll($addData);
        }

        $this->success();
    }

    /**
     * 从合集移除视频
     */
    public function removeVideo()
    {
        $ids = $this->request->post('ids');
        
        if (!$ids) {
            $this->error('参数错误');
        }

        Db::name('video_collection_item')->where('id', 'in', $ids)->delete();
        $this->success();
    }
}
