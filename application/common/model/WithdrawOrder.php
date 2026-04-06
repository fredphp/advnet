<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 提现订单模型
 * 支持按年分表
 */
class WithdrawOrder extends Model
{
    // 基础表名
    protected $name = 'withdraw_order';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 状态常量
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_APPROVED = 1;     // 审核通过
    const STATUS_TRANSFERING = 2;  // 打款中
    const STATUS_SUCCESS = 3;      // 提现成功
    const STATUS_REJECTED = 4;     // 审核拒绝
    const STATUS_FAILED = 5;       // 打款失败
    const STATUS_CANCELED = 6;     // 已取消
    
    // 状态列表
    public static $statusList = [
        0 => '待审核',
        1 => '审核通过',
        2 => '打款中',
        3 => '提现成功',
        4 => '审核拒绝',
        5 => '打款失败',
        6 => '已取消'
    ];
    
    // 提现方式（只保留微信）
    public static $typeList = [
        'wechat' => '微信',
    ];
    
    /**
     * 获取当年表名
     * @param int $timestamp 时间戳，默认当前时间
     * @return string
     */
    public static function getTableByYear($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        return 'withdraw_order_' . date('Y', $timestamp);
    }
    
    /**
     * 获取当年表名（兼容旧方法名）
     * @param int $timestamp 时间戳，默认当前时间
     * @return string
     */
    public static function getTableByMonth($timestamp = null)
    {
        return self::getTableByYear($timestamp);
    }
    
    /**
     * 根据日期范围获取所有需要查询的表名（按年分表）
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @return array
     */
    public static function getTablesByRange($startTime, $endTime)
    {
        $tables = [];
        $startYear = intval(date('Y', $startTime));
        $endYear = intval(date('Y', $endTime));
        
        for ($year = $startYear; $year <= $endYear; $year++) {
            $tables[] = 'withdraw_order_' . $year;
        }
        
        return $tables;
    }
    
    /**
     * 根据订单号获取对应分表
     * 订单号格式：WD202603061800535051
     * @param string $orderNo
     * @return string|null
     */
    public static function getTableByOrderNo($orderNo)
    {
        // 从订单号提取年份 WD20260306... -> 2026
        if (preg_match('/^WD(\d{4})/', $orderNo, $matches)) {
            return 'withdraw_order_' . $matches[1];
        }
        return null;
    }
    
    /**
     * 检查分表是否存在
     * @param string $tableName
     * @return bool
     */
    public static function tableExists($tableName)
    {
        static $existingTables = [];
        
        if (!isset($existingTables[$tableName])) {
            $prefix = \think\Config::get('database.prefix');
            $fullTable = $prefix . $tableName;
            $existingTables[$tableName] = Db::query("SHOW TABLES LIKE '{$fullTable}'") ? true : false;
        }
        
        return $existingTables[$tableName];
    }
    
    /**
     * 创建分表（复制主表结构）
     * @param string $tableName 分表名（不含前缀，如 withdraw_order_2026）
     * @return bool
     */
    public static function createTable($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTable = $prefix . $tableName;
        $mainTable = $prefix . 'withdraw_order';
        
        try {
            // 使用 LIKE 复制主表结构，确保分表和主表结构完全一致
            $sql = "CREATE TABLE IF NOT EXISTS `{$fullTable}` LIKE `{$mainTable}`";
            Db::execute($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 确保分表存在（不存在则创建）
     * @param string $tableName
     * @return bool
     */
    public static function ensureTableExists($tableName)
    {
        if (!self::tableExists($tableName)) {
            return self::createTable($tableName);
        }
        return true;
    }
    
    /**
     * 确保指定时间范围内的所有分表都存在
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @return array 返回已确保存在的表名列表
     */
    public static function ensureTablesExistByRange($startTime, $endTime)
    {
        $tables = self::getTablesByRange($startTime, $endTime);
        foreach ($tables as $table) {
            self::ensureTableExists($table);
        }
        return $tables;
    }
    
    /**
     * 获取或创建当年分表
     * @param int $timestamp
     * @return string
     */
    public static function getOrCreateTable($timestamp = null)
    {
        $tableName = self::getTableByYear($timestamp);
        self::ensureTableExists($tableName);
        return $tableName;
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 生成订单号
     * @return string
     */
    public static function generateOrderNo()
    {
        return 'WD' . date('YmdHis') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusList[$data['status']] ?? '';
    }
    
    /**
     * 获取提现方式文本
     */
    public function getWithdrawTypeTextAttr($value, $data)
    {
        return self::$typeList[$data['withdraw_type']] ?? '';
    }
    
    /**
     * 分表查询 - 获取列表
     * @param array $where 查询条件
     * @param string $sort 排序字段
     * @param string $order 排序方向
     * @param int $offset 偏移量
     * @param int $limit 限制数量
     * @param int|null $startTime 开始时间
     * @param int|null $endTime 结束时间
     * @return array
     */
    public static function getListFromSplitTables($where, $sort = 'id', $order = 'desc', $offset = 0, $limit = 10, $startTime = null, $endTime = null)
    {
        // 默认查询最近3年的数据
        if (!$startTime) {
            $startTime = strtotime('-3 years');
        }
        if (!$endTime) {
            $endTime = time();
        }
        
        // 确保分表存在
        $tables = self::ensureTablesExistByRange($startTime, $endTime);
        $prefix = \think\Config::get('database.prefix');
        
        // 构建UNION ALL查询
        $unionQueries = [];
        foreach ($tables as $table) {
            if (self::tableExists($table)) {
                $unionQueries[] = "SELECT * FROM {$prefix}{$table}";
            }
        }
        
        if (empty($unionQueries)) {
            return ['total' => 0, 'rows' => []];
        }
        
        $unionSql = '(' . implode(' UNION ALL ', $unionQueries) . ') AS wo';
        
        // 构建WHERE条件
        $whereStr = '1=1';
        $bindParams = [];
        
        foreach ($where as $key => $value) {
            if (is_array($value)) {
                if ($value[0] == 'in') {
                    $whereStr .= " AND wo.{$key} IN (" . implode(',', array_map(function($v) { return '?'; }, $value[1])) . ")";
                    $bindParams = array_merge($bindParams, $value[1]);
                } elseif ($value[0] == 'between') {
                    $whereStr .= " AND wo.{$key} BETWEEN ? AND ?";
                    $bindParams[] = $value[1][0];
                    $bindParams[] = $value[1][1];
                } elseif ($value[0] == 'like') {
                    $whereStr .= " AND wo.{$key} LIKE ?";
                    $bindParams[] = '%' . $value[1] . '%';
                } elseif ($value[0] == '=' || $value[0] == '=') {
                    $whereStr .= " AND wo.{$key} = ?";
                    $bindParams[] = $value[1];
                } else {
                    $whereStr .= " AND wo.{$key} {$value[0]} ?";
                    $bindParams[] = $value[1];
                }
            } else {
                $whereStr .= " AND wo.{$key} = ?";
                $bindParams[] = $value;
            }
        }
        
        // 查询总数
        $countSql = "SELECT COUNT(*) as total FROM {$unionSql} WHERE {$whereStr}";
        $totalResult = Db::query($countSql, $bindParams);
        $total = $totalResult[0]['total'] ?? 0;
        
        // 查询列表
        $listSql = "SELECT wo.* FROM {$unionSql} WHERE {$whereStr} ORDER BY wo.{$sort} {$order} LIMIT {$offset}, {$limit}";
        $rows = Db::query($listSql, $bindParams);
        
        return ['total' => $total, 'rows' => $rows];
    }
}
