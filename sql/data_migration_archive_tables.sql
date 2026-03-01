-- ============================================================================
-- 数据归档表结构
-- 短视频金币平台 - 历史冷数据归档表
-- ============================================================================
-- 说明:
-- 1. 归档表用于存储历史冷数据，减少主表数据量，提高查询性能
-- 2. 归档表命名规则: 原表名_archive
-- 3. 归档表结构与原表相同，但不包含自增约束
-- 4. 可以通过数据迁移命令自动创建归档表
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 金币流水归档表 (advn_coin_log_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_coin_log_archive`;
CREATE TABLE `advn_coin_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '主键ID',
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
    KEY `idx_create_time` (`createtime`),
    KEY `idx_user_date` (`user_id`, `create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币流水归档表';

-- ============================================================================
-- 2. 视频观看记录归档表 (advn_video_watch_record_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_watch_record_archive`;
CREATE TABLE `advn_video_watch_record_archive` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
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
    KEY `idx_user_id` (`user_id`),
    KEY `idx_video_id` (`video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频观看记录归档表';

-- ============================================================================
-- 3. 观看会话归档表 (advn_video_watch_session_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_video_watch_session_archive`;
CREATE TABLE `advn_video_watch_session_archive` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
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
    KEY `idx_user_video` (`user_id`, `video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='观看会话归档表';

-- ============================================================================
-- 4. 风控日志归档表 (advn_risk_log_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_risk_log_archive`;
CREATE TABLE `advn_risk_log_archive` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `rule_code` VARCHAR(50) NOT NULL COMMENT '触发的规则编码',
    `rule_name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `rule_type` ENUM('video','task','withdraw','redpacket','invite','global') NOT NULL COMMENT '规则类型',
    `risk_level` TINYINT UNSIGNED NOT NULL COMMENT '风险等级',
    `trigger_value` DECIMAL(15,4) NOT NULL COMMENT '触发值',
    `threshold` DECIMAL(15,4) NOT NULL COMMENT '阈值',
    `score_add` INT UNSIGNED NOT NULL COMMENT '增加的风险分',
    `action` ENUM('warn','block','freeze','ban') NOT NULL COMMENT '执行动作',
    `action_duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '动作持续时间',
    `action_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '动作到期时间',
    `device_id` VARCHAR(64) DEFAULT '' COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `request_data` TEXT COMMENT '请求数据JSON',
    `response_data` TEXT COMMENT '响应数据JSON',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_rule_code` (`rule_code`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控日志归档表';

-- ============================================================================
-- 5. 用户行为记录归档表 (advn_user_behavior_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_user_behavior_archive`;
CREATE TABLE `advn_user_behavior_archive` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `behavior_type` ENUM('login','video_watch','task_complete','withdraw','redpacket_grab','invite','other') NOT NULL COMMENT '行为类型',
    `behavior_action` VARCHAR(50) NOT NULL COMMENT '行为动作',
    `target_id` INT UNSIGNED DEFAULT NULL COMMENT '目标ID',
    `target_type` VARCHAR(50) DEFAULT '' COMMENT '目标类型',
    `device_id` VARCHAR(64) DEFAULT '' COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '行为持续时间(秒)',
    `extra_data` TEXT COMMENT '额外数据JSON',
    `risk_flag` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否标记为风险',
    `risk_reason` VARCHAR(255) DEFAULT '' COMMENT '风险原因',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_behavior_type` (`behavior_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录归档表';

-- ============================================================================
-- 6. 防刷日志归档表 (advn_anticheat_log_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_anticheat_log_archive`;
CREATE TABLE `advn_anticheat_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '类型: abnormal_speed/hourly_watch_exceed/high_risk_score',
    `data` TEXT DEFAULT NULL COMMENT '详细数据(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='防刷日志归档表';

-- ============================================================================
-- 7. 红包领取记录归档表 (advn_red_packet_record_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_red_packet_record_archive`;
CREATE TABLE `advn_red_packet_record_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '记录ID',
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
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包领取记录归档表';

-- ============================================================================
-- 8. 邀请分佣日志归档表 (advn_invite_commission_log_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_invite_commission_log_archive`;
CREATE TABLE `advn_invite_commission_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '获得佣金的用户ID',
    `from_user_id` INT UNSIGNED NOT NULL COMMENT '贡献佣金的用户ID',
    `order_type` VARCHAR(30) NOT NULL COMMENT '订单类型: video/task/withdraw',
    `order_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
    `order_no` VARCHAR(64) DEFAULT NULL COMMENT '关联订单号',
    `order_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '订单金额(金币)',
    `commission_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '佣金比例',
    `commission_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '佣金金额(金币)',
    `commission_level` TINYINT UNSIGNED DEFAULT 1 COMMENT '佣金等级: 1=一级, 2=二级',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待结算, 1=已结算, 2=已取消, 3=已冻结',
    `settle_time` INT UNSIGNED DEFAULT NULL COMMENT '结算时间',
    `cancel_reason` VARCHAR(200) DEFAULT NULL COMMENT '取消原因',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_from_user_id` (`from_user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请分佣日志归档表';

-- ============================================================================
-- 9. 微信打款日志归档表 (advn_wechat_transfer_log_archive)
-- ============================================================================
DROP TABLE IF EXISTS `advn_wechat_transfer_log_archive`;
CREATE TABLE `advn_wechat_transfer_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '微信转账单号',
    `partner_trade_no` VARCHAR(64) DEFAULT NULL COMMENT '商户订单号',
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '收款用户openid',
    `amount` INT UNSIGNED DEFAULT 0 COMMENT '转账金额(分)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '转账描述',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据(JSON)',
    `response_data` TEXT DEFAULT NULL COMMENT '响应数据(JSON)',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待处理, 1=处理中, 2=成功, 3=失败',
    `error_code` VARCHAR(50) DEFAULT NULL COMMENT '错误码',
    `error_msg` VARCHAR(200) DEFAULT NULL COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信打款日志归档表';

-- ============================================================================
-- 10. 数据迁移配置表 (advn_data_migration_config)
-- ============================================================================
DROP TABLE IF EXISTS `advn_data_migration_config`;
CREATE TABLE `advn_data_migration_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `table_name` VARCHAR(50) NOT NULL COMMENT '表名',
    `archive_days` INT UNSIGNED DEFAULT 90 COMMENT '归档天数',
    `batch_size` INT UNSIGNED DEFAULT 1000 COMMENT '批处理数量',
    `delete_source` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否删除源数据',
    `auto_archive` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否自动归档',
    `archive_schedule` VARCHAR(50) DEFAULT NULL COMMENT '归档计划(cron表达式)',
    `last_archive_time` INT UNSIGNED DEFAULT NULL COMMENT '最后归档时间',
    `last_archive_count` INT UNSIGNED DEFAULT 0 COMMENT '最后归档数量',
    `total_archive_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '累计归档数量',
    `enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否启用',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移配置表';

-- ============================================================================
-- 插入默认迁移配置
-- ============================================================================
INSERT INTO `advn_data_migration_config` (`table_name`, `archive_days`, `batch_size`, `delete_source`, `auto_archive`, `archive_schedule`, `enabled`, `createtime`, `updatetime`) VALUES
('coin_log', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('video_watch_record', 180, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('video_watch_session', 30, 1000, 1, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk_log', 180, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('user_behavior', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('anticheat_log', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('red_packet_record', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite_commission_log', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat_transfer_log', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 11. 数据迁移日志表 (advn_data_migration_log)
-- ============================================================================
DROP TABLE IF EXISTS `advn_data_migration_log`;
CREATE TABLE `advn_data_migration_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `table_name` VARCHAR(50) NOT NULL COMMENT '表名',
    `action` VARCHAR(30) NOT NULL COMMENT '操作类型: migrate/clean/stats',
    `total_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '总数据量',
    `migrated_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '迁移数量',
    `failed_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '失败数量',
    `deleted_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '删除数量',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '执行时长(秒)',
    `params` TEXT COMMENT '执行参数JSON',
    `result` TEXT COMMENT '执行结果JSON',
    `error` TEXT COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_table_name` (`table_name`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移日志表';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 使用说明:
-- ============================================================================
-- 1. 查看数据统计:
--    php think data:migrate --action=stats
--
-- 2. 迁移指定表数据:
--    php think data:migrate --action=coin_log --days=90
--
-- 3. 迁移并删除源数据:
--    php think data:migrate --action=coin_log --days=90 --delete
--
-- 4. 执行所有迁移:
--    php think data:migrate --action=all --days=90
--
-- 5. 定时任务配置 (crontab):
--    0 3 * * * cd /path/to/project && php think data:migrate --action=all > /dev/null 2>&1
-- ============================================================================
