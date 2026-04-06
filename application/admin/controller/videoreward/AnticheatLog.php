<?php

namespace app\admin\controller\videoreward;

use app\common\controller\Backend;
use think\Db;

/**
 * 防刷日志
 */
class AnticheatLog extends Backend
{
    /**
     * AnticheatLog模型对象
     */
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\AnticheatLog;
        
        $this->view->assign('typeList', $this->model::$typeList);
    }
    
    /**
     * 查看
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $list = $this->model
                ->with(['user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            
            foreach ($list as $row) {
                $row->visible(['id', 'user_id', 'type', 'data', 'ip', 'createtime']);
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
        $row = $this->model->with(['user'])->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 解析JSON数据
        $row->data = json_decode($row->data, true);
        
        if ($this->request->isAjax()) {
            $this->success('', null, $row);
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
     * 统计
     */
    public function stats()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            // 按类型统计
            $typeStats = Db::name('anticheat_log')
                ->where('createtime', '>=', strtotime($startDate))
                ->where('createtime', '<=', strtotime($endDate . ' 23:59:59'))
                ->field('type, COUNT(*) as count')
                ->group('type')
                ->select();
            
            // 按日期统计
            $dailyStats = Db::name('anticheat_log')
                ->where('createtime', '>=', strtotime($startDate))
                ->where('createtime', '<=', strtotime($endDate . ' 23:59:59'))
                ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, COUNT(*) as count')
                ->group('date')
                ->order('date', 'asc')
                ->select();
            
            $this->success('', null, [
                'type_stats' => $typeStats,
                'daily_stats' => $dailyStats,
            ]);
        }
        
        return $this->view->fetch();
    }
}
