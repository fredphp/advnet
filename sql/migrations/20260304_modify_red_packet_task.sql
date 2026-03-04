-- ============================================================================
-- 修改红包任务表：移除金额字段，添加展示字段
-- ============================================================================

SET NAMES utf8mb4;

-- 添加展示相关字段（如果不存在）
-- 添加背景图字段
SET @exist_bg := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'background_image');
SET @sql_bg := IF(@exist_bg = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `background_image` VARCHAR(255) DEFAULT '''' COMMENT ''背景图片'' AFTER `description`', 'SELECT 1');
PREPARE stmt FROM @sql_bg;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加跳转链接字段
SET @exist_jump := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'jump_url');
SET @sql_jump := IF(@exist_jump = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `jump_url` VARCHAR(500) DEFAULT '''' COMMENT ''跳转链接'' AFTER `background_image`', 'SELECT 1');
PREPARE stmt FROM @sql_jump;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加展示标题字段
SET @exist_title := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'display_title');
SET @sql_title := IF(@exist_title = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `display_title` VARCHAR(100) DEFAULT '''' COMMENT ''展示标题'' AFTER `name`', 'SELECT 1');
PREPARE stmt FROM @sql_title;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加展示描述字段
SET @exist_desc := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'display_description');
SET @sql_desc := IF(@exist_desc = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `display_description` VARCHAR(500) DEFAULT '''' COMMENT ''展示描述'' AFTER `display_title`', 'SELECT 1');
PREPARE stmt FROM @sql_desc;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加是否显示红包字段（只有小程序游戏类型才显示红包）
SET @exist_show := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'show_red_packet');
SET @sql_show := IF(@exist_show = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `show_red_packet` TINYINT(1) DEFAULT 1 COMMENT ''是否显示红包: 0=否, 1=是'' AFTER `type`', 'SELECT 1');
PREPARE stmt FROM @sql_show;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加每日最大点击次数字段
SET @exist_max := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'max_click_per_day');
SET @sql_max := IF(@exist_max = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `max_click_per_day` INT(10) UNSIGNED DEFAULT 10 COMMENT ''每日最大点击次数'' AFTER `show_red_packet`', 'SELECT 1');
PREPARE stmt FROM @sql_max;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 注意：保留原有的金额字段以便兼容，但后台不再显示和编辑
-- 如果需要完全移除金额字段，取消以下注释：
-- ALTER TABLE `advn_red_packet_task` DROP COLUMN `total_amount`;
-- ALTER TABLE `advn_red_packet_task` DROP COLUMN `total_count`;
-- ALTER TABLE `advn_red_packet_task` DROP COLUMN `remain_count`;
-- ALTER TABLE `advn_red_packet_task` DROP COLUMN `remain_amount`;
-- ALTER TABLE `advn_red_packet_task` DROP COLUMN `reward`;
