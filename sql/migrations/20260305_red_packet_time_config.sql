-- ============================================================================
-- 红包时间段配置表
-- 支持按时间段配置不同的奖励金额区间
-- 创建时间: 2026-03-05
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 红包时间段配置表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_time_config`;
CREATE TABLE `advn_red_packet_time_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `start_hour` tinyint(2) unsigned NOT NULL DEFAULT 0 COMMENT '开始小时(0-23)',
  `end_hour` tinyint(2) unsigned NOT NULL DEFAULT 24 COMMENT '结束小时(0-24), 24表示24点',
  `base_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户基础奖励金额下限(金币)',
  `base_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户基础奖励金额上限(金币)',
  `accumulate_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户累加奖励金额下限(金币)',
  `accumulate_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户累加奖励金额上限(金币)',
  `new_user_base_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户基础奖励下限(金币)',
  `new_user_base_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户基础奖励上限(金币)',
  `new_user_accumulate_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户累加奖励下限(金币)',
  `new_user_accumulate_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户累加奖励上限(金币)',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '排序权重(数值越大优先级越高)',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `remark` varchar(255) DEFAULT '' COMMENT '备注说明',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `time_range` (`start_hour`, `end_hour`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包时间段配置表';

-- ----------------------------
-- 初始化默认时间段配置数据
-- 按照用户要求：
-- 早0-12点：基础奖励3000-6000，累加奖励1000-2000
-- 12-18点：基础奖励2000-4000，累加奖励0-1500
-- 18-24点：基础奖励2000-3000，累加奖励0-1000
-- ----------------------------
INSERT INTO `advn_red_packet_time_config` (`name`, `start_hour`, `end_hour`, `base_min_reward`, `base_max_reward`, `accumulate_min_reward`, `accumulate_max_reward`, `new_user_base_min`, `new_user_base_max`, `new_user_accumulate_min`, `new_user_accumulate_max`, `weigh`, `status`, `remark`, `createtime`, `updatetime`) VALUES
('早间时段(0-12点)', 0, 12, 3000, 6000, 1000, 2000, 5000, 10000, 2000, 4000, 100, 'normal', '早间黄金时段，奖励最高', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('午间时段(12-18点)', 12, 18, 2000, 4000, 0, 1500, 4000, 8000, 1500, 3000, 99, 'normal', '午间时段，奖励适中', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('晚间时段(18-24点)', 18, 24, 2000, 3000, 0, 1000, 3000, 6000, 1000, 2500, 98, 'normal', '晚间时段，奖励较低', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 添加后台菜单 - 时段配置
-- ----------------------------

-- 获取红包管理父级菜单ID
SET @redpacket_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket' LIMIT 1);

-- 插入时段配置主菜单
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @redpacket_id, 'redpacket/timeconfig', '时段配置', 'fa fa-clock-o', 'redpacket/timeconfig', '', '红包时间段配置管理', 1, 'addtabs', '', 'sdpeizhi', 'shiduanpeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal');

-- 获取时段配置菜单ID
SET @timeconfig_id = LAST_INSERT_ID();

-- 插入时段配置子菜单（权限节点）
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @timeconfig_id, 'redpacket/timeconfig/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @timeconfig_id, 'redpacket/timeconfig/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @timeconfig_id, 'redpacket/timeconfig/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @timeconfig_id, 'redpacket/timeconfig/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @timeconfig_id, 'redpacket/timeconfig/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
