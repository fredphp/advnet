-- ============================================================================
-- 短视频金币平台 - 数据库表结构
-- ============================================================================
-- 表前缀: advn_
-- 数据库: MySQL 8.0+
-- 字符集: utf8mb4
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 用户表扩展字段 (user表已存在，添加扩展字段)
-- ============================================================================
ALTER TABLE `advn_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT NULL COMMENT '我的邀请码' AFTER `password`;
ALTER TABLE `advn_user` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '直接上级用户ID' AFTER `invite_code`;
ALTER TABLE `advn_user` ADD COLUMN `grandparent_id` INT UNSIGNED DEFAULT 0 COMMENT '间接上级用户ID' AFTER `parent_id`;
ALTER TABLE `advn_user` ADD COLUMN `level` TINYINT UNSIGNED DEFAULT 1 COMMENT '用户等级' AFTER `grandparent_id`;
ALTER TABLE `advn_user` ADD COLUMN `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID' AFTER `level`;
ALTER TABLE `advn_user` ADD COLUMN `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP' AFTER `device_id`;
ALTER TABLE `advn_user` ADD UNIQUE KEY `uk_invite_code` (`invite_code`);
ALTER TABLE `advn_user` ADD KEY `idx_parent_id` (`parent_id`);
ALTER TABLE `advn_user` ADD KEY `idx_device_id` (`device_id`);

-- ============================================================================
-- 2. 邀请关系表 (advn_invite_relation)
-- ============================================================================
DROP TABLE IF EXISTS `advn_invite_relation`;
CREATE TABLE `advn_invite_relation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '被邀请人ID(当前用户)',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '一级邀请人ID(直接上级)',
    `grandparent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '二级邀请人ID(间接上级)',
    `invite_code` VARCHAR(20) NOT NULL COMMENT '使用的邀请码',
    `invite_channel` VARCHAR(50) DEFAULT NULL COMMENT '邀请渠道: link/qrcode/share',
    `invite_scene` VARCHAR(50) DEFAULT NULL COMMENT '邀请场景',
    `register_reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '注册奖励状态: 0=未发放, 1=已发放',
    `register_reward_time` INT UNSIGNED DEFAULT NULL COMMENT '注册奖励发放时间',
    `invite_ip` VARCHAR(50) DEFAULT NULL COMMENT '邀请行为IP',
    `invite_device_id` VARCHAR(100) DEFAULT NULL COMMENT '邀请设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_grandparent_id` (`grandparent_id`),
    KEY `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请关系表';

-- ============================================================================
-- 3. 金币账户表 (advn_coin_account)
-- ============================================================================
DROP TABLE IF EXISTS `advn_coin_account`;
CREATE TABLE `advn_coin_account` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `balance` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '可用金币余额',
    `frozen` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '冻结金币(提现申请中)',
    `total_earn` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计获得金币',
    `total_spend` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计消费金币',
    `total_withdraw` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金币',
    `today_earn` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '今日获得金币',
    `today_earn_date` DATE DEFAULT NULL COMMENT '今日统计日期',
    `version` INT UNSIGNED DEFAULT 0 COMMENT '乐观锁版本号',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币账户表';

-- ============================================================================
-- 4. 金币流水表模板 (advn_coin_log_YYYYMM)
-- ============================================================================
DROP TABLE IF EXISTS `advn_coin_log`;
CREATE TABLE `advn_coin_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '流水类型',
    `amount` DECIMAL(18,2) NOT NULL COMMENT '金币数量(正数=收入,负数=支出)',
    `balance_before` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动前余额',
    `balance_after` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动后余额',
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: video/task/withdraw/invite',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联记录ID',
    `title` VARCHAR(100) DEFAULT NULL COMMENT '流水标题',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '详细描述',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '操作IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_relation` (`relation_type`, `relation_id`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_user_date` (`user_id`, `create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币流水表';

