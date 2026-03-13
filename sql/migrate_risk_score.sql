-- 为user_risk_score表添加缺失字段
-- 执行此SQL前请备份数据

-- 添加status字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态' AFTER `risk_level`;

-- 添加ban_expire_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `ban_expire_time` int unsigned DEFAULT NULL COMMENT '封禁到期时间' AFTER `status`;

-- 添加freeze_expire_time字段  
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `freeze_expire_time` int unsigned DEFAULT null COMMENT '冻结到期时间' AFTER `ban_expire_time`;

-- 添加last_violation_time字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `last_violation_time` int unsigned DEFAULT null COMMENT '最后违规时间' AFTER `violation_count`;

-- 添加score_history字段
ALTER TABLE `advn_user_risk_score` ADD COLUMN IF NOT EXISTS `score_history` text COMMENT '评分历史JSON' AFTER `last_violation_time`;

-- 更新SQL文件中的表结构
