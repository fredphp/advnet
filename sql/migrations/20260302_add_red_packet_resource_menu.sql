-- 红包资源管理菜单
-- 该文件用于添加红包资源管理的菜单项

-- ----------------------------
-- 红包资源管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/resource', '红包资源', 'fa fa-cubes', 'redpacket/resource', '', '管理红包任务关联的App、小程序、游戏、视频等资源', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 11, 'normal');

-- ----------------------------
-- 红包资源管理权限规则
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/index', '查看', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/add', '添加', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/del', '删除', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/select', '选择资源', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket/resource' LIMIT 1) tmp), 'redpacket/resource/detail', '资源详情', 'fa fa-circle-o', '', '', '', 0, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
