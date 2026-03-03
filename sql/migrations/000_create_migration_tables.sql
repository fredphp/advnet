-- =============================================
-- 数据迁移记录表
-- 用于跟踪已执行的迁移文件
-- =============================================

CREATE TABLE IF NOT EXISTS `advn_migration_record` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移记录表';

-- 创建迁移配置表
CREATE TABLE IF NOT EXISTS `advn_migration_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `config_value` TEXT COMMENT '配置值',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '配置说明',
    `created_at` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updated_at` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='迁移配置表';

-- 插入默认配置
INSERT IGNORE INTO `advn_migration_config` (`config_key`, `config_value`, `description`, `created_at`, `updated_at`) VALUES
('migration_path', 'sql/migrations', '迁移文件目录', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('auto_backup', '1', '迁移前自动备份', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('batch_size', '1000', '批量处理数量', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('last_batch_no', '0', '最后执行批次号', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
