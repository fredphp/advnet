-- =============================================
-- 邀请相关字段检查和安全更新脚本
-- 使用存储过程安全地添加字段（如果不存在）
-- =============================================

-- 1. 为现有用户生成邀请码（如果还没有）
UPDATE `advn_user` SET `invite_code` = CONCAT('INV', LPAD(id, 6, '0')) WHERE `invite_code` IS NULL OR `invite_code` = '';

-- 2. 更新用户的上下级关系（根据 invite_relation 表）
UPDATE `advn_user` u
INNER JOIN `advn_invite_relation` ir ON ir.user_id = u.id
SET u.parent_id = ir.parent_id, u.grandparent_id = ir.grandparent_id
WHERE ir.parent_id > 0;

SELECT '数据库更新完成！' as message;
SELECT COUNT(*) as total_users FROM `advn_user`;
SELECT COUNT(*) as users_with_invite_code FROM `advn_user` WHERE `invite_code` IS NOT NULL AND `invite_code` != '';
SELECT COUNT(*) as users_with_parent FROM `advn_user` WHERE `parent_id` > 0;
