<?php

namespace app\common\model;

use think\Db;
use think\Log;

/**
 * 广告红包分表模型
 *
 * 按月分表，表名格式：ad_red_packet_202507
 *
 * 使用方式：
 * // 创建红包（自动路由到当月分表）
 * AdRedPacketSplit::createPacket($data);
 *
 * // 跨表查找指定ID
 * $packet = AdRedPacketSplit::findById($id);
 *
 * // 获取用户未领取红包摘要（跨表）
 * $summary = AdRedPacketSplit::getUnclaimedSummary($userId);
 *
 * // 获取用户所有红包（跨表分页）
 * $result = AdRedPacketSplit::getUserPacketsPaginated($userId, 1, 20);
 */
class AdRedPacketSplit extends SplitTableModel
{
    // 分表类型：按月
    protected $splitType = 'month';

    // 分表依据字段
    protected $splitField = 'createtime';

    // 主表名
    protected $baseTable = 'ad_red_packet';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // 来源
    const SOURCE_AD_INCOME = 'ad_income';

    // 状态
    const STATUS_UNCLAIMED = 0;
    const STATUS_CLAIMED = 1;
    const STATUS_EXPIRED = 2;

    public static $statusList = [
        self::STATUS_UNCLAIMED => '未领取',
        self::STATUS_CLAIMED => '已领取',
        self::STATUS_EXPIRED => '已过期',
    ];

