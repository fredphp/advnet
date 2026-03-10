-- ============================================================================
-- 添加 admin_id 字段到 coin_account 和 coin_log 表
-- 用于记录管理员调整金币的操作者
-- 执行方法: 在数据库管理工具中执行以下SQL
-- ============================================================================

-- ============================================================================
-- 1. coin_account 表添加 admin_id 字段 (可选，如果启用了数据限制则需要)
-- ============================================================================
ALTER TABLE `advn_coin_account` ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '创建该记录的管理员ID' AFTER `updatetime`;
ALTER TABLE `advn_coin_account` ADD INDEX `idx_admin_id` (`admin_id`);

-- ============================================================================
-- 2. coin_log 主表添加 admin_id 字段
-- ============================================================================
ALTER TABLE `advn_coin_log` ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '管理员ID(后台调整时记录)' AFTER `device_id`;
ALTER TABLE `advn_coin_log` ADD INDEX `idx_admin_id` (`admin_id`);

-- ============================================================================
-- 3. 如果当月分表存在，也要添加字段
-- 请根据实际存在的分表执行，例如：
-- ============================================================================
-- ALTER TABLE `advn_coin_log_202603` ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '管理员ID' AFTER `device_id`;
-- ALTER TABLE `advn_coin_log_202603` ADD INDEX `idx_admin_id` (`admin_id`);

