-- ============================================================
-- 广告收益表分表 - 数据库变更
-- 将 advn_ad_income_log 和 advn_ad_red_packet 按月分表
-- 执行前请备份数据库
-- ============================================================

-- 说明：
-- 1. 原始主表 advn_ad_income_log 和 advn_ad_red_packet 保留不动（存历史数据）
-- 2. 新数据通过 AdIncomeLogSplit / AdRedPacketSplit 写入月度分表
-- 3. 分表格式：advn_ad_income_log_202507, advn_ad_red_packet_202507
-- 4. 查询通过分表模型的跨表查询方法自动路由

-- =====================================================
-- 一、创建当月和未来2个月的分表
-- =====================================================

-- ad_income_log 分表
CREATE TABLE IF NOT EXISTS `advn_ad_income_log_202507` LIKE `advn_ad_income_log`;
CREATE TABLE IF NOT EXISTS `advn_ad_income_log_202508` LIKE `advn_ad_income_log`;
CREATE TABLE IF NOT EXISTS `advn_ad_income_log_202509` LIKE `advn_ad_income_log`;

-- ad_red_packet 分表
CREATE TABLE IF NOT EXISTS `advn_ad_red_packet_202507` LIKE `advn_ad_red_packet`;
CREATE TABLE IF NOT EXISTS `advn_ad_red_packet_202508` LIKE `advn_ad_red_packet`;
CREATE TABLE IF NOT EXISTS `advn_ad_red_packet_202509` LIKE `advn_ad_red_packet`;

-- =====================================================
-- 二、手动创建分表（如果上面已存在会自动跳过）
-- 也可通过命令创建：
--   php think split:create-tables --type=adincome --months=3
--   php think split:create-tables --type=adredpacket --months=3
-- =====================================================

-- =====================================================
-- 执行后操作：
-- 1. 清除缓存：rm -rf runtime/cache/* runtime/temp/*
-- 2. 确认分表已创建：SHOW TABLES LIKE 'advn_ad_income_log_%';
-- 3. SplitTableService 会每天自动检查并创建未来分表
-- 4. 也可通过 crontab 定期创建：
--    0 0 1 * * cd /path/to/advnet && php think split:create-tables --months=3 >> /dev/null 2>&1
-- =====================================================
