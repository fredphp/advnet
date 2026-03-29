<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use app\common\model\UserRedPacketAccumulateSplit;
use think\Db;

/**
 * 红包领取记录
 * 使用用户红包累计记录表（单人抢红包模式）
 * 使用分表查询
 */
class Participation extends Backend
{
    protected $model = null;
    protected $splitModel = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\UserRedPacketAccumulate;
        $this->splitModel = new UserRedPacketAccumulateSplit();

        // 领取状态列表
        $this->view->assign('collectStatusList', [
            0 => '待领取',
            1 => '已领取'
        ]);

        // 是否新用户
        $this->view->assign('newUserList', [
            0 => '老用户',
            1 => '新用户'
        ]);
    }

    /**
     * 查看列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // 获取统计信息
            $stats = $this->getTodayStats();
            
            // 获取时间筛选条件
            $filter = $this->request->get('filter', '');
            $filterData = json_decode($filter, true);
            
            // 检查是否有 createtime 时间范围筛选
            $hasTimeFilter = false;
            $startTime = null;
            $endTime = null;
            
            if (isset($filterData['createtime']) && !empty($filterData['createtime'])) {
                $timeRange = $filterData['createtime'];
                if (strpos($timeRange, ' - ') !== false) {
                    $times = explode(' - ', $timeRange);
                    if (count($times) == 2) {
                        $startTime = strtotime(trim($times[0]));
                        $endTime = strtotime(trim($times[1]) . ' 23:59:59');
                        $hasTimeFilter = true;
                    }
                }
            }
            
            // 数据库表前缀
            $prefix = config('database.prefix');
            
            // 获取分页参数
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');

            // 默认按ID排序
            if ($sort == 'sort') {
                $sort = 'id';
            }
            
            if ($hasTimeFilter && $startTime && $endTime) {
                // 有时间筛选条件，跨分表查询
                $total = 0;
                $rows = [];
                
                // 计算需要查询的分表
                $currentMonth = strtotime(date('Y-m-01', $startTime));
                $endMonth = strtotime(date('Y-m-01', $endTime));
                
                $tablesToQuery = [];
                while ($currentMonth <= $endMonth) {
                    $suffix = date('Ym', $currentMonth);
                    $tableName = $prefix . 'user_red_packet_accumulate_' . $suffix;
                    $exists = Db::query("SHOW TABLES LIKE '{$tableName}'");
                    if (!empty($exists)) {
                        $tablesToQuery[] = 'user_red_packet_accumulate_' . $suffix;
                    }
                    $currentMonth = strtotime('+1 month', $currentMonth);
                }
                
                if (empty($tablesToQuery)) {
                    return json(['total' => 0, 'rows' => [], 'stats' => $stats]);
                }
                
                // 计算总数
                foreach ($tablesToQuery as $table) {
                    $total += Db::name($table)
                        ->where('createtime', '>=', $startTime)
                        ->where('createtime', '<=', $endTime)
                        ->count();
                }
                
                // 分页获取数据
                $collected = 0;
                $skipCount = $offset;
                $needCollect = $limit;
                
                // 从最新的分表开始查询
                $tablesToQuery = array_reverse($tablesToQuery);
                
                foreach ($tablesToQuery as $table) {
                    if ($collected >= $needCollect) {
                        break;
                    }
                    
                    $countQuery = Db::name($table)
                        ->where('createtime', '>=', $startTime)
                        ->where('createtime', '<=', $endTime);
                    $tableCount = $countQuery->count();
                    
                    if ($tableCount <= $skipCount) {
                        $skipCount -= $tableCount;
                        continue;
                    }
                    
                    $query = Db::name($table)
                        ->where('createtime', '>=', $startTime)
                        ->where('createtime', '<=', $endTime)
                        ->order($sort, $order)
                        ->limit($skipCount, $needCollect - $collected);
                    $skipCount = 0;
                    
                    $data = $query->select();
                    if (is_object($data)) {
                        $data = $data->toArray();
                    } elseif (!is_array($data)) {
                        $data = [];
                    }
                    
                    foreach ($data as $row) {
                        $row['_table'] = $table;
                        $row['_month'] = date('Ym', $row['createtime'] ?? time());
                        $rows[] = $row;
                        $collected++;
                    }
                }
                
                // 按时间戳排序
                usort($rows, function($a, $b) use ($sort, $order) {
                    $aVal = $a[$sort] ?? 0;
                    $bVal = $b[$sort] ?? 0;
                    return $order === 'desc' ? ($bVal - $aVal) : ($aVal - $bVal);
                });
                
            } else {
                // 没有时间筛选，默认查询当月分表
                $suffix = date('Ym');
                $tableName = 'user_red_packet_accumulate_' . $suffix;
                $fullTableName = $prefix . $tableName;
                
                // 检查分表是否存在，不存在则创建
                $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
                if (empty($exists)) {
                    $mainTable = $prefix . 'user_red_packet_accumulate';
                    $mainExists = Db::query("SHOW TABLES LIKE '{$mainTable}'");
                    if (!empty($mainExists)) {
                        Db::execute("CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`");
                    }
                }
                
                // 计算总数
                $total = Db::name($tableName)->count();
                
                // 获取数据
                $data = Db::name($tableName)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
                
                if (is_object($data)) {
                    $rows = $data->toArray();
                } elseif (!is_array($data)) {
                    $rows = [];
                } else {
                    $rows = $data;
                }
                
                // 添加月份标识
                foreach ($rows as &$row) {
                    $row['_table'] = $tableName;
                    $row['_month'] = $suffix;
                }
                unset($row);
            }
            
            // 加载关联用户和任务信息
            $userIds = array_unique(array_filter(array_column($rows, 'user_id')));
            $taskIds = array_unique(array_filter(array_column($rows, 'task_id')));
            
            $users = [];
            $tasks = [];
            
            if (!empty($userIds)) {
                $users = \app\common\model\User::where('id', 'in', $userIds)->column('id,username,nickname');
            }
            
            if (!empty($taskIds)) {
                // 从分表查询任务信息
                $taskModel = new \app\common\model\RedPacketTaskSplit();
                $taskTables = $taskModel->getTableList();
                foreach ($taskTables as $taskTable) {
                    $taskData = Db::name($taskTable)->where('id', 'in', $taskIds)->select();
                    foreach ($taskData as $task) {
                        $tasks[$task['id']] = $task;
                    }
                }
            }
            
            // 合并数据
            foreach ($rows as &$row) {
                $row['user'] = isset($users[$row['user_id']]) ? $users[$row['user_id']] : null;
                $row['task'] = isset($tasks[$row['task_id']]) ? $tasks[$row['task_id']] : null;
                $row['collect_status_text'] = $row['is_collected'] ? '已领取' : '待领取';
                $row['new_user_text'] = $row['is_new_user'] ? '新用户' : '老用户';
            }
            
            $result = ['total' => $total, 'rows' => $rows, 'stats' => $stats];
            return json($result);
        }

        return $this->view->fetch();
    }
    
    /**
     * 获取今日统计
     */
    protected function getTodayStats()
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        
        // 获取当月分表
        $suffix = date('Ym');
        $tableName = 'user_red_packet_accumulate_' . $suffix;
        $prefix = config('database.prefix');
        $fullTableName = $prefix . $tableName;
        
        $todayCount = 0;
        $todayAmount = 0;
        
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
        if (!empty($exists)) {
            $todayCount = Db::name($tableName)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->count();
            
            $todayAmount = Db::name($tableName)
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->where('is_collected', 1)
                ->sum('total_amount');
        }
        
        return [
            'today_count' => $todayCount,
            'today_amount' => $todayAmount ?: 0,
            'today_amount_formatted' => number_format($todayAmount ?: 0, 0, '', ',')
        ];
    }
    
    /**
     * 详情
     */
    public function detail($ids = null)
    {
        // 获取请求中的月份参数，默认当月
        $month = $this->request->get('month', date('Ym'));
        $tableName = 'user_red_packet_accumulate_' . $month;
        
        // 直接使用 Db 查询分表
        $row = Db::name($tableName)->where('id', $ids)->find();
        
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        
        // 加载关联用户
        $row['user'] = null;
        if (!empty($row['user_id'])) {
            $row['user'] = \app\common\model\User::where('id', $row['user_id'])->find();
        }
        
        // 加载关联任务 - 查询分表
        if (!empty($row['task_id'])) {
            $taskTable = 'red_packet_task_' . $month;
            $row['task'] = Db::name($taskTable)->where('id', $row['task_id'])->find();
        }
        
        $this->view->assign('row', $row);
        
        return $this->view->fetch();
    }
    
    /**
     * 统计数据
     */
    public function stat()
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->isAjax()) {
            // 获取所有分表统计
            $model = new UserRedPacketAccumulateSplit();
            $tables = $model->getTableList();
            
            // 总领取人数
            $totalUsers = 0;
            $totalAmount = 0;
            $pendingAmount = 0;
            $todayCount = 0;
            $todayAmount = 0;
            
            $todayStart = strtotime(date('Y-m-d'));
            
            foreach ($tables as $table) {
                $totalUsers += Db::name($table)->count();
                $totalAmount += Db::name($table)->where('is_collected', 1)->sum('total_amount');
                $pendingAmount += Db::name($table)->where('is_collected', 0)->sum('total_amount');
                
                // 今日统计
                $todayCount += Db::name($table)->where('createtime', '>=', $todayStart)->count();
                $todayAmount += Db::name($table)
                    ->where('createtime', '>=', $todayStart)
                    ->where('is_collected', 1)
                    ->sum('total_amount');
            }
            
            // 新用户统计
            $newUserCount = 0;
            $newUserAmount = 0;
            foreach ($tables as $table) {
                $newUserCount += Db::name($table)->where('is_new_user', 1)->count();
                $newUserAmount += Db::name($table)
                    ->where('is_new_user', 1)
                    ->where('is_collected', 1)
                    ->sum('total_amount');
            }
            
            // 平均点击次数
            $avgClicks = 0;
            $totalClicks = 0;
            foreach ($tables as $table) {
                $tableCount = Db::name($table)->count();
                $tableAvg = Db::name($table)->avg('click_count');
                if ($tableAvg !== null && $tableCount > 0) {
                    $avgClicks += $tableAvg * $tableCount;
                    $totalClicks += $tableCount;
                }
            }
            $avgClicks = $totalClicks > 0 ? $avgClicks / $totalClicks : 0;
            
            return json([
                'code' => 1,
                'data' => [
                    'total_users' => $totalUsers,
                    'total_amount' => number_format($totalAmount ?: 0, 0, '', ','),
                    'pending_amount' => number_format($pendingAmount ?: 0, 0, '', ','),
                    'today_count' => $todayCount,
                    'today_amount' => number_format($todayAmount ?: 0, 0, '', ','),
                    'new_user_count' => $newUserCount,
                    'new_user_amount' => number_format($newUserAmount ?: 0, 0, '', ','),
                    'avg_clicks' => round($avgClicks, 1)
                ]
            ]);
        }
        
        return $this->view->fetch();
    }
}
