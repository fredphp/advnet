<?php

namespace app\admin\controller\coin;

use app\common\controller\Backend;
use app\common\model\CoinLog;
use think\Db;

/**
 * 金币流水管理
 * 支持按月分表查询
 */
class Log extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new CoinLog();
        $this->view->assign("typeList",CoinLog::$typeList);
    }

    /**
     * 金币流水列表
     * 默认查询当月数据，通过时间筛选可以查询历史数据
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            // 获取筛选参数
            $filter = json_decode($this->request->get('filter', '{}'), true);
            $op = json_decode($this->request->get('op', '{}'), true);
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            
            // 解析时间范围
            $startTime = null;
            $endTime = null;
            
            // 处理 createtime 筛选
            if (isset($filter['createtime']) && isset($op['createtime']) && $op['createtime'] == 'RANGE') {
                $timeRange = $filter['createtime'];
                if (strpos($timeRange, ' - ') !== false) {
                    list($startStr, $endStr) = explode(' - ', $timeRange);
                    $startTime = strtotime(trim($startStr));
                    $endTime = strtotime(trim($endStr) . ' 23:59:59');
                    unset($filter['createtime']);
                }
            }
            
            // 如果没有时间筛选，默认查询当月数据
            if ($startTime === null) {
                $startTime = strtotime(date('Y-m-01')); // 当月第一天
                $endTime = strtotime(date('Y-m-t 23:59:59')); // 当月最后一天
            }
            
            // 确保当月分表存在
            CoinLog::ensureCurrentMonthTable();
            
            // 获取需要查询的分表
            $tables = CoinLog::getTablesByRange($startTime, $endTime);
            $prefix = \think\Config::get('database.prefix');
            
            // 构建UNION ALL查询
            $unionQueries = [];
            foreach ($tables as $table) {
                if (CoinLog::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }
            
            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS cl';
            
            // 构建WHERE条件
            $whereParts = ['cl.createtime BETWEEN ? AND ?'];
            $bindParams = [$startTime, $endTime];
            
            // 处理其他筛选条件
            foreach ($filter as $field => $value) {
                $fieldOp = $op[$field] ?? '=';
                $dbField = 'cl.' . $field;
                
                if ($fieldOp == 'RANGE' && strpos($value, ' - ') !== false) {
                    list($start, $end) = explode(' - ', $value);
                    $whereParts[] = "{$dbField} BETWEEN ? AND ?";
                    $bindParams[] = trim($start);
                    $bindParams[] = trim($end);
                } elseif ($fieldOp == 'LIKE') {
                    $whereParts[] = "{$dbField} LIKE ?";
                    $bindParams[] = '%' . $value . '%';
                } elseif ($fieldOp == 'IN') {
                    $values = is_array($value) ? $value : explode(',', $value);
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $whereParts[] = "{$dbField} IN ({$placeholders})";
                    $bindParams = array_merge($bindParams, $values);
                } else {
                    $whereParts[] = "{$dbField} = ?";
                    $bindParams[] = $value;
                }
            }
            
            $whereStr = implode(' AND ', $whereParts);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE {$whereStr}";
            $totalResult = Db::query($countSql, $bindParams);
            $total = $totalResult[0]['total'] ?? 0;
            
            // 查询列表
            $listSql = "SELECT cl.*, u.username, u.nickname, u.mobile 
                        FROM {$unionSql} 
                        LEFT JOIN {$prefix}user u ON u.id = cl.user_id 
                        WHERE {$whereStr} 
                        ORDER BY cl.{$sort} {$order} 
                        LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, $bindParams);
            
            // 关联类型映射
            $relationTypeMap = [
                'video' => '视频',
                'task' => '任务',
                'withdraw' => '提现',
                'invite' => '邀请',
                'red_packet' => '红包',
                'admin' => '后台操作',
                'user' => '用户转账',
                'click' => '点击奖励',
                'grab' => '抢红包',
            ];
            
            // 格式化数据
            foreach ($list as &$row) {
                $row['type_text'] = CoinLog::$typeList[$row['type']] ?? $row['type'];
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
                // 格式化金额显示
                if ($row['amount'] > 0) {
                    $row['amount_text'] = '+' . $row['amount'];
                } else {
                    $row['amount_text'] = strval($row['amount']);
                }
                // 关联类型中文
                $row['relation_type_text'] = $relationTypeMap[$row['relation_type']] ?? ($row['relation_type'] ?: '-');
                // IP处理
                $row['ip_text'] = $row['ip'] ?: '-';
            }
            
            return json(['total' => $total, 'rows' => $list]);
        }
        
        // 传递流水类型映射到视图
        $this->view->assign('typeList', CoinLog::$typeList);
        return $this->view->fetch();
    }

    /**
     * 金币统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        // 获取分表
        $tables = CoinLog::getTablesByRange($startTimestamp, $endTimestamp);
        $prefix = \think\Config::get('database.prefix');
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (CoinLog::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        $totalStats = [
            'total_income' => 0,
            'total_expense' => 0,
            'total_count' => 0
        ];
        $typeStats = [];
        $dailyStats = [];
        $topIncomeUsers = [];
        $topExpenseUsers = [];
        
        if (!empty($unionQueries)) {
            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS cl';
            
            // 总体统计
            $totalSql = "SELECT SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
                                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_expense,
                                COUNT(*) as total_count
                         FROM {$unionSql} 
                         WHERE createtime BETWEEN ? AND ?";
            $totalResult = Db::query($totalSql, [$startTimestamp, $endTimestamp]);
            if (!empty($totalResult[0])) {
                $totalStats = $totalResult[0];
            }

            // 按类型统计
            $typeSql = "SELECT type, 
                               SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                               SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense,
                               COUNT(*) as count
                        FROM {$unionSql} 
                        WHERE createtime BETWEEN ? AND ?
                        GROUP BY type";
            $typeStats = Db::query($typeSql, [$startTimestamp, $endTimestamp]);
            
            // 添加类型文本
            foreach ($typeStats as &$item) {
                $item['type_text'] = CoinLog::$typeList[$item['type']] ?? $item['type'];
            }
            unset($item);

            // 每日趋势
            $dailySql = "SELECT FROM_UNIXTIME(createtime, '%Y-%m-%d') as date,
                                COUNT(*) as count, 
                                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense
                         FROM {$unionSql} 
                         WHERE createtime BETWEEN ? AND ?
                         GROUP BY date 
                         ORDER BY date ASC";
            $dailyStats = Db::query($dailySql, [$startTimestamp, $endTimestamp]);

            // 用户收支排行
            $topIncomeSql = "SELECT u.id, u.username, u.nickname, 
                                   SUM(CASE WHEN cl.amount > 0 THEN cl.amount ELSE 0 END) as total_income
                            FROM {$unionSql} 
                            LEFT JOIN {$prefix}user u ON u.id = cl.user_id
                            WHERE cl.createtime BETWEEN ? AND ?
                            GROUP BY cl.user_id 
                            ORDER BY total_income DESC 
                            LIMIT 20";
            $topIncomeUsers = Db::query($topIncomeSql, [$startTimestamp, $endTimestamp]);

            $topExpenseSql = "SELECT u.id, u.username, u.nickname, 
                                     SUM(CASE WHEN cl.amount < 0 THEN ABS(cl.amount) ELSE 0 END) as total_expense
                              FROM {$unionSql} 
                              LEFT JOIN {$prefix}user u ON u.id = cl.user_id
                              WHERE cl.createtime BETWEEN ? AND ?
                              GROUP BY cl.user_id 
                              ORDER BY total_expense DESC 
                              LIMIT 20";
            $topExpenseUsers = Db::query($topExpenseSql, [$startTimestamp, $endTimestamp]);
        }

        if ($this->request->isAjax()) {
            $this->success('获取成功', null, [
                'total_stats' => $totalStats,
                'type_stats' => $typeStats,
                'daily_stats' => $dailyStats,
                'top_income_users' => $topIncomeUsers,
                'top_expense_users' => $topExpenseUsers,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('type_stats', $typeStats);
        $this->view->assign('daily_stats', $dailyStats);
        $this->view->assign('top_income_users', $topIncomeUsers);
        $this->view->assign('top_expense_users', $topExpenseUsers);
        return $this->view->fetch();
    }

    /**
     * 用户金币流水
     */
    public function userLog($ids = null)
    {
        $userId = $ids ?: $this->request->get('user_id');

        if (!$userId) {
            $this->error('请指定用户ID');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 20);
            $type = $this->request->get('type');
            $startDate = $this->request->get('start_date');
            $endDate = $this->request->get('end_date');
            
            // 默认查询最近3个月
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-3 months'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }
            
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate . ' 23:59:59');
            
            $tables = CoinLog::getTablesByRange($startTimestamp, $endTimestamp);
            $prefix = \think\Config::get('database.prefix');
            
            $unionQueries = [];
            foreach ($tables as $table) {
                if (CoinLog::tableExists($table)) {
                    $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
                }
            }
            
            if (empty($unionQueries)) {
                return json(['total' => 0, 'rows' => []]);
            }
            
            $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS cl';
            
            $whereParts = ['cl.user_id = ?', 'cl.createtime BETWEEN ? AND ?'];
            $bindParams = [$userId, $startTimestamp, $endTimestamp];
            
            if ($type) {
                $whereParts[] = 'cl.type = ?';
                $bindParams[] = $type;
            }
            
            $whereStr = implode(' AND ', $whereParts);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE {$whereStr}";
            $totalResult = Db::query($countSql, $bindParams);
            $total = $totalResult[0]['total'] ?? 0;
            
            // 查询列表
            $listSql = "SELECT cl.* FROM {$unionSql} WHERE {$whereStr} ORDER BY cl.createtime DESC LIMIT {$offset}, {$limit}";
            $list = Db::query($listSql, $bindParams);
            
            foreach ($list as &$row) {
                $row['type_text'] = CoinLog::$typeList[$row['type']] ?? $row['type'];
            }

            return json(['total' => $total, 'rows' => $list]);
        }

        $this->view->assign('user_id', $userId);
        return $this->view->fetch();
    }

    /**
     * 导出流水
     */
    public function export()
    {
        $type = $this->request->get('type');
        $userId = $this->request->get('user_id');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');
        
        // 默认导出当月
        if (!$startDate) {
            $startDate = date('Y-m-01');
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }
        
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');
        
        $tables = CoinLog::getTablesByRange($startTimestamp, $endTimestamp);
        $prefix = \think\Config::get('database.prefix');
        
        $unionQueries = [];
        foreach ($tables as $table) {
            if (CoinLog::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            $this->error('没有可导出的数据');
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS cl';
        
        $whereParts = ['cl.createtime BETWEEN ? AND ?'];
        $bindParams = [$startTimestamp, $endTimestamp];

        if ($type) {
            $whereParts[] = 'cl.type = ?';
            $bindParams[] = $type;
        }

        if ($userId) {
            $whereParts[] = 'cl.user_id = ?';
            $bindParams[] = $userId;
        }
        
        $whereStr = implode(' AND ', $whereParts);

        $listSql = "SELECT cl.*, u.username, u.nickname 
                    FROM {$unionSql} 
                    LEFT JOIN {$prefix}user u ON u.id = cl.user_id 
                    WHERE {$whereStr} 
                    ORDER BY cl.createtime DESC 
                    LIMIT 10000";
        $list = Db::query($listSql, $bindParams);

        $filename = 'coin_logs_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['流水ID', '用户ID', '用户名', '类型', '金额', '余额', '描述', '时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['id'],
                $row['user_id'],
                $row['username'],
                CoinLog::$typeList[$row['type']] ?? $row['type'],
                $row['amount'],
                $row['after_balance'] ?? $row['balance_after'] ?? '',
                $row['description'],
                date('Y-m-d H:i:s', $row['createtime']),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 编辑流水（从分表查询）- 改为详情展示
     */
    public function edit($ids = null)
    {
        // 从分表查找记录
        $row = $this->findLogById($ids);
        
        if (!$row) {
            $this->error(__('未找到记录'));
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
        // 格式化显示
        $row['type_text'] = CoinLog::$typeList[$row['type']] ?? $row['type'];
        $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
        $row['amount_text'] = $row['amount'] > 0 ? '+' . $row['amount'] : strval($row['amount']);
        $row['is_income'] = $row['amount'] > 0;
        $row['balance_before'] = $row['balance_before'] ?? 0;
        $row['balance_after'] = $row['balance_after'] ?? 0;
        
        // 用户信息
        $row['username'] = $user['username'] ?? '';
        $row['nickname'] = $user['nickname'] ?? '';
        $row['mobile'] = $user['mobile'] ?? '';
        $row['avatar'] = $user['avatar'] ?? '';
        
        // 关联类型中文映射
        $relationTypeMap = [
            'video' => '视频',
            'task' => '任务',
            'withdraw' => '提现',
            'invite' => '邀请',
            'red_packet' => '红包',
            'admin' => '后台操作',
            'user' => '用户转账',
            'click' => '点击奖励',
            'grab' => '抢红包',
        ];
        $row['relation_type_text'] = $relationTypeMap[$row['relation_type']] ?? ($row['relation_type'] ?: '-');
        
        // 获取关联详情（传入分表信息）
        $relationTable = $row['relation_table'] ?? '';
        $row['relation_detail'] = $this->getRelationDetail($row['relation_type'], $row['relation_id'], $relationTable);
        
        // 记录所在分表
        $row['_table'] = $row['_table'] ?? '';
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 获取关联详情
     * @param string $relationType 关联类型
     * @param int $relationId 关联ID
     * @param string $relationTable 关联数据所在分表（可选，优先使用）
     */
    protected function getRelationDetail($relationType, $relationId, $relationTable = '')
    {
        if (empty($relationType) || empty($relationId)) {
            return null;
        }
        
        $detail = null;
        $prefix = \think\Config::get('database.prefix');
        
        switch ($relationType) {
            case 'withdraw':
                // 优先使用分表信息
                if (!empty($relationTable)) {
                    $detail = Db::name($relationTable)->where('id', $relationId)->find();
                    if ($detail) {
                        $detail['_table'] = $relationTable;
                    }
                }
                
                // 如果没有分表信息，尝试从最近几年的表查找
                if (!$detail) {
                    for ($i = 0; $i <= 3; $i++) {
                        $year = date('Y') - $i;
                        $table = 'withdraw_order_' . $year;
                        if (\app\common\model\WithdrawOrder::tableExists($table)) {
                            $detail = Db::name($table)->where('id', $relationId)->find();
                            if ($detail) {
                                $detail['_table'] = $table;
                                break;
                            }
                        }
                    }
                }
                
                if ($detail) {
                    $statusMap = [
                        0 => '待审核',
                        1 => '审核通过',
                        2 => '打款中',
                        3 => '提现成功',
                        4 => '审核拒绝',
                        5 => '打款失败',
                        6 => '已取消',
                    ];
                    return [
                        'type' => '提现订单',
                        'fields' => [
                            ['label' => '订单号', 'value' => $detail['order_no'] ?? ''],
                            ['label' => '提现金币', 'value' => $detail['coin_amount'] ?? 0],
                            ['label' => '提现金额', 'value' => '¥' . ($detail['cash_amount'] ?? 0)],
                            ['label' => '实际到账', 'value' => '¥' . ($detail['actual_amount'] ?? 0)],
                            ['label' => '状态', 'value' => $statusMap[$detail['status']] ?? '未知', 'highlight' => true],
                            ['label' => '提现方式', 'value' => $detail['withdraw_type'] ?? ''],
                            ['label' => '收款账号', 'value' => $detail['withdraw_account'] ?? ''],
                            ['label' => '收款人', 'value' => $detail['withdraw_name'] ?? ''],
                            ['label' => '申请时间', 'value' => date('Y-m-d H:i:s', $detail['createtime'] ?? 0)],
                            ['label' => '分表', 'value' => $detail['_table'] ?? '-', 'is_meta' => true],
                        ]
                    ];
                }
                break;
                
            case 'video':
                $detail = Db::name('video')->where('id', $relationId)->find();
                if ($detail) {
                    return [
                        'type' => '视频',
                        'fields' => [
                            ['label' => '视频ID', 'value' => $detail['id']],
                            ['label' => '视频标题', 'value' => $detail['title'] ?? ''],
                            ['label' => '视频时长', 'value' => ($detail['duration'] ?? 0) . '秒'],
                            ['label' => '观看次数', 'value' => $detail['view_count'] ?? 0],
                        ]
                    ];
                }
                break;
                
            case 'task':
                $detail = Db::name('task')->where('id', $relationId)->find();
                if ($detail) {
                    return [
                        'type' => '任务',
                        'fields' => [
                            ['label' => '任务ID', 'value' => $detail['id']],
                            ['label' => '任务名称', 'value' => $detail['name'] ?? ''],
                            ['label' => '任务类型', 'value' => $detail['type'] ?? ''],
                            ['label' => '奖励金币', 'value' => $detail['reward_coin'] ?? 0],
                        ]
                    ];
                }
                break;
                
            case 'invite':
                $detail = Db::name('invite_relation')->where('id', $relationId)->find();
                if ($detail) {
                    $invitee = Db::name('user')->where('id', $detail['invitee_id'])->find();
                    return [
                        'type' => '邀请关系',
                        'fields' => [
                            ['label' => '被邀请人ID', 'value' => $detail['invitee_id']],
                            ['label' => '被邀请人', 'value' => $invitee['nickname'] ?? $invitee['username'] ?? ''],
                            ['label' => '邀请等级', 'value' => $detail['level'] == 1 ? '一级邀请' : '二级邀请'],
                            ['label' => '邀请时间', 'value' => date('Y-m-d H:i:s', $detail['createtime'] ?? 0)],
                        ]
                    ];
                }
                break;
                
            case 'red_packet':
            case 'grab':
            case 'click':
                $detail = Db::name('user_red_packet_accumulate')->where('id', $relationId)->find();
                if ($detail) {
                    return [
                        'type' => '红包',
                        'fields' => [
                            ['label' => '红包ID', 'value' => $detail['id']],
                            ['label' => '基础金额', 'value' => $detail['base_amount'] ?? 0],
                            ['label' => '累加金额', 'value' => $detail['accumulate_amount'] ?? 0],
                            ['label' => '总金额', 'value' => $detail['total_amount'] ?? 0],
                            ['label' => '点击次数', 'value' => $detail['click_count'] ?? 0],
                            ['label' => '领取状态', 'value' => $detail['is_collected'] ? '已领取' : '未领取'],
                        ]
                    ];
                }
                break;
                
            case 'user':
                $detail = Db::name('user')->where('id', $relationId)->find();
                if ($detail) {
                    return [
                        'type' => '关联用户',
                        'fields' => [
                            ['label' => '用户ID', 'value' => $detail['id']],
                            ['label' => '用户名', 'value' => $detail['username'] ?? ''],
                            ['label' => '昵称', 'value' => $detail['nickname'] ?? ''],
                            ['label' => '手机号', 'value' => $detail['mobile'] ?? ''],
                        ]
                    ];
                }
                break;
        }
        
        return null;
    }

    /**
     * 查看详情（从分表查询）
     */
    public function detail($ids = null)
    {
        $row = $this->findLogById($ids);
        
        if (!$row) {
            $this->error(__('未找到记录'));
        }
        
        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
        // 格式化显示
        $row['type_text'] = CoinLog::$typeList[$row['type']] ?? $row['type'];
        $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
        $row['amount_text'] = $row['amount'] > 0 ? '+' . $row['amount'] : $row['amount'];
        $row['username'] = $user['username'] ?? '';
        $row['nickname'] = $user['nickname'] ?? '';
        $row['mobile'] = $user['mobile'] ?? '';
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 根据ID从分表查找记录
     * @param int $id
     * @return array|null
     */
    protected function findLogById($id)
    {
        if (!$id) {
            return null;
        }
        
        // 查询最近12个月的分表
        $startTime = strtotime('-12 months');
        $endTime = time();
        $tables = CoinLog::getTablesByRange($startTime, $endTime);
        
        // 按时间倒序查找（优先查找最近的表）
        $tables = array_reverse($tables);
        
        foreach ($tables as $table) {
            if (CoinLog::tableExists($table)) {
                $row = Db::name($table)->where('id', $id)->find();
                if ($row) {
                    $row['_table'] = $table;
                    return $row;
                }
            }
        }
        
        return null;
    }

    /**
     * 删除（禁止删除流水记录）
     */
    public function del($ids = null)
    {
        $this->error(__('金币流水记录不允许删除'));
    }

    /**
     * 添加（禁止手动添加流水）
     */
    public function add()
    {
        $this->error(__('金币流水由系统自动生成，不支持手动添加'));
    }
}
