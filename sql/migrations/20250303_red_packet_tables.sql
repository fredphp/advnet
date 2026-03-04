-- --------------------------------------------------------
-- 红包推送系统数据表
-- 创建时间: 2025-03-03
-- --------------------------------------------------------

SET NAMES utf8mb4;

-- ----------------------------
-- 红包资源表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_resource`;
CREATE TABLE `advn_red_packet_resource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '资源名称',
  `description` varchar(500) DEFAULT '' COMMENT '资源描述',
  `logo` varchar(255) DEFAULT '' COMMENT '资源图标',
  `type` varchar(30) NOT NULL DEFAULT 'miniapp' COMMENT '类型:miniapp=小程序,download=下载App,game=游戏,video=视频',
  
  -- 小程序配置
  `miniapp_id` varchar(100) DEFAULT '' COMMENT '小程序AppID',
  `miniapp_path` varchar(255) DEFAULT '' COMMENT '小程序页面路径',
  `miniapp_type` varchar(20) DEFAULT 'release' COMMENT '小程序类型:release=正式版,trial=体验版,develop=开发版',
  
  -- App下载配置
  `download_url` varchar(500) DEFAULT '' COMMENT 'App下载链接',
  `download_type` varchar(20) DEFAULT '' COMMENT '下载类型:android,ios',
  `package_name` varchar(100) DEFAULT '' COMMENT '包名',
  
  -- 视频配置
  `video_url` varchar(500) DEFAULT '' COMMENT '视频链接',
  `video_duration` int(10) unsigned DEFAULT 0 COMMENT '视频时长(秒)',
  
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '权重',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包资源表';

-- ----------------------------
-- 红包任务表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_task`;
CREATE TABLE `advn_red_packet_task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '红包名称/祝福语',
  `description` varchar(500) DEFAULT '' COMMENT '红包描述',
  `type` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '类型:normal=普通红包,lucky=拼手气红包,video=视频红包,miniapp=小程序红包,download=下载红包,game=游戏红包',
  
  -- 金额配置
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `total_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '红包总数',
  `remain_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '剩余数量',
  `remain_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '剩余金额',
  `reward` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '单个红包金额',
  
  -- 关联资源
  `resource_id` int(10) unsigned DEFAULT 0 COMMENT '关联资源ID',
  
  -- 状态与时间
  `status` enum('pending','normal','finished','expired') NOT NULL DEFAULT 'normal' COMMENT '状态:pending=待发送,normal=进行中,finished=已抢完,expired=已过期',
  `start_time` bigint(16) DEFAULT NULL COMMENT '开始时间',
  `end_time` bigint(16) DEFAULT NULL COMMENT '结束时间',
  `expire_time` int(10) unsigned DEFAULT 86400 COMMENT '过期时间(秒),默认24小时',
  
  -- 发送者信息
  `sender_id` int(10) unsigned DEFAULT 0 COMMENT '发送者ID',
  `sender_name` varchar(50) DEFAULT '' COMMENT '发送者名称',
  `sender_avatar` varchar(255) DEFAULT '' COMMENT '发送者头像',
  
  -- 推送配置
  `push_status` tinyint(1) DEFAULT 0 COMMENT '推送状态:0=未推送,1=已推送',
  `push_time` bigint(16) DEFAULT NULL COMMENT '推送时间',
  
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  `weigh` int(10) NOT NULL DEFAULT 0 COMMENT '权重',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `resource_id` (`resource_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包任务表';

-- ----------------------------
-- 红包领取记录表
-- ----------------------------
DROP TABLE IF EXISTS `advn_red_packet_record`;
CREATE TABLE `advn_red_packet_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `task_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '任务ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '领取金额',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态:1=已领取,2=已提现',
  `createtime` bigint(16) DEFAULT NULL COMMENT '领取时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_user` (`task_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包领取记录表';

-- ----------------------------
-- 添加后台菜单
-- ----------------------------
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'redpacket', '红包管理', 'fa fa-gift', '', '', '', 1, NULL, '', 'hbgl', 'hongbaoguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 100, 'normal');

SET @redpacket_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @redpacket_id, 'redpacket/task', '红包任务', 'fa fa-money', '', '', '', 1, 'addtabs', '', 'hbrw', 'hongbaorenwu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 99, 'normal'),
('file', @redpacket_id, 'redpacket/resource', '红包资源', 'fa fa-cubes', '', '', '', 1, 'addtabs', '', 'hbzy', 'hongbaoziyuan', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 98, 'normal'),
('file', @redpacket_id, 'redpacket/record', '领取记录', 'fa fa-list', '', '', '', 1, 'addtabs', '', 'lqjl', 'lingqujilu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 97, 'normal');

SET @task_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket/task');
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @task_id, 'redpacket/task/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @task_id, 'redpacket/task/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @task_id, 'redpacket/task/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @task_id, 'redpacket/task/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @task_id, 'redpacket/task/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @task_id, 'redpacket/task/push', '推送', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @resource_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket/resource');
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @resource_id, 'redpacket/resource/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @resource_id, 'redpacket/resource/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @resource_id, 'redpacket/resource/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @resource_id, 'redpacket/resource/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @resource_id, 'redpacket/resource/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @record_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'redpacket/record');
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @record_id, 'redpacket/record/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @record_id, 'redpacket/record/detail', '详情', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @record_id, 'redpacket/record/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
