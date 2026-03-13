-- 数据库迁移: 添加user_risk_score表缺失字段
-- 生成时间: 2026-03-13
-- 说明: 此迁移用于修复user_risk_score表缺失的字段
-- 注意: 如果字段已存在会自动跳过

-- 添加status字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态' AFTER `risk_level`;

-- 添加ban_expire_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `ban_expire_time` int unsigned DEFAULT NULL COMMENT '封禁到期时间' AFTER `status`;

-- 添加freeze_expire_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `freeze_expire_time` int unsigned DEFAULT NULL COMMENT '冻结到期时间' AFTER `ban_expire_time`;

-- 添加last_violation_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `last_violation_time` int unsigned DEFAULT NULL COMMENT '最后违规时间' AFTER `violation_count`;

-- 添加score_history字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `score_history` text COMMENT '评分历史JSON' AFTER `last_violation_time`;

-- 添加global_score字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `global_score` int NOT NULL DEFAULT 0 COMMENT '全局风险分' AFTER `redpacket_score`;

-- 添加invite_score字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN `invite_score` int NOT NULL DEFAULT 0 COMMENT '邀请相关风险分' AFTER `global_score`;

-- 添加索引
ALTER TABLE `advn_user_risk_score` ADD INDEX `idx_status` (`status`);
ALTER TABLE `advn_user_risk_score` ADD INDEX `idx_ban_expire` (`ban_expire_time`);
ALTER TABLE `advn_user_risk_score` ADD INDEX `idx_freeze_expire` (`freeze_expire_time`);
