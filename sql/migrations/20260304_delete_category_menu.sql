-- ============================================================================
-- 删除红包分类菜单
-- 分类功能未使用，删除相关菜单
-- ============================================================================

SET NAMES utf8mb4;

-- 获取分类菜单ID
SET @category_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket/category' LIMIT 1);

-- 删除子菜单
DELETE FROM `advn_auth_rule` WHERE pid = @category_id;

-- 删除分类菜单
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/category';

-- 输出结果
SELECT '分类菜单已删除' AS message;
