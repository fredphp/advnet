-- =============================================
-- FastAdmin 后台菜单和权限配置
-- 短视频金币平台管理系统
-- =============================================

SET NAMES utf8mb4;

-- =============================================
-- 一级菜单
-- =============================================

-- 仪表盘
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'dashboard', '控制台', 'fa fa-dashboard', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'normal');

-- 用户管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'member', '用户管理', 'fa fa-users', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal');

-- 视频管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'video', '视频管理', 'fa fa-video-camera', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal');

-- 红包管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'redpacket', '红包管理', 'fa fa-envelope', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 85, 'normal');

-- 提现管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'withdraw', '提现管理', 'fa fa-money', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal');

-- 金币管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'coin', '金币管理', 'fa fa-diamond', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 75, 'normal');

-- 邀请管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'invite', '邀请管理', 'fa fa-share-alt', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 70, 'normal');

-- 风控管理
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'risk', '风控管理', 'fa fa-shield', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 65, 'normal');

-- 系统设置
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'setting', '系统设置', 'fa fa-cogs', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 60, 'normal');

-- =============================================
-- 获取一级菜单ID（需要执行后获取）
-- =============================================
-- SET @dashboard_id = (SELECT id FROM advn_auth_rule WHERE name = 'dashboard' LIMIT 1);
-- SET @member_id = (SELECT id FROM advn_auth_rule WHERE name = 'member' LIMIT 1);
-- SET @video_id = (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1);
-- SET @redpacket_id = (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1);
-- SET @withdraw_id = (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1);
-- SET @coin_id = (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1);
-- SET @invite_id = (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1);
-- SET @risk_id = (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1);
-- SET @setting_id = (SELECT id FROM advn_auth_rule WHERE name = 'setting' LIMIT 1);

-- =============================================
-- 二级菜单 - 用户管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member' LIMIT 1) tmp), 'member/user', '用户列表', 'fa fa-user', 'member/user', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member' LIMIT 1) tmp), 'member/statistics', '用户统计', 'fa fa-bar-chart', 'member/user/statistics', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal');

-- 用户管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/detail', '详情', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/status', '修改状态', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/recharge', '充值金币', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/deduct', '扣除金币', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member/user' LIMIT 1) tmp), 'member/user/export', '导出', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 视频管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/video', '视频列表', 'fa fa-list', 'video/video', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/collection', '视频合集', 'fa fa-folder', 'video/collection', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/watchrecord', '观看记录', 'fa fa-eye', 'video/watchrecord', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/rewardrule', '奖励规则', 'fa fa-gift', 'video/rewardrule', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- 视频管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/del', '删除', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/status', '上下架', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video/video' LIMIT 1) tmp), 'video/video/stats', '统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 红包管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/task', '红包任务', 'fa fa-tasks', 'redpacket/task', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/participation', '领取记录', 'fa fa-list-alt', 'redpacket/participation', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/category', '红包分类', 'fa fa-tags', 'redpacket/category', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/stat', '红包统计', 'fa fa-bar-chart', 'redpacket/stat', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- 红包管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/del', '删除', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/publish', '发布', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/task' LIMIT 1) tmp), 'redpacket/task/revoke', '撤回', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 提现管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/order', '提现订单', 'fa fa-list', 'withdraw/order', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/pending', '待审核', 'fa fa-clock-o', 'withdraw/order/pending', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/statistics', '提现统计', 'fa fa-bar-chart', 'withdraw/order/statistics', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal');

-- 提现管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/detail', '详情', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/approve', '审核通过', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/reject', '审核拒绝', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/complete', '确认打款', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/batchApprove', '批量审核', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/batchPay', '批量打款', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw/order' LIMIT 1) tmp), 'withdraw/order/export', '导出', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 金币管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/log', '金币流水', 'fa fa-history', 'coin/log', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/account', '金币账户', 'fa fa-bank', 'coin/account', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/statistics', '金币统计', 'fa fa-bar-chart', 'coin/log/statistics', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal');

-- 金币管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin/log' LIMIT 1) tmp), 'coin/log/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin/log' LIMIT 1) tmp), 'coin/log/statistics', '统计', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin/log' LIMIT 1) tmp), 'coin/log/export', '导出', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 邀请管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/statistic', '邀请统计', 'fa fa-bar-chart', 'invite/statistic', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/relation', '邀请关系', 'fa fa-sitemap', 'invite/relation', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/commission', '分佣记录', 'fa fa-money', 'invite/commission', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/config', '分佣配置', 'fa fa-cog', 'invite/config', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- 邀请管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite/config' LIMIT 1) tmp), 'invite/config/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite/config' LIMIT 1) tmp), 'invite/config/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite/config' LIMIT 1) tmp), 'invite/config/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite/config' LIMIT 1) tmp), 'invite/config/toggle', '启用禁用', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 风控管理
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/dashboard', '风控仪表盘', 'fa fa-dashboard', 'risk/dashboard', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/rule', '风控规则', 'fa fa-gavel', 'risk/rule', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/userrisk', '用户风险', 'fa fa-user-secret', 'risk/userrisk', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/banrecord', '封禁记录', 'fa fa-ban', 'risk/banrecord', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/blacklist', '黑白名单', 'fa fa-list', 'risk/blacklist', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 6, 'normal');

-- 风控管理权限节点
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/rule' LIMIT 1) tmp), 'risk/rule/index', '查看', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/rule' LIMIT 1) tmp), 'risk/rule/add', '添加', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/rule' LIMIT 1) tmp), 'risk/rule/edit', '编辑', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/rule' LIMIT 1) tmp), 'risk/rule/toggle', '启用禁用', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/userrisk' LIMIT 1) tmp), 'risk/userrisk/ban', '封禁用户', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/userrisk' LIMIT 1) tmp), 'risk/userrisk/release', '解封用户', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk/userrisk' LIMIT 1) tmp), 'risk/userrisk/adjustScore', '调整风险分', '', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- =============================================
-- 二级菜单 - 系统设置
-- =============================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'setting' LIMIT 1) tmp), 'setting/config/coin', '金币配置', 'fa fa-diamond', 'setting/config/coin', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'setting' LIMIT 1) tmp), 'setting/config/withdraw', '提现配置', 'fa fa-money', 'setting/config/withdraw', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'setting' LIMIT 1) tmp), 'setting/config/invite', '邀请配置', 'fa fa-share-alt', 'setting/config/invite', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'setting' LIMIT 1) tmp), 'setting/config/risk', '风控配置', 'fa fa-shield', 'setting/config/risk', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- =============================================
-- 创建管理员角色
-- =============================================
INSERT INTO `advn_auth_group` (`pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES
(0, '运营管理员', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(0, '财务管理员', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(0, '风控管理员', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal');

-- =============================================
-- 角色权限分配说明
-- =============================================
/*
运营管理员权限：
- member/user (用户管理)
- video (视频管理)
- redpacket (红包管理)
- invite (邀请管理)
- setting/config (配置管理-查看)

财务管理员权限：
- withdraw/order (提现管理)
- coin (金币管理)
- setting/config/withdraw (提现配置)
- setting/config/coin (金币配置)

风控管理员权限：
- risk (风控管理全部权限)
- member/user/status (用户状态修改)
- setting/config/risk (风控配置)

超级管理员：所有权限
*/
