<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 提现订单模型
 * 支持按月分表
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
     * 获取当月表名
     * @param int $timestamp 时间戳，默认当前时间
     * @return string
     */
    public static function getTableByMonth($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        return 'withdraw_order_' . date('Ym', $timestamp);
    }
    
    /**
     * 根据日期范围获取所有需要查询的表名
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @return array
     */
    public static function getTablesByRange($startTime, $endTime)
    {
        $tables = [];
        $startMonth = strtotime(date('Y-m-01', $startTime));
        $endMonth = strtotime(date('Y-m-01', $endTime));
        
        while ($startMonth <= $endMonth) {
            $tables[] = 'withdraw_order_' . date('Ym', $startMonth);
            $startMonth = strtotime('+1 month', $startMonth);
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
        // 从订单号提取日期 WD20260306...
        if (preg_match('/^WD(\d{6})/', $orderNo, $matches)) {
            $dateStr = '20' . substr($matches[1], 0, 2) . substr($matches[1], 2, 2); // 202603 -> 202603
            return 'withdraw_order_' . $dateStr;
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
     * 创建分表
     * @param string $tableName
     * @return bool
     */
    public static function createTable($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTable = $prefix . $tableName;
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$fullTable}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
            `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
            `coin_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '提现金币',
            `exchange_rate` int(11) NOT NULL DEFAULT '10000' COMMENT '汇率',
            `cash_amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT '提现金额(元)',
            `fee_amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT '手续费',
            `actual_amount` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
            `withdraw_type` varchar(20) NOT NULL DEFAULT 'wechat' COMMENT '提现方式',
            `withdraw_account` varchar(100) NOT NULL DEFAULT '' COMMENT '提现账号',
            `withdraw_name` varchar(50) NOT NULL DEFAULT '' COMMENT '收款人姓名',
            `bank_name` varchar(50) DEFAULT NULL COMMENT '银行名称',
            `bank_branch` varchar(100) DEFAULT NULL COMMENT '开户行',
            `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
            `audit_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核类型0自动1人工',
            `audit_admin_id` int(11) unsigned DEFAULT NULL COMMENT '审核管理员ID',
            `audit_admin_name` varchar(50) DEFAULT NULL COMMENT '审核管理员',
            `audit_time` int(11) unsigned DEFAULT NULL COMMENT '审核时间',
            `audit_remark` varchar(255) DEFAULT NULL COMMENT '审核备注',
            `reject_reason` varchar(255) DEFAULT NULL COMMENT '拒绝原因',
            `transfer_no` varchar(64) DEFAULT NULL COMMENT '打款流水号',
            `transfer_time` int(11) unsigned DEFAULT NULL COMMENT '打款时间',
            `transfer_result` text COMMENT '打款结果',
            `complete_time` int(11) unsigned DEFAULT NULL COMMENT '完成时间',
            `fail_reason` varchar(255) DEFAULT NULL COMMENT '失败原因',
            `retry_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '重试次数',
            `next_retry_time` int(11) unsigned DEFAULT NULL COMMENT '下次重试时间',
            `risk_score` tinyint(3) NOT NULL DEFAULT '0' COMMENT '风控评分',
            `risk_tags` varchar(255) DEFAULT NULL COMMENT '风控标签',
            `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
            `device_id` varchar(64) DEFAULT NULL COMMENT '设备ID',
            `user_agent` varchar(500) DEFAULT NULL COMMENT 'UA',
            `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员ID',
            `admin_name` varchar(50) DEFAULT NULL COMMENT '操作管理员',
            `admin_remark` varchar(255) DEFAULT NULL COMMENT '管理员备注',
            `approve_time` int(11) unsigned DEFAULT NULL COMMENT '审批时间',
            `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_order_no` (`order_no`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_status` (`status`),
            KEY `idx_createtime` (`createtime`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现订单表'";
        
        try {
            Db::execute($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取或创建当月分表
     * @param int $timestamp
     * @return string
     */
    public static function getOrCreateTable($timestamp = null)
    {
        $tableName = self::getTableByMonth($timestamp);
        
        if (!self::tableExists($tableName)) {
            self::createTable($tableName);
        }
        
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
        // 默认查询最近3个月的数据
        if (!$startTime) {
            $startTime = strtotime('-3 months');
        }
        if (!$endTime) {
            $endTime = time();
        }
        
        $tables = self::getTablesByRange($startTime, $endTime);
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
