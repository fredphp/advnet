-- ============================================================================
-- 红包金额配置表
-- 用于配置新用户红包金额区间和不同今日领取金额对应的额度区间
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 红包金额配置表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_amount_config`;
CREATE TABLE `advn_red_packet_amount_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_type` varchar(30) NOT NULL DEFAULT 'new_user' COMMENT '配置类型: new_user=新用户红包, base_amount=基础额度, accumulate_amount=累加额度',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `min_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额下限(金币)',
  `max_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额上限(金币), 0表示无上限',
  `min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金额下限(金币)',
  `max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金额上限(金币)',
  `weight` int(10) NOT NULL DEFAULT 0 COMMENT '权重(用于区间匹配优先级)',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `remark` varchar(255) DEFAULT '' COMMENT '备注说明',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '排序权重',
  PRIMARY KEY (`id`),
  KEY `config_type` (`config_type`),
  KEY `status` (`status`),
  KEY `today_amount` (`min_today_amount`, `max_today_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包金额配置表';

-- ----------------------------
-- 初始化默认配置数据
-- ----------------------------

-- 新用户红包配置
INSERT INTO `advn_red_packet_amount_config` (`config_type`, `name`, `min_reward`, `max_reward`, `status`, `remark`, `createtime`, `updatetime`, `weigh`) VALUES
('new_user', '新用户首单红包', 5000, 15000, 'normal', '新用户首次点击红包获得的金额区间', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100);

-- 基础额度配置（根据今日领取金额）
INSERT INTO `advn_red_packet_amount_config` (`config_type`, `name`, `min_today_amount`, `max_today_amount`, `min_reward`, `max_reward`, `status`, `remark`, `createtime`, `updatetime`, `weigh`) VALUES
('base_amount', '基础额度-入门级', 0, 100000, 3500, 6000, 'normal', '今日领取0-10万金币的基础红包额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100),
('base_amount', '基础额度-中级', 100000, 200000, 3000, 5000, 'normal', '今日领取10-20万金币的基础红包额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 99),
('base_amount', '基础额度-高级', 200000, 300000, 2500, 4000, 'normal', '今日领取20-30万金币的基础红包额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 98),
('base_amount', '基础额度-顶级', 300000, 0, 2000, 3500, 'normal', '今日领取30万以上金币的基础红包额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 97);

-- 累加额度配置（根据今日领取金额）
INSERT INTO `advn_red_packet_amount_config` (`config_type`, `name`, `min_today_amount`, `max_today_amount`, `min_reward`, `max_reward`, `status`, `remark`, `createtime`, `updatetime`, `weigh`) VALUES
('accumulate_amount', '累加额度-入门级', 0, 100000, 2000, 4000, 'normal', '今日领取0-10万金币的累加额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100),
('accumulate_amount', '累加额度-中级', 100000, 200000, 1500, 3500, 'normal', '今日领取10-20万金币的累加额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 99),
('accumulate_amount', '累加额度-高级', 200000, 300000, 1000, 3000, 'normal', '今日领取20-30万金币的累加额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 98),
('accumulate_amount', '累加额度-顶级', 300000, 0, 800, 2500, 'normal', '今日领取30万以上金币的累加额度', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 97);

-- ----------------------------
-- 用户红包累计记录表（记录用户每次点击红包累计的金额）
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_red_packet_accumulate`;
CREATE TABLE `advn_user_red_packet_accumulate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '任务ID',
  `base_amount` int(10) unsigned DEFAULT 0 COMMENT '基础金额',
  `accumulate_amount` int(10) unsigned DEFAULT 0 COMMENT '累计金额',
  `total_amount` int(10) unsigned DEFAULT 0 COMMENT '总金额(基础+累计)',
  `click_count` int(10) unsigned DEFAULT 1 COMMENT '点击次数',
  `is_new_user` tinyint(1) DEFAULT 0 COMMENT '是否新用户',
  `is_collected` tinyint(1) DEFAULT 0 COMMENT '是否已领取',
  `collect_time` bigint(16) DEFAULT NULL COMMENT '领取时间',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_task` (`user_id`, `task_id`),
  KEY `user_id` (`user_id`),
  KEY `task_id` (`task_id`),
  KEY `is_collected` (`is_collected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户红包累计记录表';

-- ----------------------------
-- 添加后台菜单
-- ----------------------------
SET @redpacket_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket' LIMIT 1);

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @redpacket_id, 'redpacket/amountconfig', '金额配置', 'fa fa-cog', '', '', '红包金额配置管理', 1, 'addtabs', '', 'jepeizhi', 'jinepeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 96, 'normal');

SET @config_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @config_id, 'redpacket/amountconfig/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @config_id, 'redpacket/amountconfig/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @config_id, 'redpacket/amountconfig/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @config_id, 'redpacket/amountconfig/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @config_id, 'redpacket/amountconfig/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