-- ============================================================================
-- 5. 人民币账户表 (advn_cash_account)
-- ============================================================================
DROP TABLE IF EXISTS `advn_cash_account`;
CREATE TABLE `advn_cash_account` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `default_account_type` VARCHAR(20) DEFAULT NULL COMMENT '默认提现方式: alipay/wechat/bank',
    `alipay_account` VARCHAR(100) DEFAULT NULL COMMENT '支付宝账号',
    `alipay_name` VARCHAR(50) DEFAULT NULL COMMENT '支付宝真实姓名',
    `alipay_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '支付宝是否已验证',
    `wechat_openid` VARCHAR(100) DEFAULT NULL COMMENT '微信OpenID',
    `wechat_name` VARCHAR(50) DEFAULT NULL COMMENT '微信昵称',
    `wechat_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '微信是否已验证',
    `bank_name` VARCHAR(50) DEFAULT NULL COMMENT '银行名称',
    `bank_code` VARCHAR(20) DEFAULT NULL COMMENT '银行代码',
    `bank_card_no` VARCHAR(100) DEFAULT NULL COMMENT '银行卡号(加密存储)',
    `bank_card_name` VARCHAR(50) DEFAULT NULL COMMENT '持卡人姓名',
    `bank_branch` VARCHAR(100) DEFAULT NULL COMMENT '开户行',
    `bank_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '银行卡是否已验证',
    `total_withdraw_amount` DECIMAL(12,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金额(元)',
    `total_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '累计提现次数',
    `last_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '最后提现时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人民币账户表';

-- ============================================================================
-- 6. 提现申请表 (advn_withdraw_order)
-- ============================================================================
DROP TABLE IF EXISTS `advn_withdraw_order`;
CREATE TABLE `advn_withdraw_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号(唯一)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `coin_amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '提现金币数量',
    `exchange_rate` DECIMAL(10,4) UNSIGNED DEFAULT 10000.0000 COMMENT '兑换比例',
    `cash_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '提现金额(元)',
    `fee_amount` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '手续费(元)',
    `actual_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '实际到账金额(元)',
    `withdraw_type` VARCHAR(20) NOT NULL COMMENT '提现方式: alipay/wechat/bank',
    `withdraw_account` VARCHAR(100) NOT NULL COMMENT '提现账号',
    `withdraw_name` VARCHAR(50) NOT NULL COMMENT '收款人姓名',
    `bank_name` VARCHAR(50) DEFAULT NULL COMMENT '银行名称(银行卡提现)',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待审核, 1=审核通过, 2=打款中, 3=提现成功, 4=审核拒绝, 5=打款失败, 6=已取消',
    `audit_type` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核类型: 0=自动审核, 1=人工审核',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_admin_name` VARCHAR(50) DEFAULT NULL COMMENT '审核管理员名称',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_remark` VARCHAR(200) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '打款流水号',
    `transfer_time` INT UNSIGNED DEFAULT NULL COMMENT '打款时间',
    `transfer_result` TEXT DEFAULT NULL COMMENT '打款结果(JSON)',
    `fail_reason` VARCHAR(200) DEFAULT NULL COMMENT '失败原因',
    `commission_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '佣金发放状态: 0=未发放, 1=已发放',
    `commission_time` INT UNSIGNED DEFAULT NULL COMMENT '佣金发放时间',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '申请IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现申请表';

