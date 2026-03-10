-- ============================================================================
-- 为任务参与记录表生成模拟数据
-- ============================================================================

SET NAMES utf8mb4;

-- 首先确保有一些红包任务数据（如果不存在则创建）
INSERT IGNORE INTO `advn_red_packet_task` (`id`, `name`, `description`, `type`, `status`, `push_status`, `resource_id`, `createtime`, `updatetime`) VALUES
(1, '新春红包大派送', '春节特别活动，点击领取红包', 'miniapp', 'normal', 1, NULL, UNIX_TIMESTAMP() - 86400*7, UNIX_TIMESTAMP()),
(2, '下载App领红包', '下载指定App即可领取红包奖励', 'download', 'normal', 1, NULL, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP()),
(3, '观看视频赚金币', '观看完整视频即可获得奖励', 'video', 'normal', 1, NULL, UNIX_TIMESTAMP() - 86400*3, UNIX_TIMESTAMP()),
(4, '每日签到红包', '每日签到领取专属红包', 'chat', 'normal', 1, NULL, UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP()),
(5, '小游戏红包雨', '玩游戏即可领取红包奖励', 'miniapp', 'normal', 1, NULL, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP());

-- 确保有测试用户数据
INSERT IGNORE INTO `advn_user` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `mobile`, `status`, `createtime`, `updatetime`) VALUES
(1001, 'test001', '测试用户001', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/001.png', '13800138001', 'normal', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(1002, 'test002', '测试用户002', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/002.png', '13800138002', 'normal', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(1003, 'test003', '测试用户003', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/003.png', '13800138003', 'normal', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),
(1004, 'test004', '测试用户004', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/004.png', '13800138004', 'normal', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP()),
(1005, 'test005', '测试用户005', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/005.png', '13800138005', 'normal', UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP());

-- 生成任务参与记录模拟数据
INSERT INTO `advn_task_participation` 
(`task_id`, `user_id`, `order_no`, `task_type`, `task_name`, `status`, `start_time`, `end_time`, `duration`, `progress`, `audit_type`, `audit_status`, `audit_time`, `reward_coin`, `reward_status`, `reward_time`, `ip`, `platform`, `createtime`, `updatetime`) VALUES
-- 已完成的记录
(1, 1001, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0001'), 'miniapp', '新春红包大派送', 3, UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 7000, 200, 100, 'auto', 1, UNIX_TIMESTAMP() - 7000, 5280.00, 1, UNIX_TIMESTAMP() - 7000, '192.168.1.101', 'android', UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 7000),
(1, 1002, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0002'), 'miniapp', '新春红包大派送', 3, UNIX_TIMESTAMP() - 6800, UNIX_TIMESTAMP() - 6500, 300, 100, 'auto', 1, UNIX_TIMESTAMP() - 6500, 4560.00, 1, UNIX_TIMESTAMP() - 6500, '192.168.1.102', 'ios', UNIX_TIMESTAMP() - 6800, UNIX_TIMESTAMP() - 6500),
(2, 1001, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0003'), 'download', '下载App领红包', 3, UNIX_TIMESTAMP() - 5000, UNIX_TIMESTAMP() - 4000, 1000, 100, 'auto', 1, UNIX_TIMESTAMP() - 4000, 3200.00, 1, UNIX_TIMESTAMP() - 4000, '192.168.1.101', 'android', UNIX_TIMESTAMP() - 5000, UNIX_TIMESTAMP() - 4000),
(2, 1003, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0004'), 'download', '下载App领红包', 3, UNIX_TIMESTAMP() - 4800, UNIX_TIMESTAMP() - 3800, 1000, 100, 'auto', 1, UNIX_TIMESTAMP() - 3800, 3150.00, 1, UNIX_TIMESTAMP() - 3800, '192.168.1.103', 'android', UNIX_TIMESTAMP() - 4800, UNIX_TIMESTAMP() - 3800),
(3, 1002, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0005'), 'video', '观看视频赚金币', 3, UNIX_TIMESTAMP() - 3000, UNIX_TIMESTAMP() - 2900, 100, 100, 'auto', 1, UNIX_TIMESTAMP() - 2900, 2100.00, 1, UNIX_TIMESTAMP() - 2900, '192.168.1.102', 'ios', UNIX_TIMESTAMP() - 3000, UNIX_TIMESTAMP() - 2900),
(3, 1004, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0006'), 'video', '观看视频赚金币', 3, UNIX_TIMESTAMP() - 2800, UNIX_TIMESTAMP() - 2700, 100, 100, 'auto', 1, UNIX_TIMESTAMP() - 2700, 2350.00, 1, UNIX_TIMESTAMP() - 2700, '192.168.1.104', 'h5', UNIX_TIMESTAMP() - 2800, UNIX_TIMESTAMP() - 2700),

-- 待审核的记录
(4, 1003, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0007'), 'chat', '每日签到红包', 1, UNIX_TIMESTAMP() - 1500, UNIX_TIMESTAMP() - 1400, 100, 100, 'manual', 0, NULL, 0.00, 0, NULL, '192.168.1.103', 'android', UNIX_TIMESTAMP() - 1500, UNIX_TIMESTAMP() - 1400),
(4, 1005, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0008'), 'chat', '每日签到红包', 1, UNIX_TIMESTAMP() - 1400, UNIX_TIMESTAMP() - 1300, 100, 100, 'manual', 0, NULL, 0.00, 0, NULL, '192.168.1.105', 'ios', UNIX_TIMESTAMP() - 1400, UNIX_TIMESTAMP() - 1300),
(5, 1001, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0009'), 'miniapp', '小游戏红包雨', 1, UNIX_TIMESTAMP() - 1000, UNIX_TIMESTAMP() - 900, 100, 100, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.101', 'android', UNIX_TIMESTAMP() - 1000, UNIX_TIMESTAMP() - 900),

-- 审核通过的记录
(5, 1002, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0010'), 'miniapp', '小游戏红包雨', 2, UNIX_TIMESTAMP() - 800, UNIX_TIMESTAMP() - 700, 100, 100, 'auto', 1, UNIX_TIMESTAMP() - 600, 4800.00, 0, NULL, '192.168.1.102', 'ios', UNIX_TIMESTAMP() - 800, UNIX_TIMESTAMP() - 600),
(5, 1004, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0011'), 'miniapp', '小游戏红包雨', 2, UNIX_TIMESTAMP() - 700, UNIX_TIMESTAMP() - 600, 100, 100, 'auto', 1, UNIX_TIMESTAMP() - 500, 3950.00, 0, NULL, '192.168.1.104', 'android', UNIX_TIMESTAMP() - 700, UNIX_TIMESTAMP() - 500),

-- 审核拒绝的记录
(1, 1003, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0012'), 'miniapp', '新春红包大派送', 4, UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP() - 500, 100, 80, 'manual', 2, UNIX_TIMESTAMP() - 400, 0.00, 0, NULL, '192.168.1.103', 'android', UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP() - 400),
(2, 1005, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0013'), 'download', '下载App领红包', 4, UNIX_TIMESTAMP() - 500, UNIX_TIMESTAMP() - 400, 100, 50, 'manual', 2, UNIX_TIMESTAMP() - 300, 0.00, 0, NULL, '192.168.1.105', 'ios', UNIX_TIMESTAMP() - 500, UNIX_TIMESTAMP() - 300),

-- 进行中的记录
(1, 1004, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0014'), 'miniapp', '新春红包大派送', 0, UNIX_TIMESTAMP() - 200, NULL, 0, 30, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.104', 'android', UNIX_TIMESTAMP() - 200, UNIX_TIMESTAMP() - 200),
(2, 1002, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0015'), 'download', '下载App领红包', 0, UNIX_TIMESTAMP() - 100, NULL, 0, 10, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.102', 'ios', UNIX_TIMESTAMP() - 100, UNIX_TIMESTAMP() - 100),
(3, 1001, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0016'), 'video', '观看视频赚金币', 0, UNIX_TIMESTAMP() - 50, NULL, 0, 60, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.101', 'android', UNIX_TIMESTAMP() - 50, UNIX_TIMESTAMP() - 50),

-- 已过期的记录
(4, 1001, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0017'), 'chat', '每日签到红包', 5, UNIX_TIMESTAMP() - 172900, NULL, 0, 0, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.101', 'android', UNIX_TIMESTAMP() - 172900, UNIX_TIMESTAMP() - 86400),
(5, 1003, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0018'), 'miniapp', '小游戏红包雨', 5, UNIX_TIMESTAMP() - 172800, NULL, 0, 20, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.103', 'ios', UNIX_TIMESTAMP() - 172800, UNIX_TIMESTAMP() - 86400),

-- 已取消的记录
(1, 1005, CONCAT('TP', DATE_FORMAT(NOW(), '%Y%m%d'), '0019'), 'miniapp', '新春红包大派送', 6, UNIX_TIMESTAMP() - 300, NULL, 0, 5, 'auto', 0, NULL, 0.00, 0, NULL, '192.168.1.105', 'h5', UNIX_TIMESTAMP() - 300, UNIX_TIMESTAMP() - 250);

-- 输出统计
SELECT '模拟数据插入完成' AS message;
SELECT COUNT(*) AS total_participations FROM `advn_task_participation`;
SELECT 
    status,
    CASE status 
        WHEN 0 THEN '已领取待完成'
        WHEN 1 THEN '已完成待审核'
        WHEN 2 THEN '审核通过待发放'
        WHEN 3 THEN '已发放'
        WHEN 4 THEN '审核拒绝'
        WHEN 5 THEN '已过期'
        WHEN 6 THEN '已取消'
    END AS status_text,
    COUNT(*) AS count 
FROM `advn_task_participation` 
GROUP BY status;
