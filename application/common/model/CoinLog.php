<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 金币流水模型
 * 支持按月分表
 */
class CoinLog extends Model
{
    // 表名 (基础表名，实际使用按月分表)
    protected $name = 'coin_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 流水类型
    public static $typeList = [
        // 收入类型
        'register_reward' => '注册奖励',
        'video_watch' => '观看视频',
        'video_share' => '分享视频',
        'task_reward' => '任务奖励',
        'sign_in' => '签到奖励',
        'invite_level1' => '一级邀请奖励',
        'invite_level2' => '二级邀请奖励',
        'commission_level1' => '一级佣金',
        'commission_level2' => '二级佣金',
        'red_packet' => '红包奖励',
        'game_reward' => '游戏奖励',
        'admin_add' => '后台增加',
        'withdraw_return' => '提现退回',
        'withdraw_freeze' => '提现冻结',
        'withdraw_refund' => '提现退还',
        'withdraw_success' => '提现成功',
        'withdraw' => '提现',
        'withdraw_cancel' => '取消提现',
        // 支出类型
        'withdraw_fee' => '提现手续费',
        'admin_reduce' => '后台扣减',
    ];
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取当月表名
     * @param int $timestamp 时间戳，默认当前时间
     * @return string
     */
    public static function getTableByMonth($timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        return 'coin_log_' . date('Ym', $timestamp);
    }
    
    /**
     * 根据日期范围获取所有需要查询的表名（按月分表）
     * @param int $startTime 开始时间戳
     * @param int $endTime 结束时间戳
     * @return array
     */
    public static function getTablesByRange($startTime, $endTime)
    {
        $tables = [];
        $startMonth = strtotime(date('Y-m-01', $startTime));
        $endMonth = strtotime(date('Y-m-01', $endTime));
        
        $current = $startMonth;
        while ($current <= $endMonth) {
            $tables[] = 'coin_log_' . date('Ym', $current);
            $current = strtotime('+1 month', $current);
        }
        
        return $tables;
    }
    
    /**
     * 检查分表是否存在
     * @param string $tableName 表名（不含前缀）
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
     * @param string $tableName 表名（不含前缀）
     * @return bool
     */
    public static function createTable($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTable = $prefix . $tableName;
        $mainTable = $prefix . 'coin_log';
        
        try {
            // 使用 CREATE TABLE ... LIKE 复制主表结构
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
     * 确保当月分表存在
     * @return string 返回当月表名
     */
    public static function ensureCurrentMonthTable()
    {
        $tableName = self::getTableByMonth();
        self::ensureTableExists($tableName);
        return $tableName;
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
     * 获取或创建当月分表
     * @param int $timestamp
     * @return string
     */
    public static function getOrCreateTable($timestamp = null)
    {
        $tableName = self::getTableByMonth($timestamp);
        self::ensureTableExists($tableName);
        return $tableName;
    }
    
    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        return self::$typeList[$data['type']] ?? $data['type'];
    }
    
    /**
     * 获取当前表名（兼容旧方法）
     * @return string
     */
    public static function getCurrentTable()
    {
        return self::getTableByMonth();
    }
    
    /**
     * 获取指定月份表名
     * @param string $date 日期 Y-m 或 Y-m-d
     * @return string
     */
    public static function getTableByDate($date)
    {
        return 'coin_log_' . date('Ym', strtotime($date));
    }
}
