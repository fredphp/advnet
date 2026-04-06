-- =====================================================
-- 广告浏览计数器表（实现"浏览N次广告后发放奖励"）
-- 创建时间：2025-08-04
-- =====================================================
CREATE TABLE IF NOT EXISTS `advn_ad_view_counter` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `ad_type` VARCHAR(20) NOT NULL DEFAULT 'feed' COMMENT '广告类型: feed=信息流, reward=激励视频',
    `view_date` DATE NOT NULL COMMENT '浏览日期（每天一条记录，天然重置）',
    `view_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当前轮已浏览次数（达到阈值后重置为0）',
    `reward_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日已领奖次数',
    `createtime` INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY `uk_user_type_date` (`user_id`, `ad_type`, `view_date`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_view_date` (`view_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告浏览计数器（按天自动重置）';

-- =====================================================
-- 新增广告配置项（插入 advn_config 表）
-- feed_reward_threshold: 信息流浏览N次后发放奖励（默认5次）
-- video_reward_threshold: 激励视频观看N次后发放奖励（默认3次）
-- =====================================================
INSERT INTO `advn_config` (`name`, `group`, `title`, `type`, `value`, `remark`, `weigh`, `status`, `updatetime`, `createtime`) VALUES
('feed_reward_threshold', 'ad', '信息流奖励阈值（次）', 'number', '5', '用户浏览多少次信息流广告后发放一次奖励，0=每次都发', 310, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

INSERT INTO `advn_config` (`name`, `group`, `title`, `type`, `value`, `remark`, `weigh`, `status`, `updatetime`, `createtime`) VALUES
('video_reward_threshold', 'ad', '激励视频奖励阈值（次）', 'number', '3', '用户观看多少次激励视频后发放一次奖励，0=每次都发', 311, 'normal', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();
