-- ============================================================================
-- 红包金额配置表（更新版）
-- 用于配置新用户红包金额区间和不同今日领取金额对应的额度区间
-- ============================================================================

SET NAMES utf8mb4;

-- 检查并更新表结构
DROP TABLE IF EXISTS `advn_red_packet_amount_config`;
CREATE TABLE `advn_red_packet_amount_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `config_type` varchar(30) NOT NULL DEFAULT 'new_user' COMMENT '配置类型: new_user=新用户红包, base_amount=基础额度, accumulate_amount=累加额度',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
  `min_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额下限(金币)',
  `max_today_amount` int(10) unsigned DEFAULT 0 COMMENT '今日领取金额上限(金币), 0表示无上限',
  `min_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金额下限(金币)',
  `max_reward` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '奖励金额上限(金币)',
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
-- 添加后台菜单
-- ----------------------------

-- 获取红包管理菜单ID
SET @redpacket_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket' LIMIT 1);

-- 检查菜单是否已存在
SELECT COUNT(*) INTO @menu_exists FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig';

-- 如果红包菜单存在且金额配置菜单不存在，则插入
-- 主菜单
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @redpacket_id, 'redpacket/amountconfig', '金额配置', 'fa fa-cog', '', '', '红包金额配置管理', 1, 'addtabs', '', 'jepeizhi', 'jinepeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 96, 'normal'
FROM DUAL WHERE @redpacket_id IS NOT NULL AND @menu_exists = 0;

-- 获取新插入的菜单ID
SET @config_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig' LIMIT 1);

-- 子菜单权限
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @config_id, 'redpacket/amountconfig/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL WHERE @config_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig/index');

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @config_id, 'redpacket/amountconfig/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL WHERE @config_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig/add');

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @config_id, 'redpacket/amountconfig/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL WHERE @config_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig/edit');

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @config_id, 'redpacket/amountconfig/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL WHERE @config_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig/del');

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @config_id, 'redpacket/amountconfig/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL WHERE @config_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig/multi');

-- 输出结果
SELECT '金额配置菜单和数据初始化完成' AS message;
SELECT COUNT(*) AS config_count FROM `advn_red_packet_amount_config`;
SELECT COUNT(*) AS menu_count FROM `advn_auth_rule` WHERE name LIKE 'redpacket/amountconfig%';
