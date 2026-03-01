<?php

namespace app\common\library;

use think\Db;
use think\Exception;

/**
 * 迁移文件管理服务
 * 
 * 用于扫描、管理和执行SQL迁移文件
 */
class MigrationFileService
{
    // 迁移文件目录
    protected $migrationPath;
    
    // 表前缀
    protected $tablePrefix;
    
    // 迁移记录表
    const MIGRATION_TABLE = 'migration_record';
    
    // 配置表
    const CONFIG_TABLE = 'migration_config';
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->tablePrefix = \think\Config::get('database.prefix');
        $this->migrationPath = ROOT_PATH . 'sql' . DS . 'migrations';
    }
    
    /**
     * 设置迁移文件目录
     */
    public function setMigrationPath($path)
    {
        $this->migrationPath = $path;
        return $this;
    }
    
    /**
     * 获取迁移文件目录
     */
    public function getMigrationPath()
    {
        return $this->migrationPath;
    }
    
    /**
     * 检查迁移记录表是否存在
     */
    public function migrationTableExists()
    {
        $fullTable = $this->tablePrefix . self::MIGRATION_TABLE;
        $exists = Db::query("SHOW TABLES LIKE '{$fullTable}'");
        return !empty($exists);
    }
    
    /**
     * 确保迁移记录表存在
     */
    public function ensureMigrationTableExists()
    {
        if ($this->migrationTableExists()) {
            return true;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tablePrefix}migration_record` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_name` VARCHAR(255) NOT NULL COMMENT '迁移文件名',
            `migration_path` VARCHAR(500) NOT NULL COMMENT '迁移文件路径',
            `batch` INT UNSIGNED DEFAULT 0 COMMENT '批次号',
            `status` ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending' COMMENT '执行状态',
            `executed_at` INT UNSIGNED DEFAULT NULL COMMENT '执行时间',
            `execution_time` DECIMAL(10, 2) DEFAULT NULL COMMENT '执行耗时(秒)',
            `error_message` TEXT COMMENT '错误信息',
            `checksum` VARCHAR(64) DEFAULT NULL COMMENT '文件MD5校验',
            `created_at` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
            `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_migration_name` (`migration_name`),
            KEY `idx_status` (`status`),
            KEY `idx_batch` (`batch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移记录表'";
        
        Db::execute($sql);
        
        // 创建配置表
        $configSql = "CREATE TABLE IF NOT EXISTS `{$this->tablePrefix}migration_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
            `config_value` TEXT COMMENT '配置值',
            `description` VARCHAR(500) DEFAULT NULL COMMENT '配置说明',
            `created_at` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
            `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_config_key` (`config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='迁移配置表'";
        
        Db::execute($configSql);
        
        // 插入默认配置
        $this->setConfig('migration_path', 'sql/migrations', '迁移文件目录');
        $this->setConfig('last_batch_no', '0', '最后执行批次号');
        
        return true;
    }
    
    /**
     * 扫描迁移文件目录
     * @return array 迁移文件列表
     */
    public function scanMigrationFiles()
    {
        $files = [];
        
        if (!is_dir($this->migrationPath)) {
            return $files;
        }
        
        $iterator = new \DirectoryIterator($this->migrationPath);
        
        foreach ($iterator as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }
            
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['sql'])) {
                continue;
            }
            
            $filename = $file->getFilename();
            $filepath = $file->getPathname();
            
            $files[] = [
                'name' => $filename,
                'path' => $filepath,
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'checksum' => md5_file($filepath),
            ];
        }
        
        // 按文件名排序
        usort($files, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $files;
    }
    
    /**
     * 获取待执行的迁移文件
     * @return array
     */
    public function getPendingMigrations()
    {
        $this->ensureMigrationTableExists();
        
        $files = $this->scanMigrationFiles();
        $pending = [];
        
        foreach ($files as $file) {
            $record = $this->getMigrationRecord($file['name']);
            
            if (!$record) {
                // 记录不存在，添加为新迁移
                $pending[] = [
                    'name' => $file['name'],
                    'path' => $file['path'],
                    'status' => 'new',
                    'checksum' => $file['checksum'],
                ];
            } elseif ($record['status'] === 'pending' || $record['status'] === 'failed') {
                // 待执行或失败的迁移
                // 检查文件是否被修改
                if ($record['checksum'] !== $file['checksum']) {
                    $pending[] = [
                        'name' => $file['name'],
                        'path' => $file['path'],
                        'status' => 'modified',
                        'checksum' => $file['checksum'],
                        'old_checksum' => $record['checksum'],
                    ];
                } else {
                    $pending[] = [
                        'name' => $file['name'],
                        'path' => $file['path'],
                        'status' => $record['status'],
                        'checksum' => $file['checksum'],
                    ];
                }
            }
        }
        
        return $pending;
    }
    
    /**
     * 获取已执行的迁移记录
     * @return array
     */
    public function getExecutedMigrations()
    {
        $this->ensureMigrationTableExists();
        
        return Db::name(self::MIGRATION_TABLE)
            ->where('status', 'completed')
            ->order('executed_at', 'desc')
            ->select();
    }
    
    /**
     * 获取所有迁移记录
     * @return array
     */
    public function getAllMigrationRecords()
    {
        $this->ensureMigrationTableExists();
        
        return Db::name(self::MIGRATION_TABLE)
            ->order('created_at', 'desc')
            ->select();
    }
    
    /**
     * 获取单个迁移记录
     * @param string $name 迁移文件名
     * @return array|null
     */
    public function getMigrationRecord($name)
    {
        return Db::name(self::MIGRATION_TABLE)
            ->where('migration_name', $name)
            ->find();
    }
    
    /**
     * 添加迁移记录
     * @param array $data 迁移数据
     * @return int
     */
    public function addMigrationRecord($data)
    {
        $now = time();
        
        $record = [
            'migration_name' => $data['name'],
            'migration_path' => $data['path'] ?? '',
            'batch' => $data['batch'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'checksum' => $data['checksum'] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        
        return Db::name(self::MIGRATION_TABLE)->insertGetId($record);
    }
    
    /**
     * 更新迁移记录
     * @param string $name 迁移文件名
     * @param array $data 更新数据
     * @return bool
     */
    public function updateMigrationRecord($name, $data)
    {
        $data['updated_at'] = time();
        
        return Db::name(self::MIGRATION_TABLE)
            ->where('migration_name', $name)
            ->update($data) !== false;
    }
    
    /**
     * 同步迁移文件到数据库
     * 扫描文件并添加不存在的记录
     * @return array 同步结果
     */
    public function syncMigrationFiles()
    {
        $this->ensureMigrationTableExists();
        
        $files = $this->scanMigrationFiles();
        $added = 0;
        $updated = 0;
        $skipped = 0;
        
        foreach ($files as $file) {
            $record = $this->getMigrationRecord($file['name']);
            
            if (!$record) {
                // 添加新记录
                $this->addMigrationRecord([
                    'name' => $file['name'],
                    'path' => $file['path'],
                    'status' => 'pending',
                    'checksum' => $file['checksum'],
                ]);
                $added++;
            } else {
                // 检查是否需要更新
                if ($record['checksum'] !== $file['checksum'] && $record['status'] !== 'completed') {
                    $this->updateMigrationRecord($file['name'], [
                        'checksum' => $file['checksum'],
                        'status' => 'pending',
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }
    
    /**
     * 执行单个迁移文件
     * @param string $name 迁移文件名
     * @return array 执行结果
     */
    public function executeMigration($name)
    {
        $this->ensureMigrationTableExists();
        
        $record = $this->getMigrationRecord($name);
        $file = null;
        
        // 查找文件
        $files = $this->scanMigrationFiles();
        foreach ($files as $f) {
            if ($f['name'] === $name) {
                $file = $f;
                break;
            }
        }
        
        if (!$file) {
            return [
                'success' => false,
                'error' => "迁移文件不存在: {$name}",
            ];
        }
        
        // 如果记录不存在，创建记录
        if (!$record) {
            $this->addMigrationRecord([
                'name' => $name,
                'path' => $file['path'],
                'status' => 'pending',
                'checksum' => $file['checksum'],
            ]);
        } elseif ($record['status'] === 'completed') {
            return [
                'success' => true,
                'message' => "迁移已执行过: {$name}",
                'skipped' => true,
            ];
        }
        
        // 获取批次号
        $batch = $this->getNextBatchNumber();
        
        // 更新状态为运行中
        $this->updateMigrationRecord($name, [
            'status' => 'running',
            'batch' => $batch,
        ]);
        
        $startTime = microtime(true);
        
        try {
            // 读取SQL文件内容
            $sql = file_get_contents($file['path']);
            
            if (empty($sql)) {
                throw new Exception("迁移文件为空: {$name}");
            }
            
            // 替换表前缀占位符
            $sql = str_replace('__PREFIX__', $this->tablePrefix, $sql);
            $sql = str_replace('advn_', $this->tablePrefix, $sql);
            
            // 执行SQL（支持多条语句）
            $this->executeSql($sql);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            // 更新为已完成
            $this->updateMigrationRecord($name, [
                'status' => 'completed',
                'executed_at' => time(),
                'execution_time' => $executionTime,
                'checksum' => $file['checksum'],
            ]);
            
            // 更新最后批次号
            $this->setConfig('last_batch_no', $batch);
            
            return [
                'success' => true,
                'name' => $name,
                'batch' => $batch,
                'execution_time' => $executionTime,
            ];
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            // 更新为失败
            $this->updateMigrationRecord($name, [
                'status' => 'failed',
                'executed_at' => time(),
                'execution_time' => $executionTime,
                'error_message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'name' => $name,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 执行所有待执行的迁移
     * @return array 执行结果
     */
    public function executeAllPending()
    {
        $this->ensureMigrationTableExists();
        $this->syncMigrationFiles();
        
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            return [
                'success' => true,
                'message' => '没有待执行的迁移',
                'executed' => [],
            ];
        }
        
        $results = [];
        $success = 0;
        $failed = 0;
        
        foreach ($pending as $migration) {
            $result = $this->executeMigration($migration['name']);
            $results[] = $result;
            
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => $failed === 0,
            'total' => count($pending),
            'executed' => $success,
            'failed' => $failed,
            'results' => $results,
        ];
    }
    
    /**
     * 执行SQL语句（支持多条语句）
     * @param string $sql SQL内容
     */
    protected function executeSql($sql)
    {
        // 分割SQL语句
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            // 跳过注释行
            $statement = preg_replace('/^--.*$/m', '', $statement);
            $statement = trim($statement);
            
            if (empty($statement)) {
                continue;
            }
            
            Db::execute($statement);
        }
    }
    
    /**
     * 获取下一个批次号
     * @return int
     */
    public function getNextBatchNumber()
    {
        $lastBatch = $this->getConfig('last_batch_no', 0);
        return intval($lastBatch) + 1;
    }
    
    /**
     * 获取配置
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        $value = Db::name(self::CONFIG_TABLE)
            ->where('config_key', $key)
            ->value('config_value');
        
        return $value !== null ? $value : $default;
    }
    
    /**
     * 设置配置
     * @param string $key 配置键
     * @param string $value 配置值
     * @param string $description 描述
     */
    public function setConfig($key, $value, $description = '')
    {
        $exists = Db::name(self::CONFIG_TABLE)
            ->where('config_key', $key)
            ->find();
        
        $now = time();
        
        if ($exists) {
            Db::name(self::CONFIG_TABLE)
                ->where('config_key', $key)
                ->update([
                    'config_value' => $value,
                    'updated_at' => $now,
                ]);
        } else {
            Db::name(self::CONFIG_TABLE)->insert([
                'config_key' => $key,
                'config_value' => $value,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
    
    /**
     * 获取迁移状态统计
     * @return array
     */
    public function getMigrationStats()
    {
        $this->ensureMigrationTableExists();
        
        $stats = [
            'total_files' => count($this->scanMigrationFiles()),
            'total_records' => Db::name(self::MIGRATION_TABLE)->count(),
            'pending' => Db::name(self::MIGRATION_TABLE)->where('status', 'pending')->count(),
            'running' => Db::name(self::MIGRATION_TABLE)->where('status', 'running')->count(),
            'completed' => Db::name(self::MIGRATION_TABLE)->where('status', 'completed')->count(),
            'failed' => Db::name(self::MIGRATION_TABLE)->where('status', 'failed')->count(),
        ];
        
        return $stats;
    }
    
    /**
     * 重置失败的迁移
     * @return int 重置数量
     */
    public function resetFailedMigrations()
    {
        return Db::name(self::MIGRATION_TABLE)
            ->where('status', 'failed')
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'updated_at' => time(),
            ]);
    }
    
    /**
     * 回滚指定批次的迁移
     * @param int $batch 批次号
     * @return array
     */
    public function rollbackBatch($batch)
    {
        $migrations = Db::name(self::MIGRATION_TABLE)
            ->where('batch', $batch)
            ->where('status', 'completed')
            ->select();
        
        $results = [];
        
        foreach ($migrations as $migration) {
            // 这里只更新状态，实际回滚需要备份或回滚SQL
            $this->updateMigrationRecord($migration['migration_name'], [
                'status' => 'pending',
                'executed_at' => null,
            ]);
            
            $results[] = [
                'name' => $migration['migration_name'],
                'rolled_back' => true,
            ];
        }
        
        return [
            'batch' => $batch,
            'rolled_back' => count($results),
            'migrations' => $results,
        ];
    }
}
