-- =============================================
-- 风控系统数据库表设计
-- 短视频金币平台 - 完整风控体系
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. 风控规则配置表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_risk_rule`;
CREATE TABLE `advn_risk_rule` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `rule_code` varchar(50) NOT NULL COMMENT '规则编码',
    `rule_name` varchar(100) NOT NULL COMMENT '规则名称',
    `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL DEFAULT 'global' COMMENT '规则类型',
    `description` varchar(500) DEFAULT '' COMMENT '规则描述',
    `threshold` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '阈值',
    `score_weight` int(10) NOT NULL DEFAULT '10' COMMENT '违规分值权重(1-100)',
    `action` enum('warn','block','freeze','ban') NOT NULL DEFAULT 'warn' COMMENT '触发动作',
    `action_duration` int(10) NOT NULL DEFAULT '0' COMMENT '动作持续时间(秒,0永久)',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '风险等级(1低2中3高)',
    `extra_config` text COMMENT '额外配置JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rule_code` (`rule_code`),
    KEY `idx_rule_type` (`rule_type`),
    KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控规则配置表';

-- 初始化规则数据
INSERT INTO `advn_risk_rule` (`rule_code`, `rule_name`, `rule_type`, `description`, `threshold`, `score_weight`, `action`, `action_duration`, `enabled`, `level`, `createtime`, `updatetime`) VALUES
-- 视频相关规则
('VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', '视频观看速度超过正常范围', 3.00, 30, 'block', 3600, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_WATCH_REPEAT', '重复观看同一视频', 'video', '短时间内重复观看同一视频次数过多', 5.00, 20, 'warn', 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_DAILY_LIMIT', '视频观看次数超限', 'video', '单日视频观看次数超过限制', 500.00, 40, 'block', 86400, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_REWARD_SPEED', '金币获取速度异常', 'video', '单位时间内金币获取速度异常', 10000.00, 50, 'freeze', 7200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_SKIP_RATIO', '视频跳过率过高', 'video', '视频跳过率超过阈值', 0.90, 25, 'warn', 0, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 任务相关规则
('TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', '任务完成速度超过正常范围', 5.00, 35, 'block', 1800, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_DAILY_LIMIT', '任务完成次数超限', 'task', '单日任务完成次数超过限制', 100.00, 30, 'block', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_REPEAT_SUBMIT', '重复提交任务', 'task', '短时间内重复提交相同任务', 3.00, 40, 'block', 3600, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_FAKE_BEHAVIOR', '任务行为异常', 'task', '检测到虚假任务行为', 1.00, 80, 'freeze', 86400, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 提现相关规则
('WITHDRAW_FREQUENCY', '提现频率异常', 'withdraw', '短时间内频繁提现', 3.00, 45, 'freeze', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_AMOUNT_ANOMALY', '提现金额异常', 'withdraw', '提现金额与用户画像不符', 1.00, 60, 'freeze', 172800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_NEW_ACCOUNT', '新账户大额提现', 'withdraw', '新注册账户大额提现', 10.00, 50, 'freeze', 259200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 红包相关规则
('REDPACKET_GRAB_SPEED', '抢红包速度异常', 'redpacket', '抢红包速度超过正常范围', 0.50, 55, 'block', 3600, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('REDPACKET_DAILY_LIMIT', '抢红包次数超限', 'redpacket', '单日抢红包次数超过限制', 50.00, 35, 'block', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 邀请相关规则
('INVITE_SPEED', '邀请速度异常', 'invite', '短时间内邀请过多用户', 10.00, 45, 'freeze', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('INVITE_FAKE_ACCOUNT', '邀请虚假账户', 'invite', '邀请的账户被识别为虚假账户', 3.00, 80, 'ban', 604800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 全局规则
('IP_MULTI_ACCOUNT', 'IP多账户关联', 'global', '同一IP下关联账户过多', 5.00, 50, 'warn', 0, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('DEVICE_MULTI_ACCOUNT', '设备多账户关联', 'global', '同一设备关联账户过多', 3.00, 60, 'freeze', 172800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('BEHAVIOR_PATTERN', '行为模式异常', 'global', '用户行为模式与正常用户差异过大', 0.80, 70, 'freeze', 259200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ---------------------------------------------
-- 2. 用户风控评分表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_user_risk_score`;
CREATE TABLE `advn_user_risk_score` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `total_score` int(10) NOT NULL DEFAULT '0' COMMENT '总风险分值(0-1000)',
    `risk_level` enum('safe','low','medium','high','dangerous') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `video_score` int(10) NOT NULL DEFAULT '0' COMMENT '视频相关风险分',
    `task_score` int(10) NOT NULL DEFAULT '0' COMMENT '任务相关风险分',
    `withdraw_score` int(10) NOT NULL DEFAULT '0' COMMENT '提现相关风险分',
    `redpacket_score` int(10) NOT NULL DEFAULT '0' COMMENT '红包相关风险分',
    `invite_score` int(10) NOT NULL DEFAULT '0' COMMENT '邀请相关风险分',
    `global_score` int(10) NOT NULL DEFAULT '0' COMMENT '全局风险分',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `last_violation_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后违规时间',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `freeze_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '冻结到期时间',
    `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态',
    `score_history` text COMMENT '评分历史JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_status` (`status`),
    KEY `idx_ban_expire` (`ban_expire_time`),
    KEY `idx_freeze_expire` (`freeze_expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户风控评分表';

-- ---------------------------------------------
-- 3. 风控日志表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_risk_log`;
CREATE TABLE `advn_risk_log` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `rule_code` varchar(50) NOT NULL COMMENT '触发的规则编码',
    `rule_name` varchar(100) NOT NULL COMMENT '规则名称',
    `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL COMMENT '规则类型',
    `risk_level` tinyint(1) NOT NULL COMMENT '风险等级',
    `trigger_value` decimal(15,4) NOT NULL COMMENT '触发值',
    `threshold` decimal(15,4) NOT NULL COMMENT '阈值',
    `score_add` int(10) NOT NULL COMMENT '增加的风险分',
    `action` enum('warn','block','freeze','ban') NOT NULL COMMENT '执行动作',
    `action_duration` int(10) NOT NULL DEFAULT '0' COMMENT '动作持续时间',
    `action_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '动作到期时间',
    `device_id` varchar(64) DEFAULT '' COMMENT '设备ID',
    `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
    `request_data` text COMMENT '请求数据JSON',
    `response_data` text COMMENT '响应数据JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_rule_code` (`rule_code`),
    KEY `idx_rule_type` (`rule_type`),
    KEY `idx_createtime` (`createtime`),
    KEY `idx_action` (`action`),
    KEY `idx_ip` (`ip`),
    KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控日志表';

-- ---------------------------------------------
-- 4. IP风控表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_ip_risk`;
CREATE TABLE `advn_ip_risk` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `ip` varchar(50) NOT NULL COMMENT 'IP地址',
    `ip_type` enum('ipv4','ipv6') NOT NULL DEFAULT 'ipv4' COMMENT 'IP类型',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '风险分值',
    `risk_level` enum('safe','suspicious','dangerous','blacklist') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `account_count` int(10) NOT NULL DEFAULT '0' COMMENT '关联账户数',
    `account_ids` text COMMENT '关联账户ID列表JSON',
    `proxy_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到代理',
    `proxy_type` varchar(50) DEFAULT '' COMMENT '代理类型',
    `country` varchar(50) DEFAULT '' COMMENT '国家',
    `province` varchar(50) DEFAULT '' COMMENT '省份',
    `city` varchar(50) DEFAULT '' COMMENT '城市',
    `isp` varchar(100) DEFAULT '' COMMENT '运营商',
    `request_count` int(10) NOT NULL DEFAULT '0' COMMENT '请求次数',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `last_request_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后请求时间',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ip` (`ip`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_risk_score` (`risk_score`),
    KEY `idx_proxy_detected` (`proxy_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP风控表';

-- ---------------------------------------------
-- 5. 设备指纹表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_device_fingerprint`;
CREATE TABLE `advn_device_fingerprint` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `device_id` varchar(64) NOT NULL COMMENT '设备唯一标识',
    `device_hash` varchar(64) NOT NULL COMMENT '设备特征哈希',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '绑定用户ID',
    `device_type` enum('ios','android','web','other') NOT NULL DEFAULT 'other' COMMENT '设备类型',
    `device_brand` varchar(50) DEFAULT '' COMMENT '设备品牌',
    `device_model` varchar(100) DEFAULT '' COMMENT '设备型号',
    `os_version` varchar(50) DEFAULT '' COMMENT '系统版本',
    `app_version` varchar(20) DEFAULT '' COMMENT 'APP版本',
    `screen_resolution` varchar(20) DEFAULT '' COMMENT '屏幕分辨率',
    `network_type` varchar(20) DEFAULT '' COMMENT '网络类型',
    `carrier` varchar(50) DEFAULT '' COMMENT '运营商',
    `root_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到Root/越狱',
    `emulator_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到模拟器',
    `hook_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到Hook框架',
    `proxy_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到代理',
    `vpn_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到VPN',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '设备风险分',
    `risk_level` enum('safe','suspicious','dangerous','blacklist') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `account_count` int(10) NOT NULL DEFAULT '1' COMMENT '关联账户数',
    `account_ids` text COMMENT '关联账户ID列表JSON',
    `login_count` int(10) NOT NULL DEFAULT '0' COMMENT '登录次数',
    `last_login_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` varchar(50) DEFAULT '' COMMENT '最后登录IP',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_device_id` (`device_id`),
    KEY `idx_device_hash` (`device_hash`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_root_detected` (`root_detected`),
    KEY `idx_emulator_detected` (`emulator_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备指纹表';

-- ---------------------------------------------
-- 6. 用户行为记录表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_user_behavior`;
CREATE TABLE `advn_user_behavior` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `behavior_type` enum('login','video_watch','task_complete','withdraw','redpacket_grab','invite','other') NOT NULL COMMENT '行为类型',
    `behavior_action` varchar(50) NOT NULL COMMENT '行为动作',
    `target_id` int(10) UNSIGNED DEFAULT NULL COMMENT '目标ID',
    `target_type` varchar(50) DEFAULT '' COMMENT '目标类型',
    `device_id` varchar(64) DEFAULT '' COMMENT '设备ID',
    `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
    `duration` int(10) NOT NULL DEFAULT '0' COMMENT '行为持续时间(秒)',
    `extra_data` text COMMENT '额外数据JSON',
    `risk_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否标记为风险',
    `risk_reason` varchar(255) DEFAULT '' COMMENT '风险原因',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_behavior_type` (`behavior_type`),
    KEY `idx_behavior_action` (`behavior_action`),
    KEY `idx_createtime` (`createtime`),
    KEY `idx_device_id` (`device_id`),
    KEY `idx_ip` (`ip`),
    KEY `idx_risk_flag` (`risk_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录表';

-- ---------------------------------------------
-- 7. 行为统计表(按天)
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_user_behavior_stat`;
CREATE TABLE `advn_user_behavior_stat` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `video_watch_count` int(10) NOT NULL DEFAULT '0' COMMENT '视频观看次数',
    `video_watch_duration` int(10) NOT NULL DEFAULT '0' COMMENT '视频观看总时长(秒)',
    `video_skip_count` int(10) NOT NULL DEFAULT '0' COMMENT '视频跳过次数',
    `video_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '视频获得金币',
    `task_complete_count` int(10) NOT NULL DEFAULT '0' COMMENT '任务完成次数',
    `task_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '任务获得金币',
    `withdraw_count` int(10) NOT NULL DEFAULT '0' COMMENT '提现次数',
    `withdraw_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
    `redpacket_grab_count` int(10) NOT NULL DEFAULT '0' COMMENT '抢红包次数',
    `redpacket_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '红包获得金币',
    `invite_count` int(10) NOT NULL DEFAULT '0' COMMENT '邀请人数',
    `invite_reward` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '邀请奖励',
    `login_count` int(10) NOT NULL DEFAULT '0' COMMENT '登录次数',
    `active_duration` int(10) NOT NULL DEFAULT '0' COMMENT '活跃时长(秒)',
    `device_change_count` int(10) NOT NULL DEFAULT '0' COMMENT '设备切换次数',
    `ip_change_count` int(10) NOT NULL DEFAULT '0' COMMENT 'IP切换次数',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `risk_score_add` int(10) NOT NULL DEFAULT '0' COMMENT '新增风险分',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `stat_date`),
    KEY `idx_stat_date` (`stat_date`),
    KEY `idx_violation_count` (`violation_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为统计表';

-- ---------------------------------------------
-- 8. 封禁记录表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_ban_record`;
CREATE TABLE `advn_ban_record` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `ban_type` enum('temporary','permanent') NOT NULL DEFAULT 'temporary' COMMENT '封禁类型',
    `ban_reason` varchar(255) NOT NULL COMMENT '封禁原因',
    `ban_source` enum('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '封禁来源',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '封禁时风险分',
    `rule_codes` text COMMENT '触发的规则编码JSON',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '操作管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '操作管理员名称',
    `start_time` int(10) UNSIGNED NOT NULL COMMENT '开始时间',
    `end_time` int(10) UNSIGNED DEFAULT NULL COMMENT '结束时间(NULL永久)',
    `duration` int(10) NOT NULL DEFAULT '0' COMMENT '封禁时长(秒,0永久)',
    `status` enum('active','released','expired') NOT NULL DEFAULT 'active' COMMENT '状态',
    `release_time` int(10) UNSIGNED DEFAULT NULL COMMENT '解封时间',
    `release_reason` varchar(255) DEFAULT '' COMMENT '解封原因',
    `release_admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '解封管理员ID',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_ban_type` (`ban_type`),
    KEY `idx_status` (`status`),
    KEY `idx_start_time` (`start_time`),
    KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='封禁记录表';

-- ---------------------------------------------
-- 9. 风控白名单表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_risk_whitelist`;
CREATE TABLE `advn_risk_whitelist` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `type` enum('user','ip','device') NOT NULL COMMENT '类型',
    `value` varchar(100) NOT NULL COMMENT '值',
    `reason` varchar(255) DEFAULT '' COMMENT '加入原因',
    `expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '过期时间(NULL永久)',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '添加管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '管理员名称',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    KEY `idx_type` (`type`),
    KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控白名单表';

-- ---------------------------------------------
-- 10. 风控黑名单表
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_risk_blacklist`;
CREATE TABLE `advn_risk_blacklist` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `type` enum('user','ip','device') NOT NULL COMMENT '类型',
    `value` varchar(100) NOT NULL COMMENT '值',
    `reason` varchar(255) DEFAULT '' COMMENT '加入原因',
    `source` enum('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '来源',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '风险分',
    `expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '过期时间(NULL永久)',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '添加管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '管理员名称',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    KEY `idx_type` (`type`),
    KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控黑名单表';

-- ---------------------------------------------
-- 11. 风控统计表(全局)
-- ---------------------------------------------
DROP TABLE IF EXISTS `advn_risk_stat`;
CREATE TABLE `advn_risk_stat` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `total_requests` bigint(20) NOT NULL DEFAULT '0' COMMENT '总请求数',
    `blocked_requests` bigint(20) NOT NULL DEFAULT '0' COMMENT '拦截请求数',
    `warn_count` int(10) NOT NULL DEFAULT '0' COMMENT '警告次数',
    `block_count` int(10) NOT NULL DEFAULT '0' COMMENT '拦截次数',
    `freeze_count` int(10) NOT NULL DEFAULT '0' COMMENT '冻结次数',
    `ban_count` int(10) NOT NULL DEFAULT '0' COMMENT '封禁次数',
    `unique_ip_count` int(10) NOT NULL DEFAULT '0' COMMENT '独立IP数',
    `unique_device_count` int(10) NOT NULL DEFAULT '0' COMMENT '独立设备数',
    `proxy_detected_count` int(10) NOT NULL DEFAULT '0' COMMENT '检测到代理数',
    `emulator_detected_count` int(10) NOT NULL DEFAULT '0' COMMENT '检测到模拟器数',
    `rule_trigger_stats` text COMMENT '规则触发统计JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控统计表';

SET FOREIGN_KEY_CHECKS = 1;
