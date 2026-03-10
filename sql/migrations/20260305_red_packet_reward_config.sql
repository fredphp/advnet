-- ============================================================================
-- 红包奖励配置表（合并时间段配置和金额配置）
-- 支持按时间段和今日领取金额双重限制配置不同的奖励金额区间
-- 创建时间: 2026-03-05
-- 更新时间: 2026-03-05 - 按用户需求调整配置数据
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 删除旧表（如果存在）
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_time_config`;
DROP TABLE IF EXISTS `advn_red_packet_amount_config`;

-- ----------------------------
-- 红包奖励配置表（统一配置）
-- ----------------------------
CREATE TABLE `advn_red_packet_reward_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  
  -- 时间段配置
  `start_hour` tinyint(2) unsigned NOT NULL DEFAULT 0 COMMENT '开始小时(0-23)，0表示不限制',
  `end_hour` tinyint(2) unsigned NOT NULL DEFAULT 24 COMMENT '结束小时(0-24)，24表示不限制',
  
  -- 今日金额限制
  `min_today_amount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '今日领取金额下限(金币)，0表示不限制',
  `max_today_amount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '今日领取金额上限(金币)，0表示无上限',
  
  -- 老用户奖励配置
  `base_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户基础奖励下限(金币)',
  `base_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户基础奖励上限(金币)',
  `accumulate_min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户累加奖励下限(金币)',
  `accumulate_max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '老用户累加奖励上限(金币)',
  
  -- 新用户奖励配置
  `new_user_base_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户基础奖励下限(金币)',
  `new_user_base_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户基础奖励上限(金币)',
  `new_user_accumulate_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户累加奖励下限(金币)',
  `new_user_accumulate_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '新用户累加奖励上限(金币)',
  
  -- 权重和状态
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '排序权重(数值越大优先级越高)',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `remark` varchar(255) DEFAULT '' COMMENT '备注说明',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `time_range` (`start_hour`, `end_hour`),
  KEY `today_amount` (`min_today_amount`, `max_today_amount`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包奖励配置表';

-- ----------------------------
-- 添加系统配置 - 红包最高金额限制
-- ----------------------------
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('red_packet_max_reward', 'basic', '红包最高金额', '单个红包最高累计金额(金币)，不超过此限制', 'number', '1', '10000', '', 'required|integer|gt:0', '', '')
ON DUPLICATE KEY UPDATE `value` = '10000';

-- ----------------------------
-- 初始化配置数据
-- 配置规则：分别配置时间段和今日金额限制，系统会取两者的交集
-- ----------------------------

-- 清空旧数据
TRUNCATE TABLE `advn_red_packet_reward_config`;

INSERT INTO `advn_red_packet_reward_config` (
  `name`, `start_hour`, `end_hour`, 
  `min_today_amount`, `max_today_amount`,
  `base_min_reward`, `base_max_reward`, 
  `accumulate_min_reward`, `accumulate_max_reward`,
  `new_user_base_min`, `new_user_base_max`,
  `new_user_accumulate_min`, `new_user_accumulate_max`,
  `weigh`, `status`, `remark`, `createtime`, `updatetime`
) VALUES

-- ==========================
-- 时间段配置（无今日金额限制）
-- ==========================

-- 早间时段(0-12点)：基础4000-6000，累加2000-4000
('早间时段(0-12点)', 0, 12, 0, 0, 4000, 6000, 2000, 4000, 5000, 10000, 2500, 5000, 100, 'normal', '早间黄金时段，奖励最高', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 午间时段(12-18点)：基础3000-4000，累加2000-3000
('午间时段(12-18点)', 12, 18, 0, 0, 3000, 4000, 2000, 3000, 4000, 8000, 2000, 4000, 99, 'normal', '午间时段，奖励适中', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 晚间时段(18-24点)：基础2000-3000，累加0-1500
('晚间时段(18-24点)', 18, 24, 0, 0, 2000, 3000, 0, 1500, 3000, 6000, 1000, 3000, 98, 'normal', '晚间时段，奖励较低', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- ==========================
-- 今日金额限制配置（无时间限制）
-- ==========================

-- 今日领取0-200000：基础4000-6000，累加2000-4000
('低消费用户(0-20万)', 0, 24, 0, 200000, 4000, 6000, 2000, 4000, 5000, 10000, 2500, 5000, 90, 'normal', '今日领取较少的用户', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 今日领取200000-500000：基础3000-4000，累加1500-3000
('中消费用户(20-50万)', 0, 24, 200000, 500000, 3000, 4000, 1500, 3000, 4000, 8000, 2000, 4000, 89, 'normal', '今日领取中等的用户', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 今日领取500000-1000000：基础2000-4000，累加1000-2000
('高消费用户(50-100万)', 0, 24, 500000, 1000000, 2000, 4000, 1000, 2000, 3000, 6000, 1500, 3000, 88, 'normal', '今日领取较多的用户', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 今日领取超过1000000：基础1000-2500，累加0-1500
('超高消费用户(100万以上)', 0, 24, 1000000, 0, 1000, 2500, 0, 1500, 2000, 5000, 500, 2000, 87, 'normal', '今日领取很多的用户', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 添加后台菜单
-- ----------------------------

-- 获取红包管理父级菜单ID
SET @redpacket_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket' LIMIT 1);

-- 删除已存在的奖励配置菜单
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/rewardconfig';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'redpacket/rewardconfig/%';

-- 插入奖励配置主菜单
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @redpacket_id, 'redpacket/rewardconfig', '奖励配置', 'fa fa-gift', 'redpacket/rewardconfig', '', '红包奖励配置管理', 1, 'addtabs', '', 'jlpeizhi', 'jianglipeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 96, 'normal');

-- 获取奖励配置菜单ID
SET @rewardconfig_id = LAST_INSERT_ID();

-- 插入子菜单（权限节点）
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @rewardconfig_id, 'redpacket/rewardconfig/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @rewardconfig_id, 'redpacket/rewardconfig/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @rewardconfig_id, 'redpacket/rewardconfig/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @rewardconfig_id, 'redpacket/rewardconfig/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @rewardconfig_id, 'redpacket/rewardconfig/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- ----------------------------
-- 删除旧菜单（如果存在）
-- ----------------------------
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/timeconfig';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'redpacket/timeconfig/%';
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'redpacket/amountconfig/%';

-- 输出结果
SELECT '红包奖励配置表初始化完成' AS message;
SELECT COUNT(*) AS config_count FROM `advn_red_packet_reward_config`;
SELECT * FROM `advn_red_packet_reward_config`;