    /**
     * 创建红包记录（自动路由到当月分表）
     * @param array $data 红包数据
     * @return array ['success'=>bool, 'id'=>int, 'table'=>string]
     */
    public static function createPacket($data)
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
            Log::error('AdRedPacketSplit::createPacket 失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 跨所有表 + 主表查找指定ID的红包
     * @param int $id
     * @return array|null 包含 _from_table 字段
     */
    public static function findById($id)
    {
        $model = new self();

        // 先查主表
        $row = Db::name($model->baseTable)->where('id', $id)->find();
        if ($row) {
            $row['_from_table'] = $model->baseTable;
            return $row;
        }

        // 查所有分表
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
     * 获取用户未领取红包数量和总金额（跨所有表）
     * @param int $userId
     * @return array ['count'=>int, 'total_amount'=>float]
     */
    public static function getUnclaimedSummary($userId)
    {
        $model = new self();
        $count = 0;
        $totalAmount = 0;

        // 主表
        $row = Db::name($model->baseTable)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_UNCLAIMED)
            ->field('COUNT(*) AS cnt, IFNULL(SUM(amount), 0) AS total')
            ->find();
        $count += (int)($row['cnt'] ?? 0);
        $totalAmount += (float)($row['total'] ?? 0);

        // 所有分表
        $tables = $model->getTableList();
        foreach ($tables as $table) {
            if ($table === $model->baseTable) continue;
            $row = Db::name($table)
                ->where('user_id', $userId)
                ->where('status', self::STATUS_UNCLAIMED)
                ->field('COUNT(*) AS cnt, IFNULL(SUM(amount), 0) AS total')
                ->find();
            $count += (int)($row['cnt'] ?? 0);
            $totalAmount += (float)($row['total'] ?? 0);
        }

        return [
            'count' => $count,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * 标记用户所有未领取红包为已领取（跨所有表）
     * 用于 claimFreezeBalance 成功后，同步更新通知红包状态
     * @param int $userId
     * @return int 更新的记录数
     */
    public static function markAllClaimed($userId)
    {
        $model = new self();
        $total = 0;

        $allTables = array_unique(array_merge([$model->baseTable], $model->getTableList()));
        $now = time();

        foreach ($allTables as $table) {
            $affected = Db::name($table)
                ->where('user_id', $userId)
                ->where('status', self::STATUS_UNCLAIMED)
                ->update([
                    'status' => self::STATUS_CLAIMED,
                    'claim_time' => $now,
                    'updatetime' => $now,
                ]);
            $total += $affected;
        }

        return $total;
    }

    /**
     * 获取用户所有红包列表（跨表分页）
     * ★ 优化：使用 UNION ALL + SQL级排序分页，避免全量内存排序
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getUserPacketsPaginated($userId, $page = 1, $limit = 20)
    {
        $model = new self();
        $prefix = config('database.prefix');
        $userId = (int)$userId;
        $page = max(1, (int)$page);
        $limit = min(100, max(1, (int)$limit));
        $offset = ($page - 1) * $limit;

        // 收集所有实际存在的表
        $allTables = array_unique(array_merge([$model->baseTable], $model->getTableList()));
        $existingTables = [];
        foreach ($allTables as $table) {
            $fullTable = $prefix . $table;
            $check = Db::query("SHOW TABLES LIKE '{$fullTable}'");
            if (!empty($check)) {
                $existingTables[] = $table;
            }
        }

        if (empty($existingTables)) {
            return [
                'list' => [],
                'total' => 0,
                'unclaimed_count' => 0,
                'unclaimed_total' => 0,
            ];
        }

        // ★ 使用 UNION ALL + SQL ORDER BY + LIMIT 实现数据库级分页
        $unions = [];
        foreach ($existingTables as $table) {
            $unions[] = "SELECT *, '{$table}' AS _table FROM `{$prefix}{$table}` WHERE user_id = {$userId}";
        }
        $unionSql = implode(' UNION ALL ', $unions);

        // 获取总数
        $countSql = "SELECT COUNT(*) AS total FROM ({$unionSql}) AS _union_all";
        $totalRow = Db::query($countSql);
        $total = (int)($totalRow[0]['total'] ?? 0);

        // 获取分页数据（SQL级排序+分页，避免全量加载到内存）
        $pageSql = "SELECT * FROM ({$unionSql}) AS _union_all ORDER BY id DESC LIMIT {$offset}, {$limit}";
        $list = Db::query($pageSql);

        // 获取未领取统计（跨表轻量聚合）
        $unclaimedCount = 0;
        $unclaimedTotal = 0;
        foreach ($existingTables as $table) {
            $row = Db::name($table)
                ->where('user_id', $userId)
                ->where('status', self::STATUS_UNCLAIMED)
                ->field('COUNT(*) AS cnt, IFNULL(SUM(amount), 0) AS total')
                ->find();
            $unclaimedCount += (int)($row['cnt'] ?? 0);
            $unclaimedTotal += (float)($row['total'] ?? 0);
        }

        return [
            'list' => $list,
            'total' => $total,
            'unclaimed_count' => $unclaimedCount,
            'unclaimed_total' => $unclaimedTotal,
        ];
    }

    /**
     * 领取红包（跨表查找并更新）
     * @param int $userId
     * @param int $packetId
     * @return array ['success'=>bool, 'amount'=>int, 'message'=>string]
     */
    public static function claimPacket($userId, $packetId)
    {
        $row = self::findById($packetId);
        if (!$row) {
            return ['success' => false, 'message' => '红包不存在'];
        }

        if ($row['user_id'] != $userId) {
            return ['success' => false, 'message' => '无权操作'];
        }

        if ($row['status'] == self::STATUS_CLAIMED) {
            return ['success' => false, 'message' => '红包已领取'];
        }

        if ($row['status'] == self::STATUS_EXPIRED) {
            return ['success' => false, 'message' => '红包已过期'];
        }

        // 检查过期
        if ($row['expire_time'] > 0 && time() > $row['expire_time']) {
            Db::name($row['_from_table'])->where('id', $packetId)
                ->update(['status' => self::STATUS_EXPIRED, 'updatetime' => time()]);
            return ['success' => false, 'message' => '红包已过期'];
        }

        $amount = (int)round($row['amount']);

        // 更新状态
        Db::name($row['_from_table'])->where('id', $packetId)
            ->update([
                'status' => self::STATUS_CLAIMED,
                'claim_time' => time(),
                'updatetime' => time(),
            ]);

        return ['success' => true, 'amount' => $amount, 'table' => $row['_from_table']];
    }

    /**
     * 过期红包处理（跨所有表）
     * @param int|null $beforeTime 截止时间戳
     * @return int 过期数量
     */
    public static function expireAllPackets($beforeTime = null)
    {
        if ($beforeTime === null) $beforeTime = time();
        $model = new self();
        $count = 0;

        $allTables = array_unique(array_merge([$model->baseTable], $model->getTableList()));

        foreach ($allTables as $table) {
            $affected = Db::name($table)
                ->where('status', self::STATUS_UNCLAIMED)
                ->where('expire_time', '<=', $beforeTime)
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'updatetime' => time(),
                ]);
            $count += $affected;
        }

        return $count;
    }

    /**
     * 获取统计信息（跨所有表）
     * @param int|null $startTime
     * @param int|null $endTime
     * @return array
     */
    public static function getStats($startTime = null, $endTime = null)
    {
        $model = new self();
        $allTables = array_unique(array_merge([$model->baseTable], $model->getTableList()));

        $stats = [
            'total' => 0,
            'unclaimed' => 0,
            'claimed' => 0,
            'expired' => 0,
            'total_amount' => 0,
            'claimed_amount' => 0,
        ];

        foreach ($allTables as $table) {
            $query = Db::name($table);
            if ($startTime !== null) {
                $query->where('createtime', '>=', $startTime);
            }
            if ($endTime !== null) {
                $query->where('createtime', '<=', $endTime);
            }
            $row = $query->field('
                COUNT(*) as total,
                SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as unclaimed,
                SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as claimed,
                SUM(CASE WHEN status=2 THEN 1 ELSE 0 END) as expired,
                IFNULL(SUM(amount), 0) as total_amount,
                IFNULL(SUM(CASE WHEN status=1 THEN amount ELSE 0 END), 0) as claimed_amount
            ')->find();

            $stats['total'] += (int)($row['total'] ?? 0);
            $stats['unclaimed'] += (int)($row['unclaimed'] ?? 0);
            $stats['claimed'] += (int)($row['claimed'] ?? 0);
            $stats['expired'] += (int)($row['expired'] ?? 0);
            $stats['total_amount'] += (float)($row['total_amount'] ?? 0);
            $stats['claimed_amount'] += (float)($row['claimed_amount'] ?? 0);
        }

        return $stats;
    }

    /**
     * 分页查询（用于后台管理，跨所有表）
     * @param array $where
     * @param string $sort
     * @param string $order
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public static function paginateAllTables($where, $sort = 'id', $order = 'desc', $offset = 0, $limit = 20)
    {
        $model = new self();
        $allTables = array_unique(array_merge([$model->baseTable], $model->getTableList()));

        // 过滤实际存在的表
        $prefix = config('database.prefix');
        $existingTables = [];
        foreach ($allTables as $table) {
            $fullTable = $prefix . $table;
            $check = Db::query("SHOW TABLES LIKE '{$fullTable}'");
            if (!empty($check) && !in_array($table, $existingTables)) {
                $existingTables[] = $table;
            }
        }

        // 收集所有ID用于排序和分页
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

        // 排序
        usort($allRows, function ($a, $b) use ($sort, $order) {
            $va = $a[$sort] ?? 0;
            $vb = $b[$sort] ?? 0;
            return $order === 'desc' ? ($vb <=> $va) : ($va <=> $vb);
        });

        $total = count($allRows);
        $pageRows = array_slice($allRows, $offset, $limit);

        if (empty($pageRows)) {
            return ['total' => $total, 'rows' => []];
        }

        // 按表分组批量查询完整记录
        $grouped = [];
        foreach ($pageRows as $row) {
            $grouped[$row['_table']][] = (int)$row['id'];
        }

        $results = [];
        foreach ($grouped as $table => $ids) {
            $rows = Db::name($table)->whereIn('id', $ids)->select();
            foreach ($rows as $row) {
                $results[] = $row;
            }
        }

        // 保持排序
        $pageIds = array_column($pageRows, 'id');
        usort($results, function ($a, $b) use ($pageIds) {
            return array_search($a['id'], $pageIds) - array_search($b['id'], $pageIds);
        });

        return ['total' => $total, 'rows' => $results];
    }
}
