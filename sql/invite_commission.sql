-- ============================================================================
-- 邀请分佣系统 - 数据库表结构
-- ============================================================================
-- 表前缀: advn_
-- 功能: 支持二级分佣，按提现金额触发，多来源收益统计
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 分佣配置表 (advn_invite_commission_config)
-- 说明: 后台可配置各级分佣比例
-- ============================================================================
DROP TABLE IF EXISTS `advn_invite_commission_config`;
CREATE TABLE `advn_invite_commission_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
    `code` VARCHAR(50) NOT NULL COMMENT '配置代码: withdraw/video/red_packet',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '配置描述',
    `level1_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '一级分佣比例(0.2表示20%)',
    `level2_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '二级分佣比例',
    `level1_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '一级固定金额(元)',
    `level2_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '二级固定金额(元)',
    `calc_type` VARCHAR(20) DEFAULT 'rate' COMMENT '计算方式: rate=比例, fixed=固定, rate_and_fixed=比例+固定',
    `min_amount` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '最低触发金额(元)',
    `max_commission` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '单笔最大佣金(元, 0=不限)',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日最大佣金次数(0=不限)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级要求',
    `need_realname` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否需要实名: 0=否, 1=是',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分佣配置表';

-- ============================================================================
-- 2. 分佣记录表 (advn_invite_commission_log)
-- 说明: 记录每一笔分佣明细
-- ============================================================================
DROP TABLE IF EXISTS `advn_invite_commission_log`;
CREATE TABLE `advn_invite_commission_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '分佣订单号',
    `source_type` VARCHAR(30) NOT NULL COMMENT '来源类型: withdraw/video/red_packet/game',
    `source_id` INT UNSIGNED DEFAULT NULL COMMENT '来源记录ID',
    `source_order_no` VARCHAR(32) DEFAULT NULL COMMENT '来源订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '产生收益的用户ID(下级)',
    `parent_id` INT UNSIGNED NOT NULL COMMENT '获得佣金的用户ID(上级)',
    `level` TINYINT UNSIGNED NOT NULL COMMENT '层级: 1=一级, 2=二级',
    `source_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '来源金额(元)',
    `commission_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '分佣比例',
    `commission_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '固定佣金',
    `commission_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '佣金金额(元)',
    `coin_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '佣金金币',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待结算, 1=已结算, 2=已取消, 3=已冻结',
    `settle_time` INT UNSIGNED DEFAULT NULL COMMENT '结算时间',
    `cancel_reason` VARCHAR(200) DEFAULT NULL COMMENT '取消原因',
    `config_id` INT UNSIGNED DEFAULT NULL COMMENT '使用的配置ID',
    `remark` VARCHAR(200) DEFAULT NULL COMMENT '备注',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_source` (`source_type`, `source_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分佣记录表';

-- ============================================================================
-- 3. 用户邀请统计表 (advn_user_invite_stat)
-- 说明: 统计用户的邀请人数
-- ============================================================================
DROP TABLE IF EXISTS `advn_user_invite_stat`;
CREATE TABLE `advn_user_invite_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_invite_count` INT UNSIGNED DEFAULT 0 COMMENT '累计邀请人数',
    `level1_count` INT UNSIGNED DEFAULT 0 COMMENT '一级邀请人数',
    `level2_count` INT UNSIGNED DEFAULT 0 COMMENT '二级邀请人数(下级的下级)',
    `valid_invite_count` INT UNSIGNED DEFAULT 0 COMMENT '有效邀请人数(已产生收益)',
    `new_invite_today` INT UNSIGNED DEFAULT 0 COMMENT '今日新增邀请',
    `new_invite_yesterday` INT UNSIGNED DEFAULT 0 COMMENT '昨日新增邀请',
    `new_invite_week` INT UNSIGNED DEFAULT 0 COMMENT '本周新增邀请',
    `new_invite_month` INT UNSIGNED DEFAULT 0 COMMENT '本月新增邀请',
    `last_invite_time` INT UNSIGNED DEFAULT NULL COMMENT '最后邀请时间',
    `last_invite_user_id` INT UNSIGNED DEFAULT NULL COMMENT '最后邀请的用户ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户邀请统计表';

-- ============================================================================
-- 4. 用户佣金统计表 (advn_user_commission_stat)
-- 说明: 统计用户的分佣收益，按来源分类
-- ============================================================================
DROP TABLE IF EXISTS `advn_user_commission_stat`;
CREATE TABLE `advn_user_commission_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计佣金(元)',
    `total_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计金币',
    `level1_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '一级佣金(元)',
    `level2_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '二级佣金(元)',
    `withdraw_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '提现分佣收益(元)',
    `video_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '视频分佣收益(元)',
    `red_packet_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '红包分佣收益(元)',
    `game_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '游戏分佣收益(元)',
    `other_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '其他分佣收益(元)',
    `today_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '今日佣金(元)',
    `today_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '今日金币',
    `yesterday_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '昨日佣金(元)',
    `week_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '本周佣金(元)',
    `month_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '本月佣金(元)',
    `pending_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '待结算佣金(元)',
    `frozen_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '冻结佣金(元)',
    `canceled_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '已取消佣金(元)',
    `withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '提现分佣次数',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频分佣次数',
    `red_packet_count` INT UNSIGNED DEFAULT 0 COMMENT '红包分佣次数',
    `game_count` INT UNSIGNED DEFAULT 0 COMMENT '游戏分佣次数',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_total_commission` (`total_commission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户佣金统计表';

-- ============================================================================
-- 5. 每日佣金统计表 (advn_daily_commission_stat)
-- 说明: 按日期统计全平台分佣数据
-- ============================================================================
DROP TABLE IF EXISTS `advn_daily_commission_stat`;
CREATE TABLE `advn_daily_commission_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    `total_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '总佣金(元)',
    `total_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '总金币',
    `withdraw_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '提现分佣(元)',
    `video_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '视频分佣(元)',
    `red_packet_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '红包分佣(元)',
    `game_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '游戏分佣(元)',
    `total_count` INT UNSIGNED DEFAULT 0 COMMENT '总次数',
    `user_count` INT UNSIGNED DEFAULT 0 COMMENT '获得佣金用户数',
    `level1_count` INT UNSIGNED DEFAULT 0 COMMENT '一级分佣次数',
    `level2_count` INT UNSIGNED DEFAULT 0 COMMENT '二级分佣次数',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日佣金统计表';

-- ============================================================================
-- 插入默认配置数据
-- ============================================================================
INSERT INTO `advn_invite_commission_config` (`name`, `code`, `description`, `level1_rate`, `level2_rate`, `level1_fixed`, `level2_fixed`, `calc_type`, `min_amount`, `max_commission`, `status`, `sort`, `createtime`, `updatetime`) VALUES
('提现分佣', 'withdraw', '下级提现时上级获得佣金', 0.2000, 0.1000, 0.00, 0.00, 'rate', 5.00, 10.00, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('视频分佣', 'video', '下级观看视频获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 5.00, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('红包分佣', 'red_packet', '下级抢红包获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 2.00, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('游戏分佣', 'game', '下级玩游戏获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 2.00, 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 插入后台菜单
-- ============================================================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'invite', '邀请分佣', 'fa fa-users', '', '', '邀请分佣管理', 1, NULL, '', 'yqfy', 'yaofenfenyong', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @parent_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'invite/commissionconfig', '分佣配置', 'fa fa-cog', '', '', '', 1, NULL, '', 'fypz', 'fenyongpeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'invite/commissionlog', '分佣记录', 'fa fa-list', '', '', '', 1, NULL, '', 'fyjl', 'fenyongjilu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'invite/invitestat', '邀请统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'yqtj', 'yaotongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'invite/commissionstat', '佣金统计', 'fa fa-money', '', '', '', 1, NULL, '', 'yjtj', 'yongjintongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- ============================================================================
-- 添加系统配置
-- ============================================================================
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('invite_commission_enabled', 'basic', '开启邀请分佣', '是否开启邀请分佣功能', 'switch', '1', '', '', '', ''),
('invite_commission_delay', 'basic', '分佣延迟时间', '提现成功后延迟多少秒发放佣金', 'number', '300', '', '', '', ''),
('invite_max_level', 'basic', '最大分佣层级', '最大支持的分佣层级(1-3)', 'number', '2', '', '', '', ''),
('invite_register_reward', 'basic', '邀请注册奖励', '邀请新用户注册奖励金币', 'number', '500', '', '', '', ''),
('invite_first_withdraw_reward', 'basic', '首提奖励', '下级首次提现额外奖励金币', 'number', '1000', '', '', '', '');

SET FOREIGN_KEY_CHECKS = 1;
