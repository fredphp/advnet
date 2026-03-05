<?php

namespace app\admin\controller\video;

use app\common\controller\Backend;
use app\common\model\Author as AuthorModel;
use think\Db;
use think\Exception;

/**
 * 发布者/作者管理
 */
class Author extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AuthorModel;

        // 状态列表
        $statusList = AuthorModel::$statusList;
        $this->view->assign('statusList', $statusList);

        // 认证状态列表
        $verifyStatusList = [
            0 => '未认证',
            1 => '已认证',
            2 => '认证中'
        ];
        $this->view->assign('verifyStatusList', $verifyStatusList);

        // 认证类型列表
        $verifyTypeList = [
            'personal' => '个人认证',
            'enterprise' => '企业认证',
            'media' => '媒体认证',
            'government' => '政府认证',
            'other' => '其他认证'
        ];
        $this->view->assign('verifyTypeList', $verifyTypeList);
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
                ->order('id', 'desc')
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
                        $this->error('作者名称不能为空');
                    }

                    // 检查名称是否重复
                    $exists = $this->model->where('name', $params['name'])->find();
                    if ($exists) {
                        $this->error('作者名称已存在');
                    }

                    // 处理IP地址
                    if (empty($params['ip'])) {
                        $params['ip'] = $this->request->ip();
                    }

                    // 初始化统计数据
                    $params['video_count'] = $params['video_count'] ?? 0;
                    $params['total_views'] = $params['total_views'] ?? 0;
                    $params['total_likes'] = $params['total_likes'] ?? 0;
                    $params['total_coins'] = $params['total_coins'] ?? 0;

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
                    if (empty($params['name'])) {
                        $this->error('作者名称不能为空');
                    }

                    // 检查名称是否重复（排除自己）
                    $exists = $this->model->where('name', $params['name'])
                        ->where('id', '<>', $row->id)
                        ->find();
                    if ($exists) {
                        $this->error('作者名称已存在');
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
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

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
                // 检查是否有关联视频
                $videoCount = Db::name('video')->where('user_id', $item->id)->count();
                if ($videoCount > 0) {
                    $this->error('发布者"' . $item->name . '"下有' . $videoCount . '个视频，不能删除');
                }
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

    /**
     * Selectpage搜索
     * 用于弹窗选择发布者
     */
    public function selectpage()
    {
        return parent::selectpage();
    }

    /**
     * 更新统计数据
     */
    public function updateStats($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }

        $ids = $ids ? $ids : $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        foreach ($ids as $id) {
            $author = $this->model->get($id);
            if (!$author) {
                continue;
            }

            // 统计视频数量
            $videoCount = Db::name('video')->where('user_id', $id)->count();
            
            // 统计总播放量
            $totalViews = Db::name('video')->where('user_id', $id)->sum('view_count');
            
            // 统计总点赞数
            $totalLikes = Db::name('video')->where('user_id', $id)->sum('like_count');

            // 更新
            $author->video_count = $videoCount;
            $author->total_views = $totalViews;
            $author->total_likes = $totalLikes;
            $author->save();
        }

        $this->success('统计数据更新成功');
    }

    /**
     * 认证操作
     */
    public function verify($ids = '')
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if ($params) {
                $row->verify_status = $params['verify_status'] ?? 0;
                $row->verify_type = $params['verify_type'] ?? '';
                $row->verify_info = $params['verify_info'] ?? '';
                $row->save();
                $this->success();
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
