-- =============================================
-- 删除分佣配置菜单脚本
-- 分佣配置已移至 advn_config 表管理
-- =============================================

-- 删除 invite/config 菜单及其子菜单
DELETE FROM `advn_auth_rule` WHERE `name` LIKE 'invite/config%';

-- 显示删除结果
SELECT '菜单删除完成' as message;
