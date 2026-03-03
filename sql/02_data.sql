-- ============================================================================
-- 短视频金币平台 - 初始数据 (整合版)
-- ============================================================================
-- 执行顺序: 第二步执行此文件插入初始数据
-- 说明: 需要先执行 01_tables.sql 创建表结构
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 第一部分: FastAdmin 基础数据
-- ============================================================================

-- ----------------------------
-- 管理员数据 (如果已存在可跳过)
-- ----------------------------
INSERT IGNORE INTO `advn_admin` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `email`, `mobile`, `loginfailure`, `logintime`, `loginip`, `createtime`, `updatetime`, `token`, `status`) VALUES 
(1, 'admin', 'Admin', 'ca5a435772eb9b8e1a310401f82e15a1', 'bf9139', '/assets/img/avatar.png', 'admin@admin.com', '', 0, UNIX_TIMESTAMP(), '127.0.0.1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '', 'normal');

-- ----------------------------
-- 管理员日志 (示例)
-- ----------------------------
INSERT IGNORE INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES 
(1, 1, 'admin', '/admin/index/login', '登录', '{"username":"admin","password":"***"}', '127.0.0.1', 'Mozilla/5.0', UNIX_TIMESTAMP());

-- ----------------------------
-- 附件数据
-- ----------------------------
INSERT IGNORE INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES 
(1, '', 1, 0, '/assets/img/qrcode.png', 150, 150, 'png', 0, 'qrcode.png', 21859, 'image/png', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'local', '17163603d0263e4838b9387ff2cd4877e8b018f6');

-- ----------------------------
-- 权限分组数据
-- ----------------------------
INSERT IGNORE INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES 
(1, 0, 'Admin group', '*', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(2, 1, 'Second group', '13,14,16,15,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,1,9,10,11,7,6,8,2,4,5', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(3, 2, 'Third group', '1,4,9,10,11,13,14,15,16,17,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,5', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(4, 1, 'Second group 2', '1,4,13,14,15,16,17,55,56,57,58,59,60,61,62,63,64,65', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal'),
(5, 2, 'Third group 2', '1,2,6,7,8,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'normal');

-- ----------------------------
-- 权限分组关联
-- ----------------------------
INSERT IGNORE INTO `advn_auth_group_access` (`uid`, `group_id`) VALUES (1, 1);

-- ============================================================================
-- 第二部分: 后台菜单数据
-- ============================================================================

-- ----------------------------
-- 一级菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'dashboard', '控制台', 'fa fa-dashboard', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'normal'),
('file', 0, 'member', '用户管理', 'fa fa-users', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 95, 'normal'),
('file', 0, 'video', '视频管理', 'fa fa-video-camera', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 90, 'normal'),
('file', 0, 'redpacket', '红包管理', 'fa fa-envelope', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 85, 'normal'),
('file', 0, 'withdraw', '提现管理', 'fa fa-money', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 80, 'normal'),
('file', 0, 'coin', '金币管理', 'fa fa-diamond', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 75, 'normal'),
('file', 0, 'invite', '邀请分佣', 'fa fa-users', '', '', '邀请分佣管理', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 70, 'normal'),
('file', 0, 'risk', '风控管理', 'fa fa-shield', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 65, 'normal'),
('file', 0, 'migration', '数据迁移', 'fa fa-database', '', '', '数据迁移管理', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 60, 'normal'),
('file', 0, 'setting', '系统设置', 'fa fa-cogs', '', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 55, 'normal');

-- ----------------------------
-- 用户管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member' LIMIT 1) tmp), 'member/user', '用户列表', 'fa fa-user', 'member/user', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'member' LIMIT 1) tmp), 'member/statistics', '用户统计', 'fa fa-bar-chart', 'member/user/statistics', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal');

-- ----------------------------
-- 视频管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/video', '视频列表', 'fa fa-list', 'video/video', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/collection', '视频合集', 'fa fa-folder', 'video/collection', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/watchrecord', '观看记录', 'fa fa-eye', 'video/watchrecord', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'video' LIMIT 1) tmp), 'video/rewardrule', '奖励规则', 'fa fa-gift', 'video/rewardrule', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- ----------------------------
-- 红包管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/task', '红包任务', 'fa fa-tasks', 'redpacket/task', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/participation', '领取记录', 'fa fa-list-alt', 'redpacket/participation', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/category', '红包分类', 'fa fa-tags', 'redpacket/category', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'redpacket' LIMIT 1) tmp), 'redpacket/stat', '红包统计', 'fa fa-bar-chart', 'redpacket/stat', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- ----------------------------
-- 提现管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/order', '提现订单', 'fa fa-list', 'withdraw/order', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/config', '提现配置', 'fa fa-cog', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/risklog', '风控记录', 'fa fa-shield', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'withdraw' LIMIT 1) tmp), 'withdraw/stat', '提现统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- ----------------------------
-- 金币管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/log', '金币流水', 'fa fa-history', 'coin/log', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/account', '金币账户', 'fa fa-bank', 'coin/account', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'coin' LIMIT 1) tmp), 'coin/statistics', '金币统计', 'fa fa-bar-chart', 'coin/log/statistics', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal');

-- ----------------------------
-- 邀请分佣子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/relation', '邀请关系', 'fa fa-sitemap', 'invite/relation', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/commissionconfig', '分佣配置', 'fa fa-cog', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/commissionlog', '分佣记录', 'fa fa-list', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/invitestat', '邀请统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'invite' LIMIT 1) tmp), 'invite/commissionstat', '佣金统计', 'fa fa-money', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 6, 'normal');

-- ----------------------------
-- 风控管理子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/dashboard', '风控仪表盘', 'fa fa-dashboard', 'risk/dashboard', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/rule', '风控规则', 'fa fa-gavel', 'risk/rule', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/userrisk', '用户风险', 'fa fa-user-secret', 'risk/userrisk', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/banrecord', '封禁记录', 'fa fa-ban', 'risk/banrecord', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'risk' LIMIT 1) tmp), 'risk/blacklist', '黑白名单', 'fa fa-list', 'risk/blacklist', '', '', 1, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 6, 'normal');

