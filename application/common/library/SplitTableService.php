<?php

namespace app\common\library;

use think\Db;
use think\Log;
use think\Cache;

/**
 * 分表管理服务
 * 
 * 负责自动创建和管理按年分表
 */
class SplitTableService
{
    // 需要分表的配置
    protected static $splitTables = [
        'withdraw_order' => [
            'type' => 'year',
            'description' => '提现订单分表',
        ],
        'red_packet_task' => [
            'type' => 'year',
            'description' => '红包任务分表',
        ],
        'user_red_packet_accumulate' => [
            'type' => 'year',
            'description' => '红包领取记录分表',
        ],
        'task_participation' => [
            'type' => 'year',
            'description' => '任务参与记录分表',
        ],
    ];

    // 缓存键
    const CACHE_KEY_LAST_CHECK = 'split_table:last_check';
    const CACHE_TTL = 86400; // 24小时

    /**
     * 自动检查并创建分表
     * 在应用启动时调用，确保当年和下一年分表存在
     */
    public static function autoCreateIfNeeded()
    {
        $lastCheck = Cache::get(self::CACHE_KEY_LAST_CHECK);
        $today = date('Y-m-d');
        
        // 每天只检查一次
        if ($lastCheck === $today) {
            return true;
        }
        
        // 检查并创建当年和下一年分表
        $result = static::createTablesForYears(2);
        
        // 更新最后检查时间
        Cache::set(self::CACHE_KEY_LAST_CHECK, $today, self::CACHE_TTL);
        
        return $result;
    }

