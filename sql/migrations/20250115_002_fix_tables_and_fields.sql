-- =============================================
-- 数据库迁移：修复缺失的表和字段
-- 版本：20250115_002
-- 执行顺序：在 fix_permissions.sql 之后执行
-- =============================================

-- =============================================
-- 1. 为 user 表添加缺失字段
-- =============================================

-- 添加 source 字段（如果不存在）
SET @exist_source := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_user' AND COLUMN_NAME = 'source');
SET @sql_source = IF(@exist_source = 0, 
    'ALTER TABLE `advn_user` ADD COLUMN `source` VARCHAR(50) NULL DEFAULT ''default'' COMMENT ''注册来源'' AFTER `status`', 
    'SELECT ''source column already exists''');
PREPARE stmt FROM @sql_source;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加 source 索引（如果不存在）
SET @exist_idx_source := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'advn_user' AND INDEX_NAME = 'idx_source');
SET @sql_idx_source = IF(@exist_idx_source = 0, 
    'ALTER TABLE `advn_user` ADD INDEX `idx_source` (`source`)', 
    'SELECT ''idx_source already exists''');
PREPARE stmt FROM @sql_idx_source;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 2. 创建 user_behavior_stat 表（如果不存在）
-- =============================================
CREATE TABLE IF NOT EXISTS `advn_user_behavior_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `login_count` INT UNSIGNED DEFAULT 0 COMMENT '登录次数',
    `watch_video_count` INT UNSIGNED DEFAULT 0 COMMENT '观看视频数',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `coin_earned` INT UNSIGNED DEFAULT 0 COMMENT '获得金币',
    `coin_spent` INT UNSIGNED DEFAULT 0 COMMENT '消费金币',
    `withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '提现次数',
    `withdraw_amount` DECIMAL(10,2) DEFAULT 0.00 COMMENT '提现金额',
    `invite_count` INT UNSIGNED DEFAULT 0 COMMENT '邀请人数',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `stat_date`),
    KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为统计表';

-- =============================================
-- 3. 创建 device_fingerprint 表（如果不存在）
-- =============================================
CREATE TABLE IF NOT EXISTS `advn_device_fingerprint` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `device_id` VARCHAR(128) NOT NULL COMMENT '设备唯一标识',
    `device_type` VARCHAR(20) DEFAULT 'android' COMMENT '设备类型: android/ios',
    `device_name` VARCHAR(100) DEFAULT NULL COMMENT '设备名称',
    `os_version` VARCHAR(50) DEFAULT NULL COMMENT '系统版本',
    `app_version` VARCHAR(20) DEFAULT NULL COMMENT 'APP版本',
    `is_emulator` TINYINT(1) DEFAULT 0 COMMENT '是否模拟器',
    `is_root` TINYINT(1) DEFAULT 0 COMMENT '是否越狱/Root',
    `last_login_time` INT UNSIGNED DEFAULT 0 COMMENT '最后登录时间',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_device_user` (`device_id`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_device_type` (`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备指纹表';

-- =============================================
-- 4. 创建 user_behavior 表（如果不存在）
-- =============================================
CREATE TABLE IF NOT EXISTS `advn_user_behavior` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `behavior_type` VARCHAR(50) NOT NULL COMMENT '行为类型',
    `behavior_data` TEXT COMMENT '行为数据JSON',
    `device_id` VARCHAR(128) DEFAULT NULL COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_behavior_type` (`behavior_type`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录表';

-- =============================================
-- 5. 创建其他可能缺失的表
-- =============================================

-- 风险统计表
CREATE TABLE IF NOT EXISTS `advn_risk_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `total_users` INT UNSIGNED DEFAULT 0 COMMENT '总用户数',
    `risk_users` INT UNSIGNED DEFAULT 0 COMMENT '风险用户数',
    `banned_users` INT UNSIGNED DEFAULT 0 COMMENT '封禁用户数',
    `frozen_users` INT UNSIGNED DEFAULT 0 COMMENT '冻结用户数',
    `total_requests` INT UNSIGNED DEFAULT 0 COMMENT '总请求数',
    `blocked_requests` INT UNSIGNED DEFAULT 0 COMMENT '拦截请求数',
    `createtime` INT UNSIGNED DEFAULT 0,
    `updatetime` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风险统计表';

-- 风险日志表
CREATE TABLE IF NOT EXISTS `advn_risk_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
    `rule_code` VARCHAR(50) DEFAULT NULL COMMENT '规则代码',
    `rule_name` VARCHAR(100) DEFAULT NULL COMMENT '规则名称',
    `rule_type` VARCHAR(50) DEFAULT NULL COMMENT '规则类型',
    `action` VARCHAR(20) DEFAULT NULL COMMENT '处理动作',
    `risk_score` INT UNSIGNED DEFAULT 0 COMMENT '风险分数',
    `device_id` VARCHAR(128) DEFAULT NULL COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `extra_data` TEXT COMMENT '额外数据JSON',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_rule_code` (`rule_code`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风险日志表';

-- 封禁记录表
CREATE TABLE IF NOT EXISTS `advn_ban_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `ban_type` VARCHAR(20) DEFAULT 'permanent' COMMENT '封禁类型',
    `reason` VARCHAR(500) DEFAULT NULL COMMENT '封禁原因',
    `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '操作管理员ID',
    `admin_name` VARCHAR(50) DEFAULT NULL COMMENT '操作管理员',
    `expire_time` INT UNSIGNED DEFAULT 0 COMMENT '过期时间',
    `unban_time` INT UNSIGNED DEFAULT 0 COMMENT '解封时间',
    `unban_admin_id` INT UNSIGNED DEFAULT 0 COMMENT '解封管理员ID',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='封禁记录表';

-- IP风险表
CREATE TABLE IF NOT EXISTS `advn_ip_risk` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` VARCHAR(50) NOT NULL COMMENT 'IP地址',
    `risk_score` INT UNSIGNED DEFAULT 0 COMMENT '风险分数',
    `is_proxy` TINYINT(1) DEFAULT 0 COMMENT '是否代理',
    `is_vpn` TINYINT(1) DEFAULT 0 COMMENT '是否VPN',
    `is_blacklist` TINYINT(1) DEFAULT 0 COMMENT '是否黑名单',
    `account_ids` TEXT COMMENT '关联账户ID列表JSON',
    `request_count` INT UNSIGNED DEFAULT 0 COMMENT '请求次数',
    `last_request_time` INT UNSIGNED DEFAULT 0 COMMENT '最后请求时间',
    `createtime` INT UNSIGNED DEFAULT 0,
    `updatetime` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP风险表';

-- =============================================
-- 完成
-- =============================================
SELECT 'Migration completed successfully!' AS message;
