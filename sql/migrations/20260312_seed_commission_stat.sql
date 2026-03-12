-- ============================================================================
-- 佣金统计模拟数据迁移
-- 功能: 为佣金统计页面生成测试数据
-- 创建时间: 2026-03-12
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. 确保测试用户存在
-- ============================================================================
INSERT IGNORE INTO `advn_user` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `mobile`, `status`, `createtime`, `updatetime`) VALUES
(10001, 'commission001', '佣金用户001', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/001.png', '13900001001', 'normal', UNIX_TIMESTAMP() - 86400*60, UNIX_TIMESTAMP()),
(10002, 'commission002', '佣金用户002', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/002.png', '13900001002', 'normal', UNIX_TIMESTAMP() - 86400*55, UNIX_TIMESTAMP()),
(10003, 'commission003', '佣金用户003', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/003.png', '13900001003', 'normal', UNIX_TIMESTAMP() - 86400*50, UNIX_TIMESTAMP()),
(10004, 'commission004', '佣金用户004', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/004.png', '13900001004', 'normal', UNIX_TIMESTAMP() - 86400*45, UNIX_TIMESTAMP()),
(10005, 'commission005', '佣金用户005', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/005.png', '13900001005', 'normal', UNIX_TIMESTAMP() - 86400*40, UNIX_TIMESTAMP()),
(10006, 'commission006', '佣金用户006', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/006.png', '13900001006', 'normal', UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP()),
(10007, 'commission007', '佣金用户007', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/007.png', '13900001007', 'normal', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(10008, 'commission008', '佣金用户008', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/008.png', '13900001008', 'normal', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(10009, 'commission009', '佣金用户009', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/009.png', '13900001009', 'normal', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),
(10010, 'commission010', '佣金用户010', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', '/uploads/avatar/010.png', '13900001010', 'normal', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP());

-- ============================================================================
-- 2. 创建邀请关系
-- ============================================================================
INSERT IGNORE INTO `advn_invite_relation` (`user_id`, `parent_id`, `grandparent_id`, `invite_code`, `invite_channel`, `createtime`, `updatetime`) VALUES
-- 10001 是顶级用户，没有上级
(10002, 10001, 0, 'COMM001', 'link', UNIX_TIMESTAMP() - 86400*55, UNIX_TIMESTAMP() - 86400*55),
(10003, 10001, 0, 'COMM001', 'qrcode', UNIX_TIMESTAMP() - 86400*50, UNIX_TIMESTAMP() - 86400*50),
(10004, 10002, 10001, 'COMM002', 'link', UNIX_TIMESTAMP() - 86400*45, UNIX_TIMESTAMP() - 86400*45),
(10005, 10002, 10001, 'COMM002', 'share', UNIX_TIMESTAMP() - 86400*40, UNIX_TIMESTAMP() - 86400*40),
(10006, 10003, 10001, 'COMM003', 'link', UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP() - 86400*35),
(10007, 10004, 10002, 'COMM004', 'qrcode', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP() - 86400*30),
(10008, 10005, 10002, 'COMM005', 'link', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25),
(10009, 10006, 10003, 'COMM006', 'share', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20),
(10010, 10007, 10004, 'COMM007', 'link', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP() - 86400*15);

-- ============================================================================
-- 3. 用户佣金统计数据
-- ============================================================================
INSERT INTO `advn_user_commission_stat` 
(`user_id`, `total_commission`, `total_coin`, `level1_commission`, `level2_commission`, 
 `withdraw_commission`, `video_commission`, `red_packet_commission`, `game_commission`, `other_commission`,
 `today_commission`, `today_coin`, `yesterday_commission`, `week_commission`, `month_commission`,
 `pending_commission`, `frozen_commission`, `canceled_commission`,
 `withdraw_count`, `video_count`, `red_packet_count`, `game_count`,
 `createtime`, `updatetime`) VALUES
-- 10001: 顶级用户，有大量下级，佣金最多
(10001, 1258.5000, 1258500.00, 856.2000, 402.3000, 
 680.5000, 320.8000, 158.2000, 99.0000, 0.0000,
 35.6000, 35600.00, 42.3000, 186.5000, 856.2000,
 128.5000, 0.0000, 15.2000,
 156, 89, 45, 28,
 UNIX_TIMESTAMP() - 86400*60, UNIX_TIMESTAMP()),

-- 10002: 一级下级，也有不少下级
(10002, 628.3000, 628300.00, 425.6000, 202.7000,
 352.1000, 158.5000, 78.2000, 39.5000, 0.0000,
 18.2000, 18200.00, 22.5000, 95.3000, 425.6000,
 68.3000, 0.0000, 8.5000,
 82, 45, 22, 12,
 UNIX_TIMESTAMP() - 86400*55, UNIX_TIMESTAMP()),

-- 10003: 一级下级
(10003, 452.8000, 452800.00, 312.5000, 140.3000,
 245.2000, 118.6000, 58.5000, 30.5000, 0.0000,
 12.5000, 12500.00, 15.8000, 68.2000, 312.5000,
 45.8000, 0.0000, 5.2000,
 58, 32, 18, 9,
 UNIX_TIMESTAMP() - 86400*50, UNIX_TIMESTAMP()),

-- 10004: 二级下级，也有下级
(10004, 325.6000, 325600.00, 215.3000, 110.3000,
 178.5000, 85.2000, 42.1000, 19.8000, 0.0000,
 8.5000, 8500.00, 10.2000, 45.6000, 215.3000,
 28.5000, 0.0000, 3.2000,
 42, 25, 12, 5,
 UNIX_TIMESTAMP() - 86400*45, UNIX_TIMESTAMP()),

-- 10005: 二级下级
(10005, 218.5000, 218500.00, 148.2000, 70.3000,
 125.8000, 52.5000, 28.2000, 12.0000, 0.0000,
 5.8000, 5800.00, 6.5000, 28.5000, 148.2000,
 18.2000, 0.0000, 2.1000,
 28, 15, 8, 3,
 UNIX_TIMESTAMP() - 86400*40, UNIX_TIMESTAMP()),

-- 10006: 二级下级
(10006, 156.8000, 156800.00, 102.5000, 54.3000,
 88.5000, 38.2000, 18.5000, 11.6000, 0.0000,
 4.2000, 4200.00, 5.1000, 22.5000, 102.5000,
 12.5000, 0.0000, 1.8000,
 22, 12, 5, 2,
 UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP()),

-- 10007: 三级下级
(10007, 98.5000, 98500.00, 62.2000, 36.3000,
 52.8000, 25.5000, 12.2000, 8.0000, 0.0000,
 2.5000, 2500.00, 3.2000, 15.8000, 62.2000,
 8.5000, 0.0000, 1.2000,
 15, 8, 3, 1,
 UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),

-- 10008: 三级下级
(10008, 68.2000, 68200.00, 42.5000, 25.7000,
 35.5000, 18.8000, 8.5000, 5.4000, 0.0000,
 1.8000, 1800.00, 2.1000, 10.5000, 42.5000,
 5.2000, 0.0000, 0.8000,
 10, 5, 2, 1,
 UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),

-- 10009: 三级下级
(10009, 45.8000, 45800.00, 28.5000, 17.3000,
 25.2000, 12.5000, 5.8000, 2.3000, 0.0000,
 1.2000, 1200.00, 1.5000, 7.5000, 28.5000,
 3.5000, 0.0000, 0.5000,
 7, 3, 1, 0,
 UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),

-- 10010: 四级下级，没有下级
(10010, 25.5000, 25500.00, 0.0000, 25.5000,
 15.8000, 5.2000, 3.1000, 1.4000, 0.0000,
 0.6000, 600.00, 0.8000, 4.2000, 15.8000,
 2.1000, 0.0000, 0.2000,
 4, 1, 1, 0,
 UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
 `total_commission` = VALUES(`total_commission`),
 `updatetime` = UNIX_TIMESTAMP();

-- ============================================================================
-- 4. 每日佣金统计数据 (过去30天)
-- ============================================================================
INSERT INTO `advn_daily_commission_stat`
(`date_key`, `total_commission`, `total_coin`, 
 `withdraw_commission`, `video_commission`, `red_packet_commission`, `game_commission`,
 `total_count`, `user_count`, `level1_count`, `level2_count`,
 `createtime`, `updatetime`) VALUES
-- 今天的统计
(DATE_FORMAT(NOW(), '%Y-%m-%d'), 89.2000, 89200.00, 
 45.5000, 25.8000, 12.2000, 5.7000,
 25, 8, 15, 10,
 UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
 
-- 昨天的统计
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d'), 112.5000, 112500.00,
 58.2000, 32.5000, 15.8000, 6.0000,
 32, 10, 18, 14,
 UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 86400),

-- 前天的统计
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d'), 98.8000, 98800.00,
 52.5000, 28.2000, 12.5000, 5.6000,
 28, 9, 16, 12,
 UNIX_TIMESTAMP() - 86400*2, UNIX_TIMESTAMP() - 86400*2),

-- 过去一周的统计
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d'), 85.6000, 85600.00,
 42.8000, 25.5000, 11.2000, 6.1000,
 24, 8, 14, 10,
 UNIX_TIMESTAMP() - 86400*3, UNIX_TIMESTAMP() - 86400*3),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 DAY), '%Y-%m-%d'), 78.5000, 78500.00,
 38.5000, 22.8000, 10.5000, 6.7000,
 22, 7, 13, 9,
 UNIX_TIMESTAMP() - 86400*4, UNIX_TIMESTAMP() - 86400*4),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d'), 92.3000, 92300.00,
 48.2000, 26.5000, 11.8000, 5.8000,
 26, 9, 15, 11,
 UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP() - 86400*5),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 DAY), '%Y-%m-%d'), 105.2000, 105200.00,
 55.8000, 30.2000, 13.5000, 5.7000,
 30, 10, 17, 13,
 UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*6),

-- 过去两周
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 7 DAY), '%Y-%m-%d'), 88.5000, 88500.00,
 45.2000, 25.8000, 11.5000, 6.0000,
 25, 8, 14, 11,
 UNIX_TIMESTAMP() - 86400*7, UNIX_TIMESTAMP() - 86400*7),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 8 DAY), '%Y-%m-%d'), 95.8000, 95800.00,
 50.5000, 27.2000, 12.8000, 5.3000,
 27, 9, 16, 11,
 UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP() - 86400*8),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 9 DAY), '%Y-%m-%d'), 82.5000, 82500.00,
 42.8000, 24.5000, 10.2000, 5.0000,
 23, 8, 13, 10,
 UNIX_TIMESTAMP() - 86400*9, UNIX_TIMESTAMP() - 86400*9),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 10 DAY), '%Y-%m-%d'), 78.2000, 78200.00,
 40.5000, 22.8000, 9.5000, 5.4000,
 22, 7, 12, 10,
 UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP() - 86400*10),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 11 DAY), '%Y-%m-%d'), 102.5000, 102500.00,
 54.2000, 29.5000, 13.2000, 5.6000,
 29, 10, 17, 12,
 UNIX_TIMESTAMP() - 86400*11, UNIX_TIMESTAMP() - 86400*11),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d'), 88.8000, 88800.00,
 46.5000, 25.2000, 11.8000, 5.3000,
 25, 8, 15, 10,
 UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP() - 86400*12),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 13 DAY), '%Y-%m-%d'), 75.5000, 75500.00,
 38.2000, 22.5000, 9.8000, 5.0000,
 21, 7, 12, 9,
 UNIX_TIMESTAMP() - 86400*13, UNIX_TIMESTAMP() - 86400*13),

