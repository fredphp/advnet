-- =============================================
-- 快速修复：为 user 表添加 source 字段
-- 请在数据库中执行此脚本
-- =============================================

-- 添加 source 字段
ALTER TABLE `advn_user` 
ADD COLUMN `source` VARCHAR(50) NULL DEFAULT 'default' COMMENT '注册来源: default-默认, wechat-微信, alipay-支付宝, invite-邀请注册, app-APP注册' AFTER `status`;

-- 添加索引
ALTER TABLE `advn_user` ADD INDEX `idx_source` (`source`);

-- 更新现有数据
UPDATE `advn_user` SET `source` = 'default' WHERE `source` IS NULL OR `source` = '';
