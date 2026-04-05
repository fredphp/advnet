<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use app\common\model\AdIncomeLog;
use app\common\model\AdIncomeLogSplit;
use think\Db;

/**
 * 广告收益记录管理（支持分表查询）
 */
class Log extends Backend
{
    protected $dataLimit = false;
    
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AdIncomeLog();
    }
    
    /**
     * 收益记录列表（跨分表分页查询）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // ★ 使用分表模型进行跨表分页查询
            $result = AdIncomeLogSplit::paginateAllTables($where, $sort, $order, $offset, $limit);
            $total = $result['total'];
            $list = $result['rows'];
            
            foreach ($list as &$row) {
                $row['ad_type_text'] = AdIncomeLog::$typeList[$row['ad_type']] ?? '未知';
                $row['ad_provider_text'] = AdIncomeLog::$providerList[$row['ad_provider']] ?? '未知';
                $row['status_text'] = AdIncomeLog::$statusList[$row['status']] ?? '未知';
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 收益详情
     */
    public function detail($ids = null)
    {
        // ★ 跨分表查找记录
        $row = AdIncomeLogSplit::findById($ids);
        
        if (!$row) {
            $this->error('未找到记录');
        }
        
        // 关联用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->field('username, nickname, mobile, avatar')->find();
        if ($user) {
            $row = array_merge($row, $user);
        }
        
        $row['ad_type_text'] = AdIncomeLog::$typeList[$row['ad_type']] ?? '未知';
        $row['ad_provider_text'] = AdIncomeLog::$providerList[$row['ad_provider']] ?? '未知';
        $row['status_text'] = AdIncomeLog::$statusList[$row['status']] ?? '未知';
        $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
        $row['updatetime_text'] = $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '';
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 统计概览（跨分表统计）
     */
    public function summary()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $monthStart = strtotime(date('Y-m-01'));
        
        $todayStats = AdIncomeLogSplit::getRangeStats($todayStart, time());
        $monthStats = AdIncomeLogSplit::getRangeStats($monthStart, time());
        $totalStats = AdIncomeLogSplit::getRangeStats(0, time());
        
        $providerStats = AdIncomeLogSplit::getGroupStats($monthStart, time(), 'ad_provider');
        $typeStats = AdIncomeLogSplit::getGroupStats($monthStart, time(), 'ad_type');
        
        $this->success('', null, [
            'today' => $todayStats,
            'month' => $monthStats,
            'total' => $totalStats,
            'providers' => $providerStats,
            'types' => $typeStats,
        ]);
    }
    
    /**
     * 禁用添加
     */
    public function add()
    {
        $this->error('禁止手动添加');
    }
    
    /**
     * 禁用编辑
     */
    public function edit($ids = null)
    {
        $this->error('禁止手动编辑');
    }
}
