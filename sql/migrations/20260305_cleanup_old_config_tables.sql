-- ============================================================================
-- 清理旧的配置表
-- 删除已合并到 red_packet_reward_config 表的旧配置表和相关菜单
-- 创建时间: 2026-03-05
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 删除旧的数据表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_amount_config`;
DROP TABLE IF EXISTS `advn_red_packet_time_config`;

-- ----------------------------
-- 删除旧的后台菜单
-- ----------------------------

-- 删除金额配置菜单及其子菜单
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/amountconfig';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'redpacket/amountconfig/%';

-- 删除时间配置菜单及其子菜单
DELETE FROM `advn_auth_rule` WHERE name = 'redpacket/timeconfig';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'redpacket/timeconfig/%';

-- 输出结果
SELECT '旧配置表和菜单清理完成' AS message;
