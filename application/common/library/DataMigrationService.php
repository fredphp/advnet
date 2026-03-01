<?php

namespace app\common\library;

use think\Db;
use think\Exception;
use think\Config;
use think\Cache;

/**
 * 数据迁移服务类
 * 
 * 用于将历史冷数据迁移到归档表，优化主表性能
 * 支持按时间范围迁移未访问的数据
 * 配置参数从 advn_config 配置表读取
 */
class DataMigrationService
{
    // 数据库配置
    protected $dbConfig;
    
    // 归档数据库配置（可以是同一个数据库或单独的归档库）
    protected $archiveDbConfig;
    
    // 表前缀
    protected $tablePrefix;
    
    // 批量处理数量
    protected $batchSize = 1000;
    
    // 是否输出日志
    protected $enableLog = true;
    
    // 迁移统计
    protected $stats = [];
    
    // 配置缓存
    protected static $configCache = null;
    
    // 配置缓存键
    const CACHE_KEY = 'migration_config';
    
    // 配置缓存时间（秒）
    const CACHE_TTL = 3600;
    
    // 默认配置
    protected static $defaultConfig = [
        // 基础配置
        'migration_enabled' => 1,
        'migration_batch_size' => 1000,
        'migration_auto_archive' => 0,
        'migration_schedule' => '0 3 * * *',
        'migration_delete_source' => 0,
        'migration_log_retention' => 365,
        
        // 各表迁移天数
        'migration_coin_log_days' => 90,
        'migration_watch_record_days' => 180,
        'migration_watch_session_days' => 30,
        'migration_risk_log_days' => 180,
        'migration_user_behavior_days' => 90,
        'migration_anticheat_days' => 90,
        'migration_red_packet_days' => 365,
        'migration_commission_days' => 365,
        'migration_transfer_days' => 365,
        
        // 清理配置
        'migration_daily_stats_keep' => 365,
        'migration_behavior_stats_keep' => 365,
        'migration_inactive_days' => 90,
        
        // 性能配置
        'migration_sleep_ms' => 10,
        'migration_transaction' => 1,
        'migration_max_runtime' => 3600,
        
        // 通知配置
        'migration_notify_enabled' => 0,
        'migration_notify_email' => '',
        'migration_notify_webhook' => '',
    ];
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->dbConfig = Config::get('database');
        $this->tablePrefix = Config::get('database.prefix');
        
