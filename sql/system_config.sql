-- =============================================
-- 系统配置表
-- =============================================

DROP TABLE IF EXISTS `advn_system_config`;
CREATE TABLE `advn_system_config` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` varchar(50) NOT NULL DEFAULT '' COMMENT '配置分组',
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
    `value` text COMMENT '配置值',
    `type` enum('string','integer','float','boolean','json','array') NOT NULL DEFAULT 'string' COMMENT '值类型',
    `title` varchar(100) NOT NULL DEFAULT '' COMMENT '配置标题',
    `tip` varchar(255) DEFAULT '' COMMENT '配置说明',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=启用',
    `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_name` (`group`, `name`),
    KEY `idx_group` (`group`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- 插入默认配置数据
INSERT INTO `advn_system_config` (`group`, `name`, `value`, `type`, `title`, `tip`, `status`, `sort`, `createtime`, `updatetime`) VALUES
-- 金币配置
('coin', 'coin_rate', '10000', 'integer', '金币汇率', '多少金币等于1元人民币', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'new_user_coin', '1000', 'integer', '新用户奖励', '新用户注册奖励金币数量', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'video_coin_reward', '100', 'integer', '视频观看奖励', '每次有效观看奖励的金币数量', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'video_watch_duration', '30', 'integer', '有效观看时长', '观看多少秒才算有效观看', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'daily_video_limit', '500', 'integer', '每日视频上限', '用户每日可观看视频次数上限', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'daily_coin_limit', '50000', 'integer', '每日金币上限', '用户每日可获取金币上限', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'hourly_coin_limit', '10000', 'integer', '每小时金币上限', '用户每小时可获取金币上限', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 提现配置
('withdraw', 'withdraw_enabled', '1', 'boolean', '提现开关', '是否开启提现功能', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'min_withdraw', '1', 'float', '最低提现金额', '最低提现金额（元）', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'max_withdraw', '500', 'float', '最大提现金额', '单次最大提现金额（元）', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'daily_withdraw_limit', '3', 'integer', '每日提现次数', '每日提现次数限制', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'daily_withdraw_amount', '500', 'float', '每日提现金额', '每日提现金额限制（元）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'fee_rate', '0', 'float', '提现手续费率', '提现手续费率，如0.01表示1%', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'auto_audit_amount', '10', 'float', '自动审核金额', '低于此金额自动审核（元）', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'manual_audit_amount', '50', 'float', '人工审核金额', '高于此金额需人工审核（元）', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'new_user_withdraw_days', '3', 'integer', '新用户提现限制', '注册多少天后才能提现', 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'same_ip_limit', '5', 'integer', '同IP提现限制', '同一IP每日提现次数限制', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'same_device_limit', '3', 'integer', '同设备提现限制', '同一设备每日提现次数限制', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 邀请配置
('invite', 'invite_enabled', '1', 'boolean', '邀请开关', '是否开启邀请功能', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_level1_reward', '1000', 'integer', '一级邀请奖励', '直接邀请奖励金币', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_level2_reward', '500', 'integer', '二级邀请奖励', '间接邀请奖励金币', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_enabled', '1', 'boolean', '分佣开关', '是否开启邀请分佣', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level1_commission_rate', '0.10', 'float', '一级分佣比例', '直接邀请的分佣比例', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level2_commission_rate', '0.05', 'float', '二级分佣比例', '间接邀请的分佣比例', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'daily_invite_limit', '50', 'integer', '每日邀请限制', '每日邀请次数上限', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 风控配置
('risk', 'risk_enabled', '1', 'boolean', '风控开关', '是否开启风控检测', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'auto_ban_enabled', '1', 'boolean', '自动封禁开关', '是否开启自动封禁', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'ban_threshold', '700', 'integer', '封禁阈值', '风险分达到此值自动封禁', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'freeze_threshold', '300', 'integer', '冻结阈值', '风险分达到此值自动冻结', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'score_decay_rate', '0.1', 'float', '评分衰减率', '风险分每日衰减比例', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'max_risk_score', '1000', 'integer', '最大风险分', '风险分最大值', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'emulator_block', '1', 'boolean', '模拟器拦截', '是否拦截模拟器访问', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'hook_block', '1', 'boolean', 'Hook框架拦截', '是否拦截Hook框架', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'proxy_detect', '1', 'boolean', '代理检测', '是否检测代理访问', 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'ip_multi_account_threshold', '5', 'integer', 'IP多账户阈值', '同IP关联账户数阈值', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'device_multi_account_threshold', '3', 'integer', '设备多账户阈值', '同设备关联账户数阈值', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 系统配置
('system', 'api_rate_limit', '60', 'integer', 'API限流', '每分钟API请求限制', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'user_rate_limit', '30', 'integer', '用户限流', '每分钟用户请求限制', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'high_risk_rate_limit', '5', 'integer', '高风险操作限流', '高风险操作每5分钟限制', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'session_timeout', '86400', 'integer', '会话超时', '会话超时时间（秒）', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'token_ttl', '604800', 'integer', 'Token有效期', 'Token有效期（秒）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'log_retention_days', '30', 'integer', '日志保留天数', '日志保留天数', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
