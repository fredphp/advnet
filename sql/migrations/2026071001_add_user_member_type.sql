-- 系统会员类型字段迁移
-- 会员类型: 0=真实会员(默认), 1=系统会员(后台生成，用于发送系统任务)

SET NAMES utf8mb4;

ALTER TABLE `advn_user` ADD COLUMN `member_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '会员类型: 0=真实会员, 1=系统会员' AFTER `group_id`;

-- 添加索引，方便后台筛选系统会员
ALTER TABLE `advn_user` ADD INDEX `idx_member_type` (`member_type`);

-- 更新现有会员确保都是真实会员
UPDATE `advn_user` SET `member_type` = 0 WHERE `member_type` IS NULL OR `member_type` = 0;
