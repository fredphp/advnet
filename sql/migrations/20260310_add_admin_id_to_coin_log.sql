-- ============================================================================
-- 添加 admin_id 字段到 coin_log 表
-- 执行方法: 在数据库管理工具中执行以下SQL
-- ============================================================================

-- 1. 添加到主表
ALTER TABLE `advn_coin_log` ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '管理员ID' AFTER `device_id`;

-- 2. 添加索引
ALTER TABLE `advn_coin_log` ADD INDEX `idx_admin_id` (`admin_id`);

-- 3. 如果当月分表存在，也要添加字段
-- 请根据实际存在的分表执行，例如：
-- ALTER TABLE `advn_coin_log_202603` ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '管理员ID' AFTER `device_id`;
-- ALTER TABLE `advn_coin_log_202603` ADD INDEX `idx_admin_id` (`admin_id`);
