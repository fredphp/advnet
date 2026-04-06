-- ============================================================
-- 红包基数额度自动发红包 - 数据库变更
-- 功能：当用户 ad_freeze_balance 达到配置的红包基数额度时，自动生成红包
-- 执行前请备份数据库
-- ============================================================

-- 1. 在广告配置分组中新增"红包基数额度"配置项
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('redpacket_threshold', 'ad', '红包基数额度(金币)', '用户广告待释放余额达到此额度时自动生成红包（设置为0则不启用自动发红包）', 'number', '', '1000', '', 'required|integer|gte:0', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- ============================================================
-- 执行后操作：
-- 1. 清除后台缓存：rm -rf runtime/cache/*
-- 2. 在后台 → 常规管理 → 系统配置 → 广告配置 中可以看到新增的"红包基数额度"
-- 3. 工作原理：
--    a) 用户观看广告 → 回调接口 → 奖励金币写入 ad_freeze_balance
--    b) 回调成功后实时检测 ad_freeze_balance 是否 >= redpacket_threshold
--    c) 达到阈值 → 立即将冻结余额转为红包
--    d) 未达到阈值 → 等待下次广告回调继续累加
--    e) 定时任务 ad:settle 作为兜底，每30分钟检查一次
-- ============================================================
