-- 风控系统数据库表结构和模拟数据

-- 1. 风控规则表
CREATE TABLE IF NOT EXISTS `fa_risk_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_code` varchar(50) NOT NULL DEFAULT '' COMMENT '规则代码',
  `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL DEFAULT 'global' COMMENT '规则类型',
  `description` varchar(500) DEFAULT '' COMMENT '规则描述',
  `threshold` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '阈值',
  `score_weight` int(11) NOT NULL DEFAULT '10' COMMENT '风险权重',
  `action` enum('warn','block','freeze','ban') NOT NULL DEFAULT 'warn' COMMENT '处理动作',
  `action_duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处罚时长(秒)',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '优先级',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=启用',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rule_code` (`rule_code`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控规则表';

-- 2. 用户风险评分表
CREATE TABLE IF NOT EXISTS `fa_user_risk_score` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `total_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总风险分',
  `video_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频风险分',
  `task_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '任务风险分',
  `withdraw_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提现风险分',
  `redpacket_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '红包风险分',
  `invite_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '邀请风险分',
  `global_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '全局风险分',
  `risk_level` enum('safe','low','medium','high','critical') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
  `status` enum('normal','warning','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `violation_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '违规次数',
  `last_violation_time` int(11) unsigned DEFAULT NULL COMMENT '最后违规时间',
  `ban_expire_time` int(11) unsigned DEFAULT NULL COMMENT '封禁到期时间',
  `freeze_expire_time` int(11) unsigned DEFAULT NULL COMMENT '冻结到期时间',
  `score_history` text COMMENT '评分历史JSON',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_status` (`status`),
  KEY `idx_total_score` (`total_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户风险评分表';

-- 3. 封禁记录表
CREATE TABLE IF NOT EXISTS `fa_ban_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `ban_type` enum('temporary','permanent') NOT NULL DEFAULT 'temporary' COMMENT '封禁类型',
  `ban_source` enum('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '封禁来源',
  `reason` varchar(255) NOT NULL DEFAULT '' COMMENT '封禁原因',
  `rule_code` varchar(50) DEFAULT '' COMMENT '触发规则代码',
  `duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '封禁时长(秒)',
  `start_time` int(11) unsigned DEFAULT NULL COMMENT '开始时间',
  `end_time` int(11) unsigned DEFAULT NULL COMMENT '结束时间',
  `status` enum('active','released','expired') NOT NULL DEFAULT 'active' COMMENT '状态',
  `release_time` int(11) unsigned DEFAULT NULL COMMENT '解封时间',
  `release_reason` varchar(255) DEFAULT '' COMMENT '解封原因',
  `release_admin_id` int(11) unsigned DEFAULT NULL COMMENT '解封管理员ID',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(50) DEFAULT '' COMMENT '操作管理员名称',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ban_type` (`ban_type`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='封禁记录表';

-- 4. 风险日志表
CREATE TABLE IF NOT EXISTS `fa_risk_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `rule_code` varchar(50) NOT NULL DEFAULT '' COMMENT '规则代码',
  `rule_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL DEFAULT 'global' COMMENT '规则类型',
  `risk_level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '风险等级',
  `trigger_value` decimal(10,2) DEFAULT '0.00' COMMENT '触发值',
  `threshold` decimal(10,2) DEFAULT '0.00' COMMENT '阈值',
  `score_add` int(11) NOT NULL DEFAULT '0' COMMENT '增加分数',
  `action` enum('warn','block','freeze','ban') NOT NULL DEFAULT 'warn' COMMENT '处理动作',
  `action_duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处罚时长',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT '' COMMENT '用户代理',
  `request_data` text COMMENT '请求数据JSON',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_rule_code` (`rule_code`),
  KEY `idx_rule_type` (`rule_type`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风险日志表';

-- 5. 黑名单表
CREATE TABLE IF NOT EXISTS `fa_risk_blacklist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('user','ip','device') NOT NULL DEFAULT 'user' COMMENT '类型',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '值',
  `reason` varchar(255) DEFAULT '' COMMENT '原因',
  `risk_level` enum('safe','low','medium','high','critical') NOT NULL DEFAULT 'high' COMMENT '风险等级',
  `source` enum('auto','manual','report') NOT NULL DEFAULT 'manual' COMMENT '来源',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(50) DEFAULT '' COMMENT '操作管理员名称',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value` (`type`, `value`),
  KEY `idx_type` (`type`),
  KEY `idx_risk_level` (`risk_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='黑名单表';

-- 6. 白名单表
CREATE TABLE IF NOT EXISTS `fa_risk_whitelist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('user','ip','device') NOT NULL DEFAULT 'user' COMMENT '类型',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '值',
  `reason` varchar(255) DEFAULT '' COMMENT '原因',
  `expire_time` int(11) unsigned DEFAULT NULL COMMENT '过期时间',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员ID',
  `admin_name` varchar(50) DEFAULT '' COMMENT '操作管理员名称',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value` (`type`, `value`),
  KEY `idx_type` (`type`),
  KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='白名单表';

-- 7. 设备指纹表
CREATE TABLE IF NOT EXISTS `fa_device_fingerprint` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `device_id` varchar(64) NOT NULL DEFAULT '' COMMENT '设备ID',
  `device_type` varchar(20) DEFAULT '' COMMENT '设备类型',
  `os_type` varchar(20) DEFAULT '' COMMENT '操作系统',
  `os_version` varchar(20) DEFAULT '' COMMENT '系统版本',
  `app_version` varchar(20) DEFAULT '' COMMENT 'APP版本',
  `account_ids` text COMMENT '关联账户ID列表JSON',
  `risk_level` enum('safe','low','medium','high','critical') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
  `last_active_time` int(11) unsigned DEFAULT NULL COMMENT '最后活跃时间',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_risk_level` (`risk_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备指纹表';

-- 8. 用户行为统计表
CREATE TABLE IF NOT EXISTS `fa_user_behavior_stat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `stat_date` date NOT NULL COMMENT '统计日期',
  `video_watch_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频观看次数',
  `video_watch_duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频观看时长(秒)',
  `video_reward_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '视频奖励次数',
  `task_complete_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '任务完成次数',
  `task_complete_duration` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '任务完成时长(秒)',
  `withdraw_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提现次数',
  `withdraw_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `redpacket_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '红包领取次数',
  `redpacket_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '红包金额',
  `invite_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人数',
  `createtime` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_date` (`user_id`, `stat_date`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为统计表';

-- =====================================================
-- 模拟数据
-- =====================================================

-- 获取一些用户ID用于模拟数据
-- 先查看现有用户

-- 插入风控规则模拟数据
INSERT INTO `fa_risk_rule` (`rule_name`, `rule_code`, `rule_type`, `description`, `threshold`, `score_weight`, `action`, `action_duration`, `level`, `enabled`, `createtime`, `updatetime`) VALUES
('视频观看速度异常', 'VIDEO_WATCH_SPEED', 'video', '检测视频观看速度是否异常，正常观看速度应在0.8-1.5倍速之间', 2.00, 20, 'warn', 0, 5, 1, UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
('视频重复观看检测', 'VIDEO_WATCH_REPEAT', 'video', '检测同一视频短时间内的重复观看次数', 5.00, 15, 'warn', 0, 3, 1, UNIX_TIMESTAMP()-86400*29, UNIX_TIMESTAMP()),
('视频奖励作弊检测', 'VIDEO_REWARD_SPEED', 'video', '检测视频奖励获取是否异常快速', 3.00, 35, 'block', 86400, 7, 1, UNIX_TIMESTAMP()-86400*28, UNIX_TIMESTAMP()),
('任务完成速度异常', 'TASK_COMPLETE_SPEED', 'task', '检测任务完成时间是否异常短', 10.00, 25, 'warn', 0, 4, 1, UNIX_TIMESTAMP()-86400*27, UNIX_TIMESTAMP()),
('任务批量刷单检测', 'TASK_BATCH_COMPLETE', 'task', '检测短时间内大量完成任务', 50.00, 40, 'freeze', 604800, 8, 1, UNIX_TIMESTAMP()-86400*26, UNIX_TIMESTAMP()),
('提现频率异常', 'WITHDRAW_FREQUENCY', 'withdraw', '检测提现频率是否异常', 5.00, 30, 'block', 86400, 6, 1, UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
('提现金额异常', 'WITHDRAW_AMOUNT_ANOMALY', 'withdraw', '检测提现金额是否异常偏高', 500.00, 45, 'freeze', 2592000, 8, 1, UNIX_TIMESTAMP()-86400*24, UNIX_TIMESTAMP()),
('红包领取异常', 'REDPACKET_CLAIM_SPEED', 'redpacket', '检测红包领取速度是否异常', 2.00, 20, 'warn', 0, 4, 1, UNIX_TIMESTAMP()-86400*23, UNIX_TIMESTAMP()),
('红包批量领取', 'REDPACKET_BATCH_CLAIM', 'redpacket', '检测短时间内大量领取红包', 20.00, 35, 'block', 86400, 6, 1, UNIX_TIMESTAMP()-86400*22, UNIX_TIMESTAMP()),
('邀请作弊检测', 'INVITE_FAKE_DETECTION', 'invite', '检测邀请用户是否为虚假用户', 3.00, 50, 'ban', 0, 9, 1, UNIX_TIMESTAMP()-86400*21, UNIX_TIMESTAMP()),
('多设备登录检测', 'GLOBAL_MULTI_DEVICE', 'global', '检测同一账户多设备登录', 5.00, 25, 'warn', 0, 5, 1, UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
('IP异常检测', 'GLOBAL_IP_ANOMALY', 'global', '检测IP地址是否异常(代理/VPN)', 1.00, 40, 'block', 86400, 7, 1, UNIX_TIMESTAMP()-86400*19, UNIX_TIMESTAMP()),
('设备切换频繁', 'GLOBAL_DEVICE_SWITCH', 'global', '检测设备切换是否频繁', 10.00, 30, 'warn', 0, 4, 0, UNIX_TIMESTAMP()-86400*18, UNIX_TIMESTAMP()),
('账号异常行为综合', 'GLOBAL_BEHAVIOR_ANOMALY', 'global', '综合评估账号异常行为', 80.00, 60, 'ban', 0, 10, 1, UNIX_TIMESTAMP()-86400*17, UNIX_TIMESTAMP()),
('新用户高风险行为', 'GLOBAL_NEW_USER_RISK', 'global', '新用户短时间内高风险行为', 50.00, 45, 'freeze', 604800, 6, 1, UNIX_TIMESTAMP()-86400*16, UNIX_TIMESTAMP());

-- 插入用户风险评分模拟数据 (使用现有用户ID，如果没有则创建测试用户)
INSERT INTO `fa_user_risk_score` (`user_id`, `total_score`, `video_score`, `task_score`, `withdraw_score`, `redpacket_score`, `invite_score`, `global_score`, `risk_level`, `status`, `violation_count`, `last_violation_time`, `ban_expire_time`, `freeze_expire_time`, `score_history`, `createtime`, `updatetime`) VALUES
(1, 15, 5, 3, 2, 5, 0, 0, 'low', 'normal', 3, UNIX_TIMESTAMP()-3600, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
(2, 85, 30, 25, 15, 10, 5, 0, 'high', 'warning', 12, UNIX_TIMESTAMP()-1800, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
(3, 0, 0, 0, 0, 0, 0, 0, 'safe', 'normal', 0, NULL, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
(4, 150, 50, 40, 30, 20, 10, 0, 'critical', 'banned', 25, UNIX_TIMESTAMP()-600, UNIX_TIMESTAMP()+86400*7, NULL, '[]', UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()),
(5, 45, 15, 10, 8, 7, 5, 0, 'medium', 'normal', 6, UNIX_TIMESTAMP()-7200, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()),
(6, 200, 80, 60, 40, 15, 5, 0, 'critical', 'frozen', 35, UNIX_TIMESTAMP()-300, NULL, UNIX_TIMESTAMP()+86400*3, '[]', UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP()),
(7, 25, 8, 6, 4, 5, 2, 0, 'low', 'normal', 4, UNIX_TIMESTAMP()-86400, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
(8, 95, 35, 28, 18, 9, 5, 0, 'high', 'warning', 15, UNIX_TIMESTAMP()-1200, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
(9, 5, 2, 1, 1, 1, 0, 0, 'safe', 'normal', 1, UNIX_TIMESTAMP()-86400*2, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()),
(10, 180, 70, 50, 35, 20, 5, 0, 'critical', 'banned', 30, UNIX_TIMESTAMP()-120, UNIX_TIMESTAMP()+86400*30, NULL, '[]', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
(11, 35, 12, 8, 6, 5, 4, 0, 'medium', 'normal', 5, UNIX_TIMESTAMP()-14400, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()),
(12, 60, 22, 18, 10, 6, 4, 0, 'medium', 'normal', 8, UNIX_TIMESTAMP()-5400, NULL, NULL, '[]', UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP());

-- 插入封禁记录模拟数据
INSERT INTO `fa_ban_record` (`user_id`, `ban_type`, `ban_source`, `reason`, `rule_code`, `duration`, `start_time`, `end_time`, `status`, `release_time`, `release_reason`, `release_admin_id`, `admin_id`, `admin_name`, `createtime`) VALUES
(4, 'temporary', 'auto', '触发视频奖励作弊检测规则', 'VIDEO_REWARD_SPEED', 604800, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()+86400*6, 'active', NULL, '', NULL, NULL, '系统', UNIX_TIMESTAMP()-86400),
(6, 'temporary', 'auto', '触发账号异常行为综合规则', 'GLOBAL_BEHAVIOR_ANOMALY', 259200, UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()+86400*3, 'active', NULL, '', NULL, NULL, '系统', UNIX_TIMESTAMP()-3600),
(10, 'permanent', 'auto', '触发邀请作弊检测规则', 'INVITE_FAKE_DETECTION', 0, UNIX_TIMESTAMP()-7200, NULL, 'active', NULL, '', NULL, NULL, '系统', UNIX_TIMESTAMP()-7200),
(2, 'temporary', 'manual', '疑似刷单行为', 'TASK_BATCH_COMPLETE', 86400, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()-86400*2, 'expired', NULL, '', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*3),
(8, 'temporary', 'auto', '触发提现频率异常规则', 'WITHDRAW_FREQUENCY', 86400, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*4, 'released', UNIX_TIMESTAMP()-86400*4+3600, '误判，已核实', 1, NULL, '系统', UNIX_TIMESTAMP()-86400*5),
(12, 'temporary', 'auto', '触发视频重复观看检测规则', 'VIDEO_WATCH_REPEAT', 172800, UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()-86400*5, 'expired', NULL, '', NULL, NULL, '系统', UNIX_TIMESTAMP()-86400*7);

-- 插入风险日志模拟数据
INSERT INTO `fa_risk_log` (`user_id`, `rule_code`, `rule_name`, `rule_type`, `risk_level`, `trigger_value`, `threshold`, `score_add`, `action`, `action_duration`, `ip`, `user_agent`, `request_data`, `createtime`) VALUES
(2, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 2, 2.50, 2.00, 20, 'warn', 0, '192.168.1.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', '{"video_id": 1001, "speed": 2.5}', UNIX_TIMESTAMP()-1800),
(2, 'TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', 2, 15.00, 10.00, 25, 'warn', 0, '192.168.1.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', '{"task_id": 2001, "duration": 15}', UNIX_TIMESTAMP()-1700),
(4, 'VIDEO_REWARD_SPEED', '视频奖励作弊检测', 'video', 3, 4.50, 3.00, 35, 'block', 86400, '10.0.0.55', 'Mozilla/5.0 (Android 11; Mobile)', '{"video_id": 1002, "reward_interval": 4.5}', UNIX_TIMESTAMP()-1600),
(4, 'VIDEO_WATCH_REPEAT', '视频重复观看检测', 'video', 2, 8.00, 5.00, 15, 'warn', 0, '10.0.0.55', 'Mozilla/5.0 (Android 11; Mobile)', '{"video_id": 1002, "repeat_count": 8}', UNIX_TIMESTAMP()-1500),
(6, 'GLOBAL_BEHAVIOR_ANOMALY', '账号异常行为综合', 'global', 3, 95.00, 80.00, 60, 'ban', 0, '172.16.0.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"total_score": 95}', UNIX_TIMESTAMP()-1400),
(6, 'WITHDRAW_FREQUENCY', '提现频率异常', 'withdraw', 2, 8.00, 5.00, 30, 'block', 86400, '172.16.0.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"withdraw_count": 8}', UNIX_TIMESTAMP()-1300),
(8, 'TASK_BATCH_COMPLETE', '任务批量刷单检测', 'task', 3, 65.00, 50.00, 40, 'freeze', 604800, '192.168.2.200', 'Mozilla/5.0 (Linux; Android 10)', '{"task_count": 65, "time_span": 3600}', UNIX_TIMESTAMP()-1200),
(8, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 2, 3.20, 2.00, 20, 'warn', 0, '192.168.2.200', 'Mozilla/5.0 (Linux; Android 10)', '{"video_id": 1003, "speed": 3.2}', UNIX_TIMESTAMP()-1100),
(10, 'INVITE_FAKE_DETECTION', '邀请作弊检测', 'invite', 3, 5.00, 3.00, 50, 'ban', 0, '10.10.10.10', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', '{"fake_invite_ratio": 0.85}', UNIX_TIMESTAMP()-1000),
(10, 'GLOBAL_IP_ANOMALY', 'IP异常检测', 'global', 3, 1.00, 1.00, 40, 'block', 86400, '10.10.10.10', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', '{"is_proxy": true}', UNIX_TIMESTAMP()-900),
(1, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 1, 2.10, 2.00, 20, 'warn', 0, '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"video_id": 1004, "speed": 2.1}', UNIX_TIMESTAMP()-800),
(5, 'REDPACKET_CLAIM_SPEED', '红包领取异常', 'redpacket', 2, 3.50, 2.00, 20, 'warn', 0, '192.168.3.50', 'Mozilla/5.0 (Android 12; Mobile)', '{"redpacket_id": 3001, "claim_speed": 3.5}', UNIX_TIMESTAMP()-700),
(7, 'TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', 1, 12.00, 10.00, 25, 'warn', 0, '192.168.4.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_5 like Mac OS X)', '{"task_id": 2002, "duration": 12}', UNIX_TIMESTAMP()-600),
(12, 'WITHDRAW_AMOUNT_ANOMALY', '提现金额异常', 'withdraw', 2, 650.00, 500.00, 45, 'freeze', 2592000, '172.20.0.15', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '{"amount": 650}', UNIX_TIMESTAMP()-500),
(11, 'GLOBAL_MULTI_DEVICE', '多设备登录检测', 'global', 2, 6.00, 5.00, 25, 'warn', 0, '192.168.5.200', 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)', '{"device_count": 6}', UNIX_TIMESTAMP()-400);

-- 插入黑名单模拟数据
INSERT INTO `fa_risk_blacklist` (`type`, `value`, `reason`, `risk_level`, `source`, `admin_id`, `admin_name`, `createtime`, `updatetime`) VALUES
('user', '10', '邀请作弊，永久封禁', 'critical', 'auto', NULL, '系统', UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()),
('ip', '10.10.10.10', '代理IP，高风险', 'critical', 'auto', NULL, '系统', UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()),
('ip', '103.25.47.88', '已知恶意IP段', 'high', 'manual', 1, 'admin', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
('device', 'DEVICE_ABC123DEF456', '多账号作弊设备', 'critical', 'auto', NULL, '系统', UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()),
('ip', '45.33.32.156', 'VPN出口IP', 'high', 'manual', 1, 'admin', UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
('device', 'DEVICE_XYZ789UVW012', '模拟器设备', 'high', 'auto', NULL, '系统', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
('user', '15', '多次违规，严重作弊', 'critical', 'manual', 1, 'admin', UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()),
('ip', '185.220.101.0', 'Tor出口节点', 'critical', 'auto', NULL, '系统', UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP());

-- 插入白名单模拟数据
INSERT INTO `fa_risk_whitelist` (`type`, `value`, `reason`, `expire_time`, `admin_id`, `admin_name`, `createtime`, `updatetime`) VALUES
('user', '1', '内部测试账号', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
('ip', '192.168.1.1', '公司内网IP', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
('ip', '10.0.0.1', '办公室IP', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
('user', '20', 'VIP用户，临时豁免', UNIX_TIMESTAMP()+86400*30, 1, 'admin', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
('device', 'DEVICE_TEST001', '测试设备', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
('ip', '172.16.0.1', '服务器IP', NULL, 1, 'admin', UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP());

-- 插入设备指纹模拟数据
INSERT INTO `fa_device_fingerprint` (`user_id`, `device_id`, `device_type`, `os_type`, `os_version`, `app_version`, `account_ids`, `risk_level`, `last_active_time`, `createtime`, `updatetime`) VALUES
(1, 'DEV_001_IPHONE12', 'iPhone 12', 'iOS', '14.0', '2.1.0', '[1]', 'safe', UNIX_TIMESTAMP()-300, UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
(2, 'DEV_002_HUAWEI_P40', 'HUAWEI P40', 'Android', '11', '2.1.0', '[2, 16]', 'high', UNIX_TIMESTAMP()-1800, UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
(3, 'DEV_003_XIAOMI12', 'Xiaomi 12', 'Android', '12', '2.1.0', '[3]', 'safe', UNIX_TIMESTAMP()-600, UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
(4, 'DEV_004_IPHONE13', 'iPhone 13', 'iOS', '15.0', '2.1.0', '[4, 17, 18]', 'critical', UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()),
(5, 'DEV_005_OPPOX3', 'OPPO Find X3', 'Android', '11', '2.1.0', '[5]', 'low', UNIX_TIMESTAMP()-1200, UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()),
(6, 'DEV_006_EMULATOR', 'Android Emulator', 'Android', '10', '2.1.0', '[6, 19, 20, 21]', 'critical', UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP()),
(7, 'DEV_007_VIVOX60', 'vivo X60', 'Android', '11', '2.1.0', '[7]', 'safe', UNIX_TIMESTAMP()-900, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
(8, 'DEV_008_SAMSUNG_S21', 'Samsung S21', 'Android', '12', '2.1.0', '[8]', 'high', UNIX_TIMESTAMP()-2400, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
(10, 'DEV_010_IPAD', 'iPad Pro', 'iOS', '15.0', '2.1.0', '[10, 22, 23]', 'critical', UNIX_TIMESTAMP()-4800, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP());

-- 插入用户行为统计模拟数据（最近7天）
INSERT INTO `fa_user_behavior_stat` (`user_id`, `stat_date`, `video_watch_count`, `video_watch_duration`, `video_reward_count`, `task_complete_count`, `task_complete_duration`, `withdraw_count`, `withdraw_amount`, `redpacket_count`, `redpacket_amount`, `invite_count`, `createtime`, `updatetime`) VALUES
(1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 25, 3600, 20, 8, 2400, 1, 50.00, 5, 15.00, 0, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 30, 4200, 25, 10, 3000, 0, 0.00, 8, 25.00, 1, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 28, 4000, 22, 12, 3600, 1, 80.00, 6, 18.00, 0, UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()-86400*4),
(2, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 85, 4500, 80, 45, 1800, 5, 250.00, 35, 120.00, 3, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 120, 6000, 115, 68, 2400, 8, 450.00, 52, 180.00, 5, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 95, 5000, 90, 55, 2100, 6, 320.00, 42, 150.00, 2, UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()-86400*4),
(4, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 200, 3000, 195, 120, 1200, 15, 800.00, 80, 350.00, 8, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(4, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 250, 3500, 245, 150, 1500, 18, 950.00, 95, 420.00, 12, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(6, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 180, 2800, 175, 100, 1000, 12, 600.00, 65, 280.00, 15, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(6, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 220, 3200, 215, 130, 1300, 16, 750.00, 78, 350.00, 20, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(8, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 150, 4000, 140, 80, 1600, 8, 400.00, 45, 200.00, 4, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(8, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 180, 4800, 170, 95, 1900, 10, 520.00, 55, 250.00, 6, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(10, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 300, 4500, 290, 200, 2000, 25, 1200.00, 120, 500.00, 30, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(10, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 350, 5000, 340, 250, 2500, 30, 1500.00, 150, 650.00, 35, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5);
