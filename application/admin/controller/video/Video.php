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

            // 处理视频URL：如果填写了链接，使用链接；否则使用上传的URL
            if (!empty($params['video_url_link'])) {
                $params['video_url'] = $params['video_url_link'];
            }
            unset($params['video_url_link']);

            // 验证视频URL
            if (empty($params['video_url'])) {
                $this->error('请上传视频或输入视频链接');
            }

            // 处理推荐设置
            $params['is_recommend'] = isset($params['is_recommend']) ? 1 : 0;
            $params['is_hot'] = isset($params['is_hot']) ? 1 : 0;
            $params['is_original'] = isset($params['is_original']) ? 1 : 0;
            
            // 处理奖励设置
            $params['reward_enabled'] = !empty($params['reward_coin']) ? 1 : 0;
            
            // 处理发布者：空值表示平台发布
            if (empty($params['user_id'])) {
                $params['user_id'] = null;
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
        
        // 获取发布者列表
        $authorList = \app\common\model\Author::where('status', 'normal')->column('id,name');
        $this->view->assign('authorList', $authorList);
        
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

            // 处理视频URL逻辑：
            // 1. 如果上传了新视频(video_url_new)，使用上传的视频
            // 2. 如果输入了新链接(video_url_link)，使用新链接
            // 3. 如果都没有，保留原视频URL
            $originalVideoUrl = $row->video_url;
            
            if (!empty($params['video_url_new'])) {
                // 用户上传了新视频
                $params['video_url'] = $params['video_url_new'];
            } elseif (!empty($params['video_url_link'])) {
                // 用户输入了新链接
                $params['video_url'] = $params['video_url_link'];
            } else {
                // 保留原视频URL
                $params['video_url'] = $originalVideoUrl;
            }
            
            // 清理临时字段
            unset($params['video_url_new']);
            unset($params['video_url_link']);

            // 验证视频URL（最终应该有值，要么新的要么原来的）
            if (empty($params['video_url'])) {
                $this->error('视频URL不能为空');
            }

            // 处理推荐设置
            $params['is_recommend'] = isset($params['is_recommend']) ? 1 : 0;
            $params['is_hot'] = isset($params['is_hot']) ? 1 : 0;
            $params['is_original'] = isset($params['is_original']) ? 1 : 0;
            
            // 处理奖励设置
            $params['reward_enabled'] = !empty($params['reward_coin']) ? 1 : 0;
            
            // 处理发布者：空值表示平台发布
            if (empty($params['user_id'])) {
                $params['user_id'] = null;
            }

            $params['updatetime'] = time();
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        // 获取发布者列表
        $authorList = \app\common\model\Author::where('status', 'normal')->column('id,name');
        $this->view->assign('authorList', $authorList);
        
        // 获取当前发布者名称
        $authorName = '平台发布';
        if (!empty($row['user_id'])) {
            $author = \app\common\model\Author::get($row['user_id']);
            if ($author) {
                $authorName = $author->name;
            }
        }
        $this->view->assign('authorName', $authorName);
        
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
                     AVG(watch_duration) as avg_duration, SUM(reward_coin) as total_coin')
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
            ->field('u.id, u.username, u.nickname, COUNT(*) as watch_count, SUM(vwr.reward_coin) as coin_earned')
            ->where('vwr.video_id', $videoId)
            ->group('vwr.user_id')
            ->order('watch_count', 'desc')
            ->limit(20)
            ->select();

        $this->view->assign('video', $video->toArray());
        $this->view->assign('watch_stats', $watchStats ?: []);
        $this->view->assign('daily_stats', $dailyStats ?: []);
        $this->view->assign('top_viewers', $topViewers ?: []);
        
        return $this->view->fetch();
    }

    /**
     * 选择视频（弹窗选择）
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            return $this->index();
        }
        
        $collectionId = $this->request->get('collection_id', 0);
        $this->view->assign('collection_id', $collectionId);
        return $this->view->fetch();
    }
}
