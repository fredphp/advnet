-- ============================================================================
-- 为红包任务表添加缺失字段
-- ============================================================================

-- 添加 resource_id 字段（如果不存在）
SET @exist_resource_id := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'resource_id');
SET @sql_resource_id := IF(@exist_resource_id = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `resource_id` INT UNSIGNED DEFAULT NULL COMMENT ''关联资源ID''', 'SELECT 1');
PREPARE stmt FROM @sql_resource_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 push_status 字段（如果不存在）
SET @exist_push_status := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'push_status');
SET @sql_push_status := IF(@exist_push_status = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `push_status` TINYINT UNSIGNED DEFAULT 0 COMMENT ''推送状态: 0=未推送, 1=已推送''', 'SELECT 1');
PREPARE stmt FROM @sql_push_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 push_time 字段（如果不存在）
SET @exist_push_time := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'push_time');
SET @sql_push_time := IF(@exist_push_time = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `push_time` INT UNSIGNED DEFAULT NULL COMMENT ''推送时间''', 'SELECT 1');
PREPARE stmt FROM @sql_push_time;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 reward 字段（如果不存在）
SET @exist_reward := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'reward');
SET @sql_reward := IF(@exist_reward = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `reward` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT ''单个红包奖励金额''', 'SELECT 1');
PREPARE stmt FROM @sql_reward;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 sender_id 字段（如果不存在）
SET @exist_sender_id := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'sender_id');
SET @sql_sender_id := IF(@exist_sender_id = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_id` INT UNSIGNED DEFAULT NULL COMMENT ''发送人ID''', 'SELECT 1');
PREPARE stmt FROM @sql_sender_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 sender_name 字段（如果不存在）
SET @exist_sender_name := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'sender_name');
SET @sql_sender_name := IF(@exist_sender_name = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_name` VARCHAR(50) DEFAULT NULL COMMENT ''发送人昵称''', 'SELECT 1');
PREPARE stmt FROM @sql_sender_name;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 sender_avatar 字段（如果不存在）
SET @exist_sender_avatar := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'sender_avatar');
SET @sql_sender_avatar := IF(@exist_sender_avatar = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `sender_avatar` VARCHAR(255) DEFAULT NULL COMMENT ''发送人头像''', 'SELECT 1');
PREPARE stmt FROM @sql_sender_avatar;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 type 字段（如果不存在）
SET @exist_type := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'type');
SET @sql_type := IF(@exist_type = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `type` VARCHAR(30) DEFAULT "chat" COMMENT "任务类型"', 'SELECT 1');
PREPARE stmt FROM @sql_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @exist_idx_push := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND INDEX_NAME = 'idx_push_status');
SET @sql_idx_push := IF(@exist_idx_push = 0, 'ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_push_status` (`push_status`)', 'SELECT 1');
PREPARE stmt FROM @sql_idx_push;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_resource := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND INDEX_NAME = 'idx_resource_id');
SET @sql_idx_resource := IF(@exist_idx_resource = 0, 'ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_resource_id` (`resource_id`)', 'SELECT 1');
PREPARE stmt FROM @sql_idx_resource;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