-- ----------------------------
-- 数据迁移子菜单
-- ----------------------------
INSERT IGNORE INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'migration' LIMIT 1) tmp), 'migration/config', '迁移配置', 'fa fa-cog', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'migration' LIMIT 1) tmp), 'migration/log', '迁移日志', 'fa fa-list', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 9, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'migration' LIMIT 1) tmp), 'migration/stats', '数据统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 8, 'normal'),
('file', (SELECT id FROM (SELECT id FROM advn_auth_rule WHERE name = 'migration' LIMIT 1) tmp), 'migration/execute', '执行迁移', 'fa fa-play', '', '', '', 1, NULL, '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 7, 'normal');

-- ============================================================================
-- 第三部分: 视频收益规则默认数据
-- ============================================================================

INSERT IGNORE INTO `advn_video_reward_rule` (`name`, `code`, `scope_type`, `reward_type`, `reward_coin`, `condition_type`, `watch_progress`, `watch_duration`, `daily_limit`, `status`, `createtime`) VALUES
('默认-看完奖励', 'default_complete', 'global', 'fixed', 100.00, 'complete', 95, 0, 50, 1, UNIX_TIMESTAMP()),
('默认-时长奖励', 'default_duration', 'global', 'fixed', 100.00, 'duration', 0, 30, 50, 1, UNIX_TIMESTAMP()),
('新用户专享', 'new_user_bonus', 'global', 'fixed', 200.00, 'complete', 80, 0, 10, 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 第四部分: 红包任务默认数据
-- ============================================================================

-- ----------------------------
-- 任务分类
-- ----------------------------
INSERT IGNORE INTO `advn_task_category` (`name`, `icon`, `description`, `sort`, `status`, `createtime`) VALUES
('下载任务', 'fa fa-download', '下载指定App获得奖励', 1, 1, UNIX_TIMESTAMP()),
('小程序任务', 'fa fa-rocket', '跳转小程序完成任务', 2, 1, UNIX_TIMESTAMP()),
('游戏任务', 'fa fa-gamepad', '玩游戏达到指定时长', 3, 1, UNIX_TIMESTAMP()),
('视频任务', 'fa fa-video-camera', '观看视频获得奖励', 4, 1, UNIX_TIMESTAMP()),
('分享任务', 'fa fa-share-alt', '分享链接获得奖励', 5, 1, UNIX_TIMESTAMP()),
('签到任务', 'fa fa-calendar-check-o', '每日签到获得奖励', 6, 1, UNIX_TIMESTAMP());

-- ----------------------------
-- 示例红包任务
-- ----------------------------
INSERT IGNORE INTO `advn_red_packet_task` (`name`, `description`, `task_type`, `task_url`, `task_params`, `total_amount`, `total_count`, `single_amount`, `required_duration`, `verify_method`, `user_limit`, `audit_type`, `start_time`, `end_time`, `status`, `createtime`) VALUES
('下载抖音极速版', '下载并安装抖音极速版App，打开运行30秒即可获得奖励', 'download_app', 'https://example.com/download/douyin', '{"package_name":"com.ss.android.ugc.aweme.lite","app_name":"抖音极速版"}', 100000.00, 100, 1000.00, 30, 'auto', 1, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 7, 1, UNIX_TIMESTAMP()),
('跳转拼多多小程序', '跳转拼多多小程序浏览商品30秒', 'mini_program', '', '{"mini_app_id":"wxapp_pdd","mini_app_name":"拼多多"}', 50000.00, 200, 250.00, 30, 'auto', 1, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 3, 1, UNIX_TIMESTAMP()),
('玩游戏领红包', '玩游戏达到指定时长(5分钟)即可领取红包', 'play_game', '', '{"game_id":1,"game_name":"消消乐"}', 500000.00, 1000, 500.00, 300, 'auto', 3, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 30, 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 第五部分: 提现系统默认配置
-- ============================================================================

INSERT IGNORE INTO `advn_withdraw_config` (`name`, `code`, `value`, `type`, `title`, `remark`, `group`, `sort`, `createtime`, `updatetime`) VALUES
('兑换比例', 'exchange_rate', '10000', 'number', '金币兑换比例', '多少金币等于1元', 'basic', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('最低提现', 'min_withdraw', '10000', 'number', '最低提现金币', '最低提现金币数量', 'basic', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('最高提现', 'max_withdraw', '1000000', 'number', '最高提现金币', '单次最高提现金币数量', 'basic', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('每日提现次数', 'daily_withdraw_limit', '3', 'number', '每日提现次数限制', '每个用户每日提现次数上限', 'basic', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('每日提现金额', 'daily_withdraw_amount', '100', 'number', '每日提现金额限制', '每个用户每日提现金额上限(元)', 'basic', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('手续费率', 'fee_rate', '0', 'string', '手续费率', '提现手续费率(0表示免费)', 'basic', 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('自动审核金额', 'auto_audit_amount', '10', 'number', '自动审核金额', '低于此金额自动审核(元)', 'audit', 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('需人工审核金额', 'manual_audit_amount', '50', 'number', '需人工审核金额', '高于此金额需人工审核(元)', 'audit', 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('新用户提现限制', 'new_user_withdraw_days', '3', 'number', '新用户提现限制', '注册多少天后才能提现', 'audit', 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('风控拦截阈值', 'risk_reject_threshold', '80', 'number', '风控拦截阈值', '风控评分超过此值直接拒绝', 'risk', 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('风控人工阈值', 'risk_manual_threshold', '50', 'number', '风控人工阈值', '风控评分超过此值需人工审核', 'risk', 21, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('同IP提现限制', 'same_ip_limit', '5', 'number', '同IP提现限制', '同一IP每日提现次数上限', 'risk', 22, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('同设备提现限制', 'same_device_limit', '3', 'number', '同设备提现限制', '同一设备每日提现次数上限', 'risk', 23, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('打款重试次数', 'transfer_retry_count', '3', 'number', '打款重试次数', '打款失败后重试次数', 'transfer', 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('打款重试间隔', 'transfer_retry_interval', '300', 'number', '打款重试间隔', '打款重试间隔时间(秒)', 'transfer', 31, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 第六部分: 风控系统默认规则
-- ============================================================================

INSERT IGNORE INTO `advn_risk_rule` (`rule_code`, `rule_name`, `rule_type`, `description`, `threshold`, `score_weight`, `action`, `action_duration`, `enabled`, `level`, `createtime`, `updatetime`) VALUES
-- 视频相关规则
('VIDEO_WATCH_SPEED', '视频观看速度异常', 'video', '视频观看速度超过正常范围', 3.00, 30, 'block', 3600, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_WATCH_REPEAT', '重复观看同一视频', 'video', '短时间内重复观看同一视频次数过多', 5.00, 20, 'warn', 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_DAILY_LIMIT', '视频观看次数超限', 'video', '单日视频观看次数超过限制', 500.00, 40, 'block', 86400, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_REWARD_SPEED', '金币获取速度异常', 'video', '单位时间内金币获取速度异常', 10000.00, 50, 'freeze', 7200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('VIDEO_SKIP_RATIO', '视频跳过率过高', 'video', '视频跳过率超过阈值', 0.90, 25, 'warn', 0, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 任务相关规则
('TASK_COMPLETE_SPEED', '任务完成速度异常', 'task', '任务完成速度超过正常范围', 5.00, 35, 'block', 1800, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_DAILY_LIMIT', '任务完成次数超限', 'task', '单日任务完成次数超过限制', 100.00, 30, 'block', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_REPEAT_SUBMIT', '重复提交任务', 'task', '短时间内重复提交相同任务', 3.00, 40, 'block', 3600, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('TASK_FAKE_BEHAVIOR', '任务行为异常', 'task', '检测到虚假任务行为', 1.00, 80, 'freeze', 86400, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 提现相关规则
('WITHDRAW_FREQUENCY', '提现频率异常', 'withdraw', '短时间内频繁提现', 3.00, 45, 'freeze', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_AMOUNT_ANOMALY', '提现金额异常', 'withdraw', '提现金额与用户画像不符', 1.00, 60, 'freeze', 172800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('WITHDRAW_NEW_ACCOUNT', '新账户大额提现', 'withdraw', '新注册账户大额提现', 10.00, 50, 'freeze', 259200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 红包相关规则
('REDPACKET_GRAB_SPEED', '抢红包速度异常', 'redpacket', '抢红包速度超过正常范围', 0.50, 55, 'block', 3600, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('REDPACKET_DAILY_LIMIT', '抢红包次数超限', 'redpacket', '单日抢红包次数超过限制', 50.00, 35, 'block', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 邀请相关规则
('INVITE_SPEED', '邀请速度异常', 'invite', '短时间内邀请过多用户', 10.00, 45, 'freeze', 86400, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('INVITE_FAKE_ACCOUNT', '邀请虚假账户', 'invite', '邀请的账户被识别为虚假账户', 3.00, 80, 'ban', 604800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 全局规则
('IP_MULTI_ACCOUNT', 'IP多账户关联', 'global', '同一IP下关联账户过多', 5.00, 50, 'warn', 0, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('DEVICE_MULTI_ACCOUNT', '设备多账户关联', 'global', '同一设备关联账户过多', 3.00, 60, 'freeze', 172800, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('BEHAVIOR_PATTERN', '行为模式异常', 'global', '用户行为模式与正常用户差异过大', 0.80, 70, 'freeze', 259200, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 第七部分: 邀请分佣默认配置
-- ============================================================================

INSERT IGNORE INTO `advn_invite_commission_config` (`name`, `code`, `description`, `level1_rate`, `level2_rate`, `level1_fixed`, `level2_fixed`, `calc_type`, `min_amount`, `max_commission`, `status`, `sort`, `createtime`, `updatetime`) VALUES
('提现分佣', 'withdraw', '下级提现时上级获得佣金', 0.2000, 0.1000, 0.00, 0.00, 'rate', 5.00, 10.00, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('视频分佣', 'video', '下级观看视频获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 5.00, 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('红包分佣', 'red_packet', '下级抢红包获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 2.00, 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('游戏分佣', 'game', '下级玩游戏获得收益时上级获得佣金', 0.0100, 0.0050, 0.00, 0.00, 'rate', 0.00, 2.00, 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 第八部分: 系统配置 (advn_config)
-- ============================================================================

INSERT IGNORE INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
-- 金币相关配置
('coin_rate', 'basic', '金币汇率', '多少金币等于1元人民币', 'number', '', '10000', '', 'required|integer', '', ''),
('new_user_coin', 'basic', '新用户奖励', '新用户注册奖励金币数量', 'number', '', '1000', '', 'required|integer', '', ''),
('video_coin_reward', 'basic', '视频观看奖励', '每次有效观看奖励的金币数量', 'number', '', '100', '', 'required|integer', '', ''),
('video_watch_duration', 'basic', '有效观看时长', '观看多少秒才算有效观看', 'number', '', '30', '', 'required|integer', '', ''),
('daily_video_limit', 'basic', '每日视频上限', '用户每日可观看视频次数上限', 'number', '', '500', '', 'required|integer', '', ''),
('daily_coin_limit', 'basic', '每日金币上限', '用户每日可获取金币上限', 'number', '', '50000', '', 'required|integer', '', ''),
('hourly_coin_limit', 'basic', '每小时金币上限', '用户每小时可获取金币上限', 'number', '', '10000', '', 'required|integer', '', ''),

-- 视频观看配置
('watch_complete_threshold', 'basic', '完成观看阈值', '观看进度达到此百分比视为完成观看', 'number', '', '95', '', '', '', ''),
('daily_watch_limit', 'basic', '每日奖励上限', '每个用户每日获得观看奖励次数上限', 'number', '', '50', '', '', '', ''),
('watch_interval', 'basic', '同视频奖励间隔', '同一视频两次奖励间隔时间(秒)', 'number', '', '300', '', '', '', ''),
('default_reward_coin', 'basic', '默认奖励金币', '默认观看视频奖励金币数', 'number', '', '100', '', '', '', ''),
('new_user_reward_coin', 'basic', '新用户奖励金币', '新用户首次观看奖励金币', 'number', '', '200', '', '', '', ''),
('new_user_days', 'basic', '新用户定义天数', '注册多少天内为新用户', 'number', '', '7', '', '', '', ''),
('level1_watch_commission', 'basic', '一级观看佣金比例', '下级观看视频时一级上级获得佣金比例', 'string', '', '0.01', '', '', '', ''),
('level2_watch_commission', 'basic', '二级观看佣金比例', '下级观看视频时二级上级获得佣金比例', 'string', '', '0.005', '', '', '', ''),
('same_ip_reward_limit', 'basic', '同IP奖励限制', '同一IP每日获得奖励次数上限', 'number', '', '100', '', '', '', ''),
('same_device_reward_limit', 'basic', '同设备奖励限制', '同一设备每日获得奖励次数上限', 'number', '', '50', '', '', '', ''),
('max_watch_speed', 'basic', '最大观看速度', '允许的最大观看速度倍率', 'string', '', '2.0', '', '', '', ''),
('hourly_watch_limit', 'basic', '每小时观看限制', '每小时内最大观看视频数量', 'number', '', '100', '', '', '', ''),
('risk_score_threshold', 'basic', '风控拦截阈值', '风控评分超过此值拦截奖励(0-100)', 'number', '', '70', '', '', '', ''),

-- 邀请分佣配置
('invite_commission_enabled', 'basic', '开启邀请分佣', '是否开启邀请分佣功能', 'switch', '', '1', '', '', '', ''),
('invite_commission_delay', 'basic', '分佣延迟时间', '提现成功后延迟多少秒发放佣金', 'number', '', '300', '', '', '', ''),
('invite_max_level', 'basic', '最大分佣层级', '最大支持的分佣层级(1-3)', 'number', '', '2', '', '', '', ''),
('invite_register_reward', 'basic', '邀请注册奖励', '邀请新用户注册奖励金币', 'number', '', '500', '', '', '', ''),
('invite_first_withdraw_reward', 'basic', '首提奖励', '下级首次提现额外奖励金币', 'number', '', '1000', '', '', '', '');

-- ============================================================================
-- 第九部分: 系统配置 (advn_system_config)
-- ============================================================================

INSERT IGNORE INTO `advn_system_config` (`group`, `name`, `value`, `type`, `title`, `tip`, `status`, `sort`, `createtime`, `updatetime`) VALUES
-- 金币配置
('coin', 'coin_rate', '10000', 'integer', '金币汇率', '多少金币等于1元人民币', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'new_user_coin', '1000', 'integer', '新用户奖励', '新用户注册奖励金币数量', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'video_coin_reward', '100', 'integer', '视频观看奖励', '每次有效观看奖励的金币数量', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'video_watch_duration', '30', 'integer', '有效观看时长', '观看多少秒才算有效观看', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'daily_video_limit', '500', 'integer', '每日视频上限', '用户每日可观看视频次数上限', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'daily_coin_limit', '50000', 'integer', '每日金币上限', '用户每日可获取金币上限', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('coin', 'hourly_coin_limit', '10000', 'integer', '每小时金币上限', '用户每小时可获取金币上限', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 提现配置
('withdraw', 'withdraw_enabled', '1', 'boolean', '提现开关', '是否开启提现功能', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'min_withdraw', '1', 'float', '最低提现金额', '最低提现金额（元）', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'max_withdraw', '500', 'float', '最大提现金额', '单次最大提现金额（元）', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'daily_withdraw_limit', '3', 'integer', '每日提现次数', '每日提现次数限制', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'daily_withdraw_amount', '500', 'float', '每日提现金额', '每日提现金额限制（元）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'fee_rate', '0', 'float', '提现手续费率', '提现手续费率，如0.01表示1%', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'auto_audit_amount', '10', 'float', '自动审核金额', '低于此金额自动审核（元）', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'manual_audit_amount', '50', 'float', '人工审核金额', '高于此金额需人工审核（元）', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'new_user_withdraw_days', '3', 'integer', '新用户提现限制', '注册多少天后才能提现', 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'same_ip_limit', '5', 'integer', '同IP提现限制', '同一IP每日提现次数限制', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('withdraw', 'same_device_limit', '3', 'integer', '同设备提现限制', '同一设备每日提现次数限制', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 邀请配置
('invite', 'invite_enabled', '1', 'boolean', '邀请开关', '是否开启邀请功能', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_level1_reward', '1000', 'integer', '一级邀请奖励', '直接邀请奖励金币', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_level2_reward', '500', 'integer', '二级邀请奖励', '间接邀请奖励金币', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_enabled', '1', 'boolean', '分佣开关', '是否开启邀请分佣', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level1_commission_rate', '0.10', 'float', '一级分佣比例', '直接邀请的分佣比例', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level2_commission_rate', '0.05', 'float', '二级分佣比例', '间接邀请的分佣比例', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'daily_invite_limit', '50', 'integer', '每日邀请限制', '每日邀请次数上限', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 风控配置
('risk', 'risk_enabled', '1', 'boolean', '风控开关', '是否开启风控检测', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'auto_ban_enabled', '1', 'boolean', '自动封禁开关', '是否开启自动封禁', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'ban_threshold', '700', 'integer', '封禁阈值', '风险分达到此值自动封禁', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'freeze_threshold', '300', 'integer', '冻结阈值', '风险分达到此值自动冻结', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'score_decay_rate', '0.1', 'float', '评分衰减率', '风险分每日衰减比例', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'max_risk_score', '1000', 'integer', '最大风险分', '风险分最大值', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'emulator_block', '1', 'boolean', '模拟器拦截', '是否拦截模拟器访问', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'hook_block', '1', 'boolean', 'Hook框架拦截', '是否拦截Hook框架', 1, 8, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'proxy_detect', '1', 'boolean', '代理检测', '是否检测代理访问', 1, 9, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'ip_multi_account_threshold', '5', 'integer', 'IP多账户阈值', '同IP关联账户数阈值', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk', 'device_multi_account_threshold', '3', 'integer', '设备多账户阈值', '同设备关联账户数阈值', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 系统配置
('system', 'api_rate_limit', '60', 'integer', 'API限流', '每分钟API请求限制', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'user_rate_limit', '30', 'integer', '用户限流', '每分钟用户请求限制', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'high_risk_rate_limit', '5', 'integer', '高风险操作限流', '高风险操作每5分钟限制', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'session_timeout', '86400', 'integer', '会话超时', '会话超时时间（秒）', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'token_ttl', '604800', 'integer', 'Token有效期', 'Token有效期（秒）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('system', 'log_retention_days', '30', 'integer', '日志保留天数', '日志保留天数', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 第十部分: 数据迁移配置
-- ============================================================================

INSERT IGNORE INTO `advn_data_migration_config` (`table_name`, `archive_days`, `batch_size`, `delete_source`, `auto_archive`, `archive_schedule`, `enabled`, `createtime`, `updatetime`) VALUES
('coin_log', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('video_watch_record', 180, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('video_watch_session', 30, 1000, 1, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('risk_log', 180, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('user_behavior', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('anticheat_log', 90, 1000, 0, 1, '0 3 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('red_packet_record', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite_commission_log', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat_transfer_log', 365, 1000, 0, 1, '0 4 * * *', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 第十一部分: 数据迁移配置 (advn_config)
-- ============================================================================

INSERT IGNORE INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
-- 基础配置
('migration_enabled', 'migration', '启用数据迁移', '是否启用数据迁移功能', 'switch', '', '1', '', '', '', ''),
('migration_batch_size', 'migration', '批量处理数量', '每批次处理的数据量，默认1000条', 'number', '', '1000', '', 'required|integer|>=:100|<=:10000', '', ''),
('migration_auto_archive', 'migration', '自动归档', '是否启用定时自动归档', 'switch', '', '0', '', '', '', ''),
('migration_schedule', 'migration', '归档计划', 'Cron表达式，如: 0 3 * * * (每天凌晨3点)', 'string', '', '0 3 * * *', '', '', '', ''),
('migration_delete_source', 'migration', '删除源数据', '迁移后是否删除源数据（谨慎开启）', 'switch', '', '0', '', '', '', ''),
('migration_log_retention', 'migration', '日志保留天数', '迁移日志保留天数', 'number', '', '365', '', 'required|integer|>=:30|<=:1095', '', ''),

-- 各表迁移天数配置
('migration_coin_log_days', 'migration', '金币流水归档天数', '金币流水数据归档天数（超过此天数的数据将被归档）', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_watch_record_days', 'migration', '观看记录归档天数', '视频观看记录归档天数', 'number', '', '180', '', 'required|integer|>=:60|<=:365', '', ''),
('migration_watch_session_days', 'migration', '观看会话归档天数', '观看会话记录归档天数', 'number', '', '30', '', 'required|integer|>=:7|<=:90', '', ''),
('migration_risk_log_days', 'migration', '风控日志归档天数', '风控日志归档天数', 'number', '', '180', '', 'required|integer|>=:60|<=:365', '', ''),
('migration_user_behavior_days', 'migration', '用户行为归档天数', '用户行为记录归档天数', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_anticheat_days', 'migration', '防刷日志归档天数', '防刷日志归档天数', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_red_packet_days', 'migration', '红包记录归档天数', '红包领取记录归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_commission_days', 'migration', '分佣日志归档天数', '邀请分佣日志归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_transfer_days', 'migration', '打款日志归档天数', '微信打款日志归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),

-- 清理配置
('migration_daily_stats_keep', 'migration', '每日统计保留天数', '用户每日收益统计保留天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_behavior_stats_keep', 'migration', '行为统计保留天数', '用户行为统计保留天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_inactive_days', 'migration', '未活跃用户天数', '超过此天数未登录的用户标记为未活跃', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),

-- 性能配置
('migration_sleep_ms', 'migration', '批次间隔(毫秒)', '每批次处理后的休眠时间，避免锁表', 'number', '', '10', '', 'required|integer|>=:0|<=:1000', '', ''),
('migration_transaction', 'migration', '启用事务', '每批次是否使用事务处理', 'switch', '', '1', '', '', '', ''),
('migration_max_runtime', 'migration', '最大运行时间(秒)', '单次迁移最大运行时间，0表示不限制', 'number', '', '3600', '', 'required|integer|>=:0|<=:86400', '', ''),

-- 通知配置
('migration_notify_enabled', 'migration', '启用通知', '迁移完成后是否发送通知', 'switch', '', '0', '', '', '', ''),
('migration_notify_email', 'migration', '通知邮箱', '接收通知的邮箱地址', 'string', '', '', '', '', '', ''),
('migration_notify_webhook', 'migration', '通知Webhook', '接收通知的Webhook地址', 'string', '', '', '', '', '', '');

-- ============================================================================
-- 数据插入完成
-- ============================================================================
