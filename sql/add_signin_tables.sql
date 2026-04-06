-- =============================================
-- 签到系统数据库表
-- =============================================

-- 签到配置表
CREATE TABLE IF NOT EXISTS `advn_signin_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否启用签到',
  `fillup_days` int(10) unsigned NOT NULL DEFAULT 3 COMMENT '补签天数限制',
  `fillup_cost` int(10) unsigned NOT NULL DEFAULT 50 COMMENT '补签消耗金币',
  `createtime` int(10) unsigned DEFAULT NULL,
  `updatetime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到配置';

-- 签到奖励规则表
CREATE TABLE IF NOT EXISTS `advn_signin_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `day` int(10) unsigned NOT NULL COMMENT '周期第几天',
  `coins` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金币',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `createtime` int(10) unsigned DEFAULT NULL,
  `updatetime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到奖励规则';

-- 签到记录表
CREATE TABLE IF NOT EXISTS `advn_signin_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `signin_date` varchar(10) NOT NULL COMMENT '签到日期 YYYY-MM-DD',
  `coins` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '获得金币',
  `type` varchar(20) NOT NULL DEFAULT 'daily' COMMENT '类型: daily/fillup',
  `successions` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '连续签到天数',
  `createtime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `signin_date`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_signin_date` (`signin_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录';

-- 初始化默认配置
INSERT INTO `advn_signin_config` (`id`, `enabled`, `fillup_days`, `fillup_cost`, `createtime`, `updatetime`) VALUES (1, 1, 3, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 初始化奖励规则（7天一个周期）
INSERT INTO `advn_signin_rule` (`day`, `coins`, `description`, `createtime`, `updatetime`) VALUES
(1, 10, '第1天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 20, '第2天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 30, '第3天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, 40, '第4天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, 50, '第5天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, 60, '第6天签到奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, 100, '连续签到7天大奖励', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
