<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use app\common\model\AdRedPacket;
use think\Db;

/**
 * 广告红包管理
 */
class Redpacket extends Backend
{
    protected $dataLimit = false;
    
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new AdRedPacket();
    }
    
    /**
     * 红包列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = Db::name('ad_red_packet')->where($where)->count();
            $list = Db::name('ad_red_packet')
                ->alias('arp')
                ->join('user u', 'u.id = arp.user_id', 'LEFT')
                ->field('arp.*, u.username, u.nickname, u.mobile')
                ->where($where)
                ->order("arp.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();
            
            foreach ($list as &$row) {
                $row['status_text'] = AdRedPacket::$statusList[$row['status']] ?? '未知';
                $row['source_text'] = $row['source'] == 'ad_income' ? '广告收益' : '其他';
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
                $row['claim_time_text'] = $row['claim_time'] ? date('Y-m-d H:i:s', $row['claim_time']) : '';
                $row['expire_time_text'] = $row['expire_time'] ? date('Y-m-d H:i:s', $row['expire_time']) : '';
                $row['is_expired'] = ($row['expire_time'] > 0 && time() > $row['expire_time'] && $row['status'] == 0) ? 1 : 0;
            }
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 红包详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('ad_red_packet')
            ->alias('arp')
            ->join('user u', 'u.id = arp.user_id', 'LEFT')
            ->field('arp.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('arp.id', $ids)
            ->find();
        
        if (!$row) {
            $this->error('未找到记录');
        }
        
        $row['status_text'] = AdRedPacket::$statusList[$row['status']] ?? '未知';
        $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
        $row['claim_time_text'] = $row['claim_time'] ? date('Y-m-d H:i:s', $row['claim_time']) : '';
        $row['expire_time_text'] = $row['expire_time'] ? date('Y-m-d H:i:s', $row['expire_time']) : '';
        
        $sourceIds = $row['source_ids'] ? explode(',', $row['source_ids']) : [];
        $incomeLogs = [];
        if (!empty($sourceIds)) {
            $incomeLogs = Db::name('ad_income_log')
                ->where('id', 'in', $sourceIds)
                ->field('id, ad_type, ad_provider, amount, user_amount_coin, platform_amount_coin, status, createtime')
                ->select();
            foreach ($incomeLogs as &$log) {
                $log['status_text'] = \app\common\model\AdIncomeLog::$statusList[$log['status']] ?? '未知';
                $log['createtime_text'] = date('Y-m-d H:i:s', $log['createtime']);
            }
        }
        
        $this->view->assign('row', $row);
        $this->view->assign('income_logs', $incomeLogs);
        return $this->view->fetch();
    }
    
    /**
     * 红包统计
     */
    public function summary()
    {
        $todayStart = strtotime(date('Y-m-d'));
        
        $todayStats = Db::name('ad_red_packet')
            ->where('createtime', '>=', $todayStart)
            ->field('COUNT(*) as total, SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as unclaimed, SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as claimed, SUM(CASE WHEN status=2 THEN 1 ELSE 0 END) as expired, SUM(amount) as total_amount, SUM(CASE WHEN status=1 THEN amount ELSE 0 END) as claimed_amount')
            ->find();
        
        $totalStats = Db::name('ad_red_packet')
            ->field('COUNT(*) as total, SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as unclaimed, SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as claimed, SUM(CASE WHEN status=2 THEN 1 ELSE 0 END) as expired, SUM(amount) as total_amount, SUM(CASE WHEN status=1 THEN amount ELSE 0 END) as claimed_amount, SUM(CASE WHEN status=2 THEN amount ELSE 0 END) as expired_amount')
            ->find();
        
        $pendingUsers = Db::name('coin_account')
            ->where('ad_freeze_balance', '>', 0)
            ->count();
        $pendingAmount = Db::name('coin_account')
            ->where('ad_freeze_balance', '>', 0)
            ->sum('ad_freeze_balance');
        
        $this->success('', null, [
            'today' => $todayStats,
            'total' => $totalStats,
            'pending_users' => $pendingUsers,
            'pending_amount' => (int)$pendingAmount,
        ]);
    }
    
    /**
     * 手动过期处理
     */
    public function expire()
    {
        if ($this->request->isPost()) {
            $count = AdRedPacket::expirePackets();
            $this->success("处理完成，过期 {$count} 个红包");
        }
        return $this->view->fetch();
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
