-- ============================================================================
-- 添加 relation_table 字段到 coin_log 表
-- 用于存储关联数据所在的分表名，解决分表后 ID 不唯一的问题
-- 创建时间：2026-03-09
-- ============================================================================

SET NAMES utf8mb4;

-- 说明：
-- 1. 如果使用分表，主表可能不存在
-- 2. 此脚本会尝试添加字段到所有分表
-- 3. 如果字段已存在会报错，可忽略

-- 添加字段到分表 (根据实际存在的分表)
-- 表前缀为 advn_

-- 2026年3月分表
ALTER TABLE `advn_coin_log_202603` ADD COLUMN `relation_table` varchar(50) DEFAULT '' COMMENT '关联数据所在分表' AFTER `relation_id`;

-- 如果有其他月份的分表，请添加类似的语句：
-- ALTER TABLE `advn_coin_log_202602` ADD COLUMN `relation_table` varchar(50) DEFAULT '' COMMENT '关联数据所在分表' AFTER `relation_id`;
-- ALTER TABLE `advn_coin_log_202601` ADD COLUMN `relation_table` varchar(50) DEFAULT '' COMMENT '关联数据所在分表' AFTER `relation_id`;

-- 查看结果
SELECT '迁移完成，请确认字段已添加' AS message;
