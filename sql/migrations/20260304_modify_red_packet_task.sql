-- ============================================================================
-- 修改红包任务表：添加展示控制字段
-- ============================================================================

SET NAMES utf8mb4;

-- 添加展示标题字段（用于前端展示，如果不想使用资源名称时可用）
SET @exist_title := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'display_title');
SET @sql_title := IF(@exist_title = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `display_title` VARCHAR(100) DEFAULT '''' COMMENT ''展示标题(可选，不填则使用资源名称)'' AFTER `name`', 'SELECT 1');
PREPARE stmt FROM @sql_title;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加展示描述字段
SET @exist_desc := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_red_packet_task' AND COLUMN_NAME = 'display_description');
SET @sql_desc := IF(@exist_desc = 0, 'ALTER TABLE `advn_red_packet_task` ADD COLUMN `display_description` VARCHAR(500) DEFAULT '''' COMMENT ''展示描述(可选，不填则使用资源描述)'' AFTER `display_title`', 'SELECT 1');
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

-- 注意：
-- 背景图、跳转链接等信息直接使用关联资源表(advn_red_packet_resource)中的字段：
-- - logo: 资源图标/封面（可作为背景图）
-- - images: 宣传图片
-- - url: 跳转链接
-- - package_name: App包名
-- - app_id: 小程序AppID
