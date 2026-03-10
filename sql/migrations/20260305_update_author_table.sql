-- ============================================================================
-- 发布者/作者表完善
-- 添加更多信息字段和后台管理菜单
-- 创建时间: 2026-03-05
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------
-- 1. 添加发布者表新字段
-- 注意：如果字段已存在会报错，但迁移工具会自动忽略Duplicate错误
-- ----------------------------

ALTER TABLE `advn_author` ADD COLUMN `nickname` varchar(50) DEFAULT '' COMMENT '昵称' AFTER `name`;
ALTER TABLE `advn_author` ADD COLUMN `phone` varchar(20) DEFAULT '' COMMENT '联系电话' AFTER `avatar`;
ALTER TABLE `advn_author` ADD COLUMN `email` varchar(100) DEFAULT '' COMMENT '邮箱' AFTER `phone`;
ALTER TABLE `advn_author` ADD COLUMN `wechat` varchar(50) DEFAULT '' COMMENT '微信号' AFTER `email`;
ALTER TABLE `advn_author` ADD COLUMN `ip` varchar(50) DEFAULT '' COMMENT 'IP地址' AFTER `wechat`;
ALTER TABLE `advn_author` ADD COLUMN `region` varchar(100) DEFAULT '' COMMENT '地区' AFTER `ip`;
ALTER TABLE `advn_author` ADD COLUMN `country` varchar(50) DEFAULT '' COMMENT '国家' AFTER `region`;
ALTER TABLE `advn_author` ADD COLUMN `province` varchar(50) DEFAULT '' COMMENT '省份' AFTER `country`;
ALTER TABLE `advn_author` ADD COLUMN `city` varchar(50) DEFAULT '' COMMENT '城市' AFTER `province`;
ALTER TABLE `advn_author` ADD COLUMN `video_count` int unsigned DEFAULT 0 COMMENT '视频数量' AFTER `description`;
ALTER TABLE `advn_author` ADD COLUMN `total_views` int unsigned DEFAULT 0 COMMENT '总播放量' AFTER `video_count`;
ALTER TABLE `advn_author` ADD COLUMN `total_likes` int unsigned DEFAULT 0 COMMENT '总点赞数' AFTER `total_views`;
ALTER TABLE `advn_author` ADD COLUMN `total_coins` int unsigned DEFAULT 0 COMMENT '总获得金币' AFTER `total_likes`;
ALTER TABLE `advn_author` ADD COLUMN `verify_status` tinyint DEFAULT 0 COMMENT '认证状态:0=未认证,1=已认证,2=认证中' AFTER `total_coins`;
ALTER TABLE `advn_author` ADD COLUMN `verify_type` varchar(30) DEFAULT '' COMMENT '认证类型' AFTER `verify_status`;
ALTER TABLE `advn_author` ADD COLUMN `verify_info` varchar(255) DEFAULT '' COMMENT '认证信息' AFTER `verify_type`;
ALTER TABLE `advn_author` ADD COLUMN `remark` varchar(500) DEFAULT '' COMMENT '备注' AFTER `verify_info`;
ALTER TABLE `advn_author` ADD COLUMN `weigh` int DEFAULT 0 COMMENT '排序权重' AFTER `remark`;

-- ----------------------------
-- 2. 更新示例数据
-- ----------------------------
UPDATE `advn_author` SET 
    `nickname` = '张三',
    `phone` = '13800138001',
    `email` = 'zhangsan@example.com',
    `wechat` = 'zhangsan_wx',
    `ip` = '192.168.1.100',
    `region` = '中国 广东 深圳',
    `country` = '中国',
    `province` = '广东',
    `city` = '深圳',
    `video_count` = 10,
    `total_views` = 50000,
    `total_likes` = 1200,
    `verify_status` = 1,
    `verify_type` = '个人认证',
    `weigh` = 100
WHERE `id` = 1;

UPDATE `advn_author` SET 
    `nickname` = '李四',
    `phone` = '13800138002',
    `email` = 'lisi@example.com',
    `ip` = '192.168.1.101',
    `region` = '中国 北京',
    `country` = '中国',
    `province` = '北京',
    `city` = '北京',
    `video_count` = 25,
    `total_views` = 120000,
    `total_likes` = 3500,
    `verify_status` = 1,
    `verify_type` = '企业认证',
    `weigh` = 99
WHERE `id` = 2;

UPDATE `advn_author` SET 
    `nickname` = '王五',
    `phone` = '13800138003',
    `ip` = '192.168.1.102',
    `region` = '中国 上海',
    `country` = '中国',
    `province` = '上海',
    `city` = '上海',
    `video_count` = 8,
    `total_views` = 30000,
    `total_likes` = 800,
    `verify_status` = 0,
    `weigh` = 98
WHERE `id` = 3;

-- ----------------------------
-- 3. 添加后台菜单
-- ----------------------------

-- 获取视频管理父级菜单ID
SET @video_id = (SELECT id FROM `advn_auth_rule` WHERE name = 'video' LIMIT 1);

-- 删除已存在的发布者管理菜单
DELETE FROM `advn_auth_rule` WHERE name = 'video/author';
DELETE FROM `advn_auth_rule` WHERE name LIKE 'video/author/%';

-- 插入发布者管理主菜单
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @video_id, 'video/author', '发布者管理', 'fa fa-user-circle', 'video/author', '', '视频发布者/作者管理', 1, 'addtabs', '', 'fbzgl', 'fabuzheguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 98, 'normal');

-- 获取发布者管理菜单ID
SET @author_id = LAST_INSERT_ID();

-- 插入子菜单（权限节点）
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @author_id, 'video/author/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @author_id, 'video/author/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @author_id, 'video/author/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @author_id, 'video/author/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @author_id, 'video/author/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
