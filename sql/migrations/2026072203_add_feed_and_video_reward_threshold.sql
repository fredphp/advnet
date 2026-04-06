-- ============================================================
-- 数据迁移：添加信息流/激励视频广告奖励阈值配置项
-- 日期：2026-07-22
-- 说明：在 advn_config 表中添加 feed_reward_threshold 和
--       video_reward_threshold 配置项，用于控制广告浏览奖励
--       的触发阈值（阈值=1 表示每次浏览都发放奖励）
-- ============================================================

-- 1. 添加信息流广告奖励阈值配置
-- 默认值：1（每次浏览信息流广告都发放奖励）
-- 旧逻辑默认值为5（浏览5次才批量发放），现已改为每次发放
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`)
SELECT 'feed_reward_threshold', 'ad', '信息流奖励阈值（次）', '用户浏览多少次信息流广告后发放一次奖励，设置为0或1表示每次浏览都发放奖励', 'number', '', '1', '', 'required|integer|gte:0', '', NULL
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `advn_config` WHERE `name` = 'feed_reward_threshold' AND `group` = 'ad'
);

-- 2. 添加激励视频广告奖励阈值配置
-- 默认值：1（每次观看激励视频都发放奖励）
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`)
SELECT 'video_reward_threshold', 'ad', '激励视频奖励阈值（次）', '用户观看多少次激励视频后发放一次奖励，设置为0或1表示每次观看都发放奖励', 'number', '', '1', '', 'required|integer|gte:0', '', NULL
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `advn_config` WHERE `name` = 'video_reward_threshold' AND `group` = 'ad'
);