-- 过去三周
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 14 DAY), '%Y-%m-%d'), 92.2000, 92200.00,
 48.8000, 26.5000, 11.5000, 5.4000,
 26, 9, 15, 11,
 UNIX_TIMESTAMP() - 86400*14, UNIX_TIMESTAMP() - 86400*14),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 15 DAY), '%Y-%m-%d'), 85.5000, 85500.00,
 44.2000, 24.8000, 10.5000, 6.0000,
 24, 8, 14, 10,
 UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP() - 86400*15),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 16 DAY), '%Y-%m-%d'), 98.2000, 98200.00,
 52.5000, 28.2000, 12.2000, 5.3000,
 28, 9, 16, 12,
 UNIX_TIMESTAMP() - 86400*16, UNIX_TIMESTAMP() - 86400*16),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 17 DAY), '%Y-%m-%d'), 72.8000, 72800.00,
 36.5000, 21.2000, 9.5000, 5.6000,
 20, 7, 12, 8,
 UNIX_TIMESTAMP() - 86400*17, UNIX_TIMESTAMP() - 86400*17),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 18 DAY), '%Y-%m-%d'), 105.5000, 105500.00,
 56.2000, 30.5000, 13.8000, 5.0000,
 30, 10, 18, 12,
 UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP() - 86400*18),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 19 DAY), '%Y-%m-%d'), 88.5000, 88500.00,
 46.2000, 25.5000, 11.2000, 5.6000,
 25, 8, 14, 11,
 UNIX_TIMESTAMP() - 86400*19, UNIX_TIMESTAMP() - 86400*19),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 20 DAY), '%Y-%m-%d'), 82.2000, 82200.00,
 42.8000, 24.2000, 10.2000, 5.0000,
 23, 8, 13, 10,
 UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20),

