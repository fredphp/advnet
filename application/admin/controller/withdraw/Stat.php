<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use think\Db;

/**
 * 提现统计
 */
class Stat extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 统计页面
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-01'));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate . ' 23:59:59');

            // 统计数据
            $stats = [
                'total_amount' => 0,
                'total_count' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'completed_amount' => 0,
                'completed_count' => 0,
                'rejected_amount' => 0,
                'rejected_count' => 0,
            ];

            try {
                // 检查表是否存在
                $prefix = \think\Config::get('database.prefix');
                $tableName = $prefix . 'withdraw_order';
                
                $tableExists = Db::query("SHOW TABLES LIKE '{$tableName}'");
                
                if (!empty($tableExists)) {
                    // 总数和总金额
                    $total = Db::name('withdraw_order')
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->field('COUNT(*) as count, COALESCE(SUM(cash_amount), 0) as amount')
                        ->find();
                    $stats['total_count'] = intval($total['count']);
                    $stats['total_amount'] = floatval($total['amount']);

                    // 待审核 status = 0
                    $pending = Db::name('withdraw_order')
                        ->where('status', 0)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->field('COUNT(*) as count, COALESCE(SUM(cash_amount), 0) as amount')
                        ->find();
                    $stats['pending_count'] = intval($pending['count']);
                    $stats['pending_amount'] = floatval($pending['amount']);

                    // 已完成 status = 1
                    $completed = Db::name('withdraw_order')
                        ->where('status', 1)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->field('COUNT(*) as count, COALESCE(SUM(cash_amount), 0) as amount')
                        ->find();
                    $stats['completed_count'] = intval($completed['count']);
                    $stats['completed_amount'] = floatval($completed['amount']);

                    // 已拒绝 status = 2
                    $rejected = Db::name('withdraw_order')
                        ->where('status', 2)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->field('COUNT(*) as count, COALESCE(SUM(cash_amount), 0) as amount')
                        ->find();
                    $stats['rejected_count'] = intval($rejected['count']);
                    $stats['rejected_amount'] = floatval($rejected['amount']);
                }
            } catch (\Exception $e) {
                // 记录错误日志
                \think\Log::error('Withdraw Stat Error: ' . $e->getMessage());
            }

            $this->success('', null, $stats);
        }

        return $this->view->fetch();
    }
}
