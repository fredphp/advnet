-- 红包奖励配置表
CREATE TABLE IF NOT EXISTS `fa_red_packet_reward_config` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '配置名称',
    `min_amount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '今日金额下限',
    `max_amount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '今日金额上限',
    `start_time` tinyint(2) unsigned NOT NULL DEFAULT 0 COMMENT '时间段开始（小时）',
    `end_time` tinyint(2) unsigned NOT NULL DEFAULT 24 COMMENT '时间段结束（小时）',
    `base_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '基础金额下限',
    `base_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '基础金额上限',
    `accumulate_min` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累加金额下限',
    `accumulate_max` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '累加金额上限',
    `max_reward` int(10) unsigned NOT NULL DEFAULT 50000 COMMENT '红包封顶金额',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '状态：0=禁用，1=启用',
    `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_amount_range` (`min_amount`, `max_amount`),
    KEY `idx_time_range` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包奖励配置表';

-- 插入默认配置数据
INSERT INTO `fa_red_packet_reward_config` (`name`, `min_amount`, `max_amount`, `start_time`, `end_time`, `base_min`, `base_max`, `accumulate_min`, `accumulate_max`, `max_reward`, `status`) VALUES
('0-12点配置（低金额用户）', 0, 50000, 0, 12, 4000, 6000, 2000, 4000, 50000, 1),
('0-12点配置（高金额用户）', 50001, 200000, 0, 12, 6000, 8000, 3000, 5000, 80000, 1),
('12-18点配置（低金额用户）', 0, 50000, 12, 18, 3000, 4000, 2000, 3000, 40000, 1),
('12-18点配置（高金额用户）', 50001, 200000, 12, 18, 5000, 7000, 2500, 4000, 60000, 1),
('18-24点配置（低金额用户）', 0, 50000, 18, 24, 2000, 3000, 1000, 2000, 30000, 1),
('18-24点配置（高金额用户）', 50001, 200000, 18, 24, 4000, 5000, 2000, 3000, 50000, 1);

-- 添加后台菜单
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM fa_auth_rule WHERE name = 'redpacket') AS tmp), 'redpacket/rewardconfig', '奖励配置', 'fa fa-gear', 'redpacket/rewardconfig', '', '红包奖励配置管理', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 获取刚插入的菜单ID并添加子菜单
SET @parent_id = (SELECT id FROM fa_auth_rule WHERE name = 'redpacket/rewardconfig');
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'redpacket/rewardconfig/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/rewardconfig/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/rewardconfig/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/rewardconfig/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/rewardconfig/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
