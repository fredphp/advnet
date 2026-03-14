-- 后台金币操作日志表
-- 用于记录管理员对用户金币的充值和扣除操作

CREATE TABLE IF NOT EXISTS `__PREFIX__admin_coin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `admin_name` varchar(50) NOT NULL DEFAULT '' COMMENT '管理员用户名',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `amount` int(11) NOT NULL DEFAULT '0' COMMENT '金币数量(正数充值,负数扣除)',
  `type` varchar(20) NOT NULL DEFAULT '' COMMENT '操作类型:recharge充值,deduct扣除',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台金币操作日志表';
