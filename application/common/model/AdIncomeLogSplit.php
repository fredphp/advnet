<?php

namespace app\common\model;

use think\Db;
use think\Log;

/**
 * 广告收益记录分表模型
 *
 * 按月分表，表名格式：ad_income_log_202507
 *
 * 使用方式：
 * // 插入数据（自动路由到当月分表）
 * AdIncomeLogSplit::createLog($data);
 *
 * // 跨表查询统计
 * $stats = AdIncomeLogSplit::getRangeStats($startTime, $endTime);
 *
 * // 查找指定ID（跨所有分表）
 * $record = AdIncomeLogSplit::findById($id);
 *
 * // 按条件跨表查询
 * $records = AdIncomeLogSplit::queryAllTables(function($q) {
 *     return $q->where('user_id', 1)->where('status', 1);
 * });
 */
class AdIncomeLogSplit extends SplitTableModel
{
    // 分表类型：按月
    protected $splitType = 'month';

    // 分表依据字段
    protected $splitField = 'createtime';

    // 主表名（保留原始主表用于兼容）
    protected $baseTable = 'ad_income_log';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 创建收益记录（自动路由到当月分表）
     * @param array $data 收益数据
     * @return array ['success'=>bool, 'id'=>int, 'table'=>string]
     */
    public static function createLog($data)
    {
        try {
            if (!isset($data['createtime'])) {
                $data['createtime'] = time();
            }

            $model = new self();
            $tableName = $model->getTableName($data['createtime']);
            $model->ensureTableExists($tableName);

            $id = Db::name($tableName)->insertGetId($data);

            return [
                'success' => true,
                'id' => $id,
                'table' => $tableName,
            ];
        } catch (\Throwable $e) {
            Log::error('AdIncomeLogSplit::createLog 失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 跨所有分表 + 主表查找指定ID的记录
     * @param int $id
     * @return array|null
     */
    public static function findById($id)
    {
        $model = new self();

        // 1. 先查主表
        $row = Db::name($model->baseTable)->where('id', $id)->find();
        if ($row) {
            $row['_from_table'] = $model->baseTable;
            return $row;
        }

        // 2. 查所有分表
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $row = Db::name($table)->where('id', $id)->find();
            if ($row) {
                $row['_from_table'] = $table;
                return $row;
            }
        }

        return null;
    }

    /**
     * 跨所有分表 + 主表按 transaction_id 查找（防重复回调）
     * @param string $transactionId
     * @return array|null
     */
    public static function findByTransactionId($transactionId)
    {
        if (empty($transactionId)) {
            return null;
        }

        $model = new self();

        // 查主表
        $row = Db::name($model->baseTable)->where('transaction_id', $transactionId)->find();
        if ($row) return $row;

        // 查所有分表
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $row = Db::name($table)->where('transaction_id', $transactionId)->find();
            if ($row) return $row;
        }

        return null;
    }

    /**
     * 获取用户在指定时间范围内的收益记录（跨表）
     * @param int $userId
     * @param int $startTime
     * @param int $endTime
     * @param array $statusFilter 状态过滤，如 [1, 2]
     * @return array
     */
    public static function getUserRecordsInRange($userId, $startTime, $endTime, $statusFilter = [])
    {
        $model = new self();
        $results = [];

        // 查主表
        $query = Db::name($model->baseTable)
            ->where('user_id', $userId)
            ->where('createtime', '>=', $startTime)
            ->where('createtime', '<=', $endTime);
        if (!empty($statusFilter)) {
            $query->whereIn('status', $statusFilter);
        }
        $mainRecords = $query->order('id', 'asc')->select();
        foreach ($mainRecords as $r) {
            $r['_from_table'] = $model->baseTable;
            $results[] = $r;
        }

        // 查分表
        $tables = $model->getTableList(date('Y-m-d', $startTime), date('Y-m-d', $endTime));
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $query = Db::name($table)
                ->where('user_id', $userId)
                ->where('createtime', '>=', $startTime)
                ->where('createtime', '<=', $endTime);
            if (!empty($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            }
            $records = $query->order('id', 'asc')->select();
            foreach ($records as $r) {
                $r['_from_table'] = $table;
                $results[] = $r;
            }
        }

        // 按ID排序
        usort($results, function ($a, $b) {
            return $a['id'] - $b['id'];
        });

        return $results;
    }

    /**
     * 获取用户今日广告收益（跨分表）
     * @param int $userId
     * @return int
     */
    public static function getTodayIncome($userId)
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = strtotime(date('Y-m-d') . ' 23:59:59');
        $model = new self();

        // 从主表统计
        $total = (int)Db::name($model->baseTable)
            ->where('user_id', $userId)
            ->where('status', 'in', [1, 2])
            ->where('createtime', '>=', $todayStart)
            ->where('createtime', '<=', $todayEnd)
            ->sum('user_amount_coin');

        // 从分表统计
        $tables = $model->getTableList(date('Y-m-d', $todayStart), date('Y-m-d', $todayEnd));
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $total += (int)Db::name($table)
                ->where('user_id', $userId)
                ->where('status', 'in', [1, 2])
                ->where('createtime', '>=', $todayStart)
                ->where('createtime', '<=', $todayEnd)
                ->sum('user_amount_coin');
        }

