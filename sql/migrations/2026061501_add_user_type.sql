-- ============================================================
-- 系统会员功能：user 表增加 user_type 字段
-- 0 = 真实会员（默认），1 = 系统会员（后台创建，用于发送系统任务）
-- ============================================================

ALTER TABLE `advn_user` ADD COLUMN `user_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户类型: 0=真实会员 1=系统会员' AFTER `source`;

-- 索引，方便按类型筛选
ALTER TABLE `advn_user` ADD INDEX `idx_user_type` (`user_type`);

-- ============================================================
-- 插入几个默认的系统会员（用于发送不同类型的系统任务）
-- ============================================================

-- 系统小助手（发送普通聊天）
INSERT INTO `advn_user` (`username`, `nickname`, `avatar`, `user_type`, `status`, `level`, `score`, `jointime`, `createtime`, `updatetime`) VALUES
('sys_helper', '系统小助手', '/assets/img/avatar.png', 1, 'normal', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 福利官（发送红包/下载任务）
INSERT INTO `advn_user` (`username`, `nickname`, `avatar`, `user_type`, `status`, `level`, `score`, `jointime`, `createtime`, `updatetime`) VALUES
('sys_welfare', '福利官', '/assets/img/avatar.png', 1, 'normal', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 活动大使（发送广告/视频任务）
INSERT INTO `advn_user` (`username`, `nickname`, `avatar`, `user_type`, `status`, `level`, `score`, `jointime`, `createtime`, `updatetime`) VALUES
('sys_activity', '活动大使', '/assets/img/avatar.png', 1, 'normal', 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
