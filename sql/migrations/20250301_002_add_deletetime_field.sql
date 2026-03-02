-- =============================================
-- 为相关表添加软删除字段
-- 版本：20250301_002
-- 修复：使用存储过程检查表和字段是否存在
-- =============================================

-- 创建辅助存储过程：添加字段（如果表存在且字段不存在）
DROP PROCEDURE IF EXISTS add_deletetime_if_exists;

DELIMITER //
CREATE PROCEDURE add_deletetime_if_exists(IN table_name VARCHAR(100))
BEGIN
    DECLARE table_count INT DEFAULT 0;
    DECLARE column_count INT DEFAULT 0;

    -- 检查表是否存在
    SELECT COUNT(*) INTO table_count
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = table_name;

    IF table_count > 0 THEN
        -- 检查字段是否已存在
        SELECT COUNT(*) INTO column_count
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = table_name
        AND column_name = 'deletetime';

        IF column_count = 0 THEN
            SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN `deletetime` BIGINT(16) DEFAULT NULL COMMENT "删除时间" AFTER `updatetime`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- 添加索引
            SET @idx_sql = CONCAT('ALTER TABLE `', table_name, '` ADD INDEX `idx_deletetime` (`deletetime`)');
            PREPARE idx_stmt FROM @idx_sql;
            EXECUTE idx_stmt;
            DEALLOCATE PREPARE idx_stmt;
        END IF;
    END IF;
END //
DELIMITER ;

-- 1. 视频相关表
CALL add_deletetime_if_exists('advn_video');
CALL add_deletetime_if_exists('advn_video_collection');
CALL add_deletetime_if_exists('advn_video_reward_rule');

-- 2. 邀请分佣相关表
CALL add_deletetime_if_exists('advn_invite_relation');
CALL add_deletetime_if_exists('advn_invite_commission_config');
CALL add_deletetime_if_exists('advn_invite_commission_log');

-- 3. 风控相关表
CALL add_deletetime_if_exists('advn_risk_rule');
CALL add_deletetime_if_exists('advn_blacklist');
CALL add_deletetime_if_exists('advn_whitelist');

-- 4. 红包任务相关表
CALL add_deletetime_if_exists('advn_red_packet_task');
CALL add_deletetime_if_exists('advn_task_category');
CALL add_deletetime_if_exists('advn_task_participation');

-- 5. 提现相关表
CALL add_deletetime_if_exists('advn_withdraw_order');
CALL add_deletetime_if_exists('advn_withdraw_config');

-- 6. 其他表
CALL add_deletetime_if_exists('advn_coin_account');
CALL add_deletetime_if_exists('advn_user_behavior_stat');

-- 删除存储过程
DROP PROCEDURE IF EXISTS add_deletetime_if_exists;
