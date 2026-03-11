-- 邀请关系迁移记录表
CREATE TABLE IF NOT EXISTS `fa_invite_relation_migration_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
  `old_parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '原上级ID',
  `old_grandparent_id` int(11) NOT NULL DEFAULT 0 COMMENT '原二级上级ID',
  `new_parent_id` int(11) NOT NULL DEFAULT 0 COMMENT '新上级ID',
  `new_grandparent_id` int(11) NOT NULL DEFAULT 0 COMMENT '新二级上级ID',
  `admin_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作管理员ID',
  `reason` varchar(500) DEFAULT '' COMMENT '迁移原因',
  `createtime` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_old_parent_id` (`old_parent_id`),
  KEY `idx_new_parent_id` (`new_parent_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请关系迁移记录表';
