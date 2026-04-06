-- ==========================================
-- 单页管理模块 SQL 迁移脚本
-- 表前缀: advn_
-- ==========================================

-- 1. 创建单页分类表
CREATE TABLE IF NOT EXISTS `advn_singlepage_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '分类描述',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重(排序)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 1=启用, 0=禁用',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='单页分类表';

-- 2. 创建单页表
CREATE TABLE IF NOT EXISTS `advn_singlepage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '页面标题',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图片',
  `content` longtext COMMENT '页面内容(富文本)',
  `tpl` varchar(100) NOT NULL DEFAULT '' COMMENT '自定义模板',
  `weigh` int(10) NOT NULL DEFAULT '0' COMMENT '权重(排序)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态: 1=启用, 0=禁用',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `deletetime` int(10) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_weigh` (`weigh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='单页管理表';

-- 3. 插入菜单规则 (advn_auth_rule)
-- 请根据你的实际父级菜单ID调整 pid 的值
-- 假设顶级后台菜单 ID 为 0，插入一级菜单"单页管理"
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (0, 'singlepage', '单页管理', 'fa fa-file-text', '', '管理网站单页内容', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 50, 'normal');

-- 获取刚插入的单页管理菜单ID (用于子菜单的pid)
SET @sp_parent_id = LAST_INSERT_ID();

-- 单页分类管理
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_parent_id, 'singlepage/category', '分类管理', 'fa fa-folder-open', '', '管理单页分类', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal');

SET @sp_cat_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_cat_id, 'singlepage/category/index', '查看', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_cat_id, 'singlepage/category/add', '添加', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_cat_id, 'singlepage/category/edit', '编辑', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_cat_id, 'singlepage/category/del', '删除', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_cat_id, 'singlepage/category/multi', '批量更新', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 单页内容管理
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_parent_id, 'singlepage/page', '单页管理', 'fa fa-file', '', '管理单页内容', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 20, 'normal');

SET @sp_page_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_page_id, 'singlepage/page/index', '查看', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_page_id, 'singlepage/page/add', '添加', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_page_id, 'singlepage/page/edit', '编辑', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_page_id, 'singlepage/page/del', '删除', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
INSERT INTO `advn_auth_rule` (`pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES (@sp_page_id, 'singlepage/page/multi', '批量更新', '', '', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- 4. 插入一些示例分类数据
INSERT INTO `advn_singlepage_category` (`name`, `description`, `weigh`, `status`, `createtime`, `updatetime`) VALUES
('关于我们', '公司介绍相关页面', 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('帮助中心', '使用帮助相关页面', 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('服务协议', '服务条款和隐私协议', 80, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('联系方式', '联系方式相关页面', 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
