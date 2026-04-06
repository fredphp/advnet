<?php

namespace app\admin\controller\adincome;

use app\common\controller\Backend;
use app\common\model\AdRedPacket;
use app\common\model\AdRedPacketSplit;
use think\Db;

/**
 * 广告红包管理（支持分表查询）
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
     * 红包列表（跨分表分页查询）
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            // ★ 使用分表模型进行跨表分页查询
            $result = AdRedPacketSplit::paginateAllTables($where, $sort, $order, $offset, $limit);
            $total = $result['total'];
            $list = $result['rows'];
            
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
        // ★ 跨分表查找记录
        $row = AdRedPacketSplit::findById($ids);
        
        if (!$row) {
            $this->error('未找到记录');
        }
        
        // 关联用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->field('username, nickname, mobile, avatar')->find();
        if ($user) {
            $row = array_merge($row, $user);
        }
        
        $row['status_text'] = AdRedPacket::$statusList[$row['status']] ?? '未知';
        $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
        $row['claim_time_text'] = $row['claim_time'] ? date('Y-m-d H:i:s', $row['claim_time']) : '';
        $row['expire_time_text'] = $row['expire_time'] ? date('Y-m-d H:i:s', $row['expire_time']) : '';
        $row['source_text'] = $row['source'] == 'ad_income' ? '广告收益' : '其他';
        
        $sourceIds = $row['source_ids'] ? explode(',', $row['source_ids']) : [];
        $incomeLogs = [];
        if (!empty($sourceIds) && $row['source_ids'] !== 'freeze_balance') {
            // 按来源表查找收益记录
            foreach ($sourceIds as $logId) {
                $logId = (int)$logId;
                $logRow = AdIncomeLogSplit::findById($logId);
                if ($logRow) {
                    $logRow['status_text'] = \app\common\model\AdIncomeLog::$statusList[$logRow['status']] ?? '未知';
                    $logRow['createtime_text'] = date('Y-m-d H:i:s', $logRow['createtime']);
                    $incomeLogs[] = $logRow;
                }
            }
        }
        
        $this->view->assign('row', $row);
        $this->view->assign('income_logs', $incomeLogs);
        return $this->view->fetch();
    }
    
    /**
     * 红包统计（跨分表统计）
     */
    public function summary()
    {
        $todayStart = strtotime(date('Y-m-d'));
        
        $todayStats = AdRedPacketSplit::getStats($todayStart, time());
        $totalStats = AdRedPacketSplit::getStats();
        
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
     * 手动过期处理（跨所有分表）
     */
    public function expire()
    {
        if ($this->request->isPost()) {
            // ★ 跨所有表处理过期红包
            $count = AdRedPacketSplit::expireAllPackets();
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
