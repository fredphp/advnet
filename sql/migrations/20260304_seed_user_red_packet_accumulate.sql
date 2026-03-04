-- ============================================================================
-- 红包领取记录模拟数据
-- 基于新的单人抢红包模式
-- ============================================================================

SET NAMES utf8mb4;

-- 首先确保有一些红包任务数据（如果不存在则创建）
INSERT IGNORE INTO `advn_red_packet_task` (`id`, `name`, `description`, `type`, `status`, `push_status`, `createtime`, `updatetime`) VALUES
(1, '新春红包大派送', '春节特别活动，点击领取红包', 'miniapp', 'finished', 1, UNIX_TIMESTAMP() - 86400*7, UNIX_TIMESTAMP()),
(2, '下载App领红包', '下载指定App即可领取红包奖励', 'download', 'finished', 1, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP()),
(3, '观看视频赚金币', '观看完整视频即可获得奖励', 'video', 'finished', 1, UNIX_TIMESTAMP() - 86400*3, UNIX_TIMESTAMP()),
(4, '每日签到红包', '每日签到领取专属红包', 'chat', 'finished', 1, UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP()),
(5, '小游戏红包雨', '玩游戏即可领取红包奖励', 'miniapp', 'finished', 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(6, '限时红包狂欢', '限时抢红包活动', 'miniapp', 'normal', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '分享好友红包', '分享给好友领取红包', 'adv', 'normal', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, '新用户专享红包', '新用户首次注册专属红包', 'chat', 'normal', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 确保有测试用户数据
INSERT IGNORE INTO `advn_user` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `mobile`, `status`, `createtime`, `updatetime`) VALUES
(1001, 'test001', '测试用户001', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/001.png', '13800138001', 'normal', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(1002, 'test002', '测试用户002', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/002.png', '13800138002', 'normal', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(1003, 'test003', '测试用户003', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/003.png', '13800138003', 'normal', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),
(1004, 'test004', '测试用户004', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/004.png', '13800138004', 'normal', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP()),
(1005, 'test005', '测试用户005', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/005.png', '13800138005', 'normal', UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP()),
(1006, 'test006', '新用户001', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/006.png', '13800138006', 'normal', UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP()),
(1007, 'test007', '新用户002', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/007.png', '13800138007', 'normal', UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP()),
(1008, 'test008', '新用户003', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/008.png', '13800138008', 'normal', UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP());

-- 清空旧数据（如果需要重新生成）
-- TRUNCATE TABLE `advn_user_red_packet_accumulate`;

-- 插入模拟数据 - 已领取的红包（老用户）
INSERT INTO `advn_user_red_packet_accumulate`
(`user_id`, `task_id`, `base_amount`, `accumulate_amount`, `total_amount`, `click_count`, `is_new_user`, `is_collected`, `collect_time`, `createtime`, `updatetime`) VALUES
-- 老用户抢到的红包
(1001, 1, 4500, 2500, 7000, 4, 0, 1, UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*7, UNIX_TIMESTAMP() - 86400*6),
(1002, 2, 3800, 1800, 5600, 3, 0, 1, UNIX_TIMESTAMP() - 86400*4, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP() - 86400*4),
(1003, 3, 5200, 3200, 8400, 5, 0, 1, UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP() - 86400*3, UNIX_TIMESTAMP() - 86400*2),
(1004, 4, 3500, 1500, 5000, 3, 0, 1, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP() - 86400),
(1005, 5, 4100, 2900, 7000, 5, 0, 1, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 3600),

-- 新用户抢到的红包（金额更高）
(1006, 6, 8500, 4500, 13000, 5, 1, 1, UNIX_TIMESTAMP() - 1800, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 1800),
(1007, 7, 9200, 3800, 13000, 4, 1, 1, UNIX_TIMESTAMP() - 900, UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 900),

-- 待领取的红包
(1001, 8, 5500, 2200, 7700, 4, 0, 0, NULL, UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP() - 60),
(1008, 6, 7800, 0, 7800, 1, 1, 0, NULL, UNIX_TIMESTAMP() - 300, UNIX_TIMESTAMP() - 300),

-- 更多已领取的老用户记录
(1002, 1, 4000, 3000, 7000, 6, 0, 1, UNIX_TIMESTAMP() - 86400*6 - 3600, UNIX_TIMESTAMP() - 86400*7 - 7200, UNIX_TIMESTAMP() - 86400*6 - 3600),
(1003, 2, 4800, 2100, 6900, 4, 0, 1, UNIX_TIMESTAMP() - 86400*4 - 1800, UNIX_TIMESTAMP() - 86400*5 - 3600, UNIX_TIMESTAMP() - 86400*4 - 1800),
(1004, 3, 3900, 2600, 6500, 5, 0, 1, UNIX_TIMESTAMP() - 86400*2 - 7200, UNIX_TIMESTAMP() - 86400*3 - 7200, UNIX_TIMESTAMP() - 86400*2 - 7200),
(1005, 4, 4200, 1800, 6000, 3, 0, 1, UNIX_TIMESTAMP() - 86400 - 3600, UNIX_TIMESTAMP() - 86400*2 - 1800, UNIX_TIMESTAMP() - 86400 - 3600);

-- 输出统计
SELECT '模拟数据插入完成' AS message;
SELECT COUNT(*) AS total_records FROM `advn_user_red_packet_accumulate`;
SELECT
    is_collected,
    CASE is_collected
        WHEN 0 THEN '待领取'
        WHEN 1 THEN '已领取'
    END AS status_text,
    COUNT(*) AS count,
    FORMAT(SUM(total_amount), 0) AS total_amount
FROM `advn_user_red_packet_accumulate`
GROUP BY is_collected;

SELECT
    is_new_user,
    CASE is_new_user
        WHEN 0 THEN '老用户'
        WHEN 1 THEN '新用户'
    END AS user_type,
    COUNT(*) AS count,
    FORMAT(AVG(total_amount), 0) AS avg_amount,
    FORMAT(SUM(total_amount), 0) AS total_amount
FROM `advn_user_red_packet_accumulate`
GROUP BY is_new_user;
