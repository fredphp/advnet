<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 视频管理
 */
class Video extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\Video();
    }

    /**
     * 视频列表
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

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加视频
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
     * 编辑视频
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
     * 删除视频
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
            $this->error(__('删除失败'));
        }
    }

    /**
     * 上/下架
     */
    public function status($ids = '')
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $row->status = $row->status == 1 ? 0 : 1;
        $row->updatetime = time();
        $row->save();

        $this->success();
    }

    /**
     * 批量上架
     */
    public function batchOnline()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $this->model->where('id', 'in', $ids)->update([
            'status' => 1,
            'updatetime' => time()
        ]);

        $this->success();
    }

    /**
     * 批量下架
     */
    public function batchOffline()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $this->model->where('id', 'in', $ids)->update([
            'status' => 0,
            'updatetime' => time()
        ]);

        $this->success();
    }

    /**
     * 视频统计
     */
    public function stats($ids = null)
    {
        $videoId = $ids ?: $this->request->get('id');
        
        if (!$videoId) {
            $this->error('请指定视频ID');
        }

        $video = $this->model->get($videoId);
        if (!$video) {
            $this->error('视频不存在');
        }

        // 观看统计
        $watchStats = Db::name('video_watch_record')
            ->where('video_id', $videoId)
            ->field('COUNT(*) as total_watches, COUNT(DISTINCT user_id) as unique_viewers, 
                     AVG(watch_duration) as avg_duration, SUM(coin_earned) as total_coin')
            ->find();

        // 每日统计
        $dailyStats = Db::name('video_watch_record')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, 
                     COUNT(*) as watches, COUNT(DISTINCT user_id) as viewers')
            ->where('video_id', $videoId)
            ->where('createtime', '>', time() - 30 * 86400)
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 用户观看排行
        $topViewers = Db::name('video_watch_record')
            ->alias('vwr')
            ->join('user u', 'u.id = vwr.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, COUNT(*) as watch_count, SUM(vwr.coin_earned) as coin_earned')
            ->where('vwr.video_id', $videoId)
            ->group('vwr.user_id')
            ->order('watch_count', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'video' => $video,
            'watch_stats' => $watchStats,
            'daily_stats' => $dailyStats,
            'top_viewers' => $topViewers,
        ]);
    }
}

/**
 * 视频合集管理
 */
class Collection extends Backend
{
    protected $model = null;

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
     * 合集视频管理
     */
    public function videos($ids = null)
    {
        $collectionId = $ids ?: $this->request->get('id');
        
        if (!$collectionId) {
            $this->error('请指定合集ID');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            $total = Db::name('video_collection_item')
                ->where('collection_id', $collectionId)
                ->count();

            $list = Db::name('video_collection_item vci')
                ->join('video v', 'v.id = vci.video_id', 'LEFT')
                ->field('vci.*, v.title, v.cover, v.duration, v.coin_reward')
                ->where('vci.collection_id', $collectionId)
                ->order('vci.sort_order', 'asc')
                ->limit($offset, $limit)
                ->select();

            return json(['total' => $total, 'rows' => $list]);
        }

        $this->view->assign('collection_id', $collectionId);
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
            ->max('sort_order') ?: 0;

        foreach ($videoIds as $videoId) {
            if (!in_array($videoId, $existsIds)) {
                $sortOrder++;
                $addData[] = [
                    'collection_id' => $collectionId,
                    'video_id' => $videoId,
                    'sort_order' => $sortOrder,
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

/**
 * 视频观看记录
 */
class WatchRecord extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoWatchRecord();
    }

    /**
     * 观看记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('vwr')
                ->join('user u', 'u.id = vwr.user_id', 'LEFT')
                ->join('video v', 'v.id = vwr.video_id', 'LEFT')
                ->field('vwr.*, u.username, u.nickname, v.title')
                ->where($where)
                ->order("vwr.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 观看统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        $totalStats = Db::name('video_watch_record')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_watches, COUNT(DISTINCT user_id) as unique_users,
                     COUNT(DISTINCT video_id) as unique_videos, SUM(coin_earned) as total_coin')
            ->find();

        $dailyStats = Db::name('video_watch_record')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as watches, COUNT(DISTINCT user_id) as users,
                     SUM(coin_earned) as coin')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        $hotVideos = Db::name('video_watch_record')
            ->alias('vwr')
            ->join('video v', 'v.id = vwr.video_id', 'LEFT')
            ->field('v.id, v.title, COUNT(*) as watch_count, SUM(vwr.coin_earned) as total_coin')
            ->where('vwr.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('vwr.video_id')
            ->order('watch_count', 'desc')
            ->limit(20)
            ->select();

        $activeUsers = Db::name('video_watch_record')
            ->alias('vwr')
            ->join('user u', 'u.id = vwr.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, COUNT(*) as watch_count, SUM(vwr.coin_earned) as total_coin')
            ->where('vwr.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('vwr.user_id')
            ->order('watch_count', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'daily_stats' => $dailyStats,
            'hot_videos' => $hotVideos,
            'active_users' => $activeUsers,
        ]);
    }
}

/**
 * 视频奖励规则
 */
class RewardRule extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\VideoRewardRule();
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    public function toggle($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $row->status = $row->status == 1 ? 0 : 1;
        $row->save();

        $this->success();
    }
}
