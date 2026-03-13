-- 数据库迁移: 添加user_risk_score表缺失字段
-- 生成时间: 2026-03-13
-- 说明: 此迁移用于修复user_risk_score表缺失的字段

-- 检查并添加status字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态' AFTER `risk_level`;

-- 检查并添加ban_expire_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `ban_expire_time` int unsigned DEFAULT NULL COMMENT '封禁到期时间' AFTER `status`;

-- 检查并添加freeze_expire_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `freeze_expire_time` int unsigned DEFAULT NULL COMMENT '冻结到期时间' AFTER `ban_expire_time`;

-- 检查并添加last_violation_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `last_violation_time` int unsigned DEFAULT NULL COMMENT '最后违规时间' AFTER `violation_count`;

-- 检查并添加score_history字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `score_history` text COMMENT '评分历史JSON' AFTER `last_violation_time`;

-- 检查并添加global_score字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `global_score` int NOT NULL DEFAULT 0 COMMENT '全局风险分' AFTER `redpacket_score`;

-- 检查并添加invite_score字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `invite_score` int NOT NULL DEFAULT 0 COMMENT '邀请相关风险分' AFTER `global_score`;

-- 添加索引
ALTER TABLE `advn_user_risk_score` ADD INDEX IF NOT EXISTS `idx_status` (`status`);
ALTER TABLE `advn_user_risk_score` ADD INDEX IF NOT EXISTS `idx_ban_expire` (`ban_expire_time`);
ALTER TABLE `advn_user_risk_score` ADD INDEX IF NOT EXISTS `idx_freeze_expire` (`freeze_expire_time`);
