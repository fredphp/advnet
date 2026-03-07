<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use app\common\library\WithdrawService;
use app\common\model\WithdrawOrder;
use think\Db;
use think\Exception;

/**
 * 提现审核管理
 * 支持按月分表查询
 */
class Order extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\WithdrawOrder();
    }

    /**
     * 提现订单列表
     * 支持分表查询
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取筛选参数
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            $status = $this->request->get('status');
            $userId = $this->request->get('user_id');
            $orderNo = $this->request->get('order_no');
            $withdrawType = $this->request->get('withdraw_type');
            
            // 排序参数
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            
            // 构建时间范围
            $startTime = $startDate ? strtotime($startDate) : strtotime('-3 months');
            $endTime = $endDate ? strtotime($endDate . ' 23:59:59') : time();
            
            // 获取需要查询的分表
            $tables = WithdrawOrder::getTablesByRange($startTime, $endTime);
            $prefix = \think\Config::get('database.prefix');
            
            // 构建UNION ALL查询
            $unionQueries = [];
            foreach ($tables as $table) {
                if (WithdrawOrder::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }
            
            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
            
            // 构建WHERE条件
            $whereParts = ['1=1'];
            $bindParams = [];
            
            // 时间范围筛选
            $whereParts[] = "wo.createtime BETWEEN ? AND ?";
            $bindParams[] = $startTime;
            $bindParams[] = $endTime;
            
            // 状态筛选
            if ($status !== '' && $status !== null) {
                $whereParts[] = "wo.status = ?";
                $bindParams[] = $status;
            }
            
            // 用户ID筛选
            if ($userId) {
                $whereParts[] = "wo.user_id = ?";
                $bindParams[] = $userId;
            }
            
            // 订单号筛选
            if ($orderNo) {
                $whereParts[] = "wo.order_no LIKE ?";
                $bindParams[] = '%' . $orderNo . '%';
            }
            
            // 提现方式筛选（只保留微信）
            if ($withdrawType) {
                $whereParts[] = "wo.withdraw_type = ?";
                $bindParams[] = $withdrawType;
            }
            
            // 其他筛选条件（通过buildparams获取）
            list($extraWhere) = $this->buildparams();
            foreach ($extraWhere as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == 'in') {
                        $placeholders = implode(',', array_fill(0, count($value[1]), '?'));
                        $whereParts[] = "wo.{$key} IN ({$placeholders})";
                        $bindParams = array_merge($bindParams, $value[1]);
                    } elseif ($value[0] == 'like') {
                        $whereParts[] = "wo.{$key} LIKE ?";
                        $bindParams[] = '%' . $value[1] . '%';
                    } elseif ($value[0] == 'between' && is_array($value[1])) {
                        $whereParts[] = "wo.{$key} BETWEEN ? AND ?";
                        $bindParams[] = $value[1][0];
                        $bindParams[] = $value[1][1];
                    } else {
                        $whereParts[] = "wo.{$key} {$value[0]} ?";
                        $bindParams[] = $value[1];
                    }
                } else {
                    $whereParts[] = "wo.{$key} = ?";
                    $bindParams[] = $value;
                }
            }
            
            $whereStr = implode(' AND ', $whereParts);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE {$whereStr}";
            $totalResult = Db::query($countSql, $bindParams);
            $total = $totalResult[0]['total'] ?? 0;
            
            // 查询列表（关联用户信息）
            $listSql = "SELECT wo.*, u.username, u.nickname, u.mobile, u.avatar 
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                        WHERE {$whereStr} 
                        ORDER BY wo.{$sort} {$order} 
                        LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, $bindParams);
            
            // 格式化数据
            foreach ($list as &$row) {
                $row['status_text'] = WithdrawOrder::$statusList[$row['status']] ?? '';
                $row['withdraw_type_text'] = WithdrawOrder::$typeList[$row['withdraw_type']] ?? '';
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
            }
            
            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch();
    }

    /**
     * 提现详情
     * 支持跨分表查询
     */
    public function detail($ids = null)
    {
        $row = $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }

        // 用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();

        // 用户提现统计（跨分表统计）
        $userStats = $this->getUserWithdrawStats($row['user_id']);

        // 风险信息
        $riskInfo = Db::name('user_risk_score')
            ->where('user_id', $row['user_id'])
            ->find();

        // 最近提现记录
        $recentOrders = $this->getRecentOrders($row['user_id'], $ids);

        $this->success('', [
            'order' => $row,
            'user' => $user,
            'user_stats' => $userStats,
            'risk_info' => $riskInfo,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * 审核通过
     */
    public function approve($ids = null)
    {
        $row = $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }

        if ($row['status'] != WithdrawOrder::STATUS_PENDING) {
            $this->error('该订单已处理');
        }

        $remark = $this->request->post('remark', '');

        Db::startTrans();
        try {
            // 更新订单状态
            $this->updateOrderStatus($ids, [
                'status' => WithdrawOrder::STATUS_APPROVED,
                'admin_id' => $this->auth->id,
                'admin_name' => $this->auth->username,
                'approve_time' => time(),
                'admin_remark' => $remark,
                'updatetime' => time(),
            ]);

            Db::commit();
            $this->success('审核通过');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 审核拒绝
     */
    public function reject($ids = null)
    {
        $row = $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }

        if ($row['status'] != WithdrawOrder::STATUS_PENDING) {
            $this->error('该订单已处理');
        }

        $reason = $this->request->post('reason', '');

        if (!$reason) {
            $this->error('请填写拒绝原因');
        }

        Db::startTrans();
        try {
            $withdrawService = new WithdrawService();
            $result = $withdrawService->reject($ids, $reason, $this->auth->id);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            Db::commit();
            $this->success('已拒绝并退还金币');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 确认打款
     */
    public function complete($ids = null)
    {
        $row = $this->getOrderById($ids);
        if (!$row) {
            $this->error('订单不存在');
        }

        if (!in_array($row['status'], [WithdrawOrder::STATUS_PENDING, WithdrawOrder::STATUS_APPROVED])) {
            $this->error('该订单状态不允许打款');
        }

        Db::startTrans();
        try {
            $withdrawService = new WithdrawService();
            $result = $withdrawService->transfer($ids);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            Db::commit();
            $this->success('打款成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 批量审核通过
     */
    public function batchApprove()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $ids = is_array($ids) ? $ids : explode(',', $ids);
        
        // 跨分表更新
        $count = 0;
        foreach ($ids as $id) {
            $row = $this->getOrderById($id);
            if ($row && $row['status'] == WithdrawOrder::STATUS_PENDING) {
                $this->updateOrderStatus($id, [
                    'status' => WithdrawOrder::STATUS_APPROVED,
                    'admin_id' => $this->auth->id,
                    'admin_name' => $this->auth->username,
                    'approve_time' => time(),
                    'updatetime' => time()
                ]);
                $count++;
            }
        }

        $this->success("成功审核{$count}条记录");
    }

    /**
     * 批量打款
     */
    public function batchPay()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $ids = is_array($ids) ? $ids : explode(',', $ids);

        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $row = $this->getOrderById($id);
            if ($row && $row['status'] == WithdrawOrder::STATUS_APPROVED) {
                try {
                    $withdrawService = new WithdrawService();
                    $result = $withdrawService->transfer($id);
                    if ($result['success']) {
                        $success++;
                    } else {
                        $failed++;
                    }
                } catch (Exception $e) {
                    $failed++;
                }
            }
        }

        $this->success("成功打款{$success}笔，失败{$failed}笔");
    }

    /**
     * 导出提现记录
     */
    public function export()
    {
        $status = $this->request->get('status');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');

        // 构建时间范围
        $startTime = $startDate ? strtotime($startDate) : strtotime('-3 months');
        $endTime = $endDate ? strtotime($endDate . ' 23:59:59') : time();

        // 获取分表
        $tables = WithdrawOrder::getTablesByRange($startTime, $endTime);
        $prefix = \think\Config::get('database.prefix');

        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }

        if (empty($unionQueries)) {
            $this->error('没有可导出的数据');
        }

        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';

        // 构建条件
        $whereParts = ['wo.createtime BETWEEN ? AND ?'];
        $bindParams = [$startTime, $endTime];

        if ($status !== '' && $status !== null) {
            $whereParts[] = "wo.status = ?";
            $bindParams[] = $status;
        }

        $whereStr = implode(' AND ', $whereParts);

        // 查询数据
        $sql = "SELECT wo.*, u.username, u.nickname, u.mobile 
                FROM {$unionSql} 
                LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                WHERE {$whereStr} 
                ORDER BY wo.createtime DESC";
        $list = Db::query($sql, $bindParams);

        // 导出CSV
        $filename = 'withdraw_orders_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['订单号', '用户ID', '用户名', '手机号', '提现金额', '提现方式', '收款账号', '收款人', '状态', '申请时间', '处理时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['order_no'],
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['cash_amount'],
                WithdrawOrder::$typeList[$row['withdraw_type']] ?? $row['withdraw_type'],
                $row['withdraw_account'],
                $row['withdraw_name'],
                WithdrawOrder::$statusList[$row['status']] ?? $row['status'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['complete_time'] ? date('Y-m-d H:i:s', $row['complete_time']) : '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 根据ID获取订单（跨分表查询）
     * @param int $id
     * @return array|null
     */
    protected function getOrderById($id)
    {
        // 尝试从最近几个月的分表中查找
        $tables = WithdrawOrder::getTablesByRange(strtotime('-6 months'), time());
        $prefix = \think\Config::get('database.prefix');
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $row = Db::name($table)->where('id', $id)->find();
                if ($row) {
                    return $row;
                }
            }
        }
        
        return null;
    }

    /**
     * 更新订单状态（跨分表）
     * @param int $id
     * @param array $data
     * @return bool
     */
    protected function updateOrderStatus($id, $data)
    {
        $tables = WithdrawOrder::getTablesByRange(strtotime('-6 months'), time());
        
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $affected = Db::name($table)->where('id', $id)->update($data);
                if ($affected > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * 获取用户提现统计（跨分表）
     * @param int $userId
     * @return array
     */
    protected function getUserWithdrawStats($userId)
    {
        $tables = WithdrawOrder::getTablesByRange(strtotime('-12 months'), time());
        $prefix = \think\Config::get('database.prefix');
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return ['total_count' => 0, 'total_amount' => 0];
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT COUNT(*) as total_count, 
                       SUM(CASE WHEN status = " . WithdrawOrder::STATUS_SUCCESS . " THEN cash_amount ELSE 0 END) as total_amount
                FROM {$unionSql} 
                WHERE user_id = ?";
        
        $result = Db::query($sql, [$userId]);
        return $result[0] ?? ['total_count' => 0, 'total_amount' => 0];
    }

    /**
     * 获取用户最近提现记录（跨分表）
     * @param int $userId
     * @param int $excludeId
     * @return array
     */
    protected function getRecentOrders($userId, $excludeId)
    {
        $tables = WithdrawOrder::getTablesByRange(strtotime('-3 months'), time());
        $prefix = \think\Config::get('database.prefix');
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return [];
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        $sql = "SELECT * FROM {$unionSql} 
                WHERE user_id = ? AND id != ? 
                ORDER BY createtime DESC 
                LIMIT 5";
        
        return Db::query($sql, [$userId, $excludeId]);
    }

    /**
     * 提现统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 获取分表
        $tables = WithdrawOrder::getTablesByRange($startTimestamp, $endTimestamp);
        $prefix = \think\Config::get('database.prefix');

        $unionQueries = [];
        foreach ($tables as $table) {
            if (WithdrawOrder::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }

        if (empty($unionQueries)) {
            $emptyStats = [
                'total_count' => 0,
                'total_amount' => 0,
                'completed_amount' => 0,
                'rejected_amount' => 0
            ];
            
            if ($this->request->isAjax()) {
                $this->success('', [
                    'total_stats' => $emptyStats,
                    'status_distribution' => [],
                    'daily_stats' => [],
                    'top_users' => [],
                ]);
            }
            
            $this->view->assign('start_date', $startDate);
            $this->view->assign('end_date', $endDate);
            return $this->view->fetch();
        }

        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';

        // 总体统计
        $totalSql = "SELECT COUNT(*) as total_count, 
                            SUM(cash_amount) as total_amount,
                            SUM(CASE WHEN status = " . WithdrawOrder::STATUS_SUCCESS . " THEN cash_amount ELSE 0 END) as completed_amount,
                            SUM(CASE WHEN status = " . WithdrawOrder::STATUS_REJECTED . " THEN cash_amount ELSE 0 END) as rejected_amount
                     FROM {$unionSql} 
                     WHERE createtime BETWEEN ? AND ?";
        $totalStats = Db::query($totalSql, [$startTimestamp, $endTimestamp]);
        $totalStats = $totalStats[0] ?? [];

        // 状态分布
        $statusSql = "SELECT status, COUNT(*) as count, SUM(cash_amount) as amount 
                      FROM {$unionSql} 
                      WHERE createtime BETWEEN ? AND ? 
                      GROUP BY status";
        $statusDistribution = Db::query($statusSql, [$startTimestamp, $endTimestamp]);

        // 每日趋势
        $dailySql = "SELECT FROM_UNIXTIME(createtime, '%Y-%m-%d') as date,
                            COUNT(*) as count, 
                            SUM(cash_amount) as amount,
                            SUM(CASE WHEN status = " . WithdrawOrder::STATUS_SUCCESS . " THEN cash_amount ELSE 0 END) as completed
                     FROM {$unionSql} 
                     WHERE createtime BETWEEN ? AND ? 
                     GROUP BY date 
                     ORDER BY date ASC";
        $dailyStats = Db::query($dailySql, [$startTimestamp, $endTimestamp]);

        // 用户提现排行
        $topUsersSql = "SELECT u.id, u.username, u.nickname, 
                               COUNT(*) as withdraw_count, 
                               SUM(wo.cash_amount) as total_amount
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = wo.user_id
                        WHERE wo.createtime BETWEEN ? AND ? 
                        AND wo.status = " . WithdrawOrder::STATUS_SUCCESS . "
                        GROUP BY wo.user_id 
                        ORDER BY total_amount DESC 
                        LIMIT 20";
        $topUsers = Db::query($topUsersSql, [$startTimestamp, $endTimestamp]);

        if ($this->request->isAjax()) {
            $this->success('', [
                'total_stats' => $totalStats,
                'status_distribution' => $statusDistribution,
                'daily_stats' => $dailyStats,
                'top_users' => $topUsers,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('status_distribution', $statusDistribution);
        $this->view->assign('daily_stats', $dailyStats);
        $this->view->assign('top_users', $topUsers);
        return $this->view->fetch();
    }

    /**
     * 待审核列表（快捷入口）
     */
    public function pending()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);

            // 跨分表查询待审核订单
            $tables = WithdrawOrder::getTablesByRange(strtotime('-1 month'), time());
            $prefix = \think\Config::get('database.prefix');

            $unionQueries = [];
            foreach ($tables as $table) {
                if (WithdrawOrder::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }

            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }

            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';

            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE status = ?";
            $totalResult = Db::query($countSql, [WithdrawOrder::STATUS_PENDING]);
            $total = $totalResult[0]['total'] ?? 0;

            // 查询列表
            $listSql = "SELECT wo.*, u.username, u.nickname, u.mobile 
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = wo.user_id 
                        WHERE wo.status = ? 
                        ORDER BY wo.createtime ASC 
                        LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, [WithdrawOrder::STATUS_PENDING]);

            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch('index');
    }
}
