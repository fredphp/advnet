<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\model\WithdrawOrder;
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

            // 获取需要查询的分表
            $tables = WithdrawOrder::getTablesByRange($startTimestamp, $endTimestamp);
            $prefix = \think\Config::get('database.prefix');

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

            foreach ($tables as $table) {
                if (WithdrawOrder::tableExists($table)) {
                    // 总数
                    $stats['total_count'] += Db::name($table)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->count();
                    $stats['total_amount'] += Db::name($table)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->sum('cash_amount');

                    // 待审核
                    $stats['pending_count'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_PENDING)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->count();
                    $stats['pending_amount'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_PENDING)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->sum('cash_amount');

                    // 已完成
                    $stats['completed_count'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_SUCCESS)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->count();
                    $stats['completed_amount'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_SUCCESS)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->sum('cash_amount');

                    // 已拒绝
                    $stats['rejected_count'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_REJECTED)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->count();
                    $stats['rejected_amount'] += Db::name($table)
                        ->where('status', WithdrawOrder::STATUS_REJECTED)
                        ->where('createtime', '>=', $startTimestamp)
                        ->where('createtime', '<=', $endTimestamp)
                        ->sum('cash_amount');
                }
            }

            $this->success('', null, $stats);
        }

        return $this->view->fetch();
    }
}
