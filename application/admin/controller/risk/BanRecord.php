<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\AutoBanService;
use think\Db;

/**
 * 封禁记录管理
 */
class BanRecord extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\BanRecord();
    }
    
    /**
     * 封禁记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');
            
            // 筛选条件
            $banType = $this->request->get('ban_type');
            $status = $this->request->get('status');
            $banSource = $this->request->get('ban_source');
            $search = $this->request->get('search');
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            
            $query = Db::name('ban_record br')
                ->join('user u', 'u.id = br.user_id', 'LEFT')
                ->field('br.*, u.username, u.nickname, u.mobile');
            
            if ($banType) {
                $query->where('br.ban_type', $banType);
            }
            
            if ($status) {
                $query->where('br.status', $status);
            }
            
            if ($banSource) {
                $query->where('br.ban_source', $banSource);
            }
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('u.username', "%{$search}%")
                      ->whereOr('u.nickname', 'like', "%{$search}%")
                      ->whereOr('u.mobile', 'like', "%{$search}%");
                });
            }
            
            if ($startDate) {
                $query->where('br.createtime', '>=', strtotime($startDate));
            }
            
            if ($endDate) {
                $query->where('br.createtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            
            $total = $query->count();
            $list = $query->order("br.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();
            
            return json(['total' => $total, 'rows' => $list]);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 封禁详情
     */
    public function detail($ids = null)
    {
        $record = Db::name('ban_record br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->field('br.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('br.id', $ids)
            ->find();
        
        if (!$record) {
            $this->error('记录不存在');
        }
        
        // 获取用户当前风险状态
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $record['user_id'])
            ->find();
        
        // 获取该用户所有封禁记录
        $allRecords = Db::name('ban_record')
            ->where('user_id', $record['user_id'])
            ->order('createtime', 'desc')
            ->select();
        
        $this->success('', null, [
            'record' => $record,
            'risk_score' => $riskScore,
            'all_records' => $allRecords,
        ]);
    }
    
    /**
     * 手动解封
     */
    public function release($ids = null)
    {
        $record = $this->model->get($ids);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record->status != 'active') {
            $this->error('该记录已处理');
        }
        
        $reason = $this->request->post('reason', '管理员手动解封');
        
        $autoBanService = new AutoBanService();
        $result = $autoBanService->releaseBan($record->user_id, $reason, $this->auth->id);
        
        if ($result['success']) {
            $this->success();
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量解封
     */
    public function batchRelease()
    {
        $ids = $this->request->post('ids/a');
        $reason = $this->request->post('reason', '管理员批量解封');
        
        if (!$ids || !is_array($ids)) {
            $this->error('请选择要解封的记录');
        }
        
        $autoBanService = new AutoBanService();
        $success = 0;
        $failed = 0;
        
        foreach ($ids as $id) {
            $record = $this->model->get($id);
            if ($record && $record->status == 'active') {
                $result = $autoBanService->releaseBan($record->user_id, $reason, $this->auth->id);
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }
        
        $this->success("成功解封{$success}个，失败{$failed}个");
    }
    
    /**
     * 封禁统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $autoBanService = new AutoBanService();
        $stats = $autoBanService->getBanStatistics($startDate, $endDate);
        
        // 按日期统计
        $dailyStats = Db::name('ban_record')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, 
                     COUNT(*) as total,
                     SUM(CASE WHEN ban_type = "temporary" THEN 1 ELSE 0 END) as temporary,
                     SUM(CASE WHEN ban_type = "permanent" THEN 1 ELSE 0 END) as permanent,
                     SUM(CASE WHEN ban_source = "auto" THEN 1 ELSE 0 END) as auto_ban,
                     SUM(CASE WHEN ban_source = "manual" THEN 1 ELSE 0 END) as manual_ban')
            ->where('createtime', '>=', strtotime($startDate))
            ->where('createtime', '<=', strtotime($endDate . ' 23:59:59'))
            ->group('date')
            ->order('date', 'asc')
            ->select();
        
        // 当前封禁状态分布
        $statusDistribution = Db::name('ban_record')
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select();
        
        $this->success('', null, [
            'stats' => $stats,
            'daily_stats' => $dailyStats,
            'status_distribution' => $statusDistribution,
        ]);
    }
}