        return $total;
    }

    /**
     * 获取用户所有待确认(CONFIRMED)的收益记录（跨所有表）
     * 用于 checkAndAutoSettle / settleToRedPacket
     * @param int $userId
     * @return array
     */
    public static function getPendingRecords($userId)
    {
        $model = new self();
        $results = [];

        // 主表
        $records = Db::name($model->baseTable)
            ->where('user_id', $userId)
            ->where('status', AdIncomeLog::STATUS_CONFIRMED)
            ->order('id', 'asc')
            ->select();
        foreach ($records as $r) {
            $r['_from_table'] = $model->baseTable;
            $results[] = $r;
        }

        // 所有分表
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $records = Db::name($table)
                ->where('user_id', $userId)
                ->where('status', AdIncomeLog::STATUS_CONFIRMED)
                ->order('id', 'asc')
                ->select();
            foreach ($records as $r) {
                $r['_from_table'] = $table;
                $results[] = $r;
            }
        }

        // 按ID排序
        usort($results, function ($a, $b) {
            return $a['id'] - $b['id'];
        });

        return $results;
    }

    /**
     * 批量更新收益记录状态（指定来源表）
     * @param int $userId
     * @param array $statusFilter 更新前的状态
     * @param int $newStatus 新状态
     * @param int $updatetime
     * @return int 影响行数
     */
    public static function batchUpdateStatus($userId, $statusFilter, $newStatus, $updatetime = null)
    {
        if ($updatetime === null) $updatetime = time();
        $model = new self();
        $total = 0;

        // 主表
        $total += Db::name($model->baseTable)
            ->where('user_id', $userId)
            ->whereIn('status', $statusFilter)
            ->update(['status' => $newStatus, 'updatetime' => $updatetime]);

        // 所有分表
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $total += Db::name($table)
                ->where('user_id', $userId)
                ->whereIn('status', $statusFilter)
                ->update(['status' => $newStatus, 'updatetime' => $updatetime]);
        }

        return $total;
    }

    /**
     * 获取指定范围内的聚合统计（跨分表）
     * @param int $startTime
     * @param int $endTime
     * @param array $extraWhere 额外条件
     * @return array
     */
    public static function getRangeStats($startTime, $endTime, $extraWhere = [])
    {
        $model = new self();
        $stats = [
            'count' => 0,
            'user_coin' => 0,
            'platform_coin' => 0,
            'total_coin' => 0,
        ];

        // 主表
        $query = Db::name($model->baseTable)
            ->where('createtime', '>=', $startTime)
            ->where('createtime', '<=', $endTime)
            ->where('status', 'in', [1, 2]);
        foreach ($extraWhere as $field => $value) {
            $query->where($field, $value);
        }
        $row = $query->field('COUNT(*) as cnt, IFNULL(SUM(user_amount_coin),0) as user_coin, IFNULL(SUM(platform_amount_coin),0) as platform_coin, IFNULL(SUM(amount_coin),0) as total_coin')->find();
        if ($row) {
            $stats['count'] += (int)$row['cnt'];
            $stats['user_coin'] += (int)$row['user_coin'];
            $stats['platform_coin'] += (int)$row['platform_coin'];
            $stats['total_coin'] += (int)$row['total_coin'];
        }

        // 分表
        $tables = $model->getTableList(date('Y-m-d', $startTime), date('Y-m-d', $endTime));
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $query = Db::name($table)
                ->where('createtime', '>=', $startTime)
                ->where('createtime', '<=', $endTime)
                ->where('status', 'in', [1, 2]);
            foreach ($extraWhere as $field => $value) {
                $query->where($field, $value);
            }
            $row = $query->field('COUNT(*) as cnt, IFNULL(SUM(user_amount_coin),0) as user_coin, IFNULL(SUM(platform_amount_coin),0) as platform_coin, IFNULL(SUM(amount_coin),0) as total_coin')->find();
            if ($row) {
                $stats['count'] += (int)$row['cnt'];
                $stats['user_coin'] += (int)$row['user_coin'];
                $stats['platform_coin'] += (int)$row['platform_coin'];
                $stats['total_coin'] += (int)$row['total_coin'];
            }
        }

        return $stats;
    }

    /**
     * 获取指定范围内的独立用户数（跨分表）
     * @param int $startTime
     * @param int $endTime
     * @return int
     */
    public static function getDistinctUserCount($startTime, $endTime)
    {
        $model = new self();
        $allUserIds = [];

        // 主表
        $rows = Db::name($model->baseTable)
            ->where('createtime', '>=', $startTime)
            ->where('createtime', '<=', $endTime)
            ->where('status', 'in', [1, 2])
            ->group('user_id')
            ->field('user_id')
            ->select();
        foreach ($rows as $row) {
            $allUserIds[$row['user_id']] = true;
        }

        // 分表
        $tables = $model->getTableList(date('Y-m-d', $startTime), date('Y-m-d', $endTime));
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $rows = Db::name($table)
                ->where('createtime', '>=', $startTime)
                ->where('createtime', '<=', $endTime)
                ->where('status', 'in', [1, 2])
                ->group('user_id')
                ->field('user_id')
                ->select();
            foreach ($rows as $row) {
                $allUserIds[$row['user_id']] = true;
            }
        }

        return count($allUserIds);
    }

    /**
     * 获取用户收益排行（跨分表，按user_id分组聚合后排序）
     * @param int $startTime
     * @param int $endTime
     * @param int $limit
     * @return array [['user_id'=>1, 'count'=>5, 'total_coin'=>250], ...]
     */
    public static function getUserRanking($startTime, $endTime, $limit = 10)
    {
        $model = new self();
        $userStats = [];

        // 主表
        $rows = Db::name($model->baseTable)
            ->where('createtime', '>=', $startTime)
            ->where('createtime', '<=', $endTime)
            ->where('status', 'in', [1, 2])
            ->group('user_id')
            ->field('user_id, COUNT(*) as cnt, SUM(user_amount_coin) as total_coin')
            ->select();
        foreach ($rows as $row) {
            $uid = $row['user_id'];
            if (!isset($userStats[$uid])) {
                $userStats[$uid] = ['user_id' => intval($uid), 'count' => 0, 'total_coin' => 0];
            }
            $userStats[$uid]['count'] += intval($row['cnt']);
            $userStats[$uid]['total_coin'] += intval($row['total_coin']);
        }

        // 分表
        $tables = $model->getTableList(date('Y-m-d', $startTime), date('Y-m-d', $endTime));
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $rows = Db::name($table)
                ->where('createtime', '>=', $startTime)
                ->where('createtime', '<=', $endTime)
                ->where('status', 'in', [1, 2])
                ->group('user_id')
                ->field('user_id, COUNT(*) as cnt, SUM(user_amount_coin) as total_coin')
                ->select();
            foreach ($rows as $row) {
                $uid = $row['user_id'];
                if (!isset($userStats[$uid])) {
                    $userStats[$uid] = ['user_id' => intval($uid), 'count' => 0, 'total_coin' => 0];
                }
                $userStats[$uid]['count'] += intval($row['cnt']);
                $userStats[$uid]['total_coin'] += intval($row['total_coin']);
            }
        }

        // 按total_coin降序排序
        $list = array_values($userStats);
        usort($list, function ($a, $b) {
            return $b['total_coin'] <=> $a['total_coin'];
        });

        return array_slice($list, 0, $limit);
    }

    /**
     * 获取分表列表列表（用于后台管理分页查询）
     * 根据时间范围确定查哪些表，然后合并结果
     * @param array $where 查询条件
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $offset 偏移量
     * @param int $limit 每页数量
     * @param bool $countOnly 是否只统计总数
     * @return array|int
     */
    public static function paginateAllTables($where, $sort = 'id', $order = 'desc', $offset = 0, $limit = 20, $countOnly = false)
    {
        $model = new self();
        $allTables = array_merge([$model->baseTable], $model->getTableList());

        // 过滤出实际存在的表（去重）
        $prefix = config('database.prefix');
        $existingTables = [];
        foreach ($allTables as $table) {
            $fullTable = $prefix . $table;
            $check = Db::query("SHOW TABLES LIKE '{$fullTable}'");
            if (!empty($check) && !in_array($table, $existingTables)) {
                $existingTables[] = $table;
            }
        }

        if ($countOnly) {
            $total = 0;
            foreach ($existingTables as $table) {
                $query = Db::name($table)->where($where);
                $total += $query->count();
            }
            return $total;
        }

        // 从所有表中收集数据（先收集ID和排序字段）
        $allRows = [];
        foreach ($existingTables as $table) {
            $rows = Db::name($table)
                ->where($where)
                ->field('id, createtime')
                ->select();
            foreach ($rows as $row) {
                $row['_table'] = $table;
                $allRows[] = $row;
            }
        }

        // 全局排序
        usort($allRows, function ($a, $b) use ($sort, $order) {
            $va = $a[$sort] ?? 0;
            $vb = $b[$sort] ?? 0;
            return $order === 'desc' ? ($vb <=> $va) : ($va <=> $vb);
        });

        $total = count($allRows);

        // 分页取 ID
        $pageRows = array_slice($allRows, $offset, $limit);
        if (empty($pageRows)) {
            return ['total' => $total, 'rows' => []];
        }

        // 按 table 分组，批量查询完整记录
        $grouped = [];
        foreach ($pageRows as $row) {
            $grouped[$row['_table']][] = (int)$row['id'];
        }

        $results = [];
        foreach ($grouped as $table => $ids) {
            $rows = Db::name($table)
                ->whereIn('id', $ids)
                ->select();
            foreach ($rows as $row) {
                $results[] = $row;
            }
        }

        // 最终排序
        $pageIds = array_column($pageRows, 'id');
        usort($results, function ($a, $b) use ($pageIds, $order) {
            $posA = array_search($a['id'], $pageIds);
            $posB = array_search($b['id'], $pageIds);
            return $posA - $posB;
        });

        return ['total' => $total, 'rows' => $results];
    }

    /**
     * 获取分组统计（按广告类型/平台等分组，跨分表）
     * @param int $startTime
     * @param int $endTime
     * @param string $groupBy 分组字段
     * @param string $sumField 统计字段
     * @return array
     */
    public static function getGroupStats($startTime, $endTime, $groupBy, $sumField = 'user_amount_coin')
    {
        $model = new self();
        $allTables = array_merge([$model->baseTable], $model->getTableList(date('Y-m-d', $startTime), date('Y-m-d', $endTime)));
        $allTables = array_unique($allTables);

        $grouped = [];

        foreach ($allTables as $table) {
            $rows = Db::name($table)
                ->where('createtime', '>=', $startTime)
                ->where('createtime', '<=', $endTime)
                ->where('status', 'in', [1, 2])
                ->group($groupBy)
                ->field("{$groupBy}, COUNT(*) as cnt, IFNULL(SUM({$sumField}),0) as total")
                ->select();

            foreach ($rows as $row) {
                $key = $row[$groupBy];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        $groupBy => $key,
                        'cnt' => 0,
                        'total' => 0,
                    ];
                }
                $grouped[$key]['cnt'] += (int)$row['cnt'];
                $grouped[$key]['total'] += (int)$row['total'];
            }
        }

        return array_values($grouped);
    }
}
