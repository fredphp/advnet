<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use think\Db;

/**
 * 白名单管理
 */
class Whitelist extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RiskWhitelist();
    }
    
    /**
     * 白名单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');
            
            $type = $this->request->get('type');
            $search = $this->request->get('search');
            
            $query = $this->model;
            
            if ($type) {
                $query->where('type', $type);
            }
            
            if ($search) {
                $query->whereLike('value', "%{$search}%");
            }
            
            $total = $query->count();
            $list = $this->model->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            return json(['total' => $total, 'rows' => $list]);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 添加白名单
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (!$params) {
                $this->error('参数不能为空');
            }
            
            // 检查是否已存在
            $exists = $this->model->where('type', $params['type'])
                ->where('value', $params['value'])
                ->find();
            
            if ($exists) {
                $this->error('该记录已存在');
            }
            
            $params['admin_id'] = $this->auth->id;
            $params['admin_name'] = $this->auth->username;
            $params['createtime'] = time();
            
            $result = $this->model->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 移除白名单
     */
    public function remove()
    {
        $ids = $this->request->post('ids');
        
        if (!$ids) {
            $this->error('请选择要移除的记录');
        }
        
        $this->model->destroy($ids);
        
        $this->success();
    }
}
