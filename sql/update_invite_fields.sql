-- =============================================
-- 邀请相关字段更新脚本
-- 请根据实际情况执行
-- =============================================

-- 1. 为用户表添加邀请相关字段（如果不存在）
-- 注意：如果字段已存在会报错，可忽略

-- 添加邀请码字段
ALTER TABLE `advn_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT NULL COMMENT '我的邀请码' AFTER `password`;

-- 添加直接上级字段
ALTER TABLE `advn_user` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '直接上级用户ID' AFTER `invite_code`;

-- 添加间接上级字段
ALTER TABLE `advn_user` ADD COLUMN `grandparent_id` INT UNSIGNED DEFAULT 0 COMMENT '间接上级用户ID' AFTER `parent_id`;

-- 添加邀请码索引
ALTER TABLE `advn_user` ADD UNIQUE KEY `uk_invite_code` (`invite_code`);

-- 添加上级索引
ALTER TABLE `advn_user` ADD KEY `idx_parent_id` (`parent_id`);

-- 2. 为现有用户生成邀请码（如果还没有）
UPDATE `advn_user` SET `invite_code` = CONCAT('INV', LPAD(id, 6, '0')) WHERE `invite_code` IS NULL OR `invite_code` = '';

SELECT '数据库更新完成！' as message;
