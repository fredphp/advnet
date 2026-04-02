-- =====================================================
-- 签到系统迁移
-- 创建签到配置表、签到奖励规则表、签到记录表
-- 插入后台管理菜单权限规则（auth_rule）
-- 初始化默认配置和7天周期奖励规则
-- 执行时间: 2026-07-16
-- =====================================================

-- 1. 创建签到配置表
CREATE TABLE IF NOT EXISTS `__PREFIX__signin_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用签到: 1=启用, 0=禁用',
  `fillup_days` int(10) unsigned NOT NULL DEFAULT 3 COMMENT '每月可补签天数限制',
  `fillup_cost` int(10) unsigned NOT NULL DEFAULT 50 COMMENT '每次补签消耗金币',
  `createtime` int(10) unsigned DEFAULT 0 COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到配置表';

-- 2. 创建签到奖励规则表
CREATE TABLE IF NOT EXISTS `__PREFIX__signin_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `day` int(10) unsigned NOT NULL COMMENT '周期第几天(7天一循环)',
  `coins` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金币数量',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '规则描述',
  `createtime` int(10) unsigned DEFAULT 0 COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到奖励规则表';

-- 3. 创建签到记录表
CREATE TABLE IF NOT EXISTS `__PREFIX__signin_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `signin_date` varchar(10) NOT NULL COMMENT '签到日期 YYYY-MM-DD',
  `coins` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '获得金币数量',
  `type` varchar(20) NOT NULL DEFAULT 'daily' COMMENT '签到类型: daily=日常签到, fillup=补签',
  `successions` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '连续签到天数',
  `createtime` int(10) unsigned DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `signin_date`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_signin_date` (`signin_date`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';

-- 4. 初始化默认签到配置（仅插入一条配置记录）
INSERT IGNORE INTO `__PREFIX__signin_config` (`id`, `enabled`, `fillup_days`, `fillup_cost`, `createtime`, `updatetime`)
VALUES (1, 1, 3, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 5. 初始化7天周期奖励规则
INSERT IGNORE INTO `__PREFIX__signin_rule` (`day`, `coins`, `description`, `createtime`, `updatetime`) VALUES
(1, 10, '第1天签到奖励10金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 20, '第2天签到奖励20金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 30, '第3天签到奖励30金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 40, '第4天签到奖励40金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 50, '第5天签到奖励50金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 60, '第6天签到奖励60金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 100, '连续签到7天大奖励100金币', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 6. 插入后台管理菜单权限规则（advn_auth_rule）
-- 一级菜单：签到管理
INSERT INTO `__PREFIX__auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (0, 'signin', '签到管理', 'fa fa-calendar-check-o', '', '签到系统配置与奖励规则管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 60, 'normal');

-- 获取刚插入的签到管理菜单ID
SET @signin_parent_id = LAST_INSERT_ID();

-- 二级菜单：签到配置
INSERT INTO `__PREFIX__auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@signin_parent_id, 'signin/config', '签到配置', 'fa fa-cog', '', '签到系统基本配置和奖励规则管理', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal');

-- 获取签到配置菜单ID
SET @signin_config_id = LAST_INSERT_ID();

-- 签到配置子权限
INSERT INTO `__PREFIX__auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
(@signin_config_id, 'signin/config/index', '查看', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@signin_config_id, 'signin/config/save', '保存配置', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@signin_config_id, 'signin/config/addRule', '添加规则', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@signin_config_id, 'signin/config/editRule', '编辑规则', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
(@signin_config_id, 'signin/config/delRule', '删除规则', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
