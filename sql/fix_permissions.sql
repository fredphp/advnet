-- =============================================
-- 修复缺失的权限节点
-- 执行此脚本修复页面无法访问的问题
-- =============================================

-- 添加 member/user/statistics 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'member/user/statistics', '用户统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'member/user' LIMIT 1;

-- 添加 member/user/behaviors 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'member/user/behaviors', '用户行为', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'member/user' LIMIT 1;

-- 添加 withdraw/order/pending 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'withdraw/order/pending', '待审核', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'withdraw/order' LIMIT 1;

-- 添加 withdraw/order/statistics 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'withdraw/order/statistics', '提现统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'withdraw/order' LIMIT 1;

-- 添加 invite/statistic/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'invite/statistic/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'invite/statistic' LIMIT 1;

-- 添加 invite/relation/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'invite/relation/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'invite/relation' LIMIT 1;

-- 添加 invite/commission/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'invite/commission/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'invite/commission' LIMIT 1;

-- 添加 invite/commission/statistics 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'invite/commission/statistics', '统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'invite/commission' LIMIT 1;

-- 添加 risk/dashboard/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/dashboard/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/dashboard' LIMIT 1;

-- 添加 risk/userrisk/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/userrisk/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/userrisk' LIMIT 1;

-- 添加 risk/userrisk/ban 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/userrisk/ban', '封禁用户', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/userrisk' LIMIT 1;

-- 添加 risk/userrisk/release 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/userrisk/release', '解封用户', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/userrisk' LIMIT 1;

-- 添加 risk/userrisk/adjustScore 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/userrisk/adjustScore', '调整风险分', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/userrisk' LIMIT 1;

-- 添加 risk/rule/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/rule/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/rule' LIMIT 1;

-- 添加 risk/rule/add 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/rule/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/rule' LIMIT 1;

-- 添加 risk/rule/edit 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/rule/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/rule' LIMIT 1;

-- 添加 risk/rule/toggle 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/rule/toggle', '启用禁用', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/rule' LIMIT 1;

-- 添加 risk/banrecord/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/banrecord/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/banrecord' LIMIT 1;

-- 添加 risk/blacklist/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/blacklist/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/blacklist' LIMIT 1;

-- 添加 risk/blacklist/add 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/blacklist/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/blacklist' LIMIT 1;

-- 添加 risk/blacklist/edit 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'risk/blacklist/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'risk/blacklist' LIMIT 1;

-- 添加 video/collection/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'video/collection/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'video/collection' LIMIT 1;

-- 添加 video/watchrecord/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'video/watchrecord/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'video/watchrecord' LIMIT 1;

-- 添加 video/rewardrule/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'video/rewardrule/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'video/rewardrule' LIMIT 1;

-- 添加 redpacket/stat/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'redpacket/stat/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'redpacket/stat' LIMIT 1;

-- 添加 redpacket/participation/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'redpacket/participation/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'redpacket/participation' LIMIT 1;

-- 添加 redpacket/category/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'redpacket/category/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'redpacket/category' LIMIT 1;

-- 添加 redpacket/category/add 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'redpacket/category/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'redpacket/category' LIMIT 1;

-- 添加 redpacket/category/edit 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'redpacket/category/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'redpacket/category' LIMIT 1;

-- 添加 coin/account/index 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'coin/account/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'coin/account' LIMIT 1;

-- 添加 coin/account/summary 权限节点
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) 
SELECT 'file', id, 'coin/account/summary', '统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal' 
FROM `advn_auth_rule` WHERE `name` = 'coin/account' LIMIT 1;
