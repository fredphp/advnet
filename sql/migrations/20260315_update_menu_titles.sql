-- =====================================================
-- 菜单标题中文化迁移
-- 更新菜单标题为中文
-- 执行时间: 2026-03-15
-- =====================================================

-- 更新常规管理下的系统配置菜单标题为中文
UPDATE `advn_auth_rule` SET `title` = '系统配置' WHERE `name` = 'general/config';

-- 删除独立的系统设置菜单
DELETE FROM `advn_auth_rule` WHERE `name` = 'setting';
DELETE FROM `advn_auth_rule` WHERE `name` LIKE 'setting/%';

-- 删除提现模块下的提现配置子菜单
DELETE FROM `advn_auth_rule` WHERE `name` = 'withdraw/config';

-- 删除迁移模块下的迁移配置子菜单
DELETE FROM `advn_auth_rule` WHERE `name` = 'migration/config';
