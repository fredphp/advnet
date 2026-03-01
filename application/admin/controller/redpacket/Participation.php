<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 参与记录
 */
class Participation extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\TaskParticipation;
        
        $this->view->assign('statusList', $this->model::$statusList);
    }
    
    /**
     * 查看列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->with(['task', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
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
        $row = $this->model->with(['task', 'user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        $row['screenshot_urls'] = json_decode($row['screenshot_urls'], true) ?: [];
        $row['proof_data'] = json_decode($row['proof_data'], true) ?: [];
        $row['device_info'] = json_decode($row['device_info'], true) ?: [];
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