    /**
     * 创建指定年数的分表
     * @param int $years 创建未来N年的分表
     * @return array 创建结果
     */
    public static function createTablesForYears($years = 2)
    {
        $prefix = config('database.prefix');
        $results = [
            'created' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach (static::$splitTables as $baseTable => $config) {
            $mainTable = $prefix . $baseTable;
            
            // 检查主表是否存在
            $mainExists = Db::query("SHOW TABLES LIKE '{$mainTable}'");
            if (empty($mainExists)) {
                Log::warning("分表管理: 主表 {$mainTable} 不存在，跳过");
                continue;
            }

            for ($i = 0; $i < $years; $i++) {
                $timestamp = strtotime("+{$i} years");
                $suffix = '_' . date('Y', $timestamp);
                $tableName = $baseTable . $suffix;
                $fullTableName = $prefix . $tableName;

                // 检查表是否已存在
                $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");

                if (!empty($exists)) {
                    $results['skipped'][] = $fullTableName;
                    continue;
                }

                // 创建表
                try {
                    $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`";
                    Db::execute($createSql);
                    
                    $results['created'][] = $fullTableName;
                    Log::info("分表自动创建成功: {$fullTableName}");
                } catch (\Exception $e) {
                    $results['failed'][] = $fullTableName;
                    Log::error("分表自动创建失败: {$fullTableName}, 错误: " . $e->getMessage());
                }
            }
        }

        return $results;
    }

    /**
     * 创建指定月数的分表（兼容旧方法名，实际调用按年创建）
     * @param int $months 创建未来N个月的分表（参数已废弃）
     * @return array 创建结果
     */
    public static function createTablesForMonths($months = 2)
    {
        return static::createTablesForYears(2);
    }

    /**
     * 确保指定表名的分表存在
     * @param string $baseTable 主表名
     * @param int|null $timestamp 时间戳，默认当前时间
     * @return bool
     */
    public static function ensureTableExists($baseTable, $timestamp = null)
    {
        if (!isset(static::$splitTables[$baseTable])) {
            return false;
        }

        $prefix = config('database.prefix');
        
        if ($timestamp === null) {
            $timestamp = time();
        }

        $config = static::$splitTables[$baseTable];
        $type = $config['type'] ?? 'year';
        
        // 根据分表类型生成后缀
        if ($type === 'year') {
            $suffix = '_' . date('Y', $timestamp);
        } else {
            $suffix = '_' . date('Ym', $timestamp);
        }
        
        $tableName = $baseTable . $suffix;
        $fullTableName = $prefix . $tableName;
        $mainTable = $prefix . $baseTable;

        // 检查表是否已存在
        $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
        if (!empty($exists)) {
            return true;
        }

        // 创建表
        try {
            $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`";
            Db::execute($createSql);
            Log::info("分表按需创建成功: {$fullTableName}");
            return true;
        } catch (\Exception $e) {
            Log::error("分表按需创建失败: {$fullTableName}, 错误: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取所有分表列表
     * @param string $baseTable 主表名
     * @return array
     */
    public static function getTableList($baseTable)
    {
        $prefix = config('database.prefix');
        $tables = [];
        
        $allTables = Db::query("SHOW TABLES LIKE '{$prefix}{$baseTable}%'");
        
        foreach ($allTables as $row) {
            $tableName = current($row);
            $tables[] = str_replace($prefix, '', $tableName);
        }
        
        return $tables;
    }

    /**
     * 获取分表统计信息
     * @return array
     */
    public static function getStats()
    {
        $stats = [];
        $prefix = config('database.prefix');

        foreach (static::$splitTables as $baseTable => $config) {
            $tables = static::getTableList($baseTable);
            $stats[$baseTable] = [
                'description' => $config['description'],
                'tables' => $tables,
                'count' => count($tables),
            ];

            // 获取每个表的记录数
            foreach ($tables as $table) {
                try {
                    $count = Db::name($table)->count();
                    $stats[$baseTable]['records'][$table] = $count;
                } catch (\Exception $e) {
                    $stats[$baseTable]['records'][$table] = 'error';
                }
            }
        }

        return $stats;
    }

    /**
     * 清理过期的分表
     * @param int $keepYears 保留最近N年的分表
     * @return array
     */
    public static function cleanOldTables($keepYears = 3)
    {
        $prefix = config('database.prefix');
        $results = [
            'deleted' => [],
            'skipped' => [],
            'failed' => [],
        ];

        $cutoffYear = date('Y', strtotime("-{$keepYears} years"));

        foreach (static::$splitTables as $baseTable => $config) {
            $tables = static::getTableList($baseTable);
            $type = $config['type'] ?? 'year';
            
            foreach ($tables as $table) {
                // 跳过主表
                if ($table === $baseTable) {
                    continue;
                }
                
                // 根据分表类型解析表名中的后缀
                if ($type === 'year') {
                    // 按年分表格式：withdraw_order_2026
                    if (preg_match('/_(\d{4})$/', $table, $matches)) {
                        $year = $matches[1];
                        
                        // 跳过保留年限内的表
                        if ($year >= $cutoffYear) {
                            $results['skipped'][] = $table;
                            continue;
                        }

                        $fullTableName = $prefix . $table;
                        
                        try {
                            // 检查表是否为空
                            $count = Db::name($table)->count();
                            if ($count > 0) {
                                $results['skipped'][] = "{$table} (有{$count}条记录)";
                                continue;
                            }

                            // 删除空表
                            Db::execute("DROP TABLE IF EXISTS `{$fullTableName}`");
                            $results['deleted'][] = $table;
                            Log::info("分表清理成功: {$fullTableName}");
                        } catch (\Exception $e) {
                            $results['failed'][] = $table;
                            Log::error("分表清理失败: {$fullTableName}, 错误: " . $e->getMessage());
                        }
                    }
                } else {
                    // 按月分表格式：withdraw_order_202603
                    if (preg_match('/_(\d{6})$/', $table, $matches)) {
                        $suffix = $matches[1];
                        $cutoffSuffix = $cutoffYear . date('m');
                        
                        // 跳过保留期限内的表
                        if ($suffix >= $cutoffSuffix) {
                            $results['skipped'][] = $table;
                            continue;
                        }

                        $fullTableName = $prefix . $table;
                        
                        try {
                            // 检查表是否为空
                            $count = Db::name($table)->count();
                            if ($count > 0) {
                                $results['skipped'][] = "{$table} (有{$count}条记录)";
                                continue;
                            }

                            // 删除空表
                            Db::execute("DROP TABLE IF EXISTS `{$fullTableName}`");
                            $results['deleted'][] = $table;
                            Log::info("分表清理成功: {$fullTableName}");
                        } catch (\Exception $e) {
                            $results['failed'][] = $table;
                            Log::error("分表清理失败: {$fullTableName}, 错误: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $results;
    }
}