-- ============================================================================
-- 7. 视频表 (advn_video)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video`;
CREATE TABLE `advn_video` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '发布者ID',
    `title` VARCHAR(200) DEFAULT NULL COMMENT '视频标题',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '视频描述',
    `cover_url` VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
    `video_url` VARCHAR(500) NOT NULL COMMENT '视频URL',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '视频时长(秒)',
    `width` INT UNSIGNED DEFAULT 0 COMMENT '视频宽度(px)',
    `height` INT UNSIGNED DEFAULT 0 COMMENT '视频高度(px)',
    `file_size` BIGINT UNSIGNED DEFAULT 0 COMMENT '文件大小(字节)',
    `format` VARCHAR(20) DEFAULT NULL COMMENT '视频格式: mp4/mov/avi',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '分类ID',
    `collection_id` INT UNSIGNED DEFAULT NULL COMMENT '所属合集ID',
    `episode` INT UNSIGNED DEFAULT 1 COMMENT '集数序号',
    `tags` VARCHAR(200) DEFAULT NULL COMMENT '标签(JSON数组)',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 100.00 COMMENT '观看奖励金币',
    `reward_duration` INT UNSIGNED DEFAULT 30 COMMENT '奖励所需观看时长(秒)',
    `reward_enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否开启奖励: 0=否, 1=是',
    `reward_rule_id` INT UNSIGNED DEFAULT NULL COMMENT '收益规则ID',
    `view_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '播放量',
    `like_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '点赞数',
    `comment_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '评论数',
    `share_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '分享数',
    `collect_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '收藏数',
    `reward_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '奖励人数',
    `reward_coin_total` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '已发放奖励金币',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待审核, 1=已发布, 2=已下架, 3=已封禁, 4=草稿',
    `is_original` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否原创: 0=转载, 1=原创',
    `is_recommend` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否推荐: 0=否, 1=是',
    `is_hot` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否热门: 0=否, 1=是',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序权重',
    `audit_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核状态: 0=待审核, 1=通过, 2=拒绝',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_remark` VARCHAR(200) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `location` VARCHAR(100) DEFAULT NULL COMMENT '发布地点',
    `latitude` DECIMAL(10,6) DEFAULT NULL COMMENT '纬度',
    `longitude` DECIMAL(10,6) DEFAULT NULL COMMENT '经度',
    `publish_time` INT UNSIGNED DEFAULT NULL COMMENT '发布时间戳',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_status` (`status`),
    KEY `idx_is_recommend` (`is_recommend`),
    KEY `idx_view_count` (`view_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频表';

-- ============================================================================
-- 8. 视频收益规则表 (advn_video_reward_rule)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_reward_rule`;
CREATE TABLE `advn_video_reward_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则ID',
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) DEFAULT NULL COMMENT '规则代码(唯一标识)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '规则描述',
    `scope_type` VARCHAR(20) NOT NULL DEFAULT 'global' COMMENT '适用范围: global=全局, category=分类, video=单视频, collection=合集',
    `scope_id` INT UNSIGNED DEFAULT NULL COMMENT '范围ID(分类ID/视频ID/合集ID)',
    `reward_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' COMMENT '奖励类型: fixed=固定, random=随机, progressive=递进',
    `reward_coin` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '固定奖励金币',
    `reward_min` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '最小奖励(随机时)',
    `reward_max` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '最大奖励(随机时)',
    `condition_type` VARCHAR(20) NOT NULL DEFAULT 'complete' COMMENT '条件类型: complete=看完, duration=时长, count=集数',
    `watch_progress` TINYINT UNSIGNED DEFAULT 95 COMMENT '观看进度(%), 达到此进度视为看完',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '需要观看时长(秒)',
    `watch_duration_ratio` TINYINT UNSIGNED DEFAULT 0 COMMENT '时长占比(%)',
    `watch_count` INT UNSIGNED DEFAULT 0 COMMENT '需要观看集数',
    `watch_count_reward_per` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否每集奖励: 0=否, 1=是',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日奖励次数限制(0=不限)',
    `daily_limit_type` VARCHAR(20) DEFAULT 'user' COMMENT '限制维度: user=用户, video=视频, rule=规则',
    `total_limit` INT UNSIGNED DEFAULT 0 COMMENT '总奖励次数限制(0=不限)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级',
    `user_level_max` TINYINT UNSIGNED DEFAULT 255 COMMENT '最高用户等级(255=不限)',
    `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是',
    `new_user_days` INT UNSIGNED DEFAULT 7 COMMENT '新用户定义(注册天数)',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '规则生效开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '规则生效结束时间',
    `priority` INT UNSIGNED DEFAULT 0 COMMENT '优先级(越大越高)',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_scope` (`scope_type`, `scope_id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频收益规则表';

