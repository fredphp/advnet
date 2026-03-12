-- ============================================================================
-- 风控系统相关数据表
-- 创建时间: 2026-03-12
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. 风控规则表
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fa_risk_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则ID',
    `rule_code` VARCHAR(50) NOT NULL COMMENT '规则代码',
    `rule_name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `rule_type` VARCHAR(30) NOT NULL DEFAULT 'behavior' COMMENT '规则类型: video/withdraw/invite/task/behavior',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '规则描述',
    `threshold` DECIMAL(10,2) UNSIGNED DEFAULT 0 COMMENT '触发阈值',
    `score_weight` INT UNSIGNED DEFAULT 10 COMMENT '风险分数权重',
    `action` VARCHAR(20) DEFAULT 'warn' COMMENT '触发动作: warn/freeze/ban',
    `action_duration` INT UNSIGNED DEFAULT 0 COMMENT '动作持续时间(秒)',
    `enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否启用: 0=否, 1=是',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rule_code` (`rule_code`),
    KEY `idx_rule_type` (`rule_type`),
    KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控规则表';

-- ============================================================================
-- 2. 用户风险评分表
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fa_user_risk_score` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_score` INT UNSIGNED DEFAULT 0 COMMENT '总风险分',
    `risk_level` VARCHAR(20) DEFAULT 'low' COMMENT '风险等级: low/medium/high/dangerous',
    `status` VARCHAR(20) DEFAULT 'normal' COMMENT '状态: normal/frozen/banned',
    `violation_count` INT UNSIGNED DEFAULT 0 COMMENT '违规次数',
    `ban_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `freeze_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '冻结到期时间',
    `last_risk_time` INT UNSIGNED DEFAULT NULL COMMENT '最后风险时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户风险评分表';

-- ============================================================================
-- 3. 封禁记录表
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fa_ban_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `ban_type` VARCHAR(20) NOT NULL DEFAULT 'temporary' COMMENT '封禁类型: temporary/permanent',
    `ban_reason` VARCHAR(500) DEFAULT NULL COMMENT '封禁原因',
    `ban_source` VARCHAR(20) DEFAULT 'auto' COMMENT '封禁来源: auto/manual',
    `risk_score` INT UNSIGNED DEFAULT 0 COMMENT '封禁时风险分',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '封禁开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '封禁结束时间(永久为null)',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '封禁时长(秒)',
    `status` VARCHAR(20) DEFAULT 'active' COMMENT '状态: active/released/expired',
    `release_time` INT UNSIGNED DEFAULT NULL COMMENT '解封时间',
    `release_reason` VARCHAR(500) DEFAULT NULL COMMENT '解封原因',
    `release_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '解封管理员ID',
    `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '操作管理员ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_ban_type` (`ban_type`),
    KEY `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='封禁记录表';

-- ============================================================================
-- 4. 风险黑名单表
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fa_risk_blacklist` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: user/ip/device/phone',
    `value` VARCHAR(100) NOT NULL COMMENT '值',
    `reason` VARCHAR(500) DEFAULT NULL COMMENT '加入原因',
    `source` VARCHAR(20) DEFAULT 'auto' COMMENT '来源: auto/manual',
    `risk_score` INT UNSIGNED DEFAULT 0 COMMENT '风险分',
    `expire_time` INT UNSIGNED DEFAULT NULL COMMENT '过期时间(永久为null)',
    `admin_id` INT UNSIGNED DEFAULT NULL COMMENT '操作管理员ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    KEY `idx_type` (`type`),
    KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风险黑名单表';

-- ============================================================================
-- 5. 风险日志表
-- ============================================================================
CREATE TABLE IF NOT EXISTS `fa_risk_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `log_type` VARCHAR(30) NOT NULL COMMENT '日志类型',
    `risk_score` INT UNSIGNED DEFAULT 0 COMMENT '风险分数',
    `rule_code` VARCHAR(50) DEFAULT NULL COMMENT '触发的规则代码',
    `rule_name` VARCHAR(100) DEFAULT NULL COMMENT '规则名称',
    `trigger_value` VARCHAR(200) DEFAULT NULL COMMENT '触发值',
    `threshold` DECIMAL(10,2) UNSIGNED DEFAULT 0 COMMENT '阈值',
    `action` VARCHAR(20) DEFAULT NULL COMMENT '执行的动作',
    `detail` TEXT DEFAULT NULL COMMENT '详细信息(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户UA',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_log_type` (`log_type`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风险日志表';

-- ============================================================================
-- 插入默认风控规则
-- ============================================================================
INSERT INTO `fa_risk_rule` (`rule_code`, `rule_name`, `rule_type`, `description`, `threshold`, `score_weight`, `action`, `action_duration`, `enabled`, `sort`, `createtime`, `updatetime`) VALUES
('VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', '用户观看视频速度超过正常范围', 5.00, 20, 'warn', 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_WATCH_REPEAT', '重复观看同一视频', 'video', '短时间内重复观看同一视频次数过多', 10.00, 30, 'freeze', 3600, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_REWARD_SPEED', '领取奖励速度异常', 'video', '领取视频奖励速度超过正常范围', 3.00, 40, 'freeze', 86400, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', '完成任务速度超过正常范围', 5.00, 30, 'warn', 0, 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_FREQUENCY', '提现频率异常', 'withdraw', '短时间内提现次数过多', 5.00, 50, 'freeze', 86400, 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_AMOUNT_ANOMALY', '提现金额异常', 'withdraw', '提现金额超过正常范围', 100.00, 60, 'ban', 0, 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('INVITE_FREQUENCY', '邀请频率异常', 'invite', '短时间内邀请人数过多', 10.00, 40, 'freeze', 86400, 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('DEVICE_MULTI_ACCOUNT', '设备多账号', 'behavior', '同一设备登录多个账号', 3.00, 50, 'ban', 0, 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('IP_MULTI_ACCOUNT', 'IP多账号', 'behavior', '同一IP登录多个账号', 5.00, 30, 'freeze', 86400, 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('EMULATOR_DETECTED', '检测到模拟器', 'behavior', '用户使用模拟器操作', 1.00, 100, 'ban', 0, 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 输出结果
-- ============================================================================
SELECT '风控系统数据表创建完成' AS message;