-- 过去四周
(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 21 DAY), '%Y-%m-%d'), 95.8000, 95800.00,
 50.5000, 27.5000, 12.5000, 5.3000,
 27, 9, 16, 11,
 UNIX_TIMESTAMP() - 86400*21, UNIX_TIMESTAMP() - 86400*21),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 22 DAY), '%Y-%m-%d'), 78.5000, 78500.00,
 40.2000, 22.8000, 10.5000, 5.0000,
 22, 7, 13, 9,
 UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP() - 86400*22),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 23 DAY), '%Y-%m-%d'), 88.2000, 88200.00,
 46.5000, 25.2000, 11.5000, 5.0000,
 25, 8, 14, 11,
 UNIX_TIMESTAMP() - 86400*23, UNIX_TIMESTAMP() - 86400*23),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 24 DAY), '%Y-%m-%d'), 102.5000, 102500.00,
 54.5000, 29.2000, 13.5000, 5.3000,
 29, 10, 17, 12,
 UNIX_TIMESTAMP() - 86400*24, UNIX_TIMESTAMP() - 86400*24),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 25 DAY), '%Y-%m-%d'), 85.8000, 85800.00,
 44.5000, 24.8000, 11.2000, 5.3000,
 24, 8, 14, 10,
 UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 26 DAY), '%Y-%m-%d'), 92.5000, 92500.00,
 48.2000, 26.5000, 12.5000, 5.3000,
 26, 9, 15, 11,
 UNIX_TIMESTAMP() - 86400*26, UNIX_TIMESTAMP() - 86400*26),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 27 DAY), '%Y-%m-%d'), 78.2000, 78200.00,
 40.2000, 22.5000, 10.5000, 5.0000,
 22, 7, 13, 9,
 UNIX_TIMESTAMP() - 86400*27, UNIX_TIMESTAMP() - 86400*27),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 28 DAY), '%Y-%m-%d'), 95.5000, 95500.00,
 50.2000, 27.5000, 12.5000, 5.3000,
 27, 9, 16, 11,
 UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*28),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 29 DAY), '%Y-%m-%d'), 88.8000, 88800.00,
 46.5000, 25.5000, 11.5000, 5.3000,
 25, 8, 15, 10,
 UNIX_TIMESTAMP() - 86400*29, UNIX_TIMESTAMP() - 86400*29),

