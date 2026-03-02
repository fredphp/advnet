<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 红包任务资源管理
 */
class Resource extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RedPacketResource();
    }

    /**
     * 资源列表
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

            // 转换为数组
            $data = [];
            foreach ($list as $item) {
                $row = $item->toArray();
                $row['type_text'] = \app\common\model\RedPacketResource::$typeList[$row['type']] ?? '';
                $data[] = $row;
            }

            return json(['total' => $total, 'rows' => $data]);
        }
        return $this->view->fetch();
    }

    /**
     * 添加资源
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }

            // 处理图片数组
            if (isset($params['images']) && is_array($params['images'])) {
                $params['images'] = json_encode($params['images']);
            }

            // 处理扩展参数
            if (isset($params['params']) && is_array($params['params'])) {
                $params['params'] = json_encode($params['params']);
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

        // 获取资源类型列表
        $this->view->assign('typeList', \app\common\model\RedPacketResource::$typeList);
        return $this->view->fetch();
    }

    /**
     * 编辑资源
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

            // 处理图片数组
            if (isset($params['images']) && is_array($params['images'])) {
                $params['images'] = json_encode($params['images']);
            }

            // 处理扩展参数
            if (isset($params['params']) && is_array($params['params'])) {
                $params['params'] = json_encode($params['params']);
            }

            $params['updatetime'] = time();

            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        // 处理图片和参数显示
        $row['images'] = $row['images'] ? json_decode($row['images'], true) : [];
        $row['params'] = $row['params'] ? json_decode($row['params'], true) : [];

        $this->view->assign('row', $row);
        $this->view->assign('typeList', \app\common\model\RedPacketResource::$typeList);
        return $this->view->fetch();
    }

    /**
     * 删除资源
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

    /**
     * 根据类型获取资源列表（供selectpage使用）
     */
    public function select()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);
        
        // 搜索关键词
        $word = (array)$this->request->request("q_word/a");
        // 当前页
        $page = $this->request->request("pageNumber", 1, 'intval');
        // 分页大小
        $pagesize = $this->request->request("pageSize", 10, 'intval');
        
        // 解析资源类型参数
        $resourceType = null;
        
        // 方式1: custom[type] - selectpage标准方式
        $custom = $this->request->request("custom");
        if ($custom) {
            if (is_string($custom)) {
                $customArr = json_decode($custom, true);
            } else {
                $customArr = (array)$custom;
            }
            if (is_array($customArr) && isset($customArr['type']) && $customArr['type']) {
                $resourceType = $customArr['type'];
            }
        }
        
        // 方式2: 直接type参数
        if (!$resourceType) {
            $typeParam = $this->request->request("type");
            if ($typeParam) {
                $resourceType = $typeParam;
            }
        }
        
        // 方式3: params参数(JSON)
        if (!$resourceType) {
            $paramsParam = $this->request->request("params");
            if ($paramsParam) {
                $paramsArr = is_string($paramsParam) ? json_decode($paramsParam, true) : (array)$paramsParam;
                if (is_array($paramsArr) && isset($paramsArr['type']) && $paramsArr['type']) {
                    $resourceType = $paramsArr['type'];
                }
            }
        }
        
        // 方式4: data参数(JSON)
        if (!$resourceType) {
            $dataParam = $this->request->request("data");
            if ($dataParam) {
                $dataArr = is_string($dataParam) ? json_decode($dataParam, true) : (array)$dataParam;
                if (is_array($dataArr) && isset($dataArr['type']) && $dataArr['type']) {
                    $resourceType = $dataArr['type'];
                }
            }
        }
        
        // 构建查询条件 - 使用数组方式，避免链式调用问题
        $where = ['status' => 1];
        
        // 应用资源类型过滤
        if ($resourceType) {
            $where['type'] = $resourceType;
        }
        
        // 先计算总数
        $total = $this->model->where($where)->count();
        
        // 再查询列表 - 重新构建查询
        $list = $this->model->where($where)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->page($page, $pagesize)
            ->select();
        
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'logo' => $item->logo,
                'url' => $item->url,
                'package_name' => $item->package_name,
                'app_id' => $item->app_id,
                'video_id' => $item->video_id,
            ];
        }
        
        // selectpage格式返回
        return json(['list' => $data, 'total' => $total]);
    }

    /**
     * 获取资源详情
     */
    public function detail($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $data = $row->getFormattedData();
        $this->success('获取成功', null, $data);
    }
}
