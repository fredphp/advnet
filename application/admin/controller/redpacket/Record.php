<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\RedPacketRecord as RedPacketRecordModel;
use think\Db;

/**
 * 红包领取记录
 */
class Record extends Backend
{
    /**
     * RedPacketRecord模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RedPacketRecordModel;
    }
    
    /**
     * 查看
     */
    public function index()
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->with(['task', 'user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            foreach ($list as $row) {
                $row->visible(['id', 'task_id', 'user_id', 'amount', 'status', 'status_text', 'createtime']);
                $row->visible(['task']);
                $row->visible(['user']);
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
        $row = $this->model->with(['task', 'task.resource', 'user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