(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 DAY), '%Y-%m-%d'), 102.2000, 102200.00,
 54.2000, 29.5000, 13.2000, 5.3000,
 29, 10, 17, 12,
 UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP() - 86400*30)
ON DUPLICATE KEY UPDATE
 `total_commission` = VALUES(`total_commission`),
 `updatetime` = UNIX_TIMESTAMP();

-- ============================================================================
-- 5. 分佣记录数据
-- ============================================================================
INSERT INTO `advn_invite_commission_log`
(`order_no`, `source_type`, `source_id`, `source_order_no`,
 `user_id`, `parent_id`, `level`, `source_amount`,
 `commission_rate`, `commission_fixed`, `commission_amount`, `coin_amount`,
 `status`, `settle_time`, `createtime`, `updatetime`) VALUES
-- 提现分佣记录 (withdraw)
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0001'), 'withdraw', 1, 'WD202603120001',
 10002, 10001, 1, 50.0000,
 0.2000, 0.00, 10.0000, 1000.00,
 1, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 3600, UNIX_TIMESTAMP() - 3600),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0002'), 'withdraw', 1, 'WD202603120001',
 10002, 10001, 1, 50.0000,
 0.2000, 0.00, 10.0000, 1000.00,
 0, NULL, UNIX_TIMESTAMP() - 1800, UNIX_TIMESTAMP() - 1800),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0003'), 'withdraw', 2, 'WD202603120002',
 10003, 10001, 1, 80.0000,
 0.2000, 0.00, 16.0000, 1600.00,
 1, UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 7200, UNIX_TIMESTAMP() - 7200),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0004'), 'withdraw', 3, 'WD202603120003',
 10004, 10002, 1, 35.0000,
 0.2000, 0.00, 7.0000, 700.00,
 1, UNIX_TIMESTAMP() - 10800, UNIX_TIMESTAMP() - 10800, UNIX_TIMESTAMP() - 10800),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0005'), 'withdraw', 3, 'WD202603120003',
 10004, 10001, 2, 35.0000,
 0.1000, 0.00, 3.5000, 350.00,
 1, UNIX_TIMESTAMP() - 10800, UNIX_TIMESTAMP() - 10800, UNIX_TIMESTAMP() - 10800),

