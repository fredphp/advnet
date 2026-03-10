-- =============================================
-- 邀请相关数据安全更新脚本
-- 执行此脚本不会报错（字段已存在时会跳过）
-- =============================================

-- 1. 为现有用户生成邀请码（如果还没有）
UPDATE `advn_user` SET `invite_code` = CONCAT('INV', LPAD(id, 6, '0')) WHERE `invite_code` IS NULL OR `invite_code` = '';

-- 2. 更新用户的上下级关系（根据 invite_relation 表）
UPDATE `advn_user` u
INNER JOIN `advn_invite_relation` ir ON ir.user_id = u.id
SET u.parent_id = ir.parent_id, u.grandparent_id = ir.grandparent_id
WHERE ir.parent_id > 0;

-- 3. 显示更新结果
SELECT '=== 数据库更新完成 ===' as message;
SELECT 
    COUNT(*) as '总用户数',
    SUM(CASE WHEN invite_code IS NOT NULL AND invite_code != '' THEN 1 ELSE 0 END) as '有邀请码的用户',
    SUM(CASE WHEN parent_id > 0 THEN 1 ELSE 0 END) as '有上级的用户'
FROM `advn_user`;