        // 加载配置
        $this->loadConfig();
    }
    
    /**
     * 加载配置
     */
    protected function loadConfig()
    {
        if (self::$configCache !== null) {
            return;
        }
        
        // 尝试从缓存读取
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            self::$configCache = $cached;
            return;
        }
        
        // 从数据库读取
        try {
            $configs = Db::name('config')
                ->where('`group`', 'migration')
                ->column('value', 'name');
            
            self::$configCache = array_merge(self::$defaultConfig, $configs);
            
            // 写入缓存
            Cache::set(self::CACHE_KEY, self::$configCache, self::CACHE_TTL);
        } catch (\Exception $e) {
            // 数据库异常时使用默认配置
            self::$configCache = self::$defaultConfig;
        }
    }
    
    /**
     * 获取配置值
     * 
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        $this->loadConfig();
        
        if (isset(self::$configCache[$key])) {
            $value = self::$configCache[$key];
            
            // 类型转换
            if (isset(self::$defaultConfig[$key])) {
                $defaultValue = self::$defaultConfig[$key];
                if (is_int($defaultValue)) {
                    return intval($value);
                } elseif (is_bool($defaultValue)) {
                    return in_array($value, [1, '1', 'true', 'yes'], true);
                } elseif (is_float($defaultValue)) {
                    return floatval($value);
                }
            }
            
            return $value;
        }
        
        return $default;
    }
    
    /**
     * 获取所有配置
     * 
     * @return array
     */
    public function getAllConfig()
    {
        $this->loadConfig();
        return self::$configCache;
    }
    
    /**
     * 清除配置缓存
     */
    public static function clearConfigCache()
    {
        Cache::delete(self::CACHE_KEY);
        self::$configCache = null;
    }
    
    /**
     * 设置批量处理数量
     */
    public function setBatchSize($size)
    {
        $this->batchSize = (int) $size;
        return $this;
    }
    
    /**
     * 从配置获取批量处理数量
     */
    protected function getBatchSize()
    {
        return $this->batchSize ?: $this->getConfig('migration_batch_size', 1000);
    }
    
    /**
     * 从配置获取休眠时间（微秒）
     */
    protected function getSleepMicroseconds()
    {
        $ms = $this->getConfig('migration_sleep_ms', 10);
        return $ms * 1000;
    }
    
    /**
     * 从配置判断是否删除源数据
     */
    protected function shouldDeleteSource()
    {
        return $this->getConfig('migration_delete_source', 0) == 1;
    }
    
    /**
     * 从配置判断是否启用事务
     */
    protected function shouldUseTransaction()
    {
        return $this->getConfig('migration_transaction', 1) == 1;
    }
    
    /**
     * 设置是否启用日志
     */
    public function setEnableLog($enable)
    {
        $this->enableLog = (bool) $enable;
        return $this;
    }
    
    /**
     * 输出日志
     */
    protected function log($message)
    {
        if ($this->enableLog) {
            echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        }
    }
    
    /**
     * 获取迁移统计
     */
    public function getStats()
    {
        return $this->stats;
    }
    
    /**
     * 检查归档表是否存在，不存在则创建
     * @param string $table 表名
     * @return bool
     */
    public function ensureArchiveTableExists($table)
    {
        $archiveTable = $table . '_archive';
        $fullTable = $this->tablePrefix . $table;
        $fullArchiveTable = $this->tablePrefix . $archiveTable;
        
        // 检查归档表是否存在
        $exists = Db::query("SHOW TABLES LIKE '{$fullArchiveTable}'");
        if (!empty($exists)) {
            return true;
        }
        
        // 获取原表结构
        $createTableSql = Db::query("SHOW CREATE TABLE `{$fullTable}`");
        if (empty($createTableSql)) {
            return false;
        }
        
        // 创建归档表（复制原表结构）
        $sql = $createTableSql[0]['Create Table'];
        $sql = str_replace("`{$fullTable}`", "`{$fullArchiveTable}`", $sql);
        $sql = preg_replace('/AUTO_INCREMENT=\d+/', '', $sql);
        
        try {
            Db::execute($sql);
            $this->log("创建归档表: {$fullArchiveTable}");
            return true;
        } catch (\Exception $e) {
            $this->log("创建归档表失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 迁移金币流水数据
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateCoinLog($days = 90, $deleteSource = false)
    {
        $this->log("开始迁移金币流水数据...");
        
        $table = 'coin_log';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        $beforeDate = date('Y-m-d', $beforeTime);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            // 统计需要迁移的数据量
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            $this->log("待迁移数据量: {$stats['total']} 条 (日期 < {$beforeDate})");
            
            if ($stats['total'] == 0) {
                $this->log("没有需要迁移的数据");
                return $stats;
            }
            
            // 分批迁移
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                // 开启事务
                Db::startTrans();
                try {
                    // 获取一批数据
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    // 插入归档表
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                            $this->log("插入归档表失败 ID={$record['id']}: " . $e->getMessage());
                        }
                    }
                    
                    // 删除源数据
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    
                    $this->log("已迁移 {$migrated}/{$stats['total']} 条");
                    
                    // 避免锁表时间过长，短暂休眠
                    usleep($this->getSleepMicroseconds());
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->log("批次迁移失败: " . $e->getMessage());
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("金币流水迁移完成: 迁移 {$migrated} 条, 失败 {$failed} 条, 耗时 {$stats['duration']} 秒");
            
        } catch (\Exception $e) {
            $this->log("迁移异常: " . $e->getMessage());
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移视频观看记录
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateVideoWatchRecord($days = 180, $deleteSource = false)
    {
        $this->log("开始迁移视频观看记录...");
        
        $table = 'video_watch_record';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        $beforeDate = date('Y-m-d', $beforeTime);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            // 只迁移已完成的观看记录（未领取奖励的不迁移）
            $query = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->where('reward_status', '<>', 0); // 0=未领取，不迁移
            
            $stats['total'] = $query->count();
            $this->log("待迁移数据量: {$stats['total']} 条 (日期 < {$beforeDate})");
            
            if ($stats['total'] == 0) {
                $this->log("没有需要迁移的数据");
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->where('reward_status', '<>', 0)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    $this->log("已迁移 {$migrated}/{$stats['total']} 条");
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->log("批次迁移失败: " . $e->getMessage());
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("视频观看记录迁移完成: 迁移 {$migrated} 条, 耗时 {$stats['duration']} 秒");
            
        } catch (\Exception $e) {
            $this->log("迁移异常: " . $e->getMessage());
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移观看会话记录
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateVideoWatchSession($days = 30, $deleteSource = false)
    {
        $this->log("开始迁移观看会话记录...");
        
        $table = 'video_watch_session';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            $this->log("待迁移数据量: {$stats['total']} 条");
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    $this->log("已迁移 {$migrated}/{$stats['total']} 条");
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("观看会话记录迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移风控日志
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateRiskLog($days = 180, $deleteSource = false)
    {
        $this->log("开始迁移风控日志...");
        
        $table = 'risk_log';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            $this->log("待迁移数据量: {$stats['total']} 条");
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    $this->log("已迁移 {$migrated}/{$stats['total']} 条");
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("风控日志迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移用户行为记录
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateUserBehavior($days = 90, $deleteSource = false)
    {
        $this->log("开始迁移用户行为记录...");
        
        $table = 'user_behavior';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            $this->log("待迁移数据量: {$stats['total']} 条");
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    $this->log("已迁移 {$migrated}/{$stats['total']} 条");
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("用户行为记录迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移防刷日志
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateAnticheatLog($days = 90, $deleteSource = false)
    {
        $this->log("开始迁移防刷日志...");
        
        $table = 'anticheat_log';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("防刷日志迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移红包领取记录
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateRedPacketRecord($days = 365, $deleteSource = false)
    {
        $this->log("开始迁移红包领取记录...");
        
        $table = 'red_packet_record';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("红包领取记录迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移邀请分佣日志
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateInviteCommissionLog($days = 365, $deleteSource = false)
    {
        $this->log("开始迁移邀请分佣日志...");
        
        $table = 'invite_commission_log';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("邀请分佣日志迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 迁移微信打款日志
     * @param int $days 迁移多少天前的数据
     * @param bool $deleteSource 是否删除源数据
     * @return array
     */
    public function migrateWechatTransferLog($days = 365, $deleteSource = false)
    {
        $this->log("开始迁移微信打款日志...");
        
        $table = 'wechat_transfer_log';
        $archiveTable = $table . '_archive';
        $this->ensureArchiveTableExists($table);
        
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['total'] = Db::name($table)
                ->where('createtime', '<', $beforeTime)
                ->count();
            
            if ($stats['total'] == 0) {
                return $stats;
            }
            
            $migrated = 0;
            $failed = 0;
            
            while (true) {
                Db::startTrans();
                try {
                    $records = Db::name($table)
                        ->where('createtime', '<', $beforeTime)
                        ->limit($this->batchSize)
                        ->select();
                    
                    if (empty($records)) {
                        Db::commit();
                        break;
                    }
                    
                    foreach ($records as $record) {
                        try {
                            Db::name($archiveTable)->insert($record);
                            $migrated++;
                        } catch (\Exception $e) {
                            $failed++;
                        }
                    }
                    
                    if ($deleteSource && $failed == 0) {
                        $ids = array_column($records, 'id');
                        Db::name($table)->whereIn('id', $ids)->delete();
                        $stats['deleted'] += count($ids);
                    }
                    
                    Db::commit();
                    usleep(10000);
                    
                } catch (\Exception $e) {
                    Db::rollback();
                    break;
                }
            }
            
            $stats['migrated'] = $migrated;
            $stats['failed'] = $failed;
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("微信打款日志迁移完成");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table] = $stats;
        return $stats;
    }
    
    /**
     * 标记未活跃用户
     * @param int $inactiveDays 未活跃天数
     * @return array
     */
    public function markInactiveUsers($inactiveDays = 90)
    {
        $this->log("开始标记未活跃用户...");
        
        $beforeTime = time() - ($inactiveDays * 86400);
        
        $stats = [
            'total' => 0,
            'marked' => 0,
            'start_time' => time()
        ];
        
        try {
            // 查找未活跃用户（根据最后登录时间或最后活动时间）
            $stats['total'] = Db::name('user')
                ->where('logintime', '<', $beforeTime)
                ->where('status', 'normal')
                ->count();
            
            $this->log("发现 {$stats['total']} 个未活跃用户 ({$inactiveDays}天未登录)");
            
            if ($stats['total'] > 0) {
                // 标记用户为未活跃状态（可以添加一个标记字段或更新用户组）
                $stats['marked'] = Db::name('user')
                    ->where('logintime', '<', $beforeTime)
                    ->where('status', 'normal')
                    ->update([
                        'status' => 'inactive',
                        'updatetime' => time()
                    ]);
            }
            
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("标记完成: {$stats['marked']} 个用户");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats['inactive_users'] = $stats;
        return $stats;
    }
    
    /**
     * 清理过期的用户每日收益统计
     * @param int $keepDays 保留天数
     * @return array
     */
    public function cleanDailyRewardStats($keepDays = 365)
    {
        $this->log("开始清理用户每日收益统计...");
        
        $table = 'user_daily_reward_stat';
        $beforeDate = date('Y-m-d', time() - ($keepDays * 86400));
        
        $stats = [
            'table' => $table,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['deleted'] = Db::name($table)
                ->where('date_key', '<', $beforeDate)
                ->delete();
            
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("清理完成: 删除 {$stats['deleted']} 条记录");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table . '_clean'] = $stats;
        return $stats;
    }
    
    /**
     * 清理过期的用户行为统计
     * @param int $keepDays 保留天数
     * @return array
     */
    public function cleanBehaviorStats($keepDays = 365)
    {
        $this->log("开始清理用户行为统计...");
        
        $table = 'user_behavior_stat';
        $beforeDate = date('Y-m-d', time() - ($keepDays * 86400));
        
        $stats = [
            'table' => $table,
            'deleted' => 0,
            'start_time' => time()
        ];
        
        try {
            $stats['deleted'] = Db::name($table)
                ->where('stat_date', '<', $beforeDate)
                ->delete();
            
            $stats['end_time'] = time();
            $stats['duration'] = $stats['end_time'] - $stats['start_time'];
            
            $this->log("清理完成: 删除 {$stats['deleted']} 条记录");
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        $this->stats[$table . '_clean'] = $stats;
        return $stats;
    }
    
    /**
     * 执行所有迁移任务
     * @param array $options 配置选项（覆盖配置表中的值）
     * @return array
     */
    public function migrateAll($options = [])
    {
        $this->log("========== 开始执行全部迁移任务 ==========");
        
        // 从配置表读取默认值
        $defaultOptions = [
            'coin_log_days' => $this->getConfig('migration_coin_log_days', 90),
            'watch_record_days' => $this->getConfig('migration_watch_record_days', 180),
            'watch_session_days' => $this->getConfig('migration_watch_session_days', 30),
            'risk_log_days' => $this->getConfig('migration_risk_log_days', 180),
            'behavior_days' => $this->getConfig('migration_user_behavior_days', 90),
            'anticheat_days' => $this->getConfig('migration_anticheat_days', 90),
            'red_packet_days' => $this->getConfig('migration_red_packet_days', 365),
            'commission_days' => $this->getConfig('migration_commission_days', 365),
            'transfer_days' => $this->getConfig('migration_transfer_days', 365),
            'delete_source' => $this->shouldDeleteSource(),
            'inactive_days' => $this->getConfig('migration_inactive_days', 90),
            'stats_keep_days' => $this->getConfig('migration_daily_stats_keep', 365),
        ];
        
        // 用户传入的选项覆盖配置表的值
        $options = array_merge($defaultOptions, $options);
        
        // 设置批量处理数量
        $this->batchSize = $this->getConfig('migration_batch_size', 1000);
        $results = [];
        
        // 迁移金币流水
        $results['coin_log'] = $this->migrateCoinLog($options['coin_log_days'], $options['delete_source']);
        
        // 迁移观看记录
        $results['video_watch_record'] = $this->migrateVideoWatchRecord($options['watch_record_days'], $options['delete_source']);
        
        // 迁移观看会话
        $results['video_watch_session'] = $this->migrateVideoWatchSession($options['watch_session_days'], $options['delete_source']);
        
        // 迁移风控日志
        $results['risk_log'] = $this->migrateRiskLog($options['risk_log_days'], $options['delete_source']);
        
        // 迁移用户行为
        $results['user_behavior'] = $this->migrateUserBehavior($options['behavior_days'], $options['delete_source']);
        
        // 迁移防刷日志
        $results['anticheat_log'] = $this->migrateAnticheatLog($options['anticheat_days'], $options['delete_source']);
        
        // 迁移红包记录
        $results['red_packet_record'] = $this->migrateRedPacketRecord($options['red_packet_days'], $options['delete_source']);
        
        // 迁移分佣日志
        $results['invite_commission_log'] = $this->migrateInviteCommissionLog($options['commission_days'], $options['delete_source']);
        
        // 迁移打款日志
        $results['wechat_transfer_log'] = $this->migrateWechatTransferLog($options['transfer_days'], $options['delete_source']);
        
        // 标记未活跃用户
        $results['inactive_users'] = $this->markInactiveUsers($options['inactive_days']);
        
        // 清理统计数据
        $results['daily_stats_clean'] = $this->cleanDailyRewardStats($options['stats_keep_days']);
        $results['behavior_stats_clean'] = $this->cleanBehaviorStats($options['stats_keep_days']);
        
        $this->log("========== 全部迁移任务完成 ==========");
        
        return $results;
    }
    
    /**
     * 获取表数据统计
     * @param string $table 表名
     * @param int $days 天数
     * @return array
     */
    public function getTableStats($table, $days = 30)
    {
        $beforeTime = time() - ($days * 86400);
        
        $stats = [
            'table' => $table,
            'total_count' => 0,
            'old_count' => 0,
            'recent_count' => 0,
            'archive_exists' => false,
        ];
        
        try {
            $stats['total_count'] = Db::name($table)->count();
            $stats['old_count'] = Db::name($table)->where('createtime', '<', $beforeTime)->count();
            $stats['recent_count'] = $stats['total_count'] - $stats['old_count'];
            
            // 检查归档表是否存在
            $archiveTable = $this->tablePrefix . $table . '_archive';
            $exists = Db::query("SHOW TABLES LIKE '{$archiveTable}'");
            $stats['archive_exists'] = !empty($exists);
            
            if ($stats['archive_exists']) {
                $stats['archive_count'] = Db::name($table . '_archive')->count();
            }
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * 获取所有可迁移表的状态
     */
    public function getAllTableStats()
    {
        $tables = [
            'coin_log',
            'video_watch_record',
            'video_watch_session',
            'risk_log',
            'user_behavior',
            'anticheat_log',
            'red_packet_record',
            'invite_commission_log',
            'wechat_transfer_log',
        ];
        
        $result = [];
        foreach ($tables as $table) {
            try {
                $result[$table] = $this->getTableStats($table);
            } catch (\Exception $e) {
                $result[$table] = ['error' => $e->getMessage()];
            }
        }
        
        return $result;
    }
}