-- 视频分佣记录 (video)
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0006'), 'video', 101, NULL,
 10005, 10002, 1, 5000.0000,
 0.0100, 0.00, 50.0000, 5000.00,
 1, UNIX_TIMESTAMP() - 14400, UNIX_TIMESTAMP() - 14400, UNIX_TIMESTAMP() - 14400),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0007'), 'video', 102, NULL,
 10006, 10003, 1, 3000.0000,
 0.0100, 0.00, 30.0000, 3000.00,
 1, UNIX_TIMESTAMP() - 18000, UNIX_TIMESTAMP() - 18000, UNIX_TIMESTAMP() - 18000),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0008'), 'video', 102, NULL,
 10006, 10001, 2, 3000.0000,
 0.0050, 0.00, 15.0000, 1500.00,
 1, UNIX_TIMESTAMP() - 18000, UNIX_TIMESTAMP() - 18000, UNIX_TIMESTAMP() - 18000),

-- 红包分佣记录 (red_packet)
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0009'), 'red_packet', 201, NULL,
 10007, 10004, 1, 8000.0000,
 0.0100, 0.00, 80.0000, 8000.00,
 1, UNIX_TIMESTAMP() - 21600, UNIX_TIMESTAMP() - 21600, UNIX_TIMESTAMP() - 21600),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0010'), 'red_packet', 201, NULL,
 10007, 10002, 2, 8000.0000,
 0.0050, 0.00, 40.0000, 4000.00,
 1, UNIX_TIMESTAMP() - 21600, UNIX_TIMESTAMP() - 21600, UNIX_TIMESTAMP() - 21600),

