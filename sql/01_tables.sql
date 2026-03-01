-- ============================================================================
-- 短视频金币平台 - 数据库表结构 (整合版)
-- ============================================================================
-- 执行顺序: 第一步执行此文件创建所有表
-- 表前缀: advn_
-- 数据库: MySQL 8.0+
-- 字符集: utf8mb4
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 第一部分: FastAdmin 基础表 (如果已存在可跳过)
-- ============================================================================

-- ----------------------------
-- Table structure for advn_admin
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '昵称',
  `password` varchar(32) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '头像',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '手机号码',
  `loginfailure` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` bigint DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '登录IP',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员表';

-- ----------------------------
-- Table structure for advn_admin_log
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_admin_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '日志标题',
  `content` longtext COLLATE utf8mb4_general_ci NOT NULL COMMENT '内容',
  `ip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'User-Agent',
  `createtime` bigint DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员日志表';

-- ----------------------------
-- Table structure for advn_area
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_area` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int DEFAULT NULL COMMENT '父id',
  `shortname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '简称',
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '名称',
  `mergename` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '全称',
  `level` tinyint DEFAULT NULL COMMENT '层级:1=省,2=市,3=区/县',
  `pinyin` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '拼音',
  `code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '长途区号',
  `zip` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '邮编',
  `first` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '首字母',
  `lng` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '经度',
  `lat` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '纬度',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='地区表';

-- ----------------------------
-- Table structure for advn_attachment
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_attachment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '类别',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '物理路径',
  `imagewidth` int unsigned DEFAULT '0' COMMENT '宽度',
  `imageheight` int unsigned DEFAULT '0' COMMENT '高度',
  `imagetype` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '图片类型',
  `imageframes` int unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '文件名称',
  `filesize` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '透传数据',
  `createtime` bigint DEFAULT NULL COMMENT '创建日期',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `uploadtime` bigint DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='附件表';

-- ----------------------------
-- Table structure for advn_auth_group
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_auth_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '组名',
  `rules` text COLLATE utf8mb4_general_ci NOT NULL COMMENT '规则ID',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分组表';

-- ----------------------------
-- Table structure for advn_auth_group_access
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_auth_group_access` (
  `uid` int unsigned NOT NULL COMMENT '会员ID',
  `group_id` int unsigned NOT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='权限分组表';

-- ----------------------------
-- Table structure for advn_auth_rule
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_auth_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '图标',
  `url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '规则URL',
  `condition` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '条件',
  `remark` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '备注',
  `ismenu` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `menutype` enum('addtabs','blank','dialog','ajax') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '菜单类型',
  `extend` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '扩展属性',
  `py` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '拼音首字母',
  `pinyin` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '拼音',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`),
  KEY `weigh` (`weigh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='节点表';

-- ----------------------------
-- Table structure for advn_config
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '变量名',
  `group` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '分组',
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '类型:string,text,num,bool,arr,json',
  `visible` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '可见条件',
  `value` text COLLATE utf8mb4_general_ci COMMENT '变量值',
  `content` text COLLATE utf8mb4_general_ci COMMENT '变量字典数据',
  `rule` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系统配置表';

-- ----------------------------
-- Table structure for advn_user (FastAdmin会员基础表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned DEFAULT '0' COMMENT '组别ID',
  `username` varchar(32) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '昵称',
  `password` varchar(32) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '密码',
  `salt` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '密码盐',
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '手机号码',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '头像',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` enum('male','female','secret') COLLATE utf8mb4_general_ci DEFAULT 'secret' COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '个人介绍',
  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '连续登录天数',
  `maxsuccessions` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '最大连续登录天数',
  `prevtime` int(10) unsigned DEFAULT NULL COMMENT '上次登录时间',
  `logintime` int(10) unsigned DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '登录IP',
  `loginfailure` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `loginfailuretime` int(10) unsigned DEFAULT NULL COMMENT '最后失败时间',
  `joinip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '加入IP',
  `jointime` int(10) unsigned DEFAULT NULL COMMENT '加入时间',
  `createtime` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` int(10) unsigned DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'Token',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '状态',
  `verification` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '验证',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `group_id` (`group_id`),
  KEY `mobile` (`mobile`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='会员表';

-- ============================================================================
-- 第二部分: 短视频金币平台核心表
-- ============================================================================

-- ----------------------------
-- 用户表扩展字段 (ALTER方式添加)
-- ----------------------------
-- 注意: 如果字段已存在会报错，可忽略或先检查
-- ALTER TABLE `advn_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT NULL COMMENT '我的邀请码' AFTER `password`;
-- ALTER TABLE `advn_user` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '直接上级用户ID' AFTER `invite_code`;
-- ALTER TABLE `advn_user` ADD COLUMN `grandparent_id` INT UNSIGNED DEFAULT 0 COMMENT '间接上级用户ID' AFTER `parent_id`;
-- ALTER TABLE `advn_user` ADD COLUMN `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID' AFTER `level`;
-- ALTER TABLE `advn_user` ADD COLUMN `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP' AFTER `device_id`;

-- ----------------------------
-- Table structure for advn_invite_relation (邀请关系表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_invite_relation` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '被邀请人ID(当前用户)',
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '一级邀请人ID(直接上级)',
    `grandparent_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '二级邀请人ID(间接上级)',
    `invite_code` VARCHAR(20) NOT NULL COMMENT '使用的邀请码',
    `invite_channel` VARCHAR(50) DEFAULT NULL COMMENT '邀请渠道: link/qrcode/share',
    `invite_scene` VARCHAR(50) DEFAULT NULL COMMENT '邀请场景',
    `register_reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '注册奖励状态: 0=未发放, 1=已发放',
    `register_reward_time` INT UNSIGNED DEFAULT NULL COMMENT '注册奖励发放时间',
    `invite_ip` VARCHAR(50) DEFAULT NULL COMMENT '邀请行为IP',
    `invite_device_id` VARCHAR(100) DEFAULT NULL COMMENT '邀请设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_grandparent_id` (`grandparent_id`),
    KEY `idx_invite_code` (`invite_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请关系表';

-- ----------------------------
-- Table structure for advn_coin_account (金币账户表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_coin_account` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `balance` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '可用金币余额',
    `frozen` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '冻结金币(提现申请中)',
    `total_earn` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计获得金币',
    `total_spend` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计消费金币',
    `total_withdraw` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金币',
    `today_earn` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '今日获得金币',
    `today_earn_date` DATE DEFAULT NULL COMMENT '今日统计日期',
    `version` INT UNSIGNED DEFAULT 0 COMMENT '乐观锁版本号',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币账户表';

-- ----------------------------
-- Table structure for advn_coin_log (金币流水表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_coin_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '流水类型',
    `amount` DECIMAL(18,2) NOT NULL COMMENT '金币数量(正数=收入,负数=支出)',
    `balance_before` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动前余额',
    `balance_after` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动后余额',
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: video/task/withdraw/invite',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联记录ID',
    `title` VARCHAR(100) DEFAULT NULL COMMENT '流水标题',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '详细描述',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '操作IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_relation` (`relation_type`, `relation_id`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_user_date` (`user_id`, `create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币流水表';

-- ----------------------------
-- Table structure for advn_cash_account (人民币账户表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_cash_account` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `default_account_type` VARCHAR(20) DEFAULT NULL COMMENT '默认提现方式: alipay/wechat/bank',
    `alipay_account` VARCHAR(100) DEFAULT NULL COMMENT '支付宝账号',
    `alipay_name` VARCHAR(50) DEFAULT NULL COMMENT '支付宝真实姓名',
    `alipay_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '支付宝是否已验证',
    `wechat_openid` VARCHAR(100) DEFAULT NULL COMMENT '微信OpenID',
    `wechat_name` VARCHAR(50) DEFAULT NULL COMMENT '微信昵称',
    `wechat_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '微信是否已验证',
    `bank_name` VARCHAR(50) DEFAULT NULL COMMENT '银行名称',
    `bank_code` VARCHAR(20) DEFAULT NULL COMMENT '银行代码',
    `bank_card_no` VARCHAR(100) DEFAULT NULL COMMENT '银行卡号(加密存储)',
    `bank_card_name` VARCHAR(50) DEFAULT NULL COMMENT '持卡人姓名',
    `bank_branch` VARCHAR(100) DEFAULT NULL COMMENT '开户行',
    `bank_verified` TINYINT UNSIGNED DEFAULT 0 COMMENT '银行卡是否已验证',
    `total_withdraw_amount` DECIMAL(12,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金额(元)',
    `total_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '累计提现次数',
    `last_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '最后提现时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='人民币账户表';

-- ----------------------------
-- Table structure for advn_withdraw_order (提现申请表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_withdraw_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号(唯一)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `coin_amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '提现金币数量',
    `exchange_rate` DECIMAL(10,4) UNSIGNED DEFAULT 10000.0000 COMMENT '兑换比例',
    `cash_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '提现金额(元)',
    `fee_amount` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '手续费(元)',
    `actual_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '实际到账金额(元)',
    `withdraw_type` VARCHAR(20) NOT NULL COMMENT '提现方式: alipay/wechat/bank',
    `withdraw_account` VARCHAR(100) NOT NULL COMMENT '提现账号',
    `withdraw_name` VARCHAR(50) NOT NULL COMMENT '收款人姓名',
    `bank_name` VARCHAR(50) DEFAULT NULL COMMENT '银行名称(银行卡提现)',
    `bank_branch` VARCHAR(100) DEFAULT NULL COMMENT '开户行',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待审核, 1=审核通过, 2=打款中, 3=提现成功, 4=审核拒绝, 5=打款失败, 6=已取消',
    `audit_type` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核类型: 0=自动审核, 1=人工审核',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_admin_name` VARCHAR(50) DEFAULT NULL COMMENT '审核管理员名称',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_remark` VARCHAR(200) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '打款流水号',
    `transfer_time` INT UNSIGNED DEFAULT NULL COMMENT '打款时间',
    `transfer_result` TEXT DEFAULT NULL COMMENT '打款结果(JSON)',
    `fail_reason` VARCHAR(200) DEFAULT NULL COMMENT '失败原因',
    `retry_count` TINYINT UNSIGNED DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` INT UNSIGNED DEFAULT NULL COMMENT '下次重试时间',
    `risk_score` TINYINT UNSIGNED DEFAULT 0 COMMENT '风控评分(0-100)',
    `risk_tags` VARCHAR(200) DEFAULT NULL COMMENT '风控标签(JSON)',
    `commission_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '佣金发放状态: 0=未发放, 1=已发放',
    `commission_time` INT UNSIGNED DEFAULT NULL COMMENT '佣金发放时间',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '申请IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户UA',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_transfer_no` (`transfer_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现申请表';

-- ----------------------------
-- Table structure for advn_video (视频表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '发布者ID',
    `title` VARCHAR(200) DEFAULT NULL COMMENT '视频标题',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '视频描述',
    `cover_url` VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
    `video_url` VARCHAR(500) NOT NULL COMMENT '视频URL',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '视频时长(秒)',
    `width` INT UNSIGNED DEFAULT 0 COMMENT '视频宽度(px)',
    `height` INT UNSIGNED DEFAULT 0 COMMENT '视频高度(px)',
    `file_size` BIGINT UNSIGNED DEFAULT 0 COMMENT '文件大小(字节)',
    `format` VARCHAR(20) DEFAULT NULL COMMENT '视频格式: mp4/mov/avi',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '分类ID',
    `collection_id` INT UNSIGNED DEFAULT NULL COMMENT '所属合集ID',
    `episode` INT UNSIGNED DEFAULT 1 COMMENT '集数序号',
    `tags` VARCHAR(200) DEFAULT NULL COMMENT '标签(JSON数组)',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 100.00 COMMENT '观看奖励金币',
    `reward_duration` INT UNSIGNED DEFAULT 30 COMMENT '奖励所需观看时长(秒)',
    `reward_enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否开启奖励: 0=否, 1=是',
    `reward_rule_id` INT UNSIGNED DEFAULT NULL COMMENT '收益规则ID',
    `view_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '播放量',
    `like_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '点赞数',
    `comment_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '评论数',
    `share_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '分享数',
    `collect_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '收藏数',
    `reward_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '奖励人数',
    `reward_coin_total` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '已发放奖励金币',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待审核, 1=已发布, 2=已下架, 3=已封禁, 4=草稿',
    `is_original` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否原创: 0=转载, 1=原创',
    `is_recommend` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否推荐: 0=否, 1=是',
    `is_hot` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否热门: 0=否, 1=是',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序权重',
    `audit_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核状态: 0=待审核, 1=通过, 2=拒绝',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_remark` VARCHAR(200) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `location` VARCHAR(100) DEFAULT NULL COMMENT '发布地点',
    `latitude` DECIMAL(10,6) DEFAULT NULL COMMENT '纬度',
    `longitude` DECIMAL(10,6) DEFAULT NULL COMMENT '经度',
    `publish_time` INT UNSIGNED DEFAULT NULL COMMENT '发布时间戳',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_status` (`status`),
    KEY `idx_is_recommend` (`is_recommend`),
    KEY `idx_view_count` (`view_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频表';

-- ----------------------------
-- Table structure for advn_video_reward_rule (视频收益规则表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_reward_rule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '规则ID',
    `name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `code` VARCHAR(50) DEFAULT NULL COMMENT '规则代码(唯一标识)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '规则描述',
    `scope_type` VARCHAR(20) NOT NULL DEFAULT 'global' COMMENT '适用范围: global=全局, category=分类, video=单视频, collection=合集',
    `scope_id` INT UNSIGNED DEFAULT NULL COMMENT '范围ID(分类ID/视频ID/合集ID)',
    `reward_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' COMMENT '奖励类型: fixed=固定, random=随机, progressive=递进',
    `reward_coin` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '固定奖励金币',
    `reward_min` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '最小奖励(随机时)',
    `reward_max` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '最大奖励(随机时)',
    `condition_type` VARCHAR(20) NOT NULL DEFAULT 'complete' COMMENT '条件类型: complete=看完, duration=时长, count=集数',
    `watch_progress` TINYINT UNSIGNED DEFAULT 95 COMMENT '观看进度(%), 达到此进度视为看完',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '需要观看时长(秒)',
    `watch_duration_ratio` TINYINT UNSIGNED DEFAULT 0 COMMENT '时长占比(%)',
    `watch_count` INT UNSIGNED DEFAULT 0 COMMENT '需要观看集数',
    `watch_count_reward_per` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否每集奖励: 0=否, 1=是',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日奖励次数限制(0=不限)',
    `daily_limit_type` VARCHAR(20) DEFAULT 'user' COMMENT '限制维度: user=用户, video=视频, rule=规则',
    `total_limit` INT UNSIGNED DEFAULT 0 COMMENT '总奖励次数限制(0=不限)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级',
    `user_level_max` TINYINT UNSIGNED DEFAULT 255 COMMENT '最高用户等级(255=不限)',
    `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是',
    `new_user_days` INT UNSIGNED DEFAULT 7 COMMENT '新用户定义(注册天数)',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '规则生效开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '规则生效结束时间',
    `priority` INT UNSIGNED DEFAULT 0 COMMENT '优先级(越大越高)',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_scope` (`scope_type`, `scope_id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频收益规则表';

-- ----------------------------
-- Table structure for advn_video_collection (视频合集表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_collection` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '合集ID',
    `title` VARCHAR(200) NOT NULL COMMENT '合集标题',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '合集描述',
    `cover_url` VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频数量',
    `total_duration` INT UNSIGNED DEFAULT 0 COMMENT '总时长(秒)',
    `view_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '播放量',
    `reward_enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否开启奖励',
    `reward_type` VARCHAR(20) DEFAULT 'per_video' COMMENT '奖励方式: per_video=每集, complete=看完合集, progressive=递进',
    `reward_coin_per_video` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '每集奖励金币',
    `reward_coin_complete` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '看完合集奖励',
    `watch_count_for_reward` INT UNSIGNED DEFAULT 0 COMMENT '需要看完集数',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频合集表';

-- ----------------------------
-- Table structure for advn_video_collection_item (合集视频关联表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_collection_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `collection_id` INT UNSIGNED NOT NULL COMMENT '合集ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `episode` INT UNSIGNED DEFAULT 1 COMMENT '集数序号',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_collection_video` (`collection_id`, `video_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='合集视频关联表';

-- ----------------------------
-- Table structure for advn_video_watch_record (视频观看记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_watch_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `collection_id` INT UNSIGNED DEFAULT NULL COMMENT '合集ID',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '累计观看时长(秒)',
    `watch_progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `watch_count` INT UNSIGNED DEFAULT 0 COMMENT '观看次数',
    `last_position` INT UNSIGNED DEFAULT 0 COMMENT '上次观看位置(秒)',
    `is_completed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否看完: 0=否, 1=是',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间',
    `reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '奖励状态: 0=未领取, 1=已领取, 2=已失效',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `reward_time` INT UNSIGNED DEFAULT NULL COMMENT '奖励时间',
    `reward_rule_id` INT UNSIGNED DEFAULT NULL COMMENT '使用的规则ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '观看IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '首次观看时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '最后观看时间',
    `date_key` DATE DEFAULT NULL COMMENT '日期键(便于统计)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_video` (`user_id`, `video_id`),
    KEY `idx_video_id` (`video_id`),
    KEY `idx_collection_id` (`collection_id`),
    KEY `idx_reward_status` (`reward_status`),
    KEY `idx_user_date` (`user_id`, `date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频观看记录表';

-- ----------------------------
-- Table structure for advn_video_watch_session (观看会话表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_watch_session` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `start_time` INT UNSIGNED NOT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `ip` VARCHAR(50) DEFAULT NULL,
    `device_id` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(20) DEFAULT NULL,
    `app_version` VARCHAR(20) DEFAULT NULL,
    `rewarded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否获得奖励',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_session_id` (`session_id`),
    KEY `idx_user_video` (`user_id`, `video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='观看会话表';

-- ----------------------------
-- Table structure for advn_user_daily_reward_stat (用户每日收益统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_daily_reward_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    `video_watch_count` INT UNSIGNED DEFAULT 0 COMMENT '观看视频数',
    `video_watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `video_reward_count` INT UNSIGNED DEFAULT 0 COMMENT '获得奖励次数',
    `video_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `task_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '任务奖励',
    `other_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '其他奖励',
    `total_reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '总获得金币',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `date_key`),
    KEY `idx_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户每日收益统计表';

-- ============================================================================
-- 第三部分: 红包任务系统表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_red_packet_task (红包任务表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_red_packet_task` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '红包ID',
    `name` VARCHAR(100) NOT NULL COMMENT '红包名称',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '红包描述',
    `icon` VARCHAR(500) DEFAULT NULL COMMENT '红包图标',
    `images` TEXT DEFAULT NULL COMMENT '任务宣传图(JSON数组)',
    `task_type` VARCHAR(30) NOT NULL COMMENT '任务类型: download_app=下载App, mini_program=跳转小程序, play_game=玩游戏时长, watch_video=观看视频, share_link=分享链接, sign_in=签到',
    `task_url` VARCHAR(500) DEFAULT NULL COMMENT '任务跳转链接',
    `task_params` TEXT DEFAULT NULL COMMENT '任务参数(JSON)',
    `total_amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包总金额(金币)',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包总数量',
    `remain_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '剩余金额',
    `remain_count` INT UNSIGNED DEFAULT 0 COMMENT '剩余数量',
    `single_amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '单个红包金额(固定)',
    `min_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最小金额(随机)',
    `max_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最大金额(随机)',
    `amount_type` VARCHAR(20) DEFAULT 'fixed' COMMENT '金额类型: fixed=固定, random=随机',
    `required_duration` INT UNSIGNED DEFAULT 0 COMMENT '要求时长(秒)',
    `required_progress` TINYINT UNSIGNED DEFAULT 100 COMMENT '要求进度(%)',
    `required_count` INT UNSIGNED DEFAULT 1 COMMENT '要求次数',
    `verify_method` VARCHAR(30) DEFAULT 'auto' COMMENT '验证方式: auto=自动, manual=人工, third_party=第三方',
    `verify_params` TEXT DEFAULT NULL COMMENT '验证参数(JSON)',
    `user_limit` INT UNSIGNED DEFAULT 1 COMMENT '每人可领取次数',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日限制领取次数(0=不限)',
    `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是',
    `new_user_days` INT UNSIGNED DEFAULT 7 COMMENT '新用户定义(注册天数)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级',
    `user_level_max` TINYINT UNSIGNED DEFAULT 255 COMMENT '最高用户等级',
    `vip_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限VIP: 0=否, 1=是',
    `audit_type` VARCHAR(20) DEFAULT 'auto' COMMENT '审核方式: auto=自动, manual=人工',
    `audit_timeout` INT UNSIGNED DEFAULT 86400 COMMENT '审核超时时间(秒)',
    `need_screenshot` TINYINT UNSIGNED DEFAULT 0 COMMENT '需要上传截图: 0=否, 1=是',
    `need_device_info` TINYINT UNSIGNED DEFAULT 1 COMMENT '需要设备信息: 0=否, 1=是',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `expire_hours` INT UNSIGNED DEFAULT 24 COMMENT '领取后有效期(小时)',
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: app/game/video/activity',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联ID',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '任务分类ID',
    `view_count` INT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
    `join_count` INT UNSIGNED DEFAULT 0 COMMENT '参与次数',
    `complete_count` INT UNSIGNED DEFAULT 0 COMMENT '完成次数',
    `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '已领取人数',
    `receive_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '已发放金额',
    `audit_pending_count` INT UNSIGNED DEFAULT 0 COMMENT '待审核数量',
    `audit_reject_count` INT UNSIGNED DEFAULT 0 COMMENT '已拒绝数量',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `is_hot` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否热门: 0=否, 1=是',
    `is_recommend` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否推荐: 0=否, 1=是',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用, 2=已结束, 3=已抢完',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_status` (`status`),
    KEY `idx_time_range` (`start_time`, `end_time`),
    KEY `idx_category` (`category_id`),
    KEY `idx_is_hot` (`is_hot`),
    KEY `idx_is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包任务表';

-- ----------------------------
-- Table structure for advn_red_packet_record (红包领取记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_red_packet_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `packet_id` INT UNSIGNED NOT NULL COMMENT '红包ID',
    `packet_type` VARCHAR(30) NOT NULL COMMENT '红包类型',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '获得金币数量',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=已退回, 1=正常',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '领取IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_packet_user` (`packet_id`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包领取记录表';

-- ----------------------------
-- Table structure for advn_task_participation (任务参与记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_task_participation` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `task_id` INT UNSIGNED NOT NULL COMMENT '红包任务ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '参与订单号',
    `task_type` VARCHAR(30) NOT NULL COMMENT '任务类型',
    `task_name` VARCHAR(100) DEFAULT NULL COMMENT '任务名称快照',
    `task_url` VARCHAR(500) DEFAULT NULL COMMENT '任务链接快照',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=已领取待完成, 1=已完成待审核, 2=审核通过待发放, 3=已发放, 4=审核拒绝, 5=已过期, 6=已取消',
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '实际耗时(秒)',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '完成进度(%)',
    `audit_type` VARCHAR(20) DEFAULT 'auto' COMMENT '审核方式',
    `audit_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核状态: 0=待审核, 1=通过, 2=拒绝',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_admin_name` VARCHAR(50) DEFAULT NULL COMMENT '审核管理员名称',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '奖励金币',
    `reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '发放状态: 0=未发放, 1=已发放',
    `reward_time` INT UNSIGNED DEFAULT NULL COMMENT '发放时间',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `device_info` TEXT DEFAULT NULL COMMENT '设备信息(JSON)',
    `app_version` VARCHAR(20) DEFAULT NULL COMMENT 'APP版本',
    `platform` VARCHAR(20) DEFAULT NULL COMMENT '平台: ios/android/h5',
    `screenshot_urls` TEXT DEFAULT NULL COMMENT '截图URLs(JSON数组)',
    `proof_data` TEXT DEFAULT NULL COMMENT '证明数据(JSON)',
    `extra_data` TEXT DEFAULT NULL COMMENT '额外数据(JSON)',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_user_task` (`user_id`, `task_id`),
    KEY `idx_audit_status` (`audit_status`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务参与记录表';

-- ----------------------------
-- Table structure for advn_task_category (任务分类表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_task_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `icon` VARCHAR(200) DEFAULT NULL COMMENT '分类图标',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '分类描述',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务分类表';

-- ----------------------------
-- Table structure for advn_task_device_log (任务设备记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_task_device_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `device_id` VARCHAR(100) NOT NULL COMMENT '设备ID',
    `device_name` VARCHAR(100) DEFAULT NULL COMMENT '设备名称',
    `device_brand` VARCHAR(50) DEFAULT NULL COMMENT '设备品牌',
    `device_model` VARCHAR(50) DEFAULT NULL COMMENT '设备型号',
    `os_version` VARCHAR(20) DEFAULT NULL COMMENT '系统版本',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `ip_location` VARCHAR(100) DEFAULT NULL COMMENT 'IP归属地',
    `network_type` VARCHAR(20) DEFAULT NULL COMMENT '网络类型',
    `participation_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '参与记录ID',
    `action` VARCHAR(30) DEFAULT NULL COMMENT '动作: receive/complete/submit',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task_device` (`task_id`, `device_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_device_id` (`device_id`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务设备记录表';

-- ----------------------------
-- Table structure for advn_task_audit_log (任务审核日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_task_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
    `participation_id` BIGINT UNSIGNED NOT NULL COMMENT '参与记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `action` VARCHAR(30) NOT NULL COMMENT '操作: submit/audit_pass/audit_reject/reward',
    `old_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '原状态',
    `new_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '新状态',
    `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
    `operator_type` VARCHAR(20) DEFAULT NULL COMMENT '操作者类型: system/admin/user',
    `operator_id` INT UNSIGNED DEFAULT NULL COMMENT '操作者ID',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_participation_id` (`participation_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务审核日志表';

-- ----------------------------
-- Table structure for advn_user_task_stat (用户任务统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_task_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '领取次数',
    `complete_count` INT UNSIGNED DEFAULT 0 COMMENT '完成次数',
    `reward_count` INT UNSIGNED DEFAULT 0 COMMENT '奖励次数',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '获得金币',
    `reject_count` INT UNSIGNED DEFAULT 0 COMMENT '被拒绝次数',
    `download_count` INT UNSIGNED DEFAULT 0 COMMENT '下载任务数',
    `mini_program_count` INT UNSIGNED DEFAULT 0 COMMENT '小程序任务数',
    `game_count` INT UNSIGNED DEFAULT 0 COMMENT '游戏任务数',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频任务数',
    `share_count` INT UNSIGNED DEFAULT 0 COMMENT '分享任务数',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `date_key`),
    KEY `idx_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户任务统计表';

-- ----------------------------
-- Table structure for advn_game_task_record (游戏任务记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_game_task_record` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `game_type` VARCHAR(30) NOT NULL COMMENT '游戏类型: spin/scratch/quiz/lucky_wheel/turntable',
    `game_name` VARCHAR(100) DEFAULT NULL COMMENT '游戏名称',
    `task_id` INT UNSIGNED DEFAULT NULL COMMENT '关联任务ID',
    `is_win` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否中奖: 0=未中奖, 1=中奖',
    `reward_type` VARCHAR(20) DEFAULT NULL COMMENT '奖励类型: coin/redpacket/item',
    `reward_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '奖励金额(金币)',
    `reward_detail` TEXT DEFAULT NULL COMMENT '奖励详情(JSON)',
    `receive_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '领取状态: 0=未领取, 1=已领取',
    `receive_time` INT UNSIGNED DEFAULT NULL COMMENT '领取时间',
    `game_data` TEXT DEFAULT NULL COMMENT '游戏数据(JSON)',
    `score` INT UNSIGNED DEFAULT 0 COMMENT '游戏得分',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '游戏时长(秒)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_game_type` (`game_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='游戏任务记录表';

-- ============================================================================
-- 第四部分: 提现系统表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_withdraw_config (提现配置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_withdraw_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
    `code` VARCHAR(50) NOT NULL COMMENT '配置代码',
    `value` VARCHAR(500) DEFAULT NULL COMMENT '配置值',
    `type` VARCHAR(20) DEFAULT 'string' COMMENT '类型: string/number/switch/json',
    `title` VARCHAR(100) DEFAULT NULL COMMENT '配置标题',
    `remark` VARCHAR(200) DEFAULT NULL COMMENT '配置说明',
    `group` VARCHAR(50) DEFAULT 'basic' COMMENT '配置分组',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现配置表';

-- ----------------------------
-- Table structure for advn_withdraw_risk_log (提现风控记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_withdraw_risk_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) DEFAULT NULL COMMENT '提现订单号',
    `risk_type` VARCHAR(50) NOT NULL COMMENT '风险类型',
    `risk_level` TINYINT UNSIGNED DEFAULT 1 COMMENT '风险等级: 1=低, 2=中, 3=高',
    `risk_score` TINYINT UNSIGNED DEFAULT 0 COMMENT '风险评分(0-100)',
    `risk_detail` TEXT DEFAULT NULL COMMENT '风险详情(JSON)',
    `handle_action` VARCHAR(50) DEFAULT NULL COMMENT '处理动作: pass/review/reject/freeze',
    `handle_remark` VARCHAR(200) DEFAULT NULL COMMENT '处理备注',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_risk_type` (`risk_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现风控记录表';

-- ----------------------------
-- Table structure for advn_withdraw_stat (提现统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_withdraw_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '累计提现次数',
    `total_withdraw_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计提现金额(元)',
    `total_withdraw_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金币',
    `total_fee_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计手续费(元)',
    `success_count` INT UNSIGNED DEFAULT 0 COMMENT '成功次数',
    `fail_count` INT UNSIGNED DEFAULT 0 COMMENT '失败次数',
    `today_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '今日提现次数',
    `today_withdraw_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '今日提现金额',
    `today_withdraw_date` DATE DEFAULT NULL COMMENT '今日统计日期',
    `first_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '首次提现时间',
    `last_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '最后提现时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现统计表';

-- ----------------------------
-- Table structure for advn_wechat_transfer_log (微信打款日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_wechat_transfer_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '微信转账单号',
    `partner_trade_no` VARCHAR(64) DEFAULT NULL COMMENT '商户订单号',
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '收款用户openid',
    `amount` INT UNSIGNED DEFAULT 0 COMMENT '转账金额(分)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '转账描述',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据(JSON)',
    `response_data` TEXT DEFAULT NULL COMMENT '响应数据(JSON)',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待处理, 1=处理中, 2=成功, 3=失败',
    `error_code` VARCHAR(50) DEFAULT NULL COMMENT '错误码',
    `error_msg` VARCHAR(200) DEFAULT NULL COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_transfer_no` (`transfer_no`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信打款日志表';

-- ============================================================================
-- 第五部分: 风控系统表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_risk_rule (风控规则配置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_rule` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `rule_code` varchar(50) NOT NULL COMMENT '规则编码',
    `rule_name` varchar(100) NOT NULL COMMENT '规则名称',
    `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL DEFAULT 'global' COMMENT '规则类型',
    `description` varchar(500) DEFAULT '' COMMENT '规则描述',
    `threshold` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '阈值',
    `score_weight` int(10) NOT NULL DEFAULT '10' COMMENT '违规分值权重(1-100)',
    `action` enum('warn','block','freeze','ban') NOT NULL DEFAULT 'warn' COMMENT '触发动作',
    `action_duration` int(10) NOT NULL DEFAULT '0' COMMENT '动作持续时间(秒,0永久)',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '风险等级(1低2中3高)',
    `extra_config` text COMMENT '额外配置JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rule_code` (`rule_code`),
    KEY `idx_rule_type` (`rule_type`),
    KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控规则配置表';

-- ----------------------------
-- Table structure for advn_user_risk_score (用户风控评分表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_risk_score` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `total_score` int(10) NOT NULL DEFAULT '0' COMMENT '总风险分值(0-1000)',
    `risk_level` enum('safe','low','medium','high','dangerous') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `video_score` int(10) NOT NULL DEFAULT '0' COMMENT '视频相关风险分',
    `task_score` int(10) NOT NULL DEFAULT '0' COMMENT '任务相关风险分',
    `withdraw_score` int(10) NOT NULL DEFAULT '0' COMMENT '提现相关风险分',
    `redpacket_score` int(10) NOT NULL DEFAULT '0' COMMENT '红包相关风险分',
    `invite_score` int(10) NOT NULL DEFAULT '0' COMMENT '邀请相关风险分',
    `global_score` int(10) NOT NULL DEFAULT '0' COMMENT '全局风险分',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `last_violation_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后违规时间',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `freeze_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '冻结到期时间',
    `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态',
    `score_history` text COMMENT '评分历史JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_status` (`status`),
    KEY `idx_ban_expire` (`ban_expire_time`),
    KEY `idx_freeze_expire` (`freeze_expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户风控评分表';

-- ----------------------------
-- Table structure for advn_risk_log (风控日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_log` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `rule_code` varchar(50) NOT NULL COMMENT '触发的规则编码',
    `rule_name` varchar(100) NOT NULL COMMENT '规则名称',
    `rule_type` enum('video','task','withdraw','redpacket','invite','global') NOT NULL COMMENT '规则类型',
    `risk_level` tinyint(1) NOT NULL COMMENT '风险等级',
    `trigger_value` decimal(15,4) NOT NULL COMMENT '触发值',
    `threshold` decimal(15,4) NOT NULL COMMENT '阈值',
    `score_add` int(10) NOT NULL COMMENT '增加的风险分',
    `action` enum('warn','block','freeze','ban') NOT NULL COMMENT '执行动作',
    `action_duration` int(10) NOT NULL DEFAULT '0' COMMENT '动作持续时间',
    `action_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '动作到期时间',
    `device_id` varchar(64) DEFAULT '' COMMENT '设备ID',
    `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
    `request_data` text COMMENT '请求数据JSON',
    `response_data` text COMMENT '响应数据JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_rule_code` (`rule_code`),
    KEY `idx_rule_type` (`rule_type`),
    KEY `idx_createtime` (`createtime`),
    KEY `idx_action` (`action`),
    KEY `idx_ip` (`ip`),
    KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控日志表';

-- ----------------------------
-- Table structure for advn_ip_risk (IP风控表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_ip_risk` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `ip` varchar(50) NOT NULL COMMENT 'IP地址',
    `ip_type` enum('ipv4','ipv6') NOT NULL DEFAULT 'ipv4' COMMENT 'IP类型',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '风险分值',
    `risk_level` enum('safe','suspicious','dangerous','blacklist') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `account_count` int(10) NOT NULL DEFAULT '0' COMMENT '关联账户数',
    `account_ids` text COMMENT '关联账户ID列表JSON',
    `proxy_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到代理',
    `proxy_type` varchar(50) DEFAULT '' COMMENT '代理类型',
    `country` varchar(50) DEFAULT '' COMMENT '国家',
    `province` varchar(50) DEFAULT '' COMMENT '省份',
    `city` varchar(50) DEFAULT '' COMMENT '城市',
    `isp` varchar(100) DEFAULT '' COMMENT '运营商',
    `request_count` int(10) NOT NULL DEFAULT '0' COMMENT '请求次数',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `last_request_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后请求时间',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_ip` (`ip`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_risk_score` (`risk_score`),
    KEY `idx_proxy_detected` (`proxy_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP风控表';

-- ----------------------------
-- Table structure for advn_device_fingerprint (设备指纹表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_device_fingerprint` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `device_id` varchar(64) NOT NULL COMMENT '设备唯一标识',
    `device_hash` varchar(64) NOT NULL COMMENT '设备特征哈希',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '绑定用户ID',
    `device_type` enum('ios','android','web','other') NOT NULL DEFAULT 'other' COMMENT '设备类型',
    `device_brand` varchar(50) DEFAULT '' COMMENT '设备品牌',
    `device_model` varchar(100) DEFAULT '' COMMENT '设备型号',
    `os_version` varchar(50) DEFAULT '' COMMENT '系统版本',
    `app_version` varchar(20) DEFAULT '' COMMENT 'APP版本',
    `screen_resolution` varchar(20) DEFAULT '' COMMENT '屏幕分辨率',
    `network_type` varchar(20) DEFAULT '' COMMENT '网络类型',
    `carrier` varchar(50) DEFAULT '' COMMENT '运营商',
    `root_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到Root/越狱',
    `emulator_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到模拟器',
    `hook_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到Hook框架',
    `proxy_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到代理',
    `vpn_detected` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否检测到VPN',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '设备风险分',
    `risk_level` enum('safe','suspicious','dangerous','blacklist') NOT NULL DEFAULT 'safe' COMMENT '风险等级',
    `account_count` int(10) NOT NULL DEFAULT '1' COMMENT '关联账户数',
    `account_ids` text COMMENT '关联账户ID列表JSON',
    `login_count` int(10) NOT NULL DEFAULT '0' COMMENT '登录次数',
    `last_login_time` int(10) UNSIGNED DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` varchar(50) DEFAULT '' COMMENT '最后登录IP',
    `ban_expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '封禁到期时间',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_device_id` (`device_id`),
    KEY `idx_device_hash` (`device_hash`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_risk_level` (`risk_level`),
    KEY `idx_root_detected` (`root_detected`),
    KEY `idx_emulator_detected` (`emulator_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备指纹表';

-- ----------------------------
-- Table structure for advn_user_behavior (用户行为记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_behavior` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `behavior_type` enum('login','video_watch','task_complete','withdraw','redpacket_grab','invite','other') NOT NULL COMMENT '行为类型',
    `behavior_action` varchar(50) NOT NULL COMMENT '行为动作',
    `target_id` int(10) UNSIGNED DEFAULT NULL COMMENT '目标ID',
    `target_type` varchar(50) DEFAULT '' COMMENT '目标类型',
    `device_id` varchar(64) DEFAULT '' COMMENT '设备ID',
    `ip` varchar(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` varchar(500) DEFAULT '' COMMENT 'User-Agent',
    `duration` int(10) NOT NULL DEFAULT '0' COMMENT '行为持续时间(秒)',
    `extra_data` text COMMENT '额外数据JSON',
    `risk_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否标记为风险',
    `risk_reason` varchar(255) DEFAULT '' COMMENT '风险原因',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_behavior_type` (`behavior_type`),
    KEY `idx_behavior_action` (`behavior_action`),
    KEY `idx_createtime` (`createtime`),
    KEY `idx_device_id` (`device_id`),
    KEY `idx_ip` (`ip`),
    KEY `idx_risk_flag` (`risk_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录表';

-- ----------------------------
-- Table structure for advn_user_behavior_stat (行为统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_behavior_stat` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `video_watch_count` int(10) NOT NULL DEFAULT '0' COMMENT '视频观看次数',
    `video_watch_duration` int(10) NOT NULL DEFAULT '0' COMMENT '视频观看总时长(秒)',
    `video_skip_count` int(10) NOT NULL DEFAULT '0' COMMENT '视频跳过次数',
    `video_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '视频获得金币',
    `task_complete_count` int(10) NOT NULL DEFAULT '0' COMMENT '任务完成次数',
    `task_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '任务获得金币',
    `withdraw_count` int(10) NOT NULL DEFAULT '0' COMMENT '提现次数',
    `withdraw_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
    `redpacket_grab_count` int(10) NOT NULL DEFAULT '0' COMMENT '抢红包次数',
    `redpacket_coin_earned` int(10) NOT NULL DEFAULT '0' COMMENT '红包获得金币',
    `invite_count` int(10) NOT NULL DEFAULT '0' COMMENT '邀请人数',
    `invite_reward` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT '邀请奖励',
    `login_count` int(10) NOT NULL DEFAULT '0' COMMENT '登录次数',
    `active_duration` int(10) NOT NULL DEFAULT '0' COMMENT '活跃时长(秒)',
    `device_change_count` int(10) NOT NULL DEFAULT '0' COMMENT '设备切换次数',
    `ip_change_count` int(10) NOT NULL DEFAULT '0' COMMENT 'IP切换次数',
    `violation_count` int(10) NOT NULL DEFAULT '0' COMMENT '违规次数',
    `risk_score_add` int(10) NOT NULL DEFAULT '0' COMMENT '新增风险分',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `stat_date`),
    KEY `idx_stat_date` (`stat_date`),
    KEY `idx_violation_count` (`violation_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为统计表';

-- ----------------------------
-- Table structure for advn_ban_record (封禁记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_ban_record` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `user_id` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `ban_type` enum('temporary','permanent') NOT NULL DEFAULT 'temporary' COMMENT '封禁类型',
    `ban_reason` varchar(255) NOT NULL COMMENT '封禁原因',
    `ban_source` enum('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '封禁来源',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '封禁时风险分',
    `rule_codes` text COMMENT '触发的规则编码JSON',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '操作管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '操作管理员名称',
    `start_time` int(10) UNSIGNED NOT NULL COMMENT '开始时间',
    `end_time` int(10) UNSIGNED DEFAULT NULL COMMENT '结束时间(NULL永久)',
    `duration` int(10) NOT NULL DEFAULT '0' COMMENT '封禁时长(秒,0永久)',
    `status` enum('active','released','expired') NOT NULL DEFAULT 'active' COMMENT '状态',
    `release_time` int(10) UNSIGNED DEFAULT NULL COMMENT '解封时间',
    `release_reason` varchar(255) DEFAULT '' COMMENT '解封原因',
    `release_admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '解封管理员ID',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_ban_type` (`ban_type`),
    KEY `idx_status` (`status`),
    KEY `idx_start_time` (`start_time`),
    KEY `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='封禁记录表';

-- ----------------------------
-- Table structure for advn_risk_whitelist (风控白名单表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_whitelist` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `type` enum('user','ip','device') NOT NULL COMMENT '类型',
    `value` varchar(100) NOT NULL COMMENT '值',
    `reason` varchar(255) DEFAULT '' COMMENT '加入原因',
    `expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '过期时间(NULL永久)',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '添加管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '管理员名称',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    KEY `idx_type` (`type`),
    KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控白名单表';

-- ----------------------------
-- Table structure for advn_risk_blacklist (风控黑名单表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_blacklist` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `type` enum('user','ip','device') NOT NULL COMMENT '类型',
    `value` varchar(100) NOT NULL COMMENT '值',
    `reason` varchar(255) DEFAULT '' COMMENT '加入原因',
    `source` enum('auto','manual') NOT NULL DEFAULT 'auto' COMMENT '来源',
    `risk_score` int(10) NOT NULL DEFAULT '0' COMMENT '风险分',
    `expire_time` int(10) UNSIGNED DEFAULT NULL COMMENT '过期时间(NULL永久)',
    `admin_id` int(10) UNSIGNED DEFAULT NULL COMMENT '添加管理员ID',
    `admin_name` varchar(50) DEFAULT '' COMMENT '管理员名称',
    `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_value` (`type`, `value`),
    KEY `idx_type` (`type`),
    KEY `idx_expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控黑名单表';

-- ----------------------------
-- Table structure for advn_risk_stat (风控统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_stat` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
    `stat_date` date NOT NULL COMMENT '统计日期',
    `total_requests` bigint(20) NOT NULL DEFAULT '0' COMMENT '总请求数',
    `blocked_requests` bigint(20) NOT NULL DEFAULT '0' COMMENT '拦截请求数',
    `warn_count` int(10) NOT NULL DEFAULT '0' COMMENT '警告次数',
    `block_count` int(10) NOT NULL DEFAULT '0' COMMENT '拦截次数',
    `freeze_count` int(10) NOT NULL DEFAULT '0' COMMENT '冻结次数',
    `ban_count` int(10) NOT NULL DEFAULT '0' COMMENT '封禁次数',
    `unique_ip_count` int(10) NOT NULL DEFAULT '0' COMMENT '独立IP数',
    `unique_device_count` int(10) NOT NULL DEFAULT '0' COMMENT '独立设备数',
    `proxy_detected_count` int(10) NOT NULL DEFAULT '0' COMMENT '检测到代理数',
    `emulator_detected_count` int(10) NOT NULL DEFAULT '0' COMMENT '检测到模拟器数',
    `rule_trigger_stats` text COMMENT '规则触发统计JSON',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控统计表';

-- ----------------------------
-- Table structure for advn_anticheat_log (防刷日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_anticheat_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '类型: abnormal_speed/hourly_watch_exceed/high_risk_score',
    `data` TEXT DEFAULT NULL COMMENT '详细数据(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='防刷日志表';

-- ============================================================================
-- 第六部分: 邀请分佣系统表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_invite_commission_config (分佣配置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_invite_commission_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
    `code` VARCHAR(50) NOT NULL COMMENT '配置代码: withdraw/video/red_packet',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '配置描述',
    `level1_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '一级分佣比例(0.2表示20%)',
    `level2_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '二级分佣比例',
    `level1_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '一级固定金额(元)',
    `level2_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '二级固定金额(元)',
    `calc_type` VARCHAR(20) DEFAULT 'rate' COMMENT '计算方式: rate=比例, fixed=固定, rate_and_fixed=比例+固定',
    `min_amount` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '最低触发金额(元)',
    `max_commission` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '单笔最大佣金(元, 0=不限)',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日最大佣金次数(0=不限)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级要求',
    `need_realname` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否需要实名: 0=否, 1=是',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分佣配置表';

-- ----------------------------
-- Table structure for advn_invite_commission_log (分佣记录表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_invite_commission_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '分佣订单号',
    `source_type` VARCHAR(30) NOT NULL COMMENT '来源类型: withdraw/video/red_packet/game',
    `source_id` INT UNSIGNED DEFAULT NULL COMMENT '来源记录ID',
    `source_order_no` VARCHAR(32) DEFAULT NULL COMMENT '来源订单号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '产生收益的用户ID(下级)',
    `parent_id` INT UNSIGNED NOT NULL COMMENT '获得佣金的用户ID(上级)',
    `level` TINYINT UNSIGNED NOT NULL COMMENT '层级: 1=一级, 2=二级',
    `source_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '来源金额(元)',
    `commission_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '分佣比例',
    `commission_fixed` DECIMAL(10,2) UNSIGNED DEFAULT 0.00 COMMENT '固定佣金',
    `commission_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '佣金金额(元)',
    `coin_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '佣金金币',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待结算, 1=已结算, 2=已取消, 3=已冻结',
    `settle_time` INT UNSIGNED DEFAULT NULL COMMENT '结算时间',
    `cancel_reason` VARCHAR(200) DEFAULT NULL COMMENT '取消原因',
    `config_id` INT UNSIGNED DEFAULT NULL COMMENT '使用的配置ID',
    `remark` VARCHAR(200) DEFAULT NULL COMMENT '备注',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_source` (`source_type`, `source_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分佣记录表';

-- ----------------------------
-- Table structure for advn_user_invite_stat (用户邀请统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_invite_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_invite_count` INT UNSIGNED DEFAULT 0 COMMENT '累计邀请人数',
    `level1_count` INT UNSIGNED DEFAULT 0 COMMENT '一级邀请人数',
    `level2_count` INT UNSIGNED DEFAULT 0 COMMENT '二级邀请人数(下级的下级)',
    `valid_invite_count` INT UNSIGNED DEFAULT 0 COMMENT '有效邀请人数(已产生收益)',
    `new_invite_today` INT UNSIGNED DEFAULT 0 COMMENT '今日新增邀请',
    `new_invite_yesterday` INT UNSIGNED DEFAULT 0 COMMENT '昨日新增邀请',
    `new_invite_week` INT UNSIGNED DEFAULT 0 COMMENT '本周新增邀请',
    `new_invite_month` INT UNSIGNED DEFAULT 0 COMMENT '本月新增邀请',
    `last_invite_time` INT UNSIGNED DEFAULT NULL COMMENT '最后邀请时间',
    `last_invite_user_id` INT UNSIGNED DEFAULT NULL COMMENT '最后邀请的用户ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户邀请统计表';

-- ----------------------------
-- Table structure for advn_user_commission_stat (用户佣金统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_commission_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计佣金(元)',
    `total_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计金币',
    `level1_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '一级佣金(元)',
    `level2_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '二级佣金(元)',
    `withdraw_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '提现分佣收益(元)',
    `video_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '视频分佣收益(元)',
    `red_packet_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '红包分佣收益(元)',
    `game_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '游戏分佣收益(元)',
    `other_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '其他分佣收益(元)',
    `today_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '今日佣金(元)',
    `today_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '今日金币',
    `yesterday_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '昨日佣金(元)',
    `week_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '本周佣金(元)',
    `month_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '本月佣金(元)',
    `pending_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '待结算佣金(元)',
    `frozen_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '冻结佣金(元)',
    `canceled_commission` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '已取消佣金(元)',
    `withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '提现分佣次数',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频分佣次数',
    `red_packet_count` INT UNSIGNED DEFAULT 0 COMMENT '红包分佣次数',
    `game_count` INT UNSIGNED DEFAULT 0 COMMENT '游戏分佣次数',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`),
    KEY `idx_total_commission` (`total_commission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户佣金统计表';

-- ----------------------------
-- Table structure for advn_daily_commission_stat (每日佣金统计表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_daily_commission_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    `total_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '总佣金(元)',
    `total_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '总金币',
    `withdraw_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '提现分佣(元)',
    `video_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '视频分佣(元)',
    `red_packet_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '红包分佣(元)',
    `game_commission` DECIMAL(14,4) UNSIGNED DEFAULT 0.0000 COMMENT '游戏分佣(元)',
    `total_count` INT UNSIGNED DEFAULT 0 COMMENT '总次数',
    `user_count` INT UNSIGNED DEFAULT 0 COMMENT '获得佣金用户数',
    `level1_count` INT UNSIGNED DEFAULT 0 COMMENT '一级分佣次数',
    `level2_count` INT UNSIGNED DEFAULT 0 COMMENT '二级分佣次数',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日佣金统计表';

-- ============================================================================
-- 第七部分: 系统配置表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_system_config (系统配置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_system_config` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` varchar(50) NOT NULL DEFAULT '' COMMENT '配置分组',
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
    `value` text COMMENT '配置值',
    `type` enum('string','integer','float','boolean','json','array') NOT NULL DEFAULT 'string' COMMENT '值类型',
    `title` varchar(100) NOT NULL DEFAULT '' COMMENT '配置标题',
    `tip` varchar(255) DEFAULT '' COMMENT '配置说明',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态:0=禁用,1=启用',
    `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
    `createtime` int(10) UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` int(10) UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_name` (`group`, `name`),
    KEY `idx_group` (`group`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ============================================================================
-- 第八部分: 数据迁移归档表
-- ============================================================================

-- ----------------------------
-- Table structure for advn_coin_log_archive (金币流水归档表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_coin_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '流水类型',
    `amount` DECIMAL(18,2) NOT NULL COMMENT '金币数量(正数=收入,负数=支出)',
    `balance_before` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动前余额',
    `balance_after` DECIMAL(18,2) UNSIGNED DEFAULT NULL COMMENT '变动后余额',
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: video/task/withdraw/invite',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联记录ID',
    `title` VARCHAR(100) DEFAULT NULL COMMENT '流水标题',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '详细描述',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '操作IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_user_date` (`user_id`, `create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='金币流水归档表';

-- ----------------------------
-- Table structure for advn_video_watch_record_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_watch_record_archive` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `collection_id` INT UNSIGNED DEFAULT NULL COMMENT '合集ID',
    `watch_duration` INT UNSIGNED DEFAULT 0 COMMENT '累计观看时长(秒)',
    `watch_progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `watch_count` INT UNSIGNED DEFAULT 0 COMMENT '观看次数',
    `last_position` INT UNSIGNED DEFAULT 0 COMMENT '上次观看位置(秒)',
    `is_completed` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否看完: 0=否, 1=是',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间',
    `reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '奖励状态: 0=未领取, 1=已领取, 2=已失效',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `reward_time` INT UNSIGNED DEFAULT NULL COMMENT '奖励时间',
    `reward_rule_id` INT UNSIGNED DEFAULT NULL COMMENT '使用的规则ID',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '观看IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '首次观看时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '最后观看时间',
    `date_key` DATE DEFAULT NULL COMMENT '日期键(便于统计)',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_video_id` (`video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频观看记录归档表';

-- ----------------------------
-- Table structure for advn_video_watch_session_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_video_watch_session_archive` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `session_id` VARCHAR(64) NOT NULL COMMENT '会话ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '视频ID',
    `start_time` INT UNSIGNED NOT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '观看时长(秒)',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '观看进度(%)',
    `ip` VARCHAR(50) DEFAULT NULL,
    `device_id` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(20) DEFAULT NULL,
    `app_version` VARCHAR(20) DEFAULT NULL,
    `rewarded` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否获得奖励',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '获得金币',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_video` (`user_id`, `video_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='观看会话归档表';

-- ----------------------------
-- Table structure for advn_risk_log_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_risk_log_archive` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `rule_code` VARCHAR(50) NOT NULL COMMENT '触发的规则编码',
    `rule_name` VARCHAR(100) NOT NULL COMMENT '规则名称',
    `rule_type` ENUM('video','task','withdraw','redpacket','invite','global') NOT NULL COMMENT '规则类型',
    `risk_level` TINYINT UNSIGNED NOT NULL COMMENT '风险等级',
    `trigger_value` DECIMAL(15,4) NOT NULL COMMENT '触发值',
    `threshold` DECIMAL(15,4) NOT NULL COMMENT '阈值',
    `score_add` INT UNSIGNED NOT NULL COMMENT '增加的风险分',
    `action` ENUM('warn','block','freeze','ban') NOT NULL COMMENT '执行动作',
    `action_duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '动作持续时间',
    `action_expire_time` INT UNSIGNED DEFAULT NULL COMMENT '动作到期时间',
    `device_id` VARCHAR(64) DEFAULT '' COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `request_data` TEXT COMMENT '请求数据JSON',
    `response_data` TEXT COMMENT '响应数据JSON',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_rule_code` (`rule_code`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='风控日志归档表';

-- ----------------------------
-- Table structure for advn_user_behavior_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_user_behavior_archive` (
    `id` BIGINT UNSIGNED NOT NULL COMMENT '主键',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `behavior_type` ENUM('login','video_watch','task_complete','withdraw','redpacket_grab','invite','other') NOT NULL COMMENT '行为类型',
    `behavior_action` VARCHAR(50) NOT NULL COMMENT '行为动作',
    `target_id` INT UNSIGNED DEFAULT NULL COMMENT '目标ID',
    `target_type` VARCHAR(50) DEFAULT '' COMMENT '目标类型',
    `device_id` VARCHAR(64) DEFAULT '' COMMENT '设备ID',
    `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
    `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User-Agent',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '行为持续时间(秒)',
    `extra_data` TEXT COMMENT '额外数据JSON',
    `risk_flag` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否标记为风险',
    `risk_reason` VARCHAR(255) DEFAULT '' COMMENT '风险原因',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_behavior_type` (`behavior_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录归档表';

-- ----------------------------
-- Table structure for advn_anticheat_log_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_anticheat_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(30) NOT NULL COMMENT '类型: abnormal_speed/hourly_watch_exceed/high_risk_score',
    `data` TEXT DEFAULT NULL COMMENT '详细数据(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='防刷日志归档表';

-- ----------------------------
-- Table structure for advn_red_packet_record_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_red_packet_record_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '记录ID',
    `packet_id` INT UNSIGNED NOT NULL COMMENT '红包ID',
    `packet_type` VARCHAR(30) NOT NULL COMMENT '红包类型',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '获得金币数量',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=已退回, 1=正常',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '领取IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `create_date` DATE DEFAULT NULL COMMENT '创建日期',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包领取记录归档表';

-- ----------------------------
-- Table structure for advn_invite_commission_log_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_invite_commission_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '获得佣金的用户ID',
    `from_user_id` INT UNSIGNED NOT NULL COMMENT '贡献佣金的用户ID',
    `order_type` VARCHAR(30) NOT NULL COMMENT '订单类型: video/task/withdraw',
    `order_id` INT UNSIGNED DEFAULT NULL COMMENT '关联订单ID',
    `order_no` VARCHAR(64) DEFAULT NULL COMMENT '关联订单号',
    `order_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '订单金额(金币)',
    `commission_rate` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '佣金比例',
    `commission_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '佣金金额(金币)',
    `commission_level` TINYINT UNSIGNED DEFAULT 1 COMMENT '佣金等级: 1=一级, 2=二级',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待结算, 1=已结算, 2=已取消, 3=已冻结',
    `settle_time` INT UNSIGNED DEFAULT NULL COMMENT '结算时间',
    `cancel_reason` VARCHAR(200) DEFAULT NULL COMMENT '取消原因',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_from_user_id` (`from_user_id`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请分佣日志归档表';

-- ----------------------------
-- Table structure for advn_wechat_transfer_log_archive
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_wechat_transfer_log_archive` (
    `id` INT UNSIGNED NOT NULL COMMENT '日志ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '微信转账单号',
    `partner_trade_no` VARCHAR(64) DEFAULT NULL COMMENT '商户订单号',
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '收款用户openid',
    `amount` INT UNSIGNED DEFAULT 0 COMMENT '转账金额(分)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '转账描述',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据(JSON)',
    `response_data` TEXT DEFAULT NULL COMMENT '响应数据(JSON)',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待处理, 1=处理中, 2=成功, 3=失败',
    `error_code` VARCHAR(50) DEFAULT NULL COMMENT '错误码',
    `error_msg` VARCHAR(200) DEFAULT NULL COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信打款日志归档表';

-- ----------------------------
-- Table structure for advn_data_migration_config (数据迁移配置表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_data_migration_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `table_name` VARCHAR(50) NOT NULL COMMENT '表名',
    `archive_days` INT UNSIGNED DEFAULT 90 COMMENT '归档天数',
    `batch_size` INT UNSIGNED DEFAULT 1000 COMMENT '批处理数量',
    `delete_source` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否删除源数据',
    `auto_archive` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否自动归档',
    `archive_schedule` VARCHAR(50) DEFAULT NULL COMMENT '归档计划(cron表达式)',
    `last_archive_time` INT UNSIGNED DEFAULT NULL COMMENT '最后归档时间',
    `last_archive_count` INT UNSIGNED DEFAULT 0 COMMENT '最后归档数量',
    `total_archive_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '累计归档数量',
    `enabled` TINYINT UNSIGNED DEFAULT 1 COMMENT '是否启用',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移配置表';

-- ----------------------------
-- Table structure for advn_data_migration_log (数据迁移日志表)
-- ----------------------------
CREATE TABLE IF NOT EXISTS `advn_data_migration_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `table_name` VARCHAR(50) NOT NULL COMMENT '表名',
    `action` VARCHAR(30) NOT NULL COMMENT '操作类型: migrate/clean/stats',
    `total_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '总数据量',
    `migrated_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '迁移数量',
    `failed_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '失败数量',
    `deleted_count` BIGINT UNSIGNED DEFAULT 0 COMMENT '删除数量',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '执行时长(秒)',
    `params` TEXT COMMENT '执行参数JSON',
    `result` TEXT COMMENT '执行结果JSON',
    `error` TEXT COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_table_name` (`table_name`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据迁移日志表';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 表结构创建完成
-- ============================================================================