-- ============================================================================
-- 9. 视频合集表 (advn_video_collection)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_collection`;
CREATE TABLE `advn_video_collection` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '合集ID',
    `title` VARCHAR(200) NOT NULL COMMENT '合集标题',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '合集描述',
    `cover_url` VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频数量',
    `total_duration` INT UNSIGNED DEFAULT 0 COMMENT '总时长(秒)',
    `view_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '播放量',
    `reward_enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否开启奖励',
    `reward_type` VARCHAR(20) DEFAULT 'per_video' COMMENT '奖励方式: per_video=每集, complete=看完合集, progressive=递进',
    `reward_coin_per_video` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '每集奖励金币',
    `reward_coin_complete` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '看完合集奖励',
    `watch_count_for_reward` INT UNSIGNED DEFAULT 0 COMMENT '需要看完集数',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频合集表';

-- ============================================================================
-- 10. 合集视频关联表 (advn_video_collection_item)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_collection_item`;
CREATE TABLE `advn_video_collection_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `collection_id` INT UNSIGNED NOT NULL COMMENT '合集ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `episode` INT UNSIGNED DEFAULT 1 COMMENT '集数序号',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_collection_video` (`collection_id`, `video_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='合集视频关联表';

-- ============================================================================
-- 11. 视频观看记录表 (advn_video_watch_record)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_watch_record`;
CREATE TABLE `advn_video_watch_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `collection_id` INT UNSIGNED DEFAULT NULL COMMENT '合集ID',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '累计观看时长(秒)',
    `watch_progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `watch_count` INT UNSIGNED DEFAULT 0 COMMENT '观看次数',
    `last_position` INT UNSIGNED DEFAULT 0 COMMENT '上次观看位置(秒)',
    `is_completed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否看完: 0=否, 1=是',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间',
    `reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '奖励状态: 0=未领取, 1=已领取, 2=已失效',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `reward_time` INT UNSIGNED DEFAULT NULL COMMENT '奖励时间',
    `reward_rule_id` INT UNSIGNED DEFAULT NULL COMMENT '使用的规则ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '观看IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '首次观看时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '最后观看时间',
    `date_key` DATE DEFAULT NULL COMMENT '日期键(便于统计)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_video` (`user_id`, `video_id`),
    KEY `idx_video_id` (`video_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_reward_status` (`reward_status`),
    KEY `idx_user_date` (`user_id`, `date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频观看记录表';

-- ============================================================================
-- 12. 观看会话表 (advn_video_watch_session)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_watch_session`;
CREATE TABLE `advn_video_watch_session` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `start_time` INT UNSIGNED NOT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `ip` VARCHAR(50) DEFAULT NULL,
    `device_id` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(20) DEFAULT NULL,
    `app_version` VARCHAR(20) DEFAULT NULL,
    `rewarded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否获得奖励',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_session_id` (`session_id`),
    KEY `idx_user_video` (`user_id`, `video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='观看会话表';

-- ============================================================================
-- 13. 用户每日收益统计表 (advn_user_daily_reward_stat)
-- ============================================================================
DROP TABLE IF EXISTS `advn_user_daily_reward_stat`;
CREATE TABLE `advn_user_daily_reward_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    `video_watch_count` INT UNSIGNED DEFAULT 0 COMMENT '观看视频数',
    `video_watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `video_reward_count` INT UNSIGNED DEFAULT 0 COMMENT '获得奖励次数',
    `video_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `task_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '任务奖励',
    `other_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '其他奖励',
    `total_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '总获得金币',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `date_key`),
    KEY `idx_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户每日收益统计表';

-- ============================================================================
-- 14. 红包任务表 (advn_red_packet_task)
-- ============================================================================
DROP TABLE IF EXISTS `advn_red_packet_task`;
CREATE TABLE `advn_red_packet_task` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '红包ID',
    `name` VARCHAR(100) NOT NULL COMMENT '红包名称',
    `packet_type` VARCHAR(30) NOT NULL COMMENT '红包类型: new_user/share/video/activity/game',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '红包描述',
    `icon` VARCHAR(200) DEFAULT NULL COMMENT '红包图标',
    `total_amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '红包总金额(金币)',
    `total_count` INT UNSIGNED NOT NULL COMMENT '红包总数量',
    `remain_amount` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '剩余金额',
    `remain_count` INT UNSIGNED DEFAULT NULL COMMENT '剩余数量',
    `min_amount` DECIMAL(18,2) UNSIGNED DEFAULT 100.00 COMMENT '单个最小金币',
    `max_amount` DECIMAL(18,2) UNSIGNED DEFAULT 1000.00 COMMENT '单个最大金币',
    `fixed_amount` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '固定金额(固定红包)',
    `random_type` VARCHAR(20) DEFAULT 'random' COMMENT '分配方式: fixed=固定, random=随机',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级',
    `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是',
    `daily_limit` INT UNSIGNED DEFAULT 1 COMMENT '每人每日领取次数',
    `total_limit` INT UNSIGNED DEFAULT 1 COMMENT '每人总领取次数',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `expire_time` INT UNSIGNED DEFAULT NULL COMMENT '过期时间',
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: video/activity/game',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联ID',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用, 2=已抢完, 3=已过期',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '已领取人数',
    `receive_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '已领取金额',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_packet_type` (`packet_type`),
    KEY `idx_status` (`status`),
    KEY `idx_time_range` (`start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包任务表';

-- ============================================================================
-- 15. 红包领取记录表 (advn_red_packet_record)
-- ============================================================================
DROP TABLE IF EXISTS `advn_red_packet_record`;
CREATE TABLE `advn_red_packet_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `packet_id` INT UNSIGNED NOT NULL COMMENT '红包ID',
    `packet_type` VARCHAR(30) NOT NULL COMMENT '红包类型',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '获得金币数量',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=已退回, 1=正常',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '领取IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_packet_user` (`packet_id`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包领取记录表';

-- ============================================================================
-- 16. 游戏任务记录表 (advn_game_task_record)
-- ============================================================================
DROP TABLE IF EXISTS `advn_game_task_record`;
CREATE TABLE `advn_game_task_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `game_type` VARCHAR(30) NOT NULL COMMENT '游戏类型: spin/scratch/quiz/lucky_wheel/turntable',
    `game_name` VARCHAR(100) DEFAULT NULL COMMENT '游戏名称',
    `task_id` INT UNSIGNED DEFAULT NULL COMMENT '关联任务ID',
    `is_win` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否中奖: 0=未中奖, 1=中奖',
    `reward_type` VARCHAR(20) DEFAULT NULL COMMENT '奖励类型: coin/redpacket/item',
    `reward_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '奖励金额(金币)',
    `reward_detail` TEXT DEFAULT NULL COMMENT '奖励详情(JSON)',
    `receive_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '领取状态: 0=未领取, 1=已领取',
    `receive_time` INT UNSIGNED DEFAULT NULL COMMENT '领取时间',
    `game_data` TEXT DEFAULT NULL COMMENT '游戏数据(JSON)',
    `score` INT UNSIGNED DEFAULT 0 COMMENT '游戏得分',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '游戏时长(秒)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_game_type` (`game_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏任务记录表';

-- ============================================================================
-- 17. 防刷日志表 (advn_anticheat_log)
-- ============================================================================
DROP TABLE IF EXISTS `advn_anticheat_log`;
CREATE TABLE `advn_anticheat_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '类型: abnormal_speed/hourly_watch_exceed/high_risk_score',
    `data` TEXT DEFAULT NULL COMMENT '详细数据(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='防刷日志表';

-- ============================================================================
-- 插入默认规则数据
-- ============================================================================
INSERT INTO `advn_video_reward_rule` (`name`, `code`, `scope_type`, `reward_type`, `reward_coin`, `condition_type`, `watch_progress`, `watch_duration`, `daily_limit`, `status`, `createtime`) VALUES
('默认-看完奖励', 'default_complete', 'global', 'fixed', 100.00, 'complete', 95, 0, 50, 1, UNIX_TIMESTAMP()),
('默认-时长奖励', 'default_duration', 'global', 'fixed', 100.00, 'duration', 0, 30, 50, 1, UNIX_TIMESTAMP()),
('新用户专享', 'new_user_bonus', 'global', 'fixed', 200.00, 'complete', 80, 0, 10, 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 插入默认配置数据 (系统配置已存在，追加视频相关配置)
-- ============================================================================
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('watch_complete_threshold', 'basic', '完成观看阈值', '观看进度达到此百分比视为完成观看', 'number', '95', '', '', '', ''),
('daily_watch_limit', 'basic', '每日奖励上限', '每个用户每日获得观看奖励次数上限', 'number', '50', '', '', '', ''),
('watch_interval', 'basic', '同视频奖励间隔', '同一视频两次奖励间隔时间(秒)', 'number', '300', '', '', '', ''),
('default_reward_coin', 'basic', '默认奖励金币', '默认观看视频奖励金币数', 'number', '100', '', '', '', ''),
('new_user_reward_coin', 'basic', '新用户奖励金币', '新用户首次观看奖励金币', 'number', '200', '', '', '', ''),
('new_user_days', 'basic', '新用户定义天数', '注册多少天内为新用户', 'number', '7', '', '', '', ''),
('level1_watch_commission', 'basic', '一级观看佣金比例', '下级观看视频时一级上级获得佣金比例', 'string', '0.01', '', '', '', ''),
('level2_watch_commission', 'basic', '二级观看佣金比例', '下级观看视频时二级上级获得佣金比例', 'string', '0.005', '', '', '', ''),
('same_ip_reward_limit', 'basic', '同IP奖励限制', '同一IP每日获得奖励次数上限', 'number', '100', '', '', '', ''),
('same_device_reward_limit', 'basic', '同设备奖励限制', '同一设备每日获得奖励次数上限', 'number', '50', '', '', '', ''),
('max_watch_speed', 'basic', '最大观看速度', '允许的最大观看速度倍率', 'string', '2.0', '', '', '', ''),
('hourly_watch_limit', 'basic', '每小时观看限制', '每小时内最大观看视频数量', 'number', '100', '', '', '', ''),
('risk_score_threshold', 'basic', '风控拦截阈值', '风控评分超过此值拦截奖励(0-100)', 'number', '70', '', '', '', '');

-- ============================================================================
-- 插入后台菜单
-- ============================================================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'videoreward', '视频收益管理', 'fa fa-money', '', '', '视频收益管理菜单', 1, NULL, '', 'spqxgl', 'shipinshouyiguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @parent_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'videoreward/videorewardrule', '收益规则', 'fa fa-circle-o', '', '', '', 1, NULL, '', 'sygz', 'shouyiguize', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'videoreward/videocollection', '视频合集', 'fa fa-list-alt', '', '', '', 1, NULL, '', 'sphj', 'shipinheji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'videoreward/videowatchrecord', '观看记录', 'fa fa-history', '', '', '', 1, NULL, '', 'gkjl', 'guankanjilu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'videoreward/rewardstat', '收益统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'sytj', 'shouyitongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'videoreward/anticheatlog', '防刷日志', 'fa fa-shield', '', '', '', 1, NULL, '', 'fsrz', 'fangshuarizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET FOREIGN_KEY_CHECKS = 1;