-- 游戏分佣记录 (game)
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0011'), 'game', 301, NULL,
 10008, 10005, 1, 2000.0000,
 0.0100, 0.00, 20.0000, 2000.00,
 1, UNIX_TIMESTAMP() - 25200, UNIX_TIMESTAMP() - 25200, UNIX_TIMESTAMP() - 25200),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0012'), 'game', 301, NULL,
 10008, 10002, 2, 2000.0000,
 0.0050, 0.00, 10.0000, 1000.00,
 1, UNIX_TIMESTAMP() - 25200, UNIX_TIMESTAMP() - 25200, UNIX_TIMESTAMP() - 25200),

-- 待结算的记录
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0013'), 'withdraw', 4, 'WD202603120004',
 10009, 10006, 1, 25.0000,
 0.2000, 0.00, 5.0000, 500.00,
 0, NULL, UNIX_TIMESTAMP() - 600, UNIX_TIMESTAMP() - 600),

(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0014'), 'video', 103, NULL,
 10010, 10007, 1, 1500.0000,
 0.0100, 0.00, 15.0000, 1500.00,
 0, NULL, UNIX_TIMESTAMP() - 300, UNIX_TIMESTAMP() - 300),

-- 已取消的记录
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0015'), 'withdraw', 5, 'WD202603120005',
 10010, 10004, 2, 30.0000,
 0.1000, 0.00, 3.0000, 300.00,
 2, NULL, UNIX_TIMESTAMP() - 86400, UNIX_TIMESTAMP() - 86400)
ON DUPLICATE KEY UPDATE
 `commission_amount` = VALUES(`commission_amount`),
 `updatetime` = UNIX_TIMESTAMP();

-- ============================================================================
-- 6. 更新用户邀请统计
-- ============================================================================
INSERT INTO `advn_user_invite_stat`
(`user_id`, `total_invite_count`, `level1_count`, `level2_count`, `valid_invite_count`,
 `new_invite_today`, `new_invite_yesterday`, `new_invite_week`, `new_invite_month`,
 `createtime`, `updatetime`) VALUES
(10001, 9, 2, 7, 8, 0, 1, 2, 3, UNIX_TIMESTAMP() - 86400*60, UNIX_TIMESTAMP()),
(10002, 4, 2, 2, 4, 0, 0, 1, 2, UNIX_TIMESTAMP() - 86400*55, UNIX_TIMESTAMP()),
(10003, 2, 1, 1, 2, 0, 0, 0, 1, UNIX_TIMESTAMP() - 86400*50, UNIX_TIMESTAMP()),
(10004, 2, 1, 1, 2, 0, 0, 1, 1, UNIX_TIMESTAMP() - 86400*45, UNIX_TIMESTAMP()),
(10005, 1, 1, 0, 1, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*40, UNIX_TIMESTAMP()),
(10006, 1, 1, 0, 1, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP()),
(10007, 1, 1, 0, 1, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(10008, 0, 0, 0, 0, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(10009, 0, 0, 0, 0, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),
(10010, 0, 0, 0, 0, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
 `total_invite_count` = VALUES(`total_invite_count`),
 `updatetime` = UNIX_TIMESTAMP();

-- ============================================================================
-- 输出统计结果
-- ============================================================================
SELECT '佣金统计模拟数据插入完成' AS message;
SELECT COUNT(*) AS total_user_commission_stat FROM `advn_user_commission_stat`;
SELECT COUNT(*) AS total_daily_commission_stat FROM `advn_daily_commission_stat`;
SELECT COUNT(*) AS total_commission_log FROM `advn_invite_commission_log`;
SELECT COUNT(*) AS total_invite_stat FROM `advn_user_invite_stat`;

-- 输出佣金汇总
SELECT 
    SUM(total_commission) AS total_platform_commission,
    SUM(level1_commission) AS total_level1_commission,
    SUM(level2_commission) AS total_level2_commission,
    SUM(pending_commission) AS total_pending_commission,
    SUM(frozen_commission) AS total_frozen_commission
FROM `advn_user_commission_stat`;
