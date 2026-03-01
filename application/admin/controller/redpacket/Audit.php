<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;
use app\common\library\RedPacketService;

/**
 * 审核管理
 */
class Audit extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\TaskParticipation;
        
        $this->view->assign('statusList', $this->model::$statusList);
        $this->view->assign('auditStatusList', $this->model::$auditStatusList);
    }
    
    /**
     * 待审核列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->with(['task', 'user'])
                ->where('status', 1) // 已完成待审核
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            foreach ($list as $row) {
                $row->visible(['id', 'order_no', 'user_id', 'task_id', 'status', 
                    'duration', 'progress', 'createtime', 'end_time',
                    'screenshot_urls', 'proof_data']);
                $row->visible(['task']);
                $row->visible(['user']);
            }
            
            $result = ['total' => $list->total(), 'rows' => $list->items()];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 查看详情
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
    
    /**
     * 审核通过
     */
    public function pass($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        
        $ids = $ids ? $ids : $this->request->post('ids');
        $remark = $this->request->post('remark', '');
        
        if (!$ids) {
            $this->error('请选择要审核的记录');
        }
        
        $service = new RedPacketService();
        $result = $service->manualAudit($ids, true, $this->auth->id, $this->auth->nickname, $remark);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 审核拒绝
     */
    public function reject($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        
        $ids = $ids ? $ids : $this->request->post('ids');
        $reason = $this->request->post('reason', '审核不通过');
        
        if (!$ids) {
            $this->error('请选择要拒绝的记录');
        }
        
        $service = new RedPacketService();
        $result = $service->manualAudit($ids, false, $this->auth->id, $this->auth->nickname, $reason);
        
        if ($result['success']) {
            $this->success($result['message']);
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量审核通过
     */
    public function batchPass()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error('请选择要审核的记录');
        }
        
        $ids = explode(',', $ids);
        $successCount = 0;
        $failCount = 0;
        
        $service = new RedPacketService();
        
        foreach ($ids as $id) {
            $result = $service->manualAudit($id, true, $this->auth->id, $this->auth->nickname);
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        $this->success("审核完成：通过 {$successCount} 条，失败 {$failCount} 条");
    }
}
