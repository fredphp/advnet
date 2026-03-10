-- ============================================================================
-- 清理 advn_red_packet_task 表中未使用的冗余字段
-- 执行时间: 2026-03-05
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 1. 删除索引 (如果已存在)
-- ----------------------------
-- 注意：先检查索引是否存在再删除，避免报错
-- 使用存储过程或直接执行以下语句（如果索引存在的话）

-- 尝试删除索引（如果存在）
SET @dbname = DATABASE();
SET @tablename = 'advn_red_packet_task';

-- 删除 idx_is_hot 索引
SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = @dbname AND table_name = @tablename AND index_name = 'idx_is_hot');
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP INDEX `idx_is_hot`', 'SELECT "idx_is_hot not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 idx_is_recommend 索引
SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = @dbname AND table_name = @tablename AND index_name = 'idx_is_recommend');
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP INDEX `idx_is_recommend`', 'SELECT "idx_is_recommend not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 idx_category 索引
SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = @dbname AND table_name = @tablename AND index_name = 'idx_category');
SET @sql = IF(@index_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP INDEX `idx_category`', 'SELECT "idx_category not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 2. 删除统计相关字段
-- ----------------------------
-- 使用存储过程来安全删除列

-- 删除 receive_amount 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'receive_amount');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `receive_amount`', 'SELECT "receive_amount not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 receive_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'receive_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `receive_count`', 'SELECT "receive_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 audit_pending_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'audit_pending_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `audit_pending_count`', 'SELECT "audit_pending_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 audit_reject_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'audit_reject_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `audit_reject_count`', 'SELECT "audit_reject_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 view_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'view_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `view_count`', 'SELECT "view_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 join_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'join_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `join_count`', 'SELECT "join_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 complete_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'complete_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `complete_count`', 'SELECT "complete_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 remain_amount 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'remain_amount');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `remain_amount`', 'SELECT "remain_amount not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 remain_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'remain_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `remain_count`', 'SELECT "remain_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 3. 删除配置与限制相关字段
-- ----------------------------
-- 删除 sort 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'sort');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `sort`', 'SELECT "sort not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 is_hot 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'is_hot');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `is_hot`', 'SELECT "is_hot not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 is_recommend 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'is_recommend');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `is_recommend`', 'SELECT "is_recommend not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 min_amount 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'min_amount');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `min_amount`', 'SELECT "min_amount not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 max_amount 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'max_amount');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `max_amount`', 'SELECT "max_amount not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 amount_type 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'amount_type');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `amount_type`', 'SELECT "amount_type not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 verify_params 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'verify_params');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `verify_params`', 'SELECT "verify_params not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 daily_limit 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'daily_limit');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `daily_limit`', 'SELECT "daily_limit not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 new_user_only 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'new_user_only');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `new_user_only`', 'SELECT "new_user_only not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 new_user_days 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'new_user_days');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `new_user_days`', 'SELECT "new_user_days not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 user_level_min 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'user_level_min');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `user_level_min`', 'SELECT "user_level_min not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 user_level_max 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'user_level_max');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `user_level_max`', 'SELECT "user_level_max not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 vip_only 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'vip_only');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `vip_only`', 'SELECT "vip_only not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 audit_timeout 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'audit_timeout');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `audit_timeout`', 'SELECT "audit_timeout not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 need_screenshot 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'need_screenshot');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `need_screenshot`', 'SELECT "need_screenshot not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 need_device_info 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'need_device_info');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `need_device_info`', 'SELECT "need_device_info not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 expire_hours 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'expire_hours');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `expire_hours`', 'SELECT "expire_hours not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 relation_type 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'relation_type');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `relation_type`', 'SELECT "relation_type not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 relation_id 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'relation_id');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `relation_id`', 'SELECT "relation_id not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 required_progress 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'required_progress');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `required_progress`', 'SELECT "required_progress not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 required_count 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'required_count');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `required_count`', 'SELECT "required_count not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 category_id 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'category_id');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `category_id`', 'SELECT "category_id not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 4. 删除媒体资源字段
-- ----------------------------
-- 删除 icon 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'icon');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `icon`', 'SELECT "icon not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 images 字段
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = 'images');
SET @sql = IF(@col_exists > 0, 'ALTER TABLE `advn_red_packet_task` DROP COLUMN `images`', 'SELECT "images not exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 完成提示
-- ----------------------------
SELECT 'Migration completed successfully!' AS message;
