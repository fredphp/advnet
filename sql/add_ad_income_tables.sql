-- ============================================================
-- 广告变现闭环系统 - 数据库变更
-- 基于 advnet 项目现有结构扩展，不新增 user_wallet 表
-- 执行前请备份数据库
-- ============================================================

-- 1. 修改 advn_coin_account 表：新增广告待释放金币字段
ALTER TABLE `advn_coin_account`
    ADD COLUMN `ad_freeze_balance` DECIMAL(18,2) UNSIGNED DEFAULT '0.00' COMMENT '广告待释放金币（空闲钱包）' AFTER `today_earn_date`,
    ADD COLUMN `total_ad_income` DECIMAL(18,2) UNSIGNED DEFAULT '0.00' COMMENT '累计广告收益（金币）' AFTER `ad_freeze_balance`;

-- 2. 新增广告收益记录表
CREATE TABLE IF NOT EXISTS `advn_ad_income_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `ad_type` VARCHAR(20) NOT NULL DEFAULT 'feed' COMMENT '广告类型: feed=信息流, reward=激励视频',
    `adpid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '广告位ID',
    `ad_provider` VARCHAR(20) NOT NULL DEFAULT 'uniad' COMMENT '广告平台: uniad=uni-ad, csj=穿山甲, ylh=优量汇',
    `ad_source` VARCHAR(50) NOT NULL DEFAULT 'redbag_page' COMMENT '广告来源页面: redbag_page=红包群, task_page=任务页',
    `amount` DECIMAL(18,4) NOT NULL DEFAULT '0.0000' COMMENT '广告返回总金额（元）',
    `amount_coin` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '广告返回总金额（金币）',
    `platform_rate` DECIMAL(5,4) NOT NULL DEFAULT '0.3000' COMMENT '平台抽成比例',
    `platform_amount` DECIMAL(18,4) NOT NULL DEFAULT '0.0000' COMMENT '平台抽成金额（元）',
    `platform_amount_coin` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '平台抽成金额（金币）',
    `user_amount` DECIMAL(18,4) NOT NULL DEFAULT '0.0000' COMMENT '用户实际获得金额（元）',
    `user_amount_coin` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户实际获得金币',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0=待确认, 1=已确认, 2=已释放, 3=已失效',
    `transaction_id` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '广告联盟交易ID（防重复回调）',
    `ip` VARCHAR(45) NOT NULL DEFAULT '' COMMENT '回调IP',
    `user_agent` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户UA',
    `device_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '设备ID',
    `remark` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '备注',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_ad_type` (`ad_type`),
    KEY `idx_adpid` (`adpid`),
    KEY `idx_ad_provider` (`ad_provider`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_createtime` (`createtime`),
    KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告收益记录表';

-- 3. 新增广告红包表（系统生成，区别于现有 red_packet_task 的人工任务红包）
CREATE TABLE IF NOT EXISTS `advn_ad_red_packet` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '红包金额（金币）',
    `source` VARCHAR(20) NOT NULL DEFAULT 'ad_income' COMMENT '来源: ad_income=广告收益',
    `source_ids` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '来源收益记录ID列表(逗号分隔)',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态: 0=未领取, 1=已领取, 2=已过期',
    `claim_time` INT UNSIGNED DEFAULT NULL COMMENT '领取时间',
    `expire_time` INT UNSIGNED DEFAULT NULL COMMENT '过期时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_expire_time` (`expire_time`),
    KEY `idx_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告红包表（系统自动生成）';

-- 4. 在 advn_config 中添加广告配置（如果表已存在则手动执行）
-- 广告系统配置说明:
--   ad_income_enabled: 是否启用广告变现 (1=启用, 0=禁用)
--   ad_platform_rate: 平台抽成比例 (0.30 = 30%)
--   ad_settle_interval: 红包生成间隔(分钟) (30 = 每30分钟生成一次红包)
--   ad_min_redpacket_amount: 最小红包金额(金币) (100 = 不足100金币不生成红包)
--   ad_redpacket_expire_hours: 红包过期时间(小时) (48 = 48小时后过期)
--   ad_daily_reward_limit: 每日广告收益上限(金币) (50000)
--   ad_reward_per_feed: 每次信息流广告奖励(金币) (50)
--   ad_reward_per_video: 每次激励视频奖励(金币) (200)
--   ad_callback_secret: 回调签名密钥
--   ad_enabled_providers: 启用的广告平台 (uniad,csj,ylh)

-- ============================================================
-- 执行完成后请运行以下命令验证:
-- SHOW COLUMNS FROM advn_coin_account LIKE 'ad_freeze_balance';
-- SELECT COUNT(*) FROM advn_ad_income_log;
-- SELECT COUNT(*) FROM advn_ad_red_packet;
-- ============================================================
