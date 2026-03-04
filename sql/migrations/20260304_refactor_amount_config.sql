-- ============================================================================
-- 红包金额配置表（重构版）
-- 将基础额度和累加额度合并到一条记录中
-- ============================================================================

SET NAMES utf8mb4;

-- 删除旧表
DROP TABLE IF EXISTS `advn_red_packet_amount_config`;

-- 创建新表
CREATE TABLE `advn_red_packet_amount_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_type` varchar(30) NOT NULL DEFAULT 'tier' COMMENT '配置类型: new_user=新用户红包, tier=阶梯配置',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `min_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额下限(金币)',
  `max_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额上限(金币), 0表示无上限',
  `base_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '基础奖励金额下限(金币)',
  `base_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '基础奖励金额上限(金币)',
  `accumulate_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累加奖励金额下限(金币)',
  `accumulate_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累加奖励金额上限(金币)',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '排序权重(越大优先级越高)',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `remark` varchar(255) DEFAULT '' COMMENT '备注说明',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `config_type` (`config_type`),
  KEY `status` (`status`),
  KEY `today_amount` (`min_today_amount`, `max_today_amount`),
  KEY `weigh` (`weigh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包金额配置表';

-- ----------------------------
-- 初始化默认配置数据
-- ----------------------------

-- 新用户红包配置（独立配置，只设置基础奖励）
INSERT INTO `advn_red_packet_amount_config` (`config_type`, `name`, `min_today_amount`, `max_today_amount`, `base_min_reward`, `base_max_reward`, `accumulate_min_reward`, `accumulate_max_reward`, `status`, `remark`, `createtime`, `updatetime`, `weigh`) VALUES
('new_user', '新用户首单红包', 0, 0, 5000, 15000, 2000, 4000, 'normal', '新用户首次点击红包获得的金额区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100);

-- 阶梯配置（合并基础额度和累加额度）
INSERT INTO `advn_red_packet_amount_config` (`config_type`, `name`, `min_today_amount`, `max_today_amount`, `base_min_reward`, `base_max_reward`, `accumulate_min_reward`, `accumulate_max_reward`, `status`, `remark`, `createtime`, `updatetime`, `weigh`) VALUES
('tier', '入门级（0-10万）', 0, 100000, 3500, 6000, 2000, 4000, 'normal', '今日领取0-10万金币的奖励区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100),
('tier', '中级（10-20万）', 100000, 200000, 3000, 5000, 1500, 3500, 'normal', '今日领取10-20万金币的奖励区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 99),
('tier', '高级（20-30万）', 200000, 300000, 2500, 4000, 1000, 3000, 'normal', '今日领取20-30万金币的奖励区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 98),
('tier', '顶级（30万以上）', 300000, 0, 2000, 3500, 800, 2500, 'normal', '今日领取30万以上金币的奖励区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 97);

-- 输出结果
SELECT '红包金额配置表重构完成' AS message;
SELECT COUNT(*) AS config_count FROM `advn_red_packet_amount_config`;
SELECT * FROM `advn_red_packet_amount_config`;
