-- =============================================
-- 邀请关系模块模拟数据（完整版）
-- =============================================

-- 确保用户表有邀请码字段（如果报错说明字段已存在，忽略即可）
-- ALTER TABLE `advn_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT NULL COMMENT '我的邀请码' AFTER `password`;
-- ALTER TABLE `advn_user` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '直接上级用户ID' AFTER `invite_code`;
-- ALTER TABLE `advn_user` ADD COLUMN `grandparent_id` INT UNSIGNED DEFAULT 0 COMMENT '间接上级用户ID' AFTER `parent_id`;

-- 插入模拟用户（带邀请码）
INSERT IGNORE INTO `advn_user` (`id`, `group_id`, `username`, `nickname`, `password`, `salt`, `email`, `mobile`, `avatar`, `level`, `gender`, `money`, `score`, `joinip`, `jointime`, `createtime`, `updatetime`, `status`, `invite_code`, `parent_id`, `grandparent_id`) VALUES
(1, 0, 'user001', '张三', 'e10adc3949ba59abbe56e057f20f883e', 'abc123', 'user001@test.com', '13800138001', '/assets/img/avatar.png', 3, 'male', 150.00, 500, '127.0.0.1', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP(), 'normal', 'INV000001', 14, 13),
(2, 0, 'user002', '李四', 'e10adc3949ba59abbe56e057f20f883e', 'abc124', 'user002@test.com', '13800138002', '/assets/img/avatar.png', 2, 'female', 280.00, 320, '127.0.0.1', UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP(), 'normal', 'INV000002', 14, 13),
(3, 0, 'user003', '王五', 'e10adc3949ba59abbe56e057f20f883e', 'abc125', 'user003@test.com', '13800138003', '/assets/img/avatar.png', 4, 'male', 520.00, 880, '127.0.0.1', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP(), 'normal', 'INV000003', 14, 13),
(4, 0, 'user004', '赵六', 'e10adc3949ba59abbe56e057f20f883e', 'abc126', 'user004@test.com', '13800138004', '/assets/img/avatar.png', 1, 'male', 80.00, 150, '127.0.0.1', UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP(), 'normal', 'INV000004', 1, 14),
(5, 0, 'user005', '钱七', 'e10adc3949ba59abbe56e057f20f883e', 'abc127', 'user005@test.com', '13800138005', '/assets/img/avatar.png', 2, 'female', 180.00, 420, '127.0.0.1', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP(), 'normal', 'INV000005', 1, 0),
(6, 0, 'user006', '孙八', 'e10adc3949ba59abbe56e057f20f883e', 'abc128', 'user006@test.com', '13800138006', '/assets/img/avatar.png', 1, 'male', 50.00, 80, '127.0.0.1', UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP(), 'normal', 'INV000006', 1, 0),
(7, 0, 'user007', '周九', 'e10adc3949ba59abbe56e057f20f883e', 'abc129', 'user007@test.com', '13800138007', '/assets/img/avatar.png', 3, 'female', 350.00, 620, '127.0.0.1', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP(), 'normal', 'INV000007', 2, 14),
(8, 0, 'user008', '吴十', 'e10adc3949ba59abbe56e057f20f883e', 'abc130', 'user008@test.com', '13800138008', '/assets/img/avatar.png', 2, 'male', 120.00, 280, '127.0.0.1', UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP(), 'normal', 'INV000008', 2, 14),
(9, 0, 'user009', '郑十一', 'e10adc3949ba59abbe56e057f20f883e', 'abc131', 'user009@test.com', '13800138009', '/assets/img/avatar.png', 1, 'male', 30.00, 50, '127.0.0.1', UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP(), 'normal', 'INV000009', 3, 14),
(10, 0, 'user010', '王小明', 'e10adc3949ba59abbe56e057f20f883e', 'abc132', 'user010@test.com', '13800138010', '/assets/img/avatar.png', 1, 'female', 45.00, 60, '127.0.0.1', UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP(), 'normal', 'INV000010', 3, 14),
(11, 0, 'user011', '李小红', 'e10adc3949ba59abbe56e057f20f883e', 'abc133', 'user011@test.com', '13800138011', '/assets/img/avatar.png', 1, 'female', 25.00, 30, '127.0.0.1', UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP(), 'normal', 'INV000011', 3, 14),
(12, 0, 'user012', '张小华', 'e10adc3949ba59abbe56e057f20f883e', 'abc134', 'user012@test.com', '13800138012', '/assets/img/avatar.png', 1, 'male', 35.00, 45, '127.0.0.1', UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP(), 'normal', 'INV000012', 4, 1),
(13, 0, 'user013', '刘德华', 'e10adc3949ba59abbe56e057f20f883e', 'abc135', 'user013@test.com', '13800138013', '/assets/img/avatar.png', 5, 'male', 880.00, 1200, '127.0.0.1', UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP(), 'normal', 'INV000013', 0, 0),
(14, 0, 'user014', '周杰伦', 'e10adc3949ba59abbe56e057f20f883e', 'abc136', 'user014@test.com', '13800138014', '/assets/img/avatar.png', 4, 'male', 620.00, 950, '127.0.0.1', UNIX_TIMESTAMP() - 86400*32, UNIX_TIMESTAMP() - 86400*32, UNIX_TIMESTAMP(), 'normal', 'INV000014', 13, 0),
(15, 0, 'user015', '林志玲', 'e10adc3949ba59abbe56e057f20f883e', 'abc137', 'user015@test.com', '13800138015', '/assets/img/avatar.png', 3, 'female', 450.00, 680, '127.0.0.1', UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP(), 'normal', 'INV000015', 13, 0);

-- 插入金币账户数据
DELETE FROM `advn_coin_account`;
INSERT INTO `advn_coin_account` (`user_id`, `balance`, `total_income`, `total_spend`, `frozen`, `createtime`, `updatetime`) VALUES
(1, 1500.00, 5000.00, 3500.00, 0.00, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(2, 2800.00, 8000.00, 5200.00, 0.00, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP()),
(3, 5200.00, 12000.00, 6800.00, 0.00, UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(4, 800.00, 2000.00, 1200.00, 0.00, UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP()),
(5, 1800.00, 5000.00, 3200.00, 0.00, UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP()),
(6, 500.00, 1000.00, 500.00, 0.00, UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP()),
(7, 3500.00, 9000.00, 5500.00, 0.00, UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP()),
(8, 1200.00, 3000.00, 1800.00, 0.00, UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP()),
(9, 300.00, 800.00, 500.00, 0.00, UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP()),
(10, 450.00, 1000.00, 550.00, 0.00, UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP()),
(11, 250.00, 600.00, 350.00, 0.00, UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP()),
(12, 350.00, 700.00, 350.00, 0.00, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP()),
(13, 8800.00, 20000.00, 11200.00, 0.00, UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP()),
(14, 6200.00, 15000.00, 8800.00, 0.00, UNIX_TIMESTAMP() - 86400*32, UNIX_TIMESTAMP()),
(15, 4500.00, 10000.00, 5500.00, 0.00, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP());

-- 插入邀请关系数据
DELETE FROM `advn_invite_relation`;
INSERT INTO `advn_invite_relation` (`user_id`, `parent_id`, `grandparent_id`, `invite_code`, `invite_channel`, `invite_scene`, `register_reward_status`, `invite_ip`, `createtime`, `updatetime`) VALUES
-- 用户14邀请了用户1、2、3 (一级关系，grandparent=13)
(1, 14, 13, 'INV000014', 'link', 'register', 1, '192.168.1.112', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP() - 86400*30),
(2, 14, 13, 'INV000014', 'share', 'register', 1, '192.168.1.113', UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*28),
(3, 14, 13, 'INV000014', 'link', 'register', 1, '192.168.1.114', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25),
-- 用户13邀请了用户14、15 (一级关系)
(14, 13, 0, 'INV000013', 'link', 'register', 1, '192.168.1.110', UNIX_TIMESTAMP() - 86400*32, UNIX_TIMESTAMP() - 86400*32),
(15, 13, 0, 'INV000013', 'qrcode', 'register', 1, '192.168.1.111', UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*28),
-- 用户1邀请了用户4、5、6 (一级关系，用户4的grandparent=14)
(4, 1, 14, 'INV000001', 'link', 'register', 1, '192.168.1.101', UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP() - 86400*22),
(5, 1, 0, 'INV000001', 'qrcode', 'register', 1, '192.168.1.102', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20),
(6, 1, 0, 'INV000001', 'share', 'register', 1, '192.168.1.103', UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP() - 86400*18),
-- 用户2邀请了用户7、8 (一级关系，grandparent=14)
(7, 2, 14, 'INV000002', 'link', 'register', 1, '192.168.1.104', UNIX_TIMESTAMP() - 86400*15, UNIX_TIMESTAMP() - 86400*15),
(8, 2, 14, 'INV000002', 'link', 'register', 1, '192.168.1.105', UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP() - 86400*12),
-- 用户3邀请了用户9、10、11 (一级关系，grandparent=14)
(9, 3, 14, 'INV000003', 'qrcode', 'register', 0, '192.168.1.106', UNIX_TIMESTAMP() - 86400*10, UNIX_TIMESTAMP() - 86400*10),
(10, 3, 14, 'INV000003', 'link', 'register', 0, '192.168.1.107', UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP() - 86400*8),
(11, 3, 14, 'INV000003', 'share', 'register', 0, '192.168.1.108', UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*6),
-- 用户4邀请了用户12 (二级关系：用户12的parent是4，grandparent是1)
(12, 4, 1, 'INV000004', 'link', 'register', 0, '192.168.1.109', UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP() - 86400*5);

-- 插入用户邀请统计数据
DELETE FROM `advn_user_invite_stat`;
INSERT INTO `advn_user_invite_stat` (`user_id`, `total_invite_count`, `level1_count`, `level2_count`, `valid_invite_count`, `new_invite_today`, `new_invite_yesterday`, `new_invite_week`, `new_invite_month`, `last_invite_time`, `createtime`, `updatetime`) VALUES
(1, 4, 3, 1, 3, 0, 0, 0, 3, UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
(2, 2, 2, 0, 2, 0, 0, 0, 2, UNIX_TIMESTAMP() - 86400*12, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP()),
(3, 3, 3, 0, 2, 1, 0, 1, 3, UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP()),
(4, 1, 1, 0, 0, 0, 0, 1, 1, UNIX_TIMESTAMP() - 86400*5, UNIX_TIMESTAMP() - 86400*22, UNIX_TIMESTAMP()),
(13, 5, 2, 3, 5, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*28, UNIX_TIMESTAMP() - 86400*35, UNIX_TIMESTAMP()),
(14, 3, 3, 0, 3, 0, 0, 0, 0, UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*32, UNIX_TIMESTAMP());

-- 插入分佣配置数据
DELETE FROM `advn_invite_commission_config`;
INSERT INTO `advn_invite_commission_config` (`name`, `code`, `description`, `level1_rate`, `level2_rate`, `level1_fixed`, `level2_fixed`, `calc_type`, `min_amount`, `max_commission`, `daily_limit`, `user_level_min`, `need_realname`, `status`, `sort`, `createtime`, `updatetime`) VALUES
('视频分佣配置', 'video', '观看视频产生的分佣', 0.1000, 0.0500, 0.00, 0.00, 'rate', 0.00, 100.00, 0, 1, 0, 1, 100, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
('充值分佣配置', 'recharge', '用户充值产生的分佣', 0.0500, 0.0200, 0.00, 0.00, 'rate', 10.00, 200.00, 0, 1, 0, 1, 99, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
('提现分佣配置', 'withdraw', '用户提现产生的分佣', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 50.00, 0, 1, 0, 1, 98, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP()),
('红包分佣配置', 'red_packet', '红包相关分佣', 0.0300, 0.0100, 0.00, 0.00, 'rate', 0.00, 30.00, 0, 1, 0, 1, 97, UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP());

-- 插入金币流水数据（用于统计）
DELETE FROM `advn_coin_log`;
INSERT INTO `advn_coin_log` (`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `relation_type`, `title`, `description`, `createtime`, `create_date`) VALUES
-- 用户4的消费和提现
(4, 'spend', -100.00, 800.00, 700.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*20, DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(4, 'withdraw', -50.00, 700.00, 650.00, 'withdraw', '提现', '用户提现', UNIX_TIMESTAMP() - 86400*19, DATE_SUB(CURDATE(), INTERVAL 19 DAY)),
-- 用户5的消费
(5, 'spend', -150.00, 1800.00, 1650.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*18, DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
-- 用户7的消费和提现
(7, 'spend', -200.00, 3500.00, 3300.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*14, DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
(7, 'withdraw', -100.00, 3300.00, 3200.00, 'withdraw', '提现', '用户提现', UNIX_TIMESTAMP() - 86400*13, DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
-- 用户9的消费
(9, 'spend', -80.00, 300.00, 220.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*8, DATE_SUB(CURDATE(), INTERVAL 8 DAY)),
-- 用户10的消费
(10, 'spend', -60.00, 450.00, 390.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*6, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
-- 用户14的消费和提现
(14, 'spend', -500.00, 6200.00, 5700.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*30, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(14, 'withdraw', -200.00, 5700.00, 5500.00, 'withdraw', '提现', '用户提现', UNIX_TIMESTAMP() - 86400*29, DATE_SUB(CURDATE(), INTERVAL 29 DAY)),
-- 用户1的消费
(1, 'spend', -120.00, 1500.00, 1380.00, 'video', '视频消费', '观看视频消费', UNIX_TIMESTAMP() - 86400*25, DATE_SUB(CURDATE(), INTERVAL 25 DAY));

-- 插入分佣记录数据
DELETE FROM `advn_invite_commission_log`;
INSERT INTO `advn_invite_commission_log` (`order_no`, `source_type`, `source_id`, `source_order_no`, `user_id`, `parent_id`, `level`, `source_amount`, `commission_rate`, `commission_fixed`, `commission_amount`, `coin_amount`, `status`, `settle_time`, `config_id`, `remark`, `createtime`, `updatetime`) VALUES
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0001'), 'video', 1001, 'V202501010001', 4, 1, 1, 100.0000, 0.1000, 0.00, 10.0000, 100.00, 1, UNIX_TIMESTAMP() - 86400*20, 1, '视频观看奖励', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0002'), 'video', 1001, 'V202501010001', 4, 14, 2, 100.0000, 0.0500, 0.00, 5.0000, 50.00, 1, UNIX_TIMESTAMP() - 86400*20, 1, '视频观看奖励-二级', UNIX_TIMESTAMP() - 86400*20, UNIX_TIMESTAMP() - 86400*20),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0003'), 'video', 1002, 'V202501010002', 5, 1, 1, 150.0000, 0.1000, 0.00, 15.0000, 150.00, 1, UNIX_TIMESTAMP() - 86400*18, 1, '视频观看奖励', UNIX_TIMESTAMP() - 86400*18, UNIX_TIMESTAMP() - 86400*18),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0004'), 'video', 1003, 'V202501010003', 7, 2, 1, 200.0000, 0.1000, 0.00, 20.0000, 200.00, 1, UNIX_TIMESTAMP() - 86400*14, 1, '视频观看奖励', UNIX_TIMESTAMP() - 86400*14, UNIX_TIMESTAMP() - 86400*14),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0005'), 'video', 1004, 'V202501010004', 9, 3, 1, 80.0000, 0.1000, 0.00, 8.0000, 80.00, 0, NULL, 1, '视频观看奖励-待结算', UNIX_TIMESTAMP() - 86400*8, UNIX_TIMESTAMP() - 86400*8),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0006'), 'video', 1005, 'V202501010005', 10, 3, 1, 60.0000, 0.1000, 0.00, 6.0000, 60.00, 0, NULL, 1, '视频观看奖励-待结算', UNIX_TIMESTAMP() - 86400*6, UNIX_TIMESTAMP() - 86400*6),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0007'), 'video', 1006, 'V202501010006', 14, 13, 1, 500.0000, 0.1000, 0.00, 50.0000, 500.00, 1, UNIX_TIMESTAMP() - 86400*30, 1, '视频观看奖励', UNIX_TIMESTAMP() - 86400*30, UNIX_TIMESTAMP() - 86400*30),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0008'), 'video', 1007, 'V202501010007', 1, 14, 1, 120.0000, 0.1000, 0.00, 12.0000, 120.00, 1, UNIX_TIMESTAMP() - 86400*25, 1, '视频观看奖励', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25),
(CONCAT('CM', DATE_FORMAT(NOW(), '%Y%m%d'), '0009'), 'video', 1007, 'V202501010007', 1, 13, 2, 120.0000, 0.0500, 0.00, 6.0000, 60.00, 1, UNIX_TIMESTAMP() - 86400*25, 1, '视频观看奖励-二级', UNIX_TIMESTAMP() - 86400*25, UNIX_TIMESTAMP() - 86400*25);

SELECT '模拟数据插入完成！' as message;
