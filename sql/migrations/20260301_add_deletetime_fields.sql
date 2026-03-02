-- =====================================================
-- Migration: Add deletetime field and create missing tables
-- Date: 2026-03-01
-- Updated: 2026-03-02
-- Description: This migration adds deletetime field to tables
--              that use SoftDelete trait in their models
--              and creates missing video table
-- =====================================================

-- =====================================================
-- Fix 1: advn_video table
-- This fixes the error: Unknown column 'advn_video.deletetime' in 'where clause'
-- =====================================================

-- Create advn_video table if not exists
CREATE TABLE IF NOT EXISTS `advn_video` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `description` text COMMENT '描述',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '视频地址',
  `cover` varchar(255) DEFAULT '' COMMENT '封面图',
  `duration` int(10) unsigned DEFAULT 0 COMMENT '时长(秒)',
  `views` int(10) unsigned DEFAULT 0 COMMENT '浏览量',
  `likes` int(10) unsigned DEFAULT 0 COMMENT '点赞数',
  `category_id` int(10) unsigned DEFAULT 0 COMMENT '分类ID',
  `admin_id` int(10) unsigned DEFAULT 0 COMMENT '管理员ID',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint(16) DEFAULT NULL COMMENT '删除时间',
  `weigh` int(10) DEFAULT 0 COMMENT '权重',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_deletetime` (`deletetime`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频表';

-- =====================================================
-- Fix 2: advn_user table (member module)
-- Add deletetime field using stored procedure
-- =====================================================

-- 创建辅助存储过程：添加字段（如果表存在且字段不存在）
DROP PROCEDURE IF EXISTS add_column_if_not_exists;

DELIMITER //
CREATE PROCEDURE add_column_if_not_exists(
    IN table_name VARCHAR(100),
    IN column_name VARCHAR(100),
    IN column_definition VARCHAR(500)
)
BEGIN
    DECLARE column_count INT DEFAULT 0;

    -- 检查字段是否已存在
    SELECT COUNT(*) INTO column_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = table_name
    AND column_name = column_name;

    IF column_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Add deletetime field to advn_user table
CALL add_column_if_not_exists('advn_user', 'deletetime', 'BIGINT(16) DEFAULT NULL COMMENT "删除时间" AFTER `updatetime`');

-- Add index if not exists
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

DELIMITER //
CREATE PROCEDURE add_index_if_not_exists(
    IN table_name VARCHAR(100),
    IN index_name VARCHAR(100),
    IN index_columns VARCHAR(500)
)
BEGIN
    DECLARE index_count INT DEFAULT 0;

    -- 检查索引是否已存在
    SELECT COUNT(*) INTO index_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
    AND table_name = table_name
    AND index_name = index_name;

    IF index_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `', index_name, '` (', index_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Add index for deletetime
CALL add_index_if_not_exists('advn_user', 'idx_deletetime', '`deletetime`');

-- 清理存储过程
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;

-- =====================================================
-- Add menu entries for member and video modules
-- =====================================================

-- Add member menu
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', 0, 'member', '会员管理', 'fa fa-user', '', '', '', 1, NULL, '', 'hygl', 'huiyuanguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE `name` = 'member');

-- Add member/user menu
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', (SELECT id FROM `advn_auth_rule` WHERE `name` = 'member' LIMIT 1), 'member/user', '会员列表', 'fa fa-users', '', '', '', 1, NULL, '', 'hylb', 'huiyuanliebiao', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE `name` = 'member/user');

-- Add member/user/statistics menu
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', (SELECT id FROM `advn_auth_rule` WHERE `name` = 'member/user' LIMIT 1), 'member/user/statistics', '会员统计', 'fa fa-bar-chart', '', '', '', 0, NULL, '', 'hytj', 'huiyuantongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE `name` = 'member/user/statistics');

-- Add video menu
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', 0, 'video', '视频管理', 'fa fa-video-camera', '', '', '', 1, NULL, '', 'spgl', 'shipinguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE `name` = 'video');

-- Add video/video menu
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', (SELECT id FROM `advn_auth_rule` WHERE `name` = 'video' LIMIT 1), 'video/video', '视频列表', 'fa fa-list', '', '', '', 1, NULL, '', 'splb', 'shipinliebiao', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE `name` = 'video/video');

-- =====================================================
-- To execute this migration, run:
-- mysql -u username -p database_name < sql/migrations/20260301_add_deletetime_fields.sql
-- Or execute the statements directly in your MySQL client
-- =====================================================
