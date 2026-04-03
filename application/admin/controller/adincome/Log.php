<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use app\common\model\AdIncomeLog;
use think\Db;

/**
 * 广告收益记录管理
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
     * 收益记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = Db::name('ad_income_log')->where($where)->count();
            $list = Db::name('ad_income_log')
                ->alias('ail')
                ->join('user u', 'u.id = ail.user_id', 'LEFT')
                ->field('ail.*, u.username, u.nickname, u.mobile')
                ->where($where)
                ->order("ail.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();
            
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
        $row = Db::name('ad_income_log')
            ->alias('ail')
            ->join('user u', 'u.id = ail.user_id', 'LEFT')
            ->field('ail.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('ail.id', $ids)
            ->find();
        
        if (!$row) {
            $this->error('未找到记录');
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
     * 统计概览
     */
    public function summary()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $monthStart = strtotime(date('Y-m-01'));
        
        $todayStats = Db::name('ad_income_log')
            ->where('status', 'in', [1, 2])
            ->where('createtime', '>=', $todayStart)
            ->field('COUNT(*) as count, SUM(user_amount_coin) as user_coin, SUM(platform_amount_coin) as platform_coin, SUM(amount_coin) as total_coin')
            ->find();
        
        $monthStats = Db::name('ad_income_log')
            ->where('status', 'in', [1, 2])
            ->where('createtime', '>=', $monthStart)
            ->field('COUNT(*) as count, SUM(user_amount_coin) as user_coin, SUM(platform_amount_coin) as platform_coin, SUM(amount_coin) as total_coin')
            ->find();
        
        $totalStats = Db::name('ad_income_log')
            ->where('status', 'in', [1, 2])
            ->field('COUNT(*) as count, SUM(user_amount_coin) as user_coin, SUM(platform_amount_coin) as platform_coin, SUM(amount_coin) as total_coin')
            ->find();
        
        $providerStats = Db::name('ad_income_log')
            ->where('status', 'in', [1, 2])
            ->where('createtime', '>=', $monthStart)
            ->group('ad_provider')
            ->field('ad_provider, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
            ->select();
        
        $typeStats = Db::name('ad_income_log')
            ->where('status', 'in', [1, 2])
            ->where('createtime', '>=', $monthStart)
            ->group('ad_type')
            ->field('ad_type, COUNT(*) as count, SUM(user_amount_coin) as user_coin')
            ->select();
        
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
