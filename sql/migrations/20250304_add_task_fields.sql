-- ============================================================================
-- 为红包任务表添加缺失字段
-- ============================================================================

-- 添加 resource_id 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `resource_id` INT UNSIGNED DEFAULT NULL COMMENT '关联资源ID' AFTER `relation_id`;

-- 添加 push_status 字段  
ALTER TABLE `advn_red_packet_task` ADD COLUMN `push_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '推送状态: 0=未推送, 1=已推送' AFTER `status`;

-- 添加 push_time 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `push_time` INT UNSIGNED DEFAULT NULL COMMENT '推送时间' AFTER `push_status`;

-- 添加 reward 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `reward` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '单个红包奖励金额' AFTER `max_amount`;

-- 添加 sender_id 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_id` INT UNSIGNED DEFAULT NULL COMMENT '发送人ID' AFTER `push_time`;

-- 添加 sender_name 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_name` VARCHAR(50) DEFAULT NULL COMMENT '发送人昵称' AFTER `sender_id`;

-- 添加 sender_avatar 字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_avatar` VARCHAR(255) DEFAULT NULL COMMENT '发送人头像' AFTER `sender_name`;

-- 添加 type 字段（如果不存在）
-- 先检查是否存在 type 字段，如果不存在则添加
SET @exist_type := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'type');
SET @sql_type := IF(@exist_type = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `type` VARCHAR(30) DEFAULT "chat" COMMENT "任务类型" AFTER `images`', 'SELECT 1');
PREPARE stmt FROM @sql_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 如果 task_type 字段存在，将数据迁移到 type 字段
UPDATE `advn_red_packet_task` SET `type` = `task_type` WHERE `type` IS NULL OR `type` = '';

-- 添加索引
ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_push_status` (`push_status`);
ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_resource_id` (`resource_id`);
