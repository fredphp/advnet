-- ============================================================
-- 新增信息流广告位ID等配置项
-- 执行：在 MySQL 中执行此 SQL
-- ============================================================

INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('feed_adpid', 'ad', '信息流广告位ID(adpid)', 'uni-ad 信息流广告的广告位ID，从 DCloud uni-ad 后台(https://uniad.dcloud.net.cn/)创建广告位后获取', 'string', '', '', '', '', '', ''),
('rewarded_video_adpid', 'ad', '激励视频广告位ID(adpid)', 'uni-ad 激励视频广告的广告位ID，从 DCloud uni-ad 后台创建广告位后获取', 'string', '', '', '', '', '', ''),
('feed_ad_count', 'ad', '红包页信息流广告数量', '红包群页面中信息流广告的展示数量（建议2-5条）', 'number', '', '3', '', 'required|integer|between:1,10', '', ''),
('reward_per_video', 'ad', '激励视频奖励金币', '完整观看一次激励视频广告获得的金币数量', 'number', '', '200', '', 'required|integer|between:1,10000', '', ''),
('rewarded_video_interval', 'ad', '激励视频推送间隔(秒)', '红包群页面每隔多少秒推送一条激励视频广告', 'number', '', '120', '', 'required|integer|between:30,600', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- ============================================================
-- 执行后操作：
-- 1. 清除缓存：rm -rf runtime/cache/*
-- 2. 刷新后台：常规管理 → 系统配置 → 广告配置标签 → 保存
-- ============================================================
