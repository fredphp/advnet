-- 风控系统模拟数据
-- 表前缀: advn_
-- 根据现有用户表数据插入风控相关模拟数据

SET NAMES utf8mb4;

-- =====================================================
-- 1. 用户风险评分模拟数据
-- =====================================================
INSERT INTO `advn_user_risk_score` (`user_id`, `total_score`, `risk_level`, `video_score`, `task_score`, `withdraw_score`, `redpacket_score`, `invite_score`, `global_score`, `violation_count`, `last_violation_time`, `ban_expire_time`, `freeze_expire_time`, `status`, `score_history`, `createtime`, `updatetime`) VALUES
(1, 0, 'safe', 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
(2, 25, 'low', 8, 5, 4, 5, 3, 0, 5, UNIX_TIMESTAMP()-3600, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
(3, 0, 'safe', 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
(4, 180, 'dangerous', 65, 50, 35, 20, 10, 0, 28, UNIX_TIMESTAMP()-1800, UNIX_TIMESTAMP()+86400*7, NULL, 'banned', '[]', UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()),
(5, 65, 'medium', 20, 15, 12, 10, 8, 0, 12, UNIX_TIMESTAMP()-7200, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()),
(6, 280, 'dangerous', 100, 80, 50, 30, 20, 0, 45, UNIX_TIMESTAMP()-900, NULL, UNIX_TIMESTAMP()+86400*3, 'frozen', '[]', UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP()),
(7, 15, 'safe', 5, 3, 2, 3, 2, 0, 3, UNIX_TIMESTAMP()-86400*2, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
(8, 95, 'high', 35, 25, 18, 10, 7, 0, 18, UNIX_TIMESTAMP()-1200, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
(9, 5, 'safe', 2, 1, 1, 1, 0, 0, 1, UNIX_TIMESTAMP()-86400*3, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()),
(10, 320, 'dangerous', 120, 90, 60, 35, 15, 0, 52, UNIX_TIMESTAMP()-600, UNIX_TIMESTAMP()+86400*30, NULL, 'banned', '[]', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
(11, 45, 'medium', 15, 10, 8, 6, 6, 0, 8, UNIX_TIMESTAMP()-14400, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()),
(12, 85, 'high', 30, 22, 15, 10, 8, 0, 15, UNIX_TIMESTAMP()-5400, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()),
(13, 120, 'high', 45, 35, 22, 12, 6, 0, 22, UNIX_TIMESTAMP()-3600, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()),
(14, 55, 'medium', 18, 14, 10, 8, 5, 0, 10, UNIX_TIMESTAMP()-10800, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*9, UNIX_TIMESTAMP()),
(15, 35, 'low', 12, 8, 6, 5, 4, 0, 6, UNIX_TIMESTAMP()-21600, NULL, NULL, 'normal', '[]', UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP());

-- =====================================================
-- 2. 封禁记录模拟数据
-- =====================================================
INSERT INTO `advn_ban_record` (`user_id`, `ban_type`, `ban_reason`, `ban_source`, `risk_score`, `rule_codes`, `admin_id`, `admin_name`, `start_time`, `end_time`, `duration`, `status`, `release_time`, `release_reason`, `release_admin_id`, `createtime`, `updatetime`) VALUES
(4, 'temporary', '触发视频观看速度异常规则，疑似作弊行为', 'auto', 180, '["VIDEO_WATCH_SPEED","VIDEO_REWARD_SPEED"]', NULL, '系统', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()+86400*6, 604800, 'active', NULL, '', NULL, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
(6, 'temporary', '触发任务完成速度异常规则，行为模式异常', 'auto', 280, '["TASK_COMPLETE_SPEED","TASK_FAKE_BEHAVIOR","BEHAVIOR_PATTERN"]', NULL, '系统', UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()+86400*3, 259200, 'active', NULL, '', NULL, UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()),
(10, 'permanent', '触发邀请虚假账户规则，严重违规', 'auto', 320, '["INVITE_FAKE_ACCOUNT","DEVICE_MULTI_ACCOUNT"]', NULL, '系统', UNIX_TIMESTAMP()-7200, NULL, 0, 'active', NULL, '', NULL, UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()),
(2, 'temporary', '疑似批量刷单行为', 'manual', 25, '["TASK_COMPLETE_SPEED"]', 1, 'admin', UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()-86400*2, 86400, 'expired', NULL, '', NULL, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
(8, 'temporary', '触发提现频率异常规则', 'auto', 95, '["WITHDRAW_FREQUENCY"]', NULL, '系统', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*4, 86400, 'released', UNIX_TIMESTAMP()-86400*4+3600, '经核实为正常操作', 1, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
(12, 'temporary', '触发视频重复观看检测规则', 'auto', 85, '["VIDEO_WATCH_REPEAT","VIDEO_WATCH_SPEED"]', NULL, '系统', UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()-86400*5, 172800, 'expired', NULL, '', NULL, UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()),
(5, 'temporary', '多次触发红包领取异常规则', 'auto', 65, '["REDPACKET_GRAB_SPEED","REDPACKET_DAILY_LIMIT"]', NULL, '系统', UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()-86400, 86400, 'expired', NULL, '', NULL, UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()),
(13, 'temporary', '新账户大额提现异常', 'auto', 120, '["WITHDRAW_NEW_ACCOUNT","WITHDRAW_AMOUNT_ANOMALY"]', NULL, '系统', UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()-86400*3, 86400, 'expired', NULL, '', NULL, UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP());

-- =====================================================
-- 3. 风险日志模拟数据
-- =====================================================
INSERT INTO `advn_risk_log` (`user_id`, `rule_code`, `rule_name`, `rule_type`, `risk_level`, `trigger_value`, `threshold`, `score_add`, `action`, `action_duration`, `action_expire_time`, `device_id`, `ip`, `user_agent`, `request_data`, `response_data`, `createtime`) VALUES
(2, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 2, 3.50, 3.00, 30, 'block', 3600, UNIX_TIMESTAMP()+3600, 'device001', '192.168.1.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', '{"video_id": 1001, "speed": 3.5}', '{"blocked": true}', UNIX_TIMESTAMP()-1800),
(2, 'TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', 2, 8.00, 5.00, 35, 'block', 1800, UNIX_TIMESTAMP()+1800, 'device001', '192.168.1.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', '{"task_id": 2001, "duration": 8}', '{"blocked": true}', UNIX_TIMESTAMP()-1700),
(4, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 3, 5.20, 3.00, 30, 'block', 3600, UNIX_TIMESTAMP()+3600, 'device003', '10.0.0.55', 'Mozilla/5.0 (Android 11; Mobile)', '{"video_id": 1002, "speed": 5.2}', '{"blocked": true}', UNIX_TIMESTAMP()-1600),
(4, 'VIDEO_REWARD_SPEED', '金币获取速度异常', 'video', 3, 15000.00, 10000.00, 50, 'freeze', 7200, UNIX_TIMESTAMP()+7200, 'device003', '10.0.0.55', 'Mozilla/5.0 (Android 11; Mobile)', '{"coin_per_minute": 15000}', '{"frozen": true}', UNIX_TIMESTAMP()-1500),
(4, 'VIDEO_WATCH_REPEAT', '重复观看同一视频', 'video', 1, 8.00, 5.00, 20, 'warn', 0, NULL, 'device003', '10.0.0.55', 'Mozilla/5.0 (Android 11; Mobile)', '{"video_id": 1002, "repeat_count": 8}', '{"warned": true}', UNIX_TIMESTAMP()-1400),
(6, 'TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', 2, 2.00, 5.00, 35, 'block', 1800, UNIX_TIMESTAMP()+1800, 'device005', '172.16.0.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"task_id": 2002, "duration": 2}', '{"blocked": true}', UNIX_TIMESTAMP()-1300),
(6, 'TASK_FAKE_BEHAVIOR', '任务行为异常', 'task', 3, 1.00, 1.00, 80, 'freeze', 86400, UNIX_TIMESTAMP()+86400, 'device005', '172.16.0.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"fake_behavior_detected": true}', '{"frozen": true}', UNIX_TIMESTAMP()-1200),
(6, 'BEHAVIOR_PATTERN', '行为模式异常', 'global', 3, 0.85, 0.80, 70, 'freeze', 259200, UNIX_TIMESTAMP()+259200, 'device005', '172.16.0.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"pattern_score": 0.85}', '{"frozen": true}', UNIX_TIMESTAMP()-1100),
(8, 'TASK_DAILY_LIMIT', '任务完成次数超限', 'task', 2, 120.00, 100.00, 30, 'block', 86400, UNIX_TIMESTAMP()+86400, 'device007', '192.168.2.200', 'Mozilla/5.0 (Linux; Android 10)', '{"daily_count": 120}', '{"blocked": true}', UNIX_TIMESTAMP()-1000),
(8, 'VIDEO_DAILY_LIMIT', '视频观看次数超限', 'video', 3, 650.00, 500.00, 40, 'block', 86400, UNIX_TIMESTAMP()+86400, 'device007', '192.168.2.200', 'Mozilla/5.0 (Linux; Android 10)', '{"daily_count": 650}', '{"blocked": true}', UNIX_TIMESTAMP()-900),
(10, 'INVITE_FAKE_ACCOUNT', '邀请虚假账户', 'invite', 3, 5.00, 3.00, 80, 'ban', 604800, NULL, 'device010', '10.10.10.10', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', '{"fake_invite_ratio": 0.85}', '{"banned": true}', UNIX_TIMESTAMP()-800),
(10, 'DEVICE_MULTI_ACCOUNT', '设备多账户关联', 'global', 3, 8.00, 3.00, 60, 'freeze', 172800, UNIX_TIMESTAMP()+172800, 'device010', '10.10.10.10', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', '{"account_count": 8}', '{"frozen": true}', UNIX_TIMESTAMP()-700),
(1, 'VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', 1, 3.10, 3.00, 30, 'warn', 0, NULL, 'device001', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '{"video_id": 1003, "speed": 3.1}', '{"warned": true}', UNIX_TIMESTAMP()-600),
(5, 'REDPACKET_GRAB_SPEED', '抢红包速度异常', 'redpacket', 3, 0.30, 0.50, 55, 'block', 3600, UNIX_TIMESTAMP()+3600, 'device004', '192.168.3.50', 'Mozilla/5.0 (Android 12; Mobile)', '{"grab_speed": 0.3}', '{"blocked": true}', UNIX_TIMESTAMP()-500),
(5, 'REDPACKET_DAILY_LIMIT', '抢红包次数超限', 'redpacket', 2, 68.00, 50.00, 35, 'block', 86400, UNIX_TIMESTAMP()+86400, 'device004', '192.168.3.50', 'Mozilla/5.0 (Android 12; Mobile)', '{"daily_count": 68}', '{"blocked": true}', UNIX_TIMESTAMP()-400),
(7, 'TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', 1, 5.50, 5.00, 35, 'warn', 0, NULL, 'device006', '192.168.4.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_5 like Mac OS X)', '{"task_id": 2003, "duration": 5.5}', '{"warned": true}', UNIX_TIMESTAMP()-300),
(12, 'WITHDRAW_FREQUENCY', '提现频率异常', 'withdraw', 2, 5.00, 3.00, 45, 'freeze', 86400, UNIX_TIMESTAMP()+86400, 'device012', '172.20.0.15', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', '{"withdraw_count": 5}', '{"frozen": true}', UNIX_TIMESTAMP()-200),
(11, 'IP_MULTI_ACCOUNT', 'IP多账户关联', 'global', 2, 6.00, 5.00, 50, 'warn', 0, NULL, 'device011', '192.168.5.200', 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)', '{"account_count": 6}', '{"warned": true}', UNIX_TIMESTAMP()-100),
(13, 'WITHDRAW_NEW_ACCOUNT', '新账户大额提现', 'withdraw', 3, 50.00, 10.00, 50, 'freeze', 259200, UNIX_TIMESTAMP()+259200, 'device013', '192.168.6.100', 'Mozilla/5.0 (Android 11; Mobile)', '{"withdraw_amount": 50}', '{"frozen": true}', UNIX_TIMESTAMP()-50),
(14, 'VIDEO_SKIP_RATIO', '视频跳过率过高', 'video', 2, 0.92, 0.90, 25, 'warn', 0, NULL, 'device014', '192.168.7.50', 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', '{"skip_ratio": 0.92}', '{"warned": true}', UNIX_TIMESTAMP());

-- =====================================================
-- 4. 黑名单模拟数据
-- =====================================================
INSERT INTO `advn_risk_blacklist` (`type`, `value`, `reason`, `source`, `risk_score`, `expire_time`, `admin_id`, `admin_name`, `enabled`, `createtime`, `updatetime`) VALUES
('user', '10', '邀请作弊，永久封禁', 'auto', 320, NULL, NULL, '系统', 1, UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()),
('ip', '10.10.10.10', '代理IP，高风险', 'auto', 280, NULL, NULL, '系统', 1, UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()),
('ip', '103.25.47.88', '已知恶意IP段', 'manual', 200, NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
('device', 'DEVICE_ABC123DEF456', '多账号作弊设备', 'auto', 250, NULL, NULL, '系统', 1, UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()),
('ip', '45.33.32.156', 'VPN出口IP', 'manual', 180, NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
('device', 'DEVICE_XYZ789UVW012', '模拟器设备', 'auto', 220, NULL, NULL, '系统', 1, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
('user', '6', '多次违规，严重作弊', 'manual', 280, NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()),
('ip', '185.220.101.0', 'Tor出口节点', 'auto', 300, NULL, NULL, '系统', 1, UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()),
('ip', '192.168.100.50', '测试环境IP', 'manual', 50, UNIX_TIMESTAMP()+86400*7, 1, 'admin', 1, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP());

-- =====================================================
-- 5. 白名单模拟数据
-- =====================================================
INSERT INTO `advn_risk_whitelist` (`type`, `value`, `reason`, `expire_time`, `admin_id`, `admin_name`, `enabled`, `createtime`, `updatetime`) VALUES
('user', '1', '内部测试账号', NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
('ip', '192.168.1.1', '公司内网IP', NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
('ip', '10.0.0.1', '办公室IP', NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
('user', '3', 'VIP用户，临时豁免', UNIX_TIMESTAMP()+86400*30, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
('device', 'DEVICE_TEST001', '测试设备', NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
('ip', '172.16.0.1', '服务器IP', NULL, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()),
('user', '9', '高信誉用户', UNIX_TIMESTAMP()+86400*60, 1, 'admin', 1, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP());

-- =====================================================
-- 6. 设备指纹模拟数据
-- =====================================================
INSERT INTO `advn_device_fingerprint` (`user_id`, `device_id`, `device_type`, `os_type`, `os_version`, `app_version`, `account_ids`, `risk_level`, `last_active_time`, `createtime`, `updatetime`) VALUES
(1, 'DEV_001_IPHONE12', 'iPhone 12', 'iOS', '14.0', '2.1.0', '[1]', 'safe', UNIX_TIMESTAMP()-300, UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()),
(2, 'DEV_002_HUAWEI_P40', 'HUAWEI P40', 'Android', '11', '2.1.0', '[2, 1001]', 'low', UNIX_TIMESTAMP()-1800, UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()),
(3, 'DEV_003_XIAOMI12', 'Xiaomi 12', 'Android', '12', '2.1.0', '[3]', 'safe', UNIX_TIMESTAMP()-600, UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()),
(4, 'DEV_004_IPHONE13', 'iPhone 13', 'iOS', '15.0', '2.1.0', '[4, 1002, 1003]', 'dangerous', UNIX_TIMESTAMP()-7200, UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()),
(5, 'DEV_005_OPPOX3', 'OPPO Find X3', 'Android', '11', '2.1.0', '[5]', 'medium', UNIX_TIMESTAMP()-1200, UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()),
(6, 'DEV_006_EMULATOR', 'Android Emulator', 'Android', '10', '2.1.0', '[6, 1004, 1005, 1006]', 'dangerous', UNIX_TIMESTAMP()-3600, UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP()),
(7, 'DEV_007_VIVOX60', 'vivo X60', 'Android', '11', '2.1.0', '[7]', 'safe', UNIX_TIMESTAMP()-900, UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()),
(8, 'DEV_008_SAMSUNG_S21', 'Samsung S21', 'Android', '12', '2.1.0', '[8]', 'high', UNIX_TIMESTAMP()-2400, UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()),
(10, 'DEV_010_IPAD', 'iPad Pro', 'iOS', '15.0', '2.1.0', '[10, 1007, 1008]', 'dangerous', UNIX_TIMESTAMP()-4800, UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()),
(11, 'DEV_011_HONOR', 'Honor 50', 'Android', '11', '2.1.0', '[11]', 'medium', UNIX_TIMESTAMP()-1500, UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()),
(12, 'DEV_012_ONEPLUS', 'OnePlus 9', 'Android', '12', '2.1.0', '[12]', 'high', UNIX_TIMESTAMP()-2100, UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()),
(13, 'DEV_013_REALME', 'realme GT', 'Android', '11', '2.1.0', '[13]', 'high', UNIX_TIMESTAMP()-1800, UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP());

-- =====================================================
-- 7. 用户行为统计模拟数据（最近7天）
-- =====================================================
INSERT INTO `advn_user_behavior_stat` (`user_id`, `stat_date`, `video_watch_count`, `video_watch_duration`, `video_reward_count`, `task_complete_count`, `task_complete_duration`, `withdraw_count`, `withdraw_amount`, `redpacket_count`, `redpacket_amount`, `invite_count`, `createtime`, `updatetime`) VALUES
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

-- =====================================================
-- 8. 风控统计模拟数据（最近30天）
-- =====================================================
INSERT INTO `advn_risk_stat` (`stat_date`, `total_requests`, `blocked_requests`, `warn_count`, `block_count`, `freeze_count`, `ban_count`, `unique_ip_count`, `unique_device_count`, `proxy_detected_count`, `emulator_detected_count`, `rule_trigger_stats`, `createtime`, `updatetime`) VALUES
(DATE_SUB(CURDATE(), INTERVAL 30 DAY), 50000, 1200, 85, 45, 12, 3, 8500, 7200, 120, 45, '{"VIDEO_WATCH_SPEED": 35, "TASK_COMPLETE_SPEED": 28}', UNIX_TIMESTAMP()-86400*30, UNIX_TIMESTAMP()-86400*30),
(DATE_SUB(CURDATE(), INTERVAL 29 DAY), 52000, 1350, 92, 52, 15, 4, 8800, 7500, 135, 52, '{"VIDEO_WATCH_SPEED": 38, "TASK_COMPLETE_SPEED": 32}', UNIX_TIMESTAMP()-86400*29, UNIX_TIMESTAMP()-86400*29),
(DATE_SUB(CURDATE(), INTERVAL 28 DAY), 48000, 1100, 78, 40, 10, 2, 8200, 6800, 110, 38, '{"VIDEO_WATCH_SPEED": 30, "TASK_COMPLETE_SPEED": 25}', UNIX_TIMESTAMP()-86400*28, UNIX_TIMESTAMP()-86400*28),
(DATE_SUB(CURDATE(), INTERVAL 27 DAY), 55000, 1500, 105, 62, 18, 5, 9200, 8000, 150, 62, '{"VIDEO_WATCH_SPEED": 42, "TASK_COMPLETE_SPEED": 35, "INVITE_FAKE_ACCOUNT": 3}', UNIX_TIMESTAMP()-86400*27, UNIX_TIMESTAMP()-86400*27),
(DATE_SUB(CURDATE(), INTERVAL 26 DAY), 53000, 1400, 98, 58, 16, 4, 9000, 7800, 142, 55, '{"VIDEO_WATCH_SPEED": 40, "TASK_COMPLETE_SPEED": 30}', UNIX_TIMESTAMP()-86400*26, UNIX_TIMESTAMP()-86400*26),
(DATE_SUB(CURDATE(), INTERVAL 25 DAY), 58000, 1600, 112, 68, 20, 6, 9500, 8200, 165, 70, '{"VIDEO_WATCH_SPEED": 45, "TASK_COMPLETE_SPEED": 38, "WITHDRAW_FREQUENCY": 8}', UNIX_TIMESTAMP()-86400*25, UNIX_TIMESTAMP()-86400*25),
(DATE_SUB(CURDATE(), INTERVAL 24 DAY), 51000, 1300, 88, 50, 14, 3, 8600, 7400, 128, 48, '{"VIDEO_WATCH_SPEED": 36, "TASK_COMPLETE_SPEED": 28}', UNIX_TIMESTAMP()-86400*24, UNIX_TIMESTAMP()-86400*24),
(DATE_SUB(CURDATE(), INTERVAL 23 DAY), 54000, 1450, 102, 60, 17, 5, 9100, 7900, 155, 58, '{"VIDEO_WATCH_SPEED": 41, "TASK_COMPLETE_SPEED": 32, "REDPACKET_GRAB_SPEED": 5}', UNIX_TIMESTAMP()-86400*23, UNIX_TIMESTAMP()-86400*23),
(DATE_SUB(CURDATE(), INTERVAL 22 DAY), 49000, 1150, 80, 42, 11, 3, 8300, 6900, 115, 42, '{"VIDEO_WATCH_SPEED": 32, "TASK_COMPLETE_SPEED": 26}', UNIX_TIMESTAMP()-86400*22, UNIX_TIMESTAMP()-86400*22),
(DATE_SUB(CURDATE(), INTERVAL 21 DAY), 56000, 1550, 108, 65, 19, 5, 9300, 8100, 160, 65, '{"VIDEO_WATCH_SPEED": 44, "TASK_COMPLETE_SPEED": 36, "DEVICE_MULTI_ACCOUNT": 4}', UNIX_TIMESTAMP()-86400*21, UNIX_TIMESTAMP()-86400*21),
(DATE_SUB(CURDATE(), INTERVAL 20 DAY), 52000, 1380, 95, 55, 15, 4, 8800, 7600, 138, 52, '{"VIDEO_WATCH_SPEED": 38, "TASK_COMPLETE_SPEED": 30}', UNIX_TIMESTAMP()-86400*20, UNIX_TIMESTAMP()-86400*20),
(DATE_SUB(CURDATE(), INTERVAL 19 DAY), 57000, 1580, 110, 63, 18, 5, 9400, 8300, 158, 60, '{"VIDEO_WATCH_SPEED": 43, "TASK_COMPLETE_SPEED": 34}', UNIX_TIMESTAMP()-86400*19, UNIX_TIMESTAMP()-86400*19),
(DATE_SUB(CURDATE(), INTERVAL 18 DAY), 50000, 1250, 86, 48, 13, 3, 8500, 7200, 125, 46, '{"VIDEO_WATCH_SPEED": 34, "TASK_COMPLETE_SPEED": 27}', UNIX_TIMESTAMP()-86400*18, UNIX_TIMESTAMP()-86400*18),
(DATE_SUB(CURDATE(), INTERVAL 17 DAY), 53000, 1420, 100, 59, 16, 4, 8900, 7700, 145, 54, '{"VIDEO_WATCH_SPEED": 39, "TASK_COMPLETE_SPEED": 31, "VIDEO_REWARD_SPEED": 4}', UNIX_TIMESTAMP()-86400*17, UNIX_TIMESTAMP()-86400*17),
(DATE_SUB(CURDATE(), INTERVAL 16 DAY), 55000, 1520, 106, 64, 18, 5, 9200, 8000, 152, 58, '{"VIDEO_WATCH_SPEED": 42, "TASK_COMPLETE_SPEED": 33}', UNIX_TIMESTAMP()-86400*16, UNIX_TIMESTAMP()-86400*16),
(DATE_SUB(CURDATE(), INTERVAL 15 DAY), 48000, 1100, 76, 38, 10, 2, 8100, 6700, 108, 36, '{"VIDEO_WATCH_SPEED": 29, "TASK_COMPLETE_SPEED": 23}', UNIX_TIMESTAMP()-86400*15, UNIX_TIMESTAMP()-86400*15),
(DATE_SUB(CURDATE(), INTERVAL 14 DAY), 54000, 1480, 104, 62, 17, 4, 9100, 7800, 148, 56, '{"VIDEO_WATCH_SPEED": 41, "TASK_COMPLETE_SPEED": 32}', UNIX_TIMESTAMP()-86400*14, UNIX_TIMESTAMP()-86400*14),
(DATE_SUB(CURDATE(), INTERVAL 13 DAY), 56000, 1560, 109, 66, 19, 5, 9300, 8100, 156, 62, '{"VIDEO_WATCH_SPEED": 43, "TASK_COMPLETE_SPEED": 35, "BEHAVIOR_PATTERN": 3}', UNIX_TIMESTAMP()-86400*13, UNIX_TIMESTAMP()-86400*13),
(DATE_SUB(CURDATE(), INTERVAL 12 DAY), 51000, 1320, 90, 52, 14, 3, 8700, 7500, 132, 50, '{"VIDEO_WATCH_SPEED": 36, "TASK_COMPLETE_SPEED": 29}', UNIX_TIMESTAMP()-86400*12, UNIX_TIMESTAMP()-86400*12),
(DATE_SUB(CURDATE(), INTERVAL 11 DAY), 53000, 1440, 101, 60, 16, 4, 8900, 7700, 144, 54, '{"VIDEO_WATCH_SPEED": 40, "TASK_COMPLETE_SPEED": 31}', UNIX_TIMESTAMP()-86400*11, UNIX_TIMESTAMP()-86400*11),
(DATE_SUB(CURDATE(), INTERVAL 10 DAY), 57000, 1590, 111, 67, 19, 5, 9500, 8300, 160, 64, '{"VIDEO_WATCH_SPEED": 44, "TASK_COMPLETE_SPEED": 36, "INVITE_FAKE_ACCOUNT": 2}', UNIX_TIMESTAMP()-86400*10, UNIX_TIMESTAMP()-86400*10),
(DATE_SUB(CURDATE(), INTERVAL 9 DAY), 49000, 1180, 82, 44, 12, 3, 8400, 7000, 118, 44, '{"VIDEO_WATCH_SPEED": 33, "TASK_COMPLETE_SPEED": 26}', UNIX_TIMESTAMP()-86400*9, UNIX_TIMESTAMP()-86400*9),
(DATE_SUB(CURDATE(), INTERVAL 8 DAY), 55000, 1500, 105, 63, 18, 4, 9200, 8000, 150, 60, '{"VIDEO_WATCH_SPEED": 42, "TASK_COMPLETE_SPEED": 34}', UNIX_TIMESTAMP()-86400*8, UNIX_TIMESTAMP()-86400*8),
(DATE_SUB(CURDATE(), INTERVAL 7 DAY), 58000, 1620, 113, 68, 20, 5, 9600, 8400, 165, 68, '{"VIDEO_WATCH_SPEED": 45, "TASK_COMPLETE_SPEED": 37, "WITHDRAW_FREQUENCY": 6}', UNIX_TIMESTAMP()-86400*7, UNIX_TIMESTAMP()-86400*7),
(DATE_SUB(CURDATE(), INTERVAL 6 DAY), 52000, 1380, 96, 56, 15, 4, 8800, 7600, 138, 52, '{"VIDEO_WATCH_SPEED": 38, "TASK_COMPLETE_SPEED": 30}', UNIX_TIMESTAMP()-86400*6, UNIX_TIMESTAMP()-86400*6),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 54000, 1460, 102, 61, 17, 4, 9100, 7900, 146, 56, '{"VIDEO_WATCH_SPEED": 40, "TASK_COMPLETE_SPEED": 32}', UNIX_TIMESTAMP()-86400*5, UNIX_TIMESTAMP()-86400*5),
(DATE_SUB(CURDATE(), INTERVAL 4 DAY), 56000, 1540, 108, 65, 18, 5, 9400, 8200, 155, 62, '{"VIDEO_WATCH_SPEED": 43, "TASK_COMPLETE_SPEED": 35}', UNIX_TIMESTAMP()-86400*4, UNIX_TIMESTAMP()-86400*4),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 51000, 1340, 93, 54, 14, 3, 8600, 7400, 134, 50, '{"VIDEO_WATCH_SPEED": 37, "TASK_COMPLETE_SPEED": 29}', UNIX_TIMESTAMP()-86400*3, UNIX_TIMESTAMP()-86400*3),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 53000, 1430, 100, 59, 16, 4, 8900, 7700, 143, 54, '{"VIDEO_WATCH_SPEED": 40, "TASK_COMPLETE_SPEED": 31}', UNIX_TIMESTAMP()-86400*2, UNIX_TIMESTAMP()-86400*2),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 55000, 1510, 106, 64, 18, 4, 9200, 8000, 152, 58, '{"VIDEO_WATCH_SPEED": 42, "TASK_COMPLETE_SPEED": 33}', UNIX_TIMESTAMP()-86400, UNIX_TIMESTAMP()-86400);
