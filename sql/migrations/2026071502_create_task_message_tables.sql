-- =====================================================
-- 任务消息推送模块迁移
-- 创建任务消息推送表和用户消息接收记录表
-- 执行时间: 2026-07-15
-- =====================================================

-- 1. 创建任务消息推送表
CREATE TABLE IF NOT EXISTS `advn_task_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联任务ID',
  `type` varchar(50) NOT NULL DEFAULT 'task_push' COMMENT '消息类型:task_push=任务推送,reward=奖励通知,system=系统通知',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '消息标题',
  `content` text COMMENT '消息内容',
  `message_data` text COMMENT '消息详细数据JSON',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=待发送,1=已发送,2=已过期',
  `target_users` text COMMENT '目标用户ID列表JSON,空为全部',
  `send_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送时间',
  `expire_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '过期时间',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务消息推送表';

-- 2. 创建用户消息接收记录表
CREATE TABLE IF NOT EXISTS `advn_user_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `message_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消息ID',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读:0=未读,1=已读',
  `read_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '阅读时间',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户消息接收记录表';
