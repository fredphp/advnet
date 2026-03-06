<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Log;

/**
 * 分表模型基类
 * 
 * 支持按月分表，自动路由数据到对应月份的表
 * 
 * 使用方式：
 * class WithdrawOrder extends SplitTableModel
 * {
 *     protected $splitType = 'month'; // 按月分表
 *     protected $splitField = 'createtime'; // 分表依据字段
 * }
 */
abstract class SplitTableModel extends Model
{
    // 分表类型：month=按月, year=按年
    protected $splitType = 'month';
    
    // 分表依据字段（时间戳字段）
    protected $splitField = 'createtime';
    
    // 主表名（不带前缀）
    protected $baseTable = '';
    
    // 当前操作的表名后缀
    protected $currentSuffix = '';
    
    // 是否启用分表（可在子类中覆盖）
    protected $enableSplit = true;
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        
        if (empty($this->baseTable)) {
            $this->baseTable = $this->name;
        }
    }
    
    /**
     * 获取分表后缀
     * @param int|null $timestamp 时间戳，默认使用当前时间
     * @return string
     */
    public function getSuffix($timestamp = null)
    {
        if (!$this->enableSplit) {
            return '';
        }
        
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        if ($this->splitType === 'month') {
            return '_' . date('Ym', $timestamp);
        } elseif ($this->splitType === 'year') {
            return '_' . date('Y', $timestamp);
        }
        
        return '';
    }
    
    /**
     * 获取表名（带后缀）
     * @param int|null $timestamp 时间戳
     * @return string
     */
    public function getTableName($timestamp = null)
    {
        if (!$this->enableSplit) {
            return $this->baseTable;
        }
        
        $suffix = $this->getSuffix($timestamp);
        return $this->baseTable . $suffix;
    }
    
    /**
     * 设置当前操作的表
     * @param int|null $timestamp 时间戳
     * @return $this
     */
    public function useTable($timestamp = null)
    {
        $this->currentSuffix = $this->getSuffix($timestamp);
        $this->name = $this->baseTable . $this->currentSuffix;
        return $this;
    }
    
    /**
     * 使用主表（不分表）
     * @return $this
     */
    public function useMainTable()
    {
        $this->name = $this->baseTable;
        return $this;
    }
    
    /**
     * 获取所有分表名列表
     * @param string $startTime 开始时间 Y-m-d
     * @param string $endTime 结束时间 Y-m-d
     * @return array
     */
    public function getTableList($startTime = null, $endTime = null)
    {
        $tables = [];
        
        if (!$this->enableSplit) {
            return [$this->baseTable];
        }
        
        // 获取数据库中所有表
        $prefix = config('database.prefix');
        $allTables = Db::query("SHOW TABLES LIKE '{$prefix}{$this->baseTable}%'");
        
        $tableKey = 'Tables_in_' . config('database.database') . ' (' . $prefix . $this->baseTable . '%)';
        
        foreach ($allTables as $row) {
            $tableName = current($row);
            $tables[] = str_replace($prefix, '', $tableName);
        }
        
        // 如果有时间范围，筛选
        if ($startTime && $endTime) {
            $start = strtotime($startTime);
            $end = strtotime($endTime);
            $result = [];
            
            $current = $start;
            while ($current <= $end) {
                $suffix = $this->getSuffix($current);
                $tableName = $this->baseTable . $suffix;
                if (in_array($tableName, $tables)) {
                    $result[] = $tableName;
                }
                
                if ($this->splitType === 'month') {
                    $current = strtotime('+1 month', $current);
                } else {
                    $current = strtotime('+1 year', $current);
                }
            }
            
            return $result;
        }
        
        return $tables;
    }
    
    /**
     * 检查表是否存在，不存在则创建
     * @param string $tableName 表名（不带前缀）
     * @return bool
     */
    public function ensureTableExists($tableName)
    {
        $prefix = config('database.prefix');
        $fullTableName = $prefix . $tableName;
        
        // 检查表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$fullTableName}'");
        if (!empty($exists)) {
            return true;
        }
        
        // 获取主表结构
        $mainTable = $prefix . $this->baseTable;
        
        // 先检查主表是否存在
        $mainExists = Db::query("SHOW TABLES LIKE '{$mainTable}'");
        if (empty($mainExists)) {
            Log::error("分表创建失败: 主表 {$mainTable} 不存在");
            return false;
        }
        
        $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` LIKE `{$mainTable}`";
        
        try {
            Db::execute($createSql);
            Log::info("分表创建成功: {$fullTableName}");
            return true;
        } catch (\Exception $e) {
            Log::error("分表创建失败: {$fullTableName}, 错误: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 自动创建当月和下月分表
     * 在应用启动时调用
     * @return bool
     */
    public static function autoCreateTables()
    {
        $instance = new static();
        
        if (!$instance->enableSplit) {
            return true;
        }
        
        // 创建当月和下月分表
        for ($i = 0; $i < 2; $i++) {
            $timestamp = strtotime("+{$i} months");
            $tableName = $instance->getTableName($timestamp);
            $instance->ensureTableExists($tableName);
        }
        
        return true;
    }
    
    /**
     * 创建当月分表
     * @return bool
     */
    public function createCurrentMonthTable()
    {
        $tableName = $this->getTableName();
        return $this->ensureTableExists($tableName);
    }
    
    /**
     * 插入数据（自动路由到正确的表）
     * @param array $data 数据
     * @return int|string
     */
    public function insertSplit($data)
    {
        // 获取时间戳
        $timestamp = isset($data[$this->splitField]) ? $data[$this->splitField] : time();
        
        // 设置目标表
        $tableName = $this->getTableName($timestamp);
        $this->ensureTableExists($tableName);
        
        // 切换到目标表插入
        $this->name = $tableName;
        
        return $this->insertGetId($data);
    }
    
    /**
     * 跨表查询（查询指定时间范围内的所有表）
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param callable|null $callback 查询回调函数
     * @return array
     */
    public function querySplit($startTime, $endTime, $callback = null)
    {
        $results = [];
        
        // 获取时间范围内的所有表
        $tables = $this->getTableList(
            date('Y-m-d', $startTime),
            date('Y-m-d', $endTime)
        );
        
        foreach ($tables as $table) {
            $query = Db::name($table);
            
            if ($callback) {
                $query = $callback($query);
            }
            
            // 添加时间范围条件
            $query->where($this->splitField, '>=', $startTime)
                  ->where($this->splitField, '<=', $endTime);
            
            $data = $query->select();
            if ($data) {
                $results = array_merge($results, $data->toArray());
            }
        }
        
        return $results;
    }
    
    /**
     * 跨表统计
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param string $aggregate 统计类型：count/sum/avg/max/min
     * @param string $field 统计字段
     * @param callable|null $callback 查询回调
     * @return mixed
     */
    public function aggregateSplit($startTime, $endTime, $aggregate = 'count', $field = '*', $callback = null)
    {
        $tables = $this->getTableList(
            date('Y-m-d', $startTime),
            date('Y-m-d', $endTime)
        );
        
        $total = 0;
        $count = 0;
        $values = [];
        
        foreach ($tables as $table) {
            $query = Db::name($table);
            
            if ($callback) {
                $query = $callback($query);
            }
            
            $query->where($this->splitField, '>=', $startTime)
                  ->where($this->splitField, '<=', $endTime);
            
            switch ($aggregate) {
                case 'count':
                    $total += $query->count($field);
                    break;
                case 'sum':
                    $total += $query->sum($field);
                    break;
                case 'avg':
                    $result = $query->avg($field);
                    if ($result) {
                        $total += $result * $query->count();
                        $count += $query->count();
                    }
                    break;
                case 'max':
                    $result = $query->max($field);
                    $values[] = $result;
                    break;
                case 'min':
                    $result = $query->min($field);
                    $values[] = $result;
                    break;
            }
        }
        
        switch ($aggregate) {
            case 'avg':
                return $count > 0 ? $total / $count : 0;
            case 'max':
                return !empty($values) ? max($values) : 0;
            case 'min':
                return !empty($values) ? min($values) : 0;
            default:
                return $total;
        }
    }
    
    /**
     * 跨表分组统计
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @param string $groupBy 分组字段
     * @param array $aggregates 聚合函数配置 [['type'=>'count', 'field'=>'*', 'as'=>'total'], ...]
     * @param callable|null $callback 查询回调
     * @return array
     */
    public function groupAggregateSplit($startTime, $endTime, $groupBy, $aggregates = [], $callback = null)
    {
        $tables = $this->getTableList(
            date('Y-m-d', $startTime),
            date('Y-m-d', $endTime)
        );
        
        $groupedResults = [];
        
        foreach ($tables as $table) {
            $query = Db::name($table);
            
            if ($callback) {
                $query = $callback($query);
            }
            
            $query->where($this->splitField, '>=', $startTime)
                  ->where($this->splitField, '<=', $endTime);
            
            // 构建聚合查询
            $fields = [$groupBy];
            foreach ($aggregates as $agg) {
                $type = $agg['type'];
                $field = $agg['field'];
                $as = $agg['as'];
                $fields[] = "{$type}({$field}) as {$as}";
            }
            
            $query->field(implode(',', $fields));
            $query->group($groupBy);
            
            $data = $query->select();
            
            foreach ($data as $row) {
                $key = $row[$groupBy];
                if (!isset($groupedResults[$key])) {
                    $groupedResults[$key] = [];
                    foreach ($aggregates as $agg) {
                        $groupedResults[$key][$agg['as']] = 0;
                    }
                    $groupedResults[$key][$groupBy] = $key;
                }
                
                foreach ($aggregates as $agg) {
                    $groupedResults[$key][$agg['as']] += $row[$agg['as']];
                }
            }
        }
        
        return array_values($groupedResults);
    }
}
