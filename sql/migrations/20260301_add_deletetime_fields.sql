-- =====================================================
-- Migration: Add deletetime field and create missing tables
-- Date: 2026-03-01
-- Updated: 2026-03-02
-- Description: PDO兼容版本 - 只创建必要的表和菜单
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
-- Add menu entries for member and video modules
-- =====================================================

-- Add member menu (ignore if exists)
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', 0, 'member', '会员管理', 'fa fa-user', '', '', '', 1, NULL, '', 'hygl', 'huiyuanguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- Add member/user menu
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', (SELECT id FROM (SELECT id FROM `advn_auth_rule` WHERE `name` = 'member' LIMIT 1) AS tmp), 'member/user', '会员列表', 'fa fa-users', '', '', '', 1, NULL, '', 'hylb', 'huiyuanliebiao', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- Add member/user/statistics menu
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', (SELECT id FROM (SELECT id FROM `advn_auth_rule` WHERE `name` = 'member/user' LIMIT 1) AS tmp), 'member/user/statistics', '会员统计', 'fa fa-bar-chart', '', '', '', 0, NULL, '', 'hytj', 'huiyuantongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- Add video menu
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', 0, 'video', '视频管理', 'fa fa-video-camera', '', '', '', 1, NULL, '', 'spgl', 'shipinguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- Add video/video menu
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', (SELECT id FROM (SELECT id FROM `advn_auth_rule` WHERE `name` = 'video' LIMIT 1) AS tmp), 'video/video', '视频列表', 'fa fa-list', '', '', '', 1, NULL, '', 'splb', 'shipinliebiao', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =====================================================
-- 执行说明:
-- mysql -u username -p database_name < sql/migrations/20260301_add_deletetime_fields.sql
-- =====================================================
