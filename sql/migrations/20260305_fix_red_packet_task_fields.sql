-- ============================================================================
-- 修复red_packet_task表缺失字段
-- 创建时间: 2026-03-05
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 添加缺失的统计字段
-- ----------------------------

-- 已发放金额
ALTER TABLE `advn_red_packet_task` ADD COLUMN `receive_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '已发放金额';

-- 已领取人数
ALTER TABLE `advn_red_packet_task` ADD COLUMN `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '已领取人数';

-- 待审核数量
ALTER TABLE `advn_red_packet_task` ADD COLUMN `audit_pending_count` INT UNSIGNED DEFAULT 0 COMMENT '待审核数量';

-- 已拒绝数量
ALTER TABLE `advn_red_packet_task` ADD COLUMN `audit_reject_count` INT UNSIGNED DEFAULT 0 COMMENT '已拒绝数量';

-- 浏览次数
ALTER TABLE `advn_red_packet_task` ADD COLUMN `view_count` INT UNSIGNED DEFAULT 0 COMMENT '浏览次数';

-- 参与次数
ALTER TABLE `advn_red_packet_task` ADD COLUMN `join_count` INT UNSIGNED DEFAULT 0 COMMENT '参与次数';

-- 完成次数
ALTER TABLE `advn_red_packet_task` ADD COLUMN `complete_count` INT UNSIGNED DEFAULT 0 COMMENT '完成次数';

-- 剩余金额
ALTER TABLE `advn_red_packet_task` ADD COLUMN `remain_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '剩余金额';

-- 剩余数量
ALTER TABLE `advn_red_packet_task` ADD COLUMN `remain_count` INT UNSIGNED DEFAULT 0 COMMENT '剩余数量';

-- 排序字段
ALTER TABLE `advn_red_packet_task` ADD COLUMN `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序';

-- 是否热门
ALTER TABLE `advn_red_packet_task` ADD COLUMN `is_hot` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否热门: 0=否, 1=是';

-- 是否推荐
ALTER TABLE `advn_red_packet_task` ADD COLUMN `is_recommend` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否推荐: 0=否, 1=是';

-- 最小金额
ALTER TABLE `advn_red_packet_task` ADD COLUMN `min_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最小金额(随机)';

-- 最大金额
ALTER TABLE `advn_red_packet_task` ADD COLUMN `max_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最大金额(随机)';

-- 金额类型
ALTER TABLE `advn_red_packet_task` ADD COLUMN `amount_type` VARCHAR(20) DEFAULT 'fixed' COMMENT '金额类型: fixed=固定, random=随机';

-- 验证参数
ALTER TABLE `advn_red_packet_task` ADD COLUMN `verify_params` TEXT DEFAULT NULL COMMENT '验证参数(JSON)';

-- 每日限制
ALTER TABLE `advn_red_packet_task` ADD COLUMN `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日限制领取次数(0=不限)';

-- 新用户限定
ALTER TABLE `advn_red_packet_task` ADD COLUMN `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是';
ALTER TABLE `advn_red_packet_task` ADD COLUMN `new_user_days` INT UNSIGNED DEFAULT 7 COMMENT '新用户定义(注册天数)';

-- 用户等级限制
ALTER TABLE `advn_red_packet_task` ADD COLUMN `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级';
ALTER TABLE `advn_red_packet_task` ADD COLUMN `user_level_max` TINYINT UNSIGNED DEFAULT 255 COMMENT '最高用户等级';

-- VIP限定
ALTER TABLE `advn_red_packet_task` ADD COLUMN `vip_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限VIP: 0=否, 1=是';

-- 审核超时
ALTER TABLE `advn_red_packet_task` ADD COLUMN `audit_timeout` INT UNSIGNED DEFAULT 86400 COMMENT '审核超时时间(秒)';

-- 截图要求
ALTER TABLE `advn_red_packet_task` ADD COLUMN `need_screenshot` TINYINT UNSIGNED DEFAULT 0 COMMENT '需要上传截图: 0=否, 1=是';
ALTER TABLE `advn_red_packet_task` ADD COLUMN `need_device_info` TINYINT UNSIGNED DEFAULT 1 COMMENT '需要设备信息: 0=否, 1=是';

-- 有效期
ALTER TABLE `advn_red_packet_task` ADD COLUMN `expire_hours` INT UNSIGNED DEFAULT 24 COMMENT '领取后有效期(小时)';

-- 关联信息
ALTER TABLE `advn_red_packet_task` ADD COLUMN `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: app/game/video/activity';
ALTER TABLE `advn_red_packet_task` ADD COLUMN `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联ID';

-- 任务图标和图片
ALTER TABLE `advn_red_packet_task` ADD COLUMN `icon` VARCHAR(500) DEFAULT NULL COMMENT '红包图标' AFTER `description`;
ALTER TABLE `advn_red_packet_task` ADD COLUMN `images` TEXT DEFAULT NULL COMMENT '任务宣传图(JSON数组)' AFTER `icon`;

-- 要求进度
ALTER TABLE `advn_red_packet_task` ADD COLUMN `required_progress` TINYINT UNSIGNED DEFAULT 100 COMMENT '要求进度(%)';
ALTER TABLE `advn_red_packet_task` ADD COLUMN `required_count` INT UNSIGNED DEFAULT 1 COMMENT '要求次数';

-- 分类ID
ALTER TABLE `advn_red_packet_task` ADD COLUMN `category_id` INT UNSIGNED DEFAULT NULL COMMENT '任务分类ID';

-- 添加索引
ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_is_hot` (`is_hot`);
ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_is_recommend` (`is_recommend`);
ALTER TABLE `advn_red_packet_task` ADD INDEX `idx_category` (`category_id`);
