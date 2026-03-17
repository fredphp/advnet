/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80043 (8.0.43)
 Source Host           : localhost:3306
 Source Schema         : mashangzhuan

 Target Server Type    : MySQL
 Target Server Version : 80043 (8.0.43)
 File Encoding         : 65001

 Date: 17/03/2026 14:34:02
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for advn_admin
-- ----------------------------
DROP TABLE IF EXISTS `advn_admin`;
CREATE TABLE `advn_admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `username` varchar(20) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT '' COMMENT '手机号码',
  `loginfailure` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `logintime` bigint DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `token` varchar(59) DEFAULT '' COMMENT 'Session标识',
  `status` varchar(30) NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员表';

-- ----------------------------
-- Records of advn_admin
-- ----------------------------
BEGIN;
INSERT INTO `advn_admin` (`id`, `username`, `nickname`, `password`, `salt`, `avatar`, `email`, `mobile`, `loginfailure`, `logintime`, `loginip`, `createtime`, `updatetime`, `token`, `status`) VALUES (1, 'admin', 'Admin', 'dd8be46383d21da22b2e91250c32ec28', '3e7c1a', '/assets/img/avatar.png', 'admin@admin.com', '', 0, 1770558286, '127.0.0.1', 1491635035, 1770558286, '26f22c66-1432-4012-ad84-f8188107aee8', 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `advn_admin_log`;
CREATE TABLE `advn_admin_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `username` varchar(30) DEFAULT '' COMMENT '管理员名字',
  `url` varchar(1500) DEFAULT '' COMMENT '操作页面',
  `title` varchar(100) DEFAULT '' COMMENT '日志标题',
  `content` longtext NOT NULL COMMENT '内容',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
  `createtime` bigint DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `name` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='管理员日志表';

-- ----------------------------
-- Records of advn_admin_log
-- ----------------------------
BEGIN;
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (1, 1, 'admin', '/vZztINgFdC.php/index/login', '登录', '{\"__token__\":\"***\",\"username\":\"admin\",\"password\":\"***\",\"captcha\":\"ya76\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178406);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (2, 1, 'admin', '/vZztINgFdC.php/addon/install', '插件管理', '{\"name\":\"command\",\"force\":\"0\",\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.1.3\",\"faversion\":\"1.6.1.20250430\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178422);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (3, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"command\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178422);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (4, 1, 'admin', '/vZztINgFdC.php/addon/install', '插件管理', '{\"name\":\"shop\",\"force\":\"0\",\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.2.8\",\"faversion\":\"1.6.1.20250430\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178430);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (5, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"shop\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178431);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (6, 1, 'admin', '/vZztINgFdC.php/addon/testdata', '插件管理', '{\"name\":\"shop\",\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.2.8\",\"faversion\":\"1.6.1.20250430\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178432);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (7, 1, 'admin', '/vZztINgFdC.php/addon/install', '插件管理', '{\"name\":\"signin\",\"force\":\"0\",\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.0.5\",\"faversion\":\"1.6.1.20250430\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178445);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (8, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"signin\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178445);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (9, 1, 'admin', '/vZztINgFdC.php/addon/install', '插件管理', '{\"name\":\"third\",\"force\":\"0\",\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.4.7\",\"faversion\":\"1.6.1.20250430\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178499);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (10, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"third\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178499);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (11, 1, 'admin', '/vZztINgFdC.php/addon/local', '插件管理', '{\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.6.1.20250430\",\"force\":\"0\",\"category\":\"\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178599);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (12, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"cobase\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178599);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (13, 1, 'admin', '/vZztINgFdC.php/auth/rule/edit/ids/343?dialog=1', '权限管理 / 菜单规则 / 编辑', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"346\",\"name\":\"third\",\"title\":\"第三方登录管理\",\"url\":\"\",\"icon\":\"fa fa-users\",\"condition\":\"\",\"menutype\":\"addtabs\",\"extend\":\"\",\"remark\":\"\",\"weigh\":\"0\",\"status\":\"normal\"},\"ids\":\"343\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178624);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (14, 1, 'admin', '/vZztINgFdC.php/auth/rule/edit/ids/85?dialog=1', '权限管理 / 菜单规则 / 编辑', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"346\",\"name\":\"command\",\"title\":\"在线命令管理\",\"url\":\"\",\"icon\":\"fa fa-terminal\",\"condition\":\"\",\"menutype\":\"addtabs\",\"extend\":\"\",\"remark\":\"\",\"weigh\":\"0\",\"status\":\"normal\"},\"ids\":\"85\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178631);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (15, 1, 'admin', '/vZztINgFdC.php/auth/rule/edit/ids/4?dialog=1', '权限管理 / 菜单规则 / 编辑', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"ismenu\":\"1\",\"pid\":\"346\",\"name\":\"addon\",\"title\":\"插件管理\",\"url\":\"\",\"icon\":\"fa fa-rocket\",\"condition\":\"\",\"menutype\":\"addtabs\",\"extend\":\"\",\"remark\":\"可在线安装、卸载、禁用、启用、配置、升级插件，插件升级前请做好备份。\",\"weigh\":\"0\",\"status\":\"normal\"},\"ids\":\"4\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770178638);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (16, 1, 'admin', '/vZztINgFdC.php/addon/local', '插件管理', '{\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.6.1.20250430\",\"force\":\"0\",\"category\":\"\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192017);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (17, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"coagent\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192017);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (18, 1, 'admin', '/vZztINgFdC.php/user/user/edit/ids/1?dialog=1', '会员管理 / 会员管理 / 编辑', '{\"dialog\":\"1\",\"__token__\":\"***\",\"row\":{\"id\":\"1\",\"group_id\":\"1\",\"username\":\"admin\",\"nickname\":\"admin\",\"password\":\"***\",\"email\":\"admin@163.com\",\"mobile\":\"13000000000\",\"avatar\":\"\\/assets\\/img\\/avatar.png\",\"level\":\"0\",\"gender\":\"0\",\"birthday\":\"2017-04-08\",\"bio\":\"\",\"money\":\"0.00\",\"score\":\"0\",\"successions\":\"1\",\"maxsuccessions\":\"1\",\"prevtime\":\"2017-04-08 15:03:55\",\"logintime\":\"2017-04-08 15:03:55\",\"loginip\":\"127.0.0.1\",\"loginfailure\":\"1\",\"joinip\":\"127.0.0.1\",\"jointime\":\"2017-04-08 15:03:55\",\"status\":\"normal\"},\"ids\":\"1\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192136);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (19, 1, 'admin', '/vZztINgFdC.php/addon/local', '插件管理', '{\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.6.1.20250430\",\"force\":\"0\",\"category\":\"\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192992);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (20, 1, 'admin', '/vZztINgFdC.php/addon/local', '插件管理', '{\"uid\":\"2980\",\"token\":\"***\",\"version\":\"1.6.1.20250430\",\"force\":\"1\",\"category\":\"\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192994);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (21, 1, 'admin', '/vZztINgFdC.php/addon/state', '插件管理 / 禁用启用', '{\"name\":\"cowithdraw\",\"action\":\"enable\",\"force\":\"0\"}', '113.120.52.151', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 SocketLog(client_id=xumu)', 1770192994);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (22, 1, 'admin', '/vZztINgFdC.php/index/login?url=/vZztINgFdC.php/auth/rule?ref=addtabs', '登录', '{\"url\":\"\\/vZztINgFdC.php\\/auth\\/rule?ref=addtabs\",\"__token__\":\"***\",\"username\":\"admin\",\"password\":\"***\",\"captcha\":\"i5b4\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463659);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (23, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (24, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (25, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (26, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (27, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (28, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (29, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463680);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (30, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770463681);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (31, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770466069);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (32, 1, 'admin', '/vZztINgFdC.php/ajax/upload', '', '{\"category\":\"\"}', '123.232.237.192', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 1770466069);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (33, 1, 'admin', '/vZztINgFdC.php/index/login', '登录', '{\"__token__\":\"***\",\"username\":\"admin\",\"password\":\"***\",\"captcha\":\"bnzl\"}', '136.158.56.48', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 1770552069);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (34, 0, 'Unknown', '/vZztINgFdC.php/index/logout', '', '{\"__token__\":\"***\"}', '136.158.56.48', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 1770555933);
INSERT INTO `advn_admin_log` (`id`, `admin_id`, `username`, `url`, `title`, `content`, `ip`, `useragent`, `createtime`) VALUES (35, 1, 'admin', '/vZztINgFdC.php/index/login', '登录', '{\"__token__\":\"***\",\"username\":\"admin\",\"password\":\"***\",\"captcha\":\"a5xq\"}', '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 1770558286);
COMMIT;

-- ----------------------------
-- Table structure for advn_area
-- ----------------------------
DROP TABLE IF EXISTS `advn_area`;
CREATE TABLE `advn_area` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int DEFAULT NULL COMMENT '父id',
  `shortname` varchar(100) DEFAULT NULL COMMENT '简称',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `mergename` varchar(255) DEFAULT NULL COMMENT '全称',
  `level` tinyint DEFAULT NULL COMMENT '层级:1=省,2=市,3=区/县',
  `pinyin` varchar(100) DEFAULT NULL COMMENT '拼音',
  `code` varchar(100) DEFAULT NULL COMMENT '长途区号',
  `zip` varchar(100) DEFAULT NULL COMMENT '邮编',
  `first` varchar(50) DEFAULT NULL COMMENT '首字母',
  `lng` varchar(100) DEFAULT NULL COMMENT '经度',
  `lat` varchar(100) DEFAULT NULL COMMENT '纬度',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='地区表';

-- ----------------------------
-- Records of advn_area
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_attachment
-- ----------------------------
DROP TABLE IF EXISTS `advn_attachment`;
CREATE TABLE `advn_attachment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category` varchar(50) DEFAULT '' COMMENT '类别',
  `admin_id` int unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `url` varchar(255) DEFAULT '' COMMENT '物理路径',
  `imagewidth` int unsigned DEFAULT '0' COMMENT '宽度',
  `imageheight` int unsigned DEFAULT '0' COMMENT '高度',
  `imagetype` varchar(30) DEFAULT '' COMMENT '图片类型',
  `imageframes` int unsigned NOT NULL DEFAULT '0' COMMENT '图片帧数',
  `filename` varchar(100) DEFAULT '' COMMENT '文件名称',
  `filesize` int unsigned NOT NULL DEFAULT '0' COMMENT '文件大小',
  `mimetype` varchar(100) DEFAULT '' COMMENT 'mime类型',
  `extparam` varchar(255) DEFAULT '' COMMENT '透传数据',
  `createtime` bigint DEFAULT NULL COMMENT '创建日期',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `uploadtime` bigint DEFAULT NULL COMMENT '上传时间',
  `storage` varchar(100) NOT NULL DEFAULT 'local' COMMENT '存储位置',
  `sha1` varchar(40) DEFAULT '' COMMENT '文件 sha1编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='附件表';

-- ----------------------------
-- Records of advn_attachment
-- ----------------------------
BEGIN;
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (1, '', 1, 0, '/assets/img/qrcode.png', 150, 150, 'png', 0, 'qrcode.png', 21859, 'image/png', '', 1491635035, 1491635035, 1491635035, 'local', '17163603d0263e4838b9387ff2cd4877e8b018f6');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (2, '', 1, 0, '/uploads/20260207/0a9ea4745a7ccd9e2ae340ffefb72b60.png', 72, 72, 'png', 0, 'Frame@3x.png', 4708, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '607401d9d226c20031ae5963ba1524e943b4d7cb');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (3, '', 1, 0, '/uploads/20260207/51c925bb62334bd74d3c7735db24b9cc.png', 72, 72, 'png', 0, 'Frame@3x(1).png', 4819, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', 'f8402ec5c03b8f78254fcccbe08c50eb805d4be7');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (4, '', 1, 0, '/uploads/20260207/41c9f037985d27a5d3333db8d4a5b291.png', 72, 72, 'png', 0, 'Frame@3x(2).png', 3519, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '3b01c2c6107fdcb35529910a716755e028859839');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (5, '', 1, 0, '/uploads/20260207/d6058ca126a60fe30230d0d706d86117.png', 72, 72, 'png', 0, 'Frame@3x(3).png', 5252, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '4b74dfe79bc752423d6f11fe43c5bbd67e122058');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (6, '', 1, 0, '/uploads/20260207/26de5b71fb61ad800ef8b6986766fb22.png', 72, 72, 'png', 0, 'Frame@3x(4).png', 1741, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '96e6f5826cc7c0e690afb54dcbc65d9d1db85835');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (7, '', 1, 0, '/uploads/20260207/fed595937c90f3cb7a11c13ba3b624cb.png', 72, 72, 'png', 0, 'Frame@3x(5).png', 1939, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '78a12ae4366f58d75b84b92a4108ff7504bd1f6f');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (8, '', 1, 0, '/uploads/20260207/483e2bd37c27d0d3f07d376c83cb6d52.png', 72, 72, 'png', 0, 'Frame@3x(6).png', 3020, 'image/png', '', 1770463680, 1770463680, 1770463680, 'local', '348255795e8d02046607b4e40ea4b5b9b0db6fa8');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (9, '', 1, 0, '/uploads/20260207/96be97c4b9e42623b7be8b701846dde2.png', 72, 72, 'png', 0, 'Frame@3x(7).png', 2319, 'image/png', '', 1770463681, 1770463681, 1770463681, 'local', 'bd52456968b3f75fe5cbd8e11e7f3b6bc9d8f72b');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (10, '', 1, 0, '/uploads/20260207/a8655f1c2676a16a07157fcd456c8852.png', 72, 72, 'png', 0, 'Frame@3x.png', 3901, 'image/png', '', 1770466069, 1770466069, 1770466069, 'local', '65c61fe0c711ee8fd6dabcf0da1ceba6b9c83056');
INSERT INTO `advn_attachment` (`id`, `category`, `admin_id`, `user_id`, `url`, `imagewidth`, `imageheight`, `imagetype`, `imageframes`, `filename`, `filesize`, `mimetype`, `extparam`, `createtime`, `updatetime`, `uploadtime`, `storage`, `sha1`) VALUES (11, '', 1, 0, '/uploads/20260207/4333f33c4f213f95d1c1f3ec2adfd88b.png', 72, 72, 'png', 0, 'Frame@3x(1).png', 5473, 'image/png', '', 1770466069, 1770466069, 1770466069, 'local', '38b11ae5132da99e2b4e8e1dc971b42f650e102f');
COMMIT;

-- ----------------------------
-- Table structure for advn_auth_group
-- ----------------------------
DROP TABLE IF EXISTS `advn_auth_group`;
CREATE TABLE `advn_auth_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父组别',
  `name` varchar(100) DEFAULT '' COMMENT '组名',
  `rules` text NOT NULL COMMENT '规则ID',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分组表';

-- ----------------------------
-- Records of advn_auth_group
-- ----------------------------
BEGIN;
INSERT INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (1, 0, 'Admin group', '*', 1491635035, 1491635035, 'normal');
INSERT INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (2, 1, 'Second group', '13,14,16,15,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,1,9,10,11,7,6,8,2,4,5', 1491635035, 1491635035, 'normal');
INSERT INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (3, 2, 'Third group', '1,4,9,10,11,13,14,15,16,17,40,41,42,43,44,45,46,47,48,49,50,55,56,57,58,59,60,61,62,63,64,65,5', 1491635035, 1491635035, 'normal');
INSERT INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (4, 1, 'Second group 2', '1,4,13,14,15,16,17,55,56,57,58,59,60,61,62,63,64,65', 1491635035, 1491635035, 'normal');
INSERT INTO `advn_auth_group` (`id`, `pid`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (5, 2, 'Third group 2', '1,2,6,7,8,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34', 1491635035, 1491635035, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_auth_group_access
-- ----------------------------
DROP TABLE IF EXISTS `advn_auth_group_access`;
CREATE TABLE `advn_auth_group_access` (
  `uid` int unsigned NOT NULL COMMENT '会员ID',
  `group_id` int unsigned NOT NULL COMMENT '级别ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='权限分组表';

-- ----------------------------
-- Records of advn_auth_group_access
-- ----------------------------
BEGIN;
INSERT INTO `advn_auth_group_access` (`uid`, `group_id`) VALUES (1, 1);
COMMIT;

-- ----------------------------
-- Table structure for advn_auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `advn_auth_rule`;
CREATE TABLE `advn_auth_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('menu','file') NOT NULL DEFAULT 'file' COMMENT 'menu为菜单,file为权限节点',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(100) DEFAULT '' COMMENT '规则名称',
  `title` varchar(50) DEFAULT '' COMMENT '规则名称',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `url` varchar(255) DEFAULT '' COMMENT '规则URL',
  `condition` varchar(255) DEFAULT '' COMMENT '条件',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `ismenu` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否为菜单',
  `menutype` enum('addtabs','blank','dialog','ajax') DEFAULT NULL COMMENT '菜单类型',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  `py` varchar(30) DEFAULT '' COMMENT '拼音首字母',
  `pinyin` varchar(100) DEFAULT '' COMMENT '拼音',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `pid` (`pid`),
  KEY `weigh` (`weigh`)
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='节点表';

-- ----------------------------
-- Records of advn_auth_rule
-- ----------------------------
BEGIN;
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (1, 'file', 0, 'dashboard', 'Dashboard', 'fa fa-dashboard', '', '', 'Dashboard tips', 1, NULL, '', 'kzt', 'kongzhitai', 1491635035, 1491635035, 143, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (2, 'file', 0, 'general', 'General', 'fa fa-cogs', '', '', '', 1, NULL, '', 'cggl', 'changguiguanli', 1491635035, 1491635035, 137, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (3, 'file', 0, 'category', 'Category', 'fa fa-leaf', '', '', 'Category tips', 0, NULL, '', 'flgl', 'fenleiguanli', 1491635035, 1491635035, 119, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (4, 'file', 346, 'addon', '插件管理', 'fa fa-rocket', '', '', '可在线安装、卸载、禁用、启用、配置、升级插件，插件升级前请做好备份。', 1, 'addtabs', '', 'cjgl', 'chajianguanli', 1491635035, 1770178638, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (5, 'file', 0, 'auth', 'Auth', 'fa fa-group', '', '', '', 1, NULL, '', 'qxgl', 'quanxianguanli', 1491635035, 1491635035, 99, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (6, 'file', 2, 'general/config', 'Config', 'fa fa-cog', '', '', 'Config tips', 1, NULL, '', 'xtpz', 'xitongpeizhi', 1491635035, 1491635035, 60, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (7, 'file', 2, 'general/attachment', 'Attachment', 'fa fa-file-image-o', '', '', 'Attachment tips', 1, NULL, '', 'fjgl', 'fujianguanli', 1491635035, 1491635035, 53, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (8, 'file', 2, 'general/profile', 'Profile', 'fa fa-user', '', '', '', 1, NULL, '', 'grzl', 'gerenziliao', 1491635035, 1491635035, 34, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (9, 'file', 5, 'auth/admin', 'Admin', 'fa fa-user', '', '', 'Admin tips', 1, NULL, '', 'glygl', 'guanliyuanguanli', 1491635035, 1491635035, 118, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (10, 'file', 5, 'auth/adminlog', 'Admin log', 'fa fa-list-alt', '', '', 'Admin log tips', 1, NULL, '', 'glyrz', 'guanliyuanrizhi', 1491635035, 1491635035, 113, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (11, 'file', 5, 'auth/group', 'Group', 'fa fa-group', '', '', 'Group tips', 1, NULL, '', 'jsz', 'juesezu', 1491635035, 1491635035, 109, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (12, 'file', 5, 'auth/rule', 'Rule', 'fa fa-bars', '', '', 'Rule tips', 1, NULL, '', 'cdgz', 'caidanguize', 1491635035, 1491635035, 104, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (13, 'file', 1, 'dashboard/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 136, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (14, 'file', 1, 'dashboard/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 135, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (15, 'file', 1, 'dashboard/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 133, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (16, 'file', 1, 'dashboard/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 134, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (17, 'file', 1, 'dashboard/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 132, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (18, 'file', 6, 'general/config/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 52, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (19, 'file', 6, 'general/config/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 51, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (20, 'file', 6, 'general/config/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (21, 'file', 6, 'general/config/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 49, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (22, 'file', 6, 'general/config/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 48, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (23, 'file', 7, 'general/attachment/index', 'View', 'fa fa-circle-o', '', '', 'Attachment tips', 0, NULL, '', '', '', 1491635035, 1491635035, 59, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (24, 'file', 7, 'general/attachment/select', 'Select attachment', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 58, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (25, 'file', 7, 'general/attachment/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 57, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (26, 'file', 7, 'general/attachment/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 56, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (27, 'file', 7, 'general/attachment/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 55, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (28, 'file', 7, 'general/attachment/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 54, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (29, 'file', 8, 'general/profile/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 33, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (30, 'file', 8, 'general/profile/update', 'Update profile', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 32, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (31, 'file', 8, 'general/profile/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 31, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (32, 'file', 8, 'general/profile/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 30, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (33, 'file', 8, 'general/profile/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 29, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (34, 'file', 8, 'general/profile/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 28, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (35, 'file', 3, 'category/index', 'View', 'fa fa-circle-o', '', '', 'Category tips', 0, NULL, '', '', '', 1491635035, 1491635035, 142, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (36, 'file', 3, 'category/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 141, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (37, 'file', 3, 'category/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 140, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (38, 'file', 3, 'category/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 139, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (39, 'file', 3, 'category/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 138, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (40, 'file', 9, 'auth/admin/index', 'View', 'fa fa-circle-o', '', '', 'Admin tips', 0, NULL, '', '', '', 1491635035, 1491635035, 117, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (41, 'file', 9, 'auth/admin/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 116, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (42, 'file', 9, 'auth/admin/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 115, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (43, 'file', 9, 'auth/admin/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 114, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (44, 'file', 10, 'auth/adminlog/index', 'View', 'fa fa-circle-o', '', '', 'Admin log tips', 0, NULL, '', '', '', 1491635035, 1491635035, 112, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (45, 'file', 10, 'auth/adminlog/detail', 'Detail', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 111, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (46, 'file', 10, 'auth/adminlog/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 110, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (47, 'file', 11, 'auth/group/index', 'View', 'fa fa-circle-o', '', '', 'Group tips', 0, NULL, '', '', '', 1491635035, 1491635035, 108, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (48, 'file', 11, 'auth/group/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 107, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (49, 'file', 11, 'auth/group/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 106, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (50, 'file', 11, 'auth/group/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 105, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (51, 'file', 12, 'auth/rule/index', 'View', 'fa fa-circle-o', '', '', 'Rule tips', 0, NULL, '', '', '', 1491635035, 1491635035, 103, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (52, 'file', 12, 'auth/rule/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 102, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (53, 'file', 12, 'auth/rule/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 101, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (54, 'file', 12, 'auth/rule/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 100, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (55, 'file', 4, 'addon/index', 'View', 'fa fa-circle-o', '', '', 'Addon tips', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (56, 'file', 4, 'addon/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (57, 'file', 4, 'addon/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (58, 'file', 4, 'addon/del', 'Delete', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (59, 'file', 4, 'addon/downloaded', 'Local addon', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (60, 'file', 4, 'addon/state', 'Update state', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (63, 'file', 4, 'addon/config', 'Setting', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (64, 'file', 4, 'addon/refresh', 'Refresh', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (65, 'file', 4, 'addon/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (66, 'file', 0, 'user', 'User', 'fa fa-user-circle', '', '', '', 1, NULL, '', 'hygl', 'huiyuanguanli', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (67, 'file', 66, 'user/user', 'User', 'fa fa-user', '', '', '', 1, NULL, '', 'hygl', 'huiyuanguanli', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (68, 'file', 67, 'user/user/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (69, 'file', 67, 'user/user/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (70, 'file', 67, 'user/user/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (71, 'file', 67, 'user/user/del', 'Del', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (72, 'file', 67, 'user/user/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (73, 'file', 66, 'user/group', 'User group', 'fa fa-users', '', '', '', 1, NULL, '', 'hyfz', 'huiyuanfenzu', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (74, 'file', 73, 'user/group/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (75, 'file', 73, 'user/group/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (76, 'file', 73, 'user/group/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (77, 'file', 73, 'user/group/del', 'Del', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (78, 'file', 73, 'user/group/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (79, 'file', 66, 'user/rule', 'User rule', 'fa fa-circle-o', '', '', '', 1, NULL, '', 'hygz', 'huiyuanguize', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (80, 'file', 79, 'user/rule/index', 'View', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (81, 'file', 79, 'user/rule/del', 'Del', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (82, 'file', 79, 'user/rule/add', 'Add', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (83, 'file', 79, 'user/rule/edit', 'Edit', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (84, 'file', 79, 'user/rule/multi', 'Multi', 'fa fa-circle-o', '', '', '', 0, NULL, '', '', '', 1491635035, 1491635035, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (85, 'file', 346, 'command', '在线命令管理', 'fa fa-terminal', '', '', '', 1, 'addtabs', '', 'zxmlgl', 'zaixianminglingguanli', 1770178422, 1770178631, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (86, 'file', 85, 'command/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (87, 'file', 85, 'command/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (88, 'file', 85, 'command/detail', '详情', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xq', 'xiangqing', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (89, 'file', 85, 'command/command', '生成并执行命令', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'scbzxml', 'shengchengbingzhixingmingling', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (90, 'file', 85, 'command/execute', '再次执行命令', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zczxml', 'zaicizhixingmingling', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (91, 'file', 85, 'command/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (92, 'file', 85, 'command/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178422, 1770178422, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (93, 'file', 0, 'shop', '简单商城', 'fa fa-shopping-bag', '', '', '', 1, NULL, '', 'jdsc', 'jiandanshangcheng', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (94, 'file', 93, 'shop/goods', '商品管理', 'fa fa-shopping-basket', '', '', '', 1, NULL, '', 'spgl', 'shangpinguanli', 1770178429, 1770178430, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (95, 'file', 94, 'shop/goods/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (96, 'file', 94, 'shop/goods/recyclebin', '回收站', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hsz', 'huishouzhan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (97, 'file', 94, 'shop/goods/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (98, 'file', 94, 'shop/goods/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (99, 'file', 94, 'shop/goods/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (100, 'file', 94, 'shop/goods/destroy', '真实删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zssc', 'zhenshishanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (101, 'file', 94, 'shop/goods/restore', '还原', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hy', 'huanyuan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (102, 'file', 94, 'shop/goods/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (103, 'file', 94, 'shop/goods/select', '选择', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xz', 'xuanze', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (104, 'file', 93, 'shop/goods_sku', '商品属性', 'fa fa-line-chart', '', '', '', 0, NULL, '', 'spsx', 'shangpinshuxing', 1770178429, 1770178430, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (105, 'file', 104, 'shop/goods_sku/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (106, 'file', 104, 'shop/goods_sku/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (107, 'file', 104, 'shop/goods_sku/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (108, 'file', 104, 'shop/goods_sku/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (109, 'file', 104, 'shop/goods_sku/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (110, 'file', 93, 'shop/goods_sku_spec', '商品规格属性', 'fa fa-line-chart', '', '', '', 0, NULL, '', 'spggsx', 'shangpinguigeshuxing', 1770178429, 1770178430, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (111, 'file', 110, 'shop/goods_sku_spec/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (112, 'file', 110, 'shop/goods_sku_spec/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (113, 'file', 110, 'shop/goods_sku_spec/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (114, 'file', 110, 'shop/goods_sku_spec/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (115, 'file', 110, 'shop/goods_sku_spec/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (116, 'file', 93, 'shop/spec', '商品规格', 'fa fa-pencil', '', '', '', 0, NULL, '', 'spgg', 'shangpinguige', 1770178429, 1770178430, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (117, 'file', 116, 'shop/spec/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (118, 'file', 116, 'shop/spec/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (119, 'file', 116, 'shop/spec/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (120, 'file', 116, 'shop/spec/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (121, 'file', 116, 'shop/spec/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (122, 'file', 93, 'shop/spec_value', '商品规格值', 'fa fa-list', '', '', '', 0, NULL, '', 'spggz', 'shangpinguigezhi', 1770178429, 1770178430, 50, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (123, 'file', 122, 'shop/spec_value/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (124, 'file', 122, 'shop/spec_value/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (125, 'file', 122, 'shop/spec_value/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (126, 'file', 122, 'shop/spec_value/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (127, 'file', 122, 'shop/spec_value/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (128, 'file', 93, 'shop/sku_template', '规格模板', 'fa fa-asterisk', '', '', '', 0, NULL, '', 'ggmb', 'guigemuban', 1770178429, 1770178430, 49, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (129, 'file', 128, 'shop/sku_template/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (130, 'file', 128, 'shop/sku_template/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (131, 'file', 128, 'shop/sku_template/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (132, 'file', 128, 'shop/sku_template/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (133, 'file', 128, 'shop/sku_template/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (134, 'file', 93, 'shop/guarantee', '服务保障', 'fa fa-asterisk', '', '', '', 0, NULL, '', 'fwbz', 'fuwubaozhang', 1770178429, 1770178430, 49, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (135, 'file', 134, 'shop/guarantee/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (136, 'file', 134, 'shop/guarantee/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (137, 'file', 134, 'shop/guarantee/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (138, 'file', 134, 'shop/guarantee/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (139, 'file', 134, 'shop/guarantee/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (140, 'file', 93, 'shop/freight', '运费模板', 'fa fa-sticky-note-o', '', '', '', 1, NULL, '', 'yfmb', 'yunfeimuban', 1770178429, 1770178430, 49, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (141, 'file', 140, 'shop/freight/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (142, 'file', 140, 'shop/freight/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (143, 'file', 140, 'shop/freight/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (144, 'file', 140, 'shop/freight/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (145, 'file', 140, 'shop/freight/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (146, 'file', 93, 'shop/freight_items', '运费模板值', 'fa fa-list', '', '', '', 0, NULL, '', 'yfmbz', 'yunfeimubanzhi', 1770178429, 1770178430, 49, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (147, 'file', 146, 'shop/freight_items/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (148, 'file', 146, 'shop/freight_items/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (149, 'file', 146, 'shop/freight_items/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (150, 'file', 146, 'shop/freight_items/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (151, 'file', 146, 'shop/freight_items/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (152, 'file', 93, 'shop/order', '订单管理', 'fa fa-align-left', '', '', '', 1, NULL, '', 'ddgl', 'dingdanguanli', 1770178429, 1770178430, 48, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (153, 'file', 152, 'shop/order/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (154, 'file', 152, 'shop/order/recyclebin', '回收站', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hsz', 'huishouzhan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (155, 'file', 152, 'shop/order/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (156, 'file', 152, 'shop/order/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (157, 'file', 152, 'shop/order/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (158, 'file', 152, 'shop/order/destroy', '真实删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zssc', 'zhenshishanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (159, 'file', 152, 'shop/order/restore', '还原', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hy', 'huanyuan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (160, 'file', 152, 'shop/order/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (161, 'file', 152, 'shop/order/deliver', '发货', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'fh', 'fahuo', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (162, 'file', 152, 'shop/order/edit_info', '编辑订单信息', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bjddxx', 'bianjidingdanxinxi', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (163, 'file', 152, 'shop/order/refund', '同意退款', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tytk', 'tongyituikuan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (164, 'file', 152, 'shop/order/edit_status', '订单状态编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'ddztbj', 'dingdanzhuangtaibianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (165, 'file', 152, 'shop/order/detail', '订单详情', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'ddxq', 'dingdanxiangqing', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (166, 'file', 152, 'shop/order/cancel_electronics', '取消电子面单', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'qxdzmd', 'quxiaodianzimiandan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (167, 'file', 152, 'shop/order/electronics', '打印电子面单', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'dydzmd', 'dayindianzimiandan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (168, 'file', 152, 'shop/order/prints', '批量打印电子面单', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'pldydzmd', 'piliangdayindianzimiandan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (169, 'file', 152, 'shop/order/orderList', '批量打印发货单', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'pldyfhd', 'piliangdayinfahuodan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (170, 'file', 93, 'shop/order_goods', '订单商品', 'fa fa-list', '', '', '', 0, NULL, '', 'ddsp', 'dingdanshangpin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (171, 'file', 170, 'shop/order_goods/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (172, 'file', 170, 'shop/order_goods/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (173, 'file', 170, 'shop/order_goods/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (174, 'file', 170, 'shop/order_goods/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (175, 'file', 170, 'shop/order_goods/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (176, 'file', 93, 'shop/order_action', '订单操作记录', 'fa fa-list', '', '', '', 0, NULL, '', 'ddczjl', 'dingdancaozuojilu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (177, 'file', 176, 'shop/order_action/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (178, 'file', 176, 'shop/order_action/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (179, 'file', 176, 'shop/order_action/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (180, 'file', 176, 'shop/order_action/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (181, 'file', 176, 'shop/order_action/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (182, 'file', 93, 'shop/comment', '评论管理', 'fa fa-commenting-o', '', '', '', 1, NULL, '', 'plgl', 'pinglunguanli', 1770178429, 1770178430, 47, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (183, 'file', 182, 'shop/comment/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (184, 'file', 182, 'shop/comment/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (185, 'file', 182, 'shop/comment/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (186, 'file', 182, 'shop/comment/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (187, 'file', 182, 'shop/comment/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (188, 'file', 182, 'shop/comment/reply', '回复', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hf', 'huifu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (189, 'file', 93, 'shop/shipper', '快递公司', 'fa fa-truck', '', '', '', 0, NULL, '', 'kdgs', 'kuaidigongsi', 1770178429, 1770178430, 47, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (190, 'file', 189, 'shop/shipper/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (191, 'file', 189, 'shop/shipper/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (192, 'file', 189, 'shop/shipper/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (193, 'file', 189, 'shop/shipper/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (194, 'file', 189, 'shop/shipper/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (195, 'file', 93, 'shop/electronics_order', '电子面单', 'fa fa-sticky-note-o', '', '', '', 1, NULL, '', 'dzmd', 'dianzimiandan', 1770178429, 1770178430, 47, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (196, 'file', 195, 'shop/electronics_order/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (197, 'file', 195, 'shop/electronics_order/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (198, 'file', 195, 'shop/electronics_order/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (199, 'file', 195, 'shop/electronics_order/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (200, 'file', 195, 'shop/electronics_order/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (201, 'file', 93, 'shop/collect', '收藏管理', 'fa fa-heart', '', '', '', 1, NULL, '', 'scgl', 'shoucangguanli', 1770178429, 1770178430, 46, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (202, 'file', 201, 'shop/collect/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (203, 'file', 201, 'shop/collect/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (204, 'file', 201, 'shop/collect/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (205, 'file', 201, 'shop/collect/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (206, 'file', 201, 'shop/collect/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (207, 'file', 93, 'shop/category', '分类管理', 'fa fa-sitemap', '', '', '', 1, NULL, '', 'flgl', 'fenleiguanli', 1770178429, 1770178430, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (208, 'file', 207, 'shop/category/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (209, 'file', 207, 'shop/category/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (210, 'file', 207, 'shop/category/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (211, 'file', 207, 'shop/category/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (212, 'file', 207, 'shop/category/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (213, 'file', 93, 'shop/address', '收货地址', 'fa fa-map-signs', '', '', '', 1, NULL, '', 'shdz', 'shouhuodizhi', 1770178429, 1770178430, 44, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (214, 'file', 213, 'shop/address/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (215, 'file', 213, 'shop/address/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (216, 'file', 213, 'shop/address/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178430, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (217, 'file', 213, 'shop/address/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (218, 'file', 213, 'shop/address/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (219, 'file', 213, 'shop/address/recyclebin', '回收站', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hsz', 'huishouzhan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (220, 'file', 213, 'shop/address/restore', '还原', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hy', 'huanyuan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (221, 'file', 213, 'shop/address/destroy', '真实删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zssc', 'zhenshishanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (222, 'file', 93, 'shop/order_aftersales', '售后管理', 'fa fa-buysellads', '', '', '', 1, NULL, '', 'shgl', 'shouhouguanli', 1770178429, 1770178431, 41, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (223, 'file', 222, 'shop/order_aftersales/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (224, 'file', 222, 'shop/order_aftersales/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (225, 'file', 222, 'shop/order_aftersales/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (226, 'file', 222, 'shop/order_aftersales/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (227, 'file', 222, 'shop/order_aftersales/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (228, 'file', 93, 'shop/exchange', '积分兑换', 'fa fa-pinterest', '', '', '', 1, NULL, '', 'jfdh', 'jifenduihuan', 1770178429, 1770178431, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (229, 'file', 228, 'shop/exchange/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (230, 'file', 228, 'shop/exchange/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (231, 'file', 228, 'shop/exchange/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (232, 'file', 228, 'shop/exchange/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (233, 'file', 228, 'shop/exchange/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (234, 'file', 228, 'shop/exchange/creategoods', '生成商品', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'scsp', 'shengchengshangpin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (235, 'file', 93, 'shop/exchange_order', '兑换订单', 'fa fa-pinterest', '', '', '', 0, NULL, '', 'dhdd', 'duihuandingdan', 1770178429, 1770178431, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (236, 'file', 235, 'shop/exchange_order/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (237, 'file', 235, 'shop/exchange_order/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (238, 'file', 235, 'shop/exchange_order/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (239, 'file', 235, 'shop/exchange_order/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (240, 'file', 235, 'shop/exchange_order/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (241, 'file', 93, 'shop/coupon', '优惠券管理', 'fa fa-jpy', '', '', '', 1, NULL, '', 'yhqgl', 'youhuiquanguanli', 1770178429, 1770178431, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (242, 'file', 241, 'shop/coupon/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (243, 'file', 241, 'shop/coupon/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (244, 'file', 241, 'shop/coupon/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (245, 'file', 241, 'shop/coupon/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (246, 'file', 241, 'shop/coupon/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (247, 'file', 93, 'shop/user_coupon', '优惠券领取记录', 'fa fa-jpy', '', '', '', 0, NULL, '', 'yhqlqjl', 'youhuiquanlingqujilu', 1770178429, 1770178431, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (248, 'file', 247, 'shop/user_coupon/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (249, 'file', 247, 'shop/user_coupon/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (250, 'file', 247, 'shop/user_coupon/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (251, 'file', 247, 'shop/user_coupon/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (252, 'file', 247, 'shop/user_coupon/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (253, 'file', 93, 'shop/coupon_condition', '优惠券条件', 'fa fa-jpy', '', '', '', 0, NULL, '', 'yhqtj', 'youhuiquantiaojian', 1770178429, 1770178431, 45, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (254, 'file', 253, 'shop/coupon_condition/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (255, 'file', 253, 'shop/coupon_condition/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (256, 'file', 253, 'shop/coupon_condition/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (257, 'file', 253, 'shop/coupon_condition/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (258, 'file', 253, 'shop/coupon_condition/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (259, 'file', 93, 'shop/navigation', '导航配置', 'fa fa-th', '', '', '', 1, NULL, '', 'dhpz', 'daohangpeizhi', 1770178429, 1770178431, 38, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (260, 'file', 259, 'shop/navigation/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (261, 'file', 259, 'shop/navigation/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (262, 'file', 259, 'shop/navigation/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (263, 'file', 259, 'shop/navigation/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (264, 'file', 259, 'shop/navigation/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (265, 'file', 93, 'shop/menu', '菜单管理', 'fa fa-navicon', '', '', '', 1, NULL, '', 'cdgl', 'caidanguanli', 1770178429, 1770178431, 36, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (266, 'file', 265, 'shop/menu/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (267, 'file', 265, 'shop/menu/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (268, 'file', 265, 'shop/menu/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (269, 'file', 265, 'shop/menu/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (270, 'file', 265, 'shop/menu/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (271, 'file', 93, 'shop/theme', '移动端预览', 'fa fa-mobile', '', '', '', 1, NULL, '', 'yddyl', 'yidongduanyulan', 1770178429, 1770178431, 32, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (272, 'file', 271, 'shop/theme/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (273, 'file', 93, 'shop/config', '配置管理', 'fa fa-cog', '', '', '', 1, NULL, '', 'pzgl', 'peizhiguanli', 1770178429, 1770178431, 55, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (274, 'file', 273, 'shop/config/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (275, 'file', 93, 'shop/report', '统计控制台', 'fa fa-line-chart', '', '', '', 1, NULL, '', 'tjkzt', 'tongjikongzhitai', 1770178429, 1770178431, 56, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (276, 'file', 275, 'shop/report/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (277, 'file', 275, 'shop/report/areas', '地区明细', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'dqmx', 'diqumingxi', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (278, 'file', 93, 'shop/block', '区块管理', 'fa fa-th-large', '', '', '用于管理站点的自定义区块内容,常用于广告、JS脚本、焦点图、片段代码等', 1, NULL, '', 'qkgl', 'qukuaiguanli', 1770178429, 1770178431, 16, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (279, 'file', 278, 'shop/block/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (280, 'file', 278, 'shop/block/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (281, 'file', 278, 'shop/block/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (282, 'file', 278, 'shop/block/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (283, 'file', 278, 'shop/block/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (284, 'file', 93, 'shop/page', '单页管理', 'fa fa-file', '', '', '用于管理网站的单页面，可任意创建修改删除单页面', 1, NULL, '', 'dygl', 'danyeguanli', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (285, 'file', 284, 'shop/page/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (286, 'file', 284, 'shop/page/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (287, 'file', 284, 'shop/page/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (288, 'file', 284, 'shop/page/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (289, 'file', 284, 'shop/page/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (290, 'file', 284, 'shop/page/recyclebin', '回收站', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hsz', 'huishouzhan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (291, 'file', 284, 'shop/page/restore', '还原', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'hy', 'huanyuan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (292, 'file', 284, 'shop/page/destroy', '真实删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zssc', 'zhenshishanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (293, 'file', 93, 'shop/search_log', '搜索记录管理', 'fa fa-history', '', '', '用于管理网站的搜索记录日志', 1, NULL, '', 'ssjlgl', 'sousuojiluguanli', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (294, 'file', 293, 'shop/search_log/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (295, 'file', 293, 'shop/search_log/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (296, 'file', 293, 'shop/search_log/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (297, 'file', 293, 'shop/search_log/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (298, 'file', 293, 'shop/search_log/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (299, 'file', 93, 'shop/template_msg', '模板消息', 'fa fa-comment', '', '', '用于发送消息通知用户', 1, NULL, '', 'mbxx', 'mubanxiaoxi', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (300, 'file', 299, 'shop/template_msg/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (301, 'file', 299, 'shop/template_msg/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (302, 'file', 299, 'shop/template_msg/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (303, 'file', 299, 'shop/template_msg/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (304, 'file', 299, 'shop/template_msg/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (305, 'file', 93, 'shop/attribute', '商品属性', 'fa fa-comment', '', '', '', 0, NULL, '', 'spsx', 'shangpinshuxing', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (306, 'file', 305, 'shop/attribute/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (307, 'file', 305, 'shop/attribute/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (308, 'file', 305, 'shop/attribute/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (309, 'file', 305, 'shop/attribute/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (310, 'file', 305, 'shop/attribute/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (311, 'file', 93, 'shop/attribute_value', '商品属性值', 'fa fa-comment', '', '', '', 0, NULL, '', 'spsxz', 'shangpinshuxingzhi', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (312, 'file', 311, 'shop/attribute_value/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (313, 'file', 311, 'shop/attribute_value/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (314, 'file', 311, 'shop/attribute_value/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (315, 'file', 311, 'shop/attribute_value/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (316, 'file', 311, 'shop/attribute_value/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (317, 'file', 93, 'shop/brand', '品牌管理', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'ppgl', 'pinpaiguanli', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (318, 'file', 317, 'shop/brand/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (319, 'file', 317, 'shop/brand/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (320, 'file', 317, 'shop/brand/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (321, 'file', 317, 'shop/brand/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (322, 'file', 317, 'shop/brand/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (323, 'file', 93, 'shop/area', '地区管理', 'fa fa-map-marker', '', '', '', 1, NULL, '', 'dqgl', 'diquguanli', 1770178429, 1770178431, 14, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (324, 'file', 323, 'shop/area/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (325, 'file', 323, 'shop/area/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (326, 'file', 323, 'shop/area/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (327, 'file', 323, 'shop/area/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (328, 'file', 323, 'shop/area/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (329, 'file', 323, 'shop/area/import', '导入', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'dr', 'daoru', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (330, 'file', 323, 'shop/area/refresh', '刷新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sx', 'shuaxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (331, 'file', 93, 'shop/card', '卡片模板', 'fa fa-file-photo-o', '', '', '', 1, NULL, '', 'kpmb', 'kapianmuban', 1770178429, 1770178431, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (332, 'file', 331, 'shop/card/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (333, 'file', 331, 'shop/card/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (334, 'file', 331, 'shop/card/edit', '修改', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'xg', 'xiugai', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (335, 'file', 331, 'shop/card/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (336, 'file', 331, 'shop/card/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178429, 1770178431, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (337, 'file', 0, 'signin', '签到管理', 'fa fa-map-marker', '', '', '', 1, NULL, '', 'qdgl', 'qiandaoguanli', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (338, 'file', 337, 'signin/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (339, 'file', 337, 'signin/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (340, 'file', 337, 'signin/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (341, 'file', 337, 'signin/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (342, 'file', 337, 'signin/multi', '批量更新', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'plgx', 'pilianggengxin', 1770178445, 1770178445, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (343, 'file', 346, 'third', '第三方登录管理', 'fa fa-users', '', '', '', 1, 'addtabs', '', 'dsfdlgl', 'disanfangdengluguanli', 1770178499, 1770178624, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (344, 'file', 343, 'third/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770178499, 1770178499, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (345, 'file', 343, 'third/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770178499, 1770178499, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (346, 'file', 0, 'developer', '开发人员工具', 'fa fa-circle-o', '', '', '', 1, NULL, '', '', '', 1575451669, 1575451669, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (347, 'file', 0, 'coagent', '分销管理', 'fa fa-list', '', '', '', 1, NULL, '', 'fxgl', 'fenxiaoguanli', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (348, 'file', 347, 'coagent_config/config', '分销配置', 'fa fa-circle-o', '', '', '', 1, NULL, '', 'fxpz', 'fenxiaopeizhi', 1770192017, 1770192017, 10, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (349, 'file', 347, 'coagent_user_level', '分销等级', 'fa fa-list', '', '', '', 1, NULL, '', 'fxdj', 'fenxiaodengji', 1770192017, 1770192017, 15, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (350, 'file', 349, 'coagent_user_level/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (351, 'file', 349, 'coagent_user_level/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (352, 'file', 349, 'coagent_user_level/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (353, 'file', 349, 'coagent_user_level/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (354, 'file', 347, 'coagent_user', '分销员管理', 'fa fa-list', '', '', '', 1, NULL, '', 'fxygl', 'fenxiaoyuanguanli', 1770192017, 1770192017, 14, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (355, 'file', 354, 'coagent_user/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (356, 'file', 354, 'coagent_user/add', '添加', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'tj', 'tianjia', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (357, 'file', 354, 'coagent_user/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (358, 'file', 354, 'coagent_user/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (359, 'file', 347, 'coagent_reward', '佣金管理', 'fa fa-list', '', '', '', 1, NULL, '', 'yjgl', 'yongjinguanli', 1770192017, 1770192017, 13, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (360, 'file', 359, 'coagent_reward/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (361, 'file', 359, 'coagent_reward/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (362, 'file', 359, 'coagent_reward/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (363, 'file', 347, 'coagent_order', '推广订单', 'fa fa-list', '', '', '', 1, NULL, '', 'tgdd', 'tuiguangdingdan', 1770192017, 1770192017, 12, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (364, 'file', 363, 'coagent_order/index', '查看', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'zk', 'zhakan', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (365, 'file', 363, 'coagent_order/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (366, 'file', 363, 'coagent_order/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770192017, 1770192017, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (367, 'file', 0, 'cowithdraw', '提现管理', 'fa fa-list', '', '', '', 1, NULL, '', 'txgl', 'tixianguanli', 1770192994, 1770192994, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (368, 'file', 367, 'cowithdraw/index', '提现管理', 'fa fa-list', '', '', '', 1, NULL, '', 'txgl', 'tixianguanli', 1770192994, 1770192994, 10, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (369, 'file', 368, 'cowithdraw/audit', '审核', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sh', 'shenhe', 1770192994, 1770192994, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (370, 'file', 368, 'cowithdraw/edit', '编辑', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'bj', 'bianji', 1770192994, 1770192994, 0, 'normal');
INSERT INTO `advn_auth_rule` (`id`, `type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (371, 'file', 368, 'cowithdraw/del', '删除', 'fa fa-circle-o', '', '', '', 0, NULL, '', 'sc', 'shanchu', 1770192994, 1770192994, 0, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_category
-- ----------------------------
DROP TABLE IF EXISTS `advn_category`;
CREATE TABLE `advn_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` varchar(30) DEFAULT '' COMMENT '栏目类型',
  `name` varchar(30) DEFAULT '',
  `nickname` varchar(50) DEFAULT '',
  `flag` set('hot','index','recommend') DEFAULT '',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `diyname` varchar(30) DEFAULT '' COMMENT '自定义名称',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `weigh` (`weigh`,`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分类表';

-- ----------------------------
-- Records of advn_category
-- ----------------------------
BEGIN;
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (1, 0, 'page', '官方新闻', 'news', 'recommend', '/assets/img/qrcode.png', '', '', 'news', 1491635035, 1491635035, 1, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (2, 0, 'page', '移动应用', 'mobileapp', 'hot', '/assets/img/qrcode.png', '', '', 'mobileapp', 1491635035, 1491635035, 2, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (3, 2, 'page', '微信公众号', 'wechatpublic', 'index', '/assets/img/qrcode.png', '', '', 'wechatpublic', 1491635035, 1491635035, 3, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (4, 2, 'page', 'Android开发', 'android', 'recommend', '/assets/img/qrcode.png', '', '', 'android', 1491635035, 1491635035, 4, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (5, 0, 'page', '软件产品', 'software', 'recommend', '/assets/img/qrcode.png', '', '', 'software', 1491635035, 1491635035, 5, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (6, 5, 'page', '网站建站', 'website', 'recommend', '/assets/img/qrcode.png', '', '', 'website', 1491635035, 1491635035, 6, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (7, 5, 'page', '企业管理软件', 'company', 'index', '/assets/img/qrcode.png', '', '', 'company', 1491635035, 1491635035, 7, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (8, 6, 'page', 'PC端', 'website-pc', 'recommend', '/assets/img/qrcode.png', '', '', 'website-pc', 1491635035, 1491635035, 8, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (9, 6, 'page', '移动端', 'website-mobile', 'recommend', '/assets/img/qrcode.png', '', '', 'website-mobile', 1491635035, 1491635035, 9, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (10, 7, 'page', 'CRM系统 ', 'company-crm', 'recommend', '/assets/img/qrcode.png', '', '', 'company-crm', 1491635035, 1491635035, 10, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (11, 7, 'page', 'SASS平台软件', 'company-sass', 'recommend', '/assets/img/qrcode.png', '', '', 'company-sass', 1491635035, 1491635035, 11, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (12, 0, 'test', '测试1', 'test1', 'recommend', '/assets/img/qrcode.png', '', '', 'test1', 1491635035, 1491635035, 12, 'normal');
INSERT INTO `advn_category` (`id`, `pid`, `type`, `name`, `nickname`, `flag`, `image`, `keywords`, `description`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (13, 0, 'test', '测试2', 'test2', 'recommend', '/assets/img/qrcode.png', '', '', 'test2', 1491635035, 1491635035, 13, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_coagent_config
-- ----------------------------
DROP TABLE IF EXISTS `advn_coagent_config`;
CREATE TABLE `advn_coagent_config` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `value` text NOT NULL COMMENT '值',
  `group` varchar(50) NOT NULL DEFAULT '' COMMENT '分组',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COMMENT='分销设置';

-- ----------------------------
-- Records of advn_coagent_config
-- ----------------------------
BEGIN;
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (1, 'agent_status', '1', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (2, 'poster_bg', '', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (3, 'one_rate', '7', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (4, 'two_rate', '3', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (5, 'rate', '1', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (6, 'share_title', '分销标题', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (7, 'share_image', '', '', '');
INSERT INTO `advn_coagent_config` (`id`, `name`, `value`, `group`, `description`) VALUES (8, 'share_desc', '分销介绍', '', '');
COMMIT;

-- ----------------------------
-- Table structure for advn_coagent_order
-- ----------------------------
DROP TABLE IF EXISTS `advn_coagent_order`;
CREATE TABLE `advn_coagent_order` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `agent_id` bigint NOT NULL DEFAULT '0' COMMENT '分销商',
  `order_id` bigint NOT NULL DEFAULT '0' COMMENT '订单ID',
  `buyer_id` bigint NOT NULL DEFAULT '0' COMMENT '买家',
  `amount` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单结算金额',
  `reward_rules` text NOT NULL COMMENT '佣金规则',
  `createtime` bigint NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` bigint NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` enum('0','1','-1','-2') NOT NULL DEFAULT '0' COMMENT '结算状态:0=未结算,1=已结算,-1=已退回,-2=已取消',
  `reward_time` bigint NOT NULL DEFAULT '0' COMMENT '结算时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='推广订单';

-- ----------------------------
-- Records of advn_coagent_order
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_coagent_reward
-- ----------------------------
DROP TABLE IF EXISTS `advn_coagent_reward`;
CREATE TABLE `advn_coagent_reward` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `agent_id` bigint NOT NULL DEFAULT '0' COMMENT '分销商',
  `order_id` bigint NOT NULL DEFAULT '0' COMMENT '来源订单',
  `buyer_id` bigint NOT NULL DEFAULT '0' COMMENT '购买者',
  `reward_money` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金金额',
  `status` enum('0','1','-1','-2') NOT NULL DEFAULT '0' COMMENT '状态:0=待入账,1=已入账,-1=已退回,-2=已取消',
  `createtime` bigint NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` bigint NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='分销佣金';

-- ----------------------------
-- Records of advn_coagent_reward
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_coagent_user
-- ----------------------------
DROP TABLE IF EXISTS `advn_coagent_user`;
CREATE TABLE `advn_coagent_user` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint NOT NULL DEFAULT '0' COMMENT '所属用户',
  `child_agent_nums` bigint NOT NULL DEFAULT '0' COMMENT '推广用户数',
  `level_id` bigint NOT NULL DEFAULT '1' COMMENT '等级',
  `order_nums` bigint NOT NULL DEFAULT '0' COMMENT '订单数量',
  `order_amount` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `total_income` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金总额',
  `parent_user_id` bigint NOT NULL DEFAULT '0' COMMENT '上级推荐人',
  `status` enum('normal','pending','forbidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,pending=审核中,forbidden=禁用',
  `createtime` bigint NOT NULL DEFAULT '0' COMMENT '创建时间',
  `parent_pid` int NOT NULL DEFAULT '0' COMMENT '上上级推荐人',
  `child_child_nums` int NOT NULL DEFAULT '0' COMMENT '下下级用户数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='分销员管理';

-- ----------------------------
-- Records of advn_coagent_user
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_coagent_user_level
-- ----------------------------
DROP TABLE IF EXISTS `advn_coagent_user_level`;
CREATE TABLE `advn_coagent_user_level` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '等级名称',
  `level` varchar(50) NOT NULL DEFAULT '' COMMENT '等级',
  `rules` text NOT NULL COMMENT '等级规则',
  `upgrade_rules` text NOT NULL COMMENT '升级规则',
  `createtime` bigint NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COMMENT='分销员等级';

-- ----------------------------
-- Records of advn_coagent_user_level
-- ----------------------------
BEGIN;
INSERT INTO `advn_coagent_user_level` (`id`, `name`, `level`, `rules`, `upgrade_rules`, `createtime`) VALUES (1, '一级', '1', '规则', '规则', 1683963724);
COMMIT;

-- ----------------------------
-- Table structure for advn_command
-- ----------------------------
DROP TABLE IF EXISTS `advn_command`;
CREATE TABLE `advn_command` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '类型',
  `params` varchar(1500) NOT NULL DEFAULT '' COMMENT '参数',
  `command` varchar(1500) NOT NULL DEFAULT '' COMMENT '命令',
  `content` text COMMENT '返回结果',
  `executetime` bigint unsigned DEFAULT NULL COMMENT '执行时间',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  `status` enum('successed','failured') NOT NULL DEFAULT 'failured' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='在线命令表';

-- ----------------------------
-- Records of advn_command
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_config
-- ----------------------------
DROP TABLE IF EXISTS `advn_config`;
CREATE TABLE `advn_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT '' COMMENT '变量名',
  `group` varchar(30) DEFAULT '' COMMENT '分组',
  `title` varchar(100) DEFAULT '' COMMENT '变量标题',
  `tip` varchar(100) DEFAULT '' COMMENT '变量描述',
  `type` varchar(30) DEFAULT '' COMMENT '类型:string,text,int,bool,array,datetime,date,file',
  `visible` varchar(255) DEFAULT '' COMMENT '可见条件',
  `value` text COMMENT '变量值',
  `content` text COMMENT '变量字典数据',
  `rule` varchar(100) DEFAULT '' COMMENT '验证规则',
  `extend` varchar(255) DEFAULT '' COMMENT '扩展属性',
  `setting` varchar(255) DEFAULT '' COMMENT '配置',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='系统配置';

-- ----------------------------
-- Records of advn_config
-- ----------------------------
BEGIN;
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (1, 'name', 'basic', 'Site name', '请填写站点名称', 'string', '', '马上赚', '', 'required', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (2, 'beian', 'basic', 'Beian', '粤ICP备15000000号-1', 'string', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (3, 'cdnurl', 'basic', 'Cdn url', '如果全站静态资源使用第三方云储存请配置该值', 'string', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (4, 'version', 'basic', 'Version', '如果静态资源有变动请重新配置该值', 'string', '', '1.0.1', '', 'required', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (5, 'timezone', 'basic', 'Timezone', '', 'string', '', 'Asia/Shanghai', '', 'required', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (6, 'forbiddenip', 'basic', 'Forbidden ip', '一行一条记录', 'text', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (7, 'languages', 'basic', 'Languages', '', 'array', '', '{\"backend\":\"zh-cn\",\"frontend\":\"zh-cn\"}', '', 'required', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (8, 'fixedpage', 'basic', 'Fixed page', '请输入左侧菜单栏存在的链接', 'string', '', 'dashboard', '', 'required', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (9, 'categorytype', 'dictionary', 'Category type', '', 'array', '', '{\"default\":\"Default\",\"page\":\"Page\",\"article\":\"Article\",\"test\":\"Test\"}', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (10, 'configgroup', 'dictionary', 'Config group', '', 'array', '', '{\"basic\":\"Basic\",\"email\":\"Email\",\"dictionary\":\"Dictionary\",\"user\":\"User\",\"example\":\"Example\"}', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (11, 'mail_type', 'email', 'Mail type', '选择邮件发送方式', 'select', '', '1', '[\"请选择\",\"SMTP\"]', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (12, 'mail_smtp_host', 'email', 'Mail smtp host', '错误的配置发送邮件会导致服务器超时', 'string', '', 'smtp.qq.com', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (13, 'mail_smtp_port', 'email', 'Mail smtp port', '(不加密默认25,SSL默认465,TLS默认587)', 'string', '', '465', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (14, 'mail_smtp_user', 'email', 'Mail smtp user', '（填写完整用户名）', 'string', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (15, 'mail_smtp_pass', 'email', 'Mail smtp password', '（填写您的密码或授权码）', 'password', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (16, 'mail_verify_type', 'email', 'Mail vertify type', '（SMTP验证方式[推荐SSL]）', 'select', '', '2', '[\"无\",\"TLS\",\"SSL\"]', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (17, 'mail_from', 'email', 'Mail from', '', 'string', '', '', '', '', '', '');
INSERT INTO `advn_config` (`id`, `name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES (18, 'attachmentcategory', 'dictionary', 'Attachment category', '', 'array', '', '{\"category1\":\"Category1\",\"category2\":\"Category2\",\"custom\":\"Custom\"}', '', '', '', '');
COMMIT;

-- ----------------------------
-- Table structure for advn_cowithdraw
-- ----------------------------
DROP TABLE IF EXISTS `advn_cowithdraw`;
CREATE TABLE `advn_cowithdraw` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint NOT NULL DEFAULT '0' COMMENT '用户',
  `money` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `payment` enum('wechat','alipay','bank') NOT NULL DEFAULT 'wechat' COMMENT '收款方式:wechat=微信,alipay=支付宝,bank=银行卡',
  `accounts` text NOT NULL COMMENT '帐号信息',
  `status` enum('wait','success','fail') NOT NULL DEFAULT 'wait' COMMENT '状态:wait=等待转账,success=提现成功,fail=提现失败',
  `createtime` bigint NOT NULL DEFAULT '0' COMMENT '申请时间',
  `paytime` bigint NOT NULL DEFAULT '0' COMMENT '转账付款时间',
  `rate_money` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `pay_money` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '到账金额',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='提现管理';

-- ----------------------------
-- Records of advn_cowithdraw
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_ems
-- ----------------------------
DROP TABLE IF EXISTS `advn_ems`;
CREATE TABLE `advn_ems` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int unsigned NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='邮箱验证码表';

-- ----------------------------
-- Records of advn_ems
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_address
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_address`;
CREATE TABLE `advn_shop_address` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '编号',
  `user_id` int DEFAULT NULL COMMENT '用户ID',
  `province_id` int DEFAULT NULL COMMENT '省id',
  `city_id` int DEFAULT NULL COMMENT '市id',
  `area_id` int DEFAULT NULL COMMENT '区域ID',
  `receiver` varchar(255) DEFAULT NULL COMMENT '收货人',
  `mobile` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL COMMENT '地址详情',
  `zipcode` varchar(60) DEFAULT NULL COMMENT '邮编',
  `usednums` int unsigned DEFAULT '0' COMMENT '使用次数',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint unsigned DEFAULT NULL COMMENT '删除时间',
  `isdefault` tinyint unsigned DEFAULT '0' COMMENT '是否默认',
  `status` varchar(30) DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收货地址';

-- ----------------------------
-- Records of advn_shop_address
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_area
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_area`;
CREATE TABLE `advn_shop_area` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int DEFAULT NULL COMMENT '父ID',
  `level` tinyint(1) DEFAULT '0' COMMENT '等级',
  `name` varchar(100) DEFAULT '' COMMENT '名称',
  `pinyin` varchar(100) DEFAULT '' COMMENT '拼音',
  `py` varchar(50) DEFAULT '' COMMENT '拼音前缀',
  `adcode` varchar(50) DEFAULT '' COMMENT '唯一ID',
  `zipcode` varchar(50) DEFAULT '' COMMENT '邮编',
  `lng` varchar(30) DEFAULT NULL COMMENT '经度',
  `lat` varchar(30) DEFAULT NULL COMMENT '纬度',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='地区表';

-- ----------------------------
-- Records of advn_shop_area
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_attribute
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_attribute`;
CREATE TABLE `advn_shop_attribute` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `category_id` int DEFAULT NULL COMMENT '分类ID',
  `is_search` tinyint(1) DEFAULT '0' COMMENT '是否可搜索:0=否,1=是',
  `is_must` tinyint(1) DEFAULT '0' COMMENT '是否必填:0=可选,1=必选',
  `type` enum('radio','checkbox') DEFAULT 'radio' COMMENT '类型:radio=单选,checkbox=多选',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品属性';

-- ----------------------------
-- Records of advn_shop_attribute
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_attribute_value
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_attribute_value`;
CREATE TABLE `advn_shop_attribute_value` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `attribute_id` int DEFAULT NULL COMMENT '属性ID',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品属性值';

-- ----------------------------
-- Records of advn_shop_attribute_value
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_block
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_block`;
CREATE TABLE `advn_shop_block` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '类型',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '链接',
  `content` mediumtext COMMENT '内容',
  `parsetpl` tinyint unsigned DEFAULT '0' COMMENT '解析模板标签',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `begintime` bigint DEFAULT NULL COMMENT '开始时间',
  `endtime` bigint DEFAULT NULL COMMENT '结束时间',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='区块表';

-- ----------------------------
-- Records of advn_shop_block
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_block` (`id`, `type`, `name`, `title`, `image`, `url`, `content`, `parsetpl`, `weigh`, `begintime`, `endtime`, `createtime`, `updatetime`, `status`) VALUES (1, '焦点图', 'indexfocus', '首页焦点图1', 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixid=MnwxMjA3fDF8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1500&q=80', '/', '<h2 class=\"animated bounceInDown\">\r\n	畅享你的水果下午茶\r\n</h2>\r\n<p class=\"animated slideInRight\">\r\n	无限量应季水果供应<br />\r\n精选水果菠萝橙子牛油果猕猴桃\r\n</p>', 0, 2, NULL, NULL, 1624603959, 1625108648, 'normal');
INSERT INTO `advn_shop_block` (`id`, `type`, `name`, `title`, `image`, `url`, `content`, `parsetpl`, `weigh`, `begintime`, `endtime`, `createtime`, `updatetime`, `status`) VALUES (2, '焦点图', 'indexfocus', '首页焦点图2', 'https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=2100&q=80', '/', '<h2 class=\"animated bounceInDown\">\r\n	美味点心大集合\r\n</h2>\r\n<p class=\"animated slideInRight\">\r\n	家庭自制美味小点心<br />\r\n在家也能吃到蓝莓咖啡蓝莓鸡蛋\r\n</p>', 0, 1, NULL, NULL, 1624603959, 1625108843, 'normal');
INSERT INTO `advn_shop_block` (`id`, `type`, `name`, `title`, `image`, `url`, `content`, `parsetpl`, `weigh`, `begintime`, `endtime`, `createtime`, `updatetime`, `status`) VALUES (3, 'uniapp焦点图', 'uniappfocus', 'uniapp焦点图1', '/assets/addons/shop/img/swiper1.jpg', '/pages/category/index', '', 0, 4, NULL, NULL, 1625556492, 1625556891, 'normal');
INSERT INTO `advn_shop_block` (`id`, `type`, `name`, `title`, `image`, `url`, `content`, `parsetpl`, `weigh`, `begintime`, `endtime`, `createtime`, `updatetime`, `status`) VALUES (4, 'uniapp焦点图', 'uniappfocus', 'uniapp焦点图2', '/assets/addons/shop/img/swiper2.jpg', '/pages/goods/goods', '', 0, 5, NULL, NULL, 1625556518, 1625556882, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_brand
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_brand`;
CREATE TABLE `advn_shop_brand` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `image` varchar(255) DEFAULT '' COMMENT '图片',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='品牌管理';

-- ----------------------------
-- Records of advn_shop_brand
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_card
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_card`;
CREATE TABLE `advn_shop_card` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type` tinyint(1) DEFAULT '0' COMMENT '类型:0=商品,1=优惠券',
  `title` varchar(100) DEFAULT NULL COMMENT '标题',
  `content` longtext COMMENT '内容',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品卡片模板';

-- ----------------------------
-- Records of advn_shop_card
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_carts
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_carts`;
CREATE TABLE `advn_shop_carts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增长id',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `goods_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_sku_id` int DEFAULT '0' COMMENT '商品属性id',
  `sceneval` tinyint(1) DEFAULT '1' COMMENT '类型:1=加入购物车,2=立即购买',
  `nums` smallint unsigned NOT NULL DEFAULT '0' COMMENT '商品购买件数',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='购物车';

-- ----------------------------
-- Records of advn_shop_carts
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_category
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_category`;
CREATE TABLE `advn_shop_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) DEFAULT NULL,
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `isnav` tinyint(1) DEFAULT '0',
  `name` varchar(30) NOT NULL DEFAULT '',
  `nickname` varchar(50) NOT NULL DEFAULT '',
  `outlink` varchar(255) DEFAULT NULL,
  `flag` set('hot','index','recommend') NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `icon` varchar(50) NOT NULL DEFAULT '' COMMENT '图标',
  `diyname` varchar(30) NOT NULL DEFAULT '' COMMENT '自定义名称',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) NOT NULL DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `weigh` (`weigh`,`id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分类表';

-- ----------------------------
-- Records of advn_shop_category
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (1, NULL, 0, 1, '电子产品', '', NULL, '', '', '', '', 'shopicon icon-laptop-computer', 'dianzichanpin', 1623309726, 1625553392, 1, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (2, NULL, 0, 1, '家用电器', '', NULL, '', '', '', '', 'shopicon icon-washing-machine', 'jiayongjnkk', 1623309740, 1625553386, 2, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (3, NULL, 0, 1, '医药保健', '', NULL, '', '', '', '', 'shopicon icon-medical-box', 'yiyaobaojian', 1623314646, 1625553381, 3, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (4, NULL, 0, 1, '生活居家', '', NULL, 'index', '', '', '', 'shopicon icon-refrigerator', 'shenghuojujia', 1623314655, 1625553369, 4, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (5, NULL, 0, 1, '家纺家饰', '', NULL, '', '', '', '', 'shopicon icon-sofa', 'jiafangjiashi', 1623314662, 1625553360, 5, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (6, NULL, 0, 1, '汽车配件', '', NULL, '', '', '', '', 'shopicon icon-car', 'qichepeijian', 1623314669, 1625553337, 6, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (7, NULL, 0, 1, '化妆品', '', NULL, '', '', '', '', 'shopicon icon-lipstick', 'huazhuangpin', 1623314675, 1625553328, 7, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (8, NULL, 0, 1, '户外运动', '', NULL, '', '', '', '', 'shopicon icon-outdoor', 'huwaiyundong', 1623314682, 1625553318, 8, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (9, NULL, 0, 1, '食物饮品', '', NULL, 'index', '', '', '', 'shopicon icon-bowl-one', 'shiwuyinpin', 1623314689, 1625553311, 9, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (10, NULL, 0, 1, '数码手机', '', NULL, 'index', '', '', '', 'shopicon icon-devices', 'shumashouji', 1623314698, 1625553303, 10, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (11, NULL, 0, 1, '母婴玩具', '', NULL, '', '', '', '', 'shopicon icon-pokeball-one', 'muyingwanju', 1623314721, 1625553290, 11, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (12, NULL, 7, 1, '纸品湿巾', '', NULL, '', '', '', '', 'shopicon icon-juanzhi', 'zhipinshijin', 1623314835, 1625133737, 12, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (13, NULL, 7, 1, '衣物清洁', '', NULL, '', '', '', '', 'shopicon icon-xizhuang', 'yiwuqingjie', 1623314877, 1625133742, 13, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (14, NULL, 7, 1, '身体护理', '', NULL, '', '', '', '', 'shopicon icon-hufupin', 'shentihuli', 1623314885, 1625133747, 14, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (15, NULL, 7, 1, '洗发护发', '', NULL, '', '', '', '', 'shopicon icon-wanju', 'xifahufa', 1623314909, 1625133752, 15, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (17, NULL, 7, 1, '家庭清洁', '', NULL, '', '', '', '', 'shopicon icon-naiping', 'jiatingqingjie', 1623314931, 1625133710, 17, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (18, NULL, 5, 1, '清凉夏被', '', NULL, '', '', '', '', 'shopicon icon-lvxingdai', 'qingliangxiabei', 1623314959, 1625133774, 18, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (20, NULL, 5, 1, '枕头枕芯', '', NULL, '', '', '', '', 'shopicon icon-dianyundou', 'zhentouzhenxin', 1623314997, 1625133766, 20, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (21, NULL, 5, 1, '毛巾浴巾', '', NULL, '', '', '', '', 'shopicon icon-bangqiu', 'maojinyujin', 1623315006, 1625133762, 21, 'normal');
INSERT INTO `advn_shop_category` (`id`, `type`, `pid`, `isnav`, `name`, `nickname`, `outlink`, `flag`, `image`, `keywords`, `description`, `icon`, `diyname`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (22, NULL, 5, 1, '床垫床褥', '', NULL, '', '', '', '', 'shopicon icon-jieri', 'chuangdianchuangru', 1623315018, 1625133758, 22, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_collect
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_collect`;
CREATE TABLE `advn_shop_collect` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户id',
  `goods_id` int DEFAULT NULL COMMENT '商品id',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态:0=已取消,1=已收藏',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='收藏表';

-- ----------------------------
-- Records of advn_shop_collect
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_comment
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_comment`;
CREATE TABLE `advn_shop_comment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父评论ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `goods_id` int unsigned NOT NULL DEFAULT '0' COMMENT '关联ID',
  `star` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '评分',
  `content` text COMMENT '内容',
  `comments` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `images` varchar(1500) DEFAULT NULL COMMENT '评论图片',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `useragent` varchar(255) NOT NULL DEFAULT '' COMMENT 'User Agent',
  `subscribe` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '订阅',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `post_id` (`goods_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='评论表';

-- ----------------------------
-- Records of advn_shop_comment
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_coupon
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_coupon`;
CREATE TABLE `advn_shop_coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '名称',
  `condition_ids` varchar(50) DEFAULT NULL COMMENT '条件ids',
  `result` tinyint DEFAULT NULL COMMENT '结果:0=订单满xx打x折,1=订单满xx减x元',
  `result_data` varchar(255) DEFAULT NULL COMMENT '结果补充',
  `mode` enum('fixation','dates') DEFAULT 'dates' COMMENT '有效期模式:fixation=固定天数,dates=日期范围',
  `is_private` enum('yes','no') DEFAULT 'no' COMMENT '是否私有:yes=是,no=否',
  `is_open` tinyint(1) DEFAULT '1' COMMENT '是否开启:0=关闭,1=开启',
  `allow_num` int DEFAULT '1' COMMENT '一人可领取数量',
  `give_num` int DEFAULT '1' COMMENT '发放总量',
  `received_num` int DEFAULT '0' COMMENT '已经领取数量',
  `receive_times` varchar(100) DEFAULT NULL COMMENT '领取时间段',
  `begintime` bigint DEFAULT NULL COMMENT '领取开始时间',
  `endtime` bigint DEFAULT NULL COMMENT '领取结束时间',
  `use_times` varchar(100) DEFAULT NULL COMMENT '使用时间段',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券表';

-- ----------------------------
-- Records of advn_shop_coupon
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_coupon_condition
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_coupon_condition`;
CREATE TABLE `advn_shop_coupon_condition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '条件名称',
  `type` tinyint DEFAULT NULL COMMENT '类型:1=指定商品,2=新用户专享,3=老用户专享',
  `content` varchar(100) DEFAULT NULL COMMENT '条件补充内容',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券条件';

-- ----------------------------
-- Records of advn_shop_coupon_condition
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_electronics_order
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_electronics_order`;
CREATE TABLE `advn_shop_electronics_order` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `shipper_id` int DEFAULT NULL COMMENT '快递公司id',
  `paytype` tinyint(1) DEFAULT '1' COMMENT '运费支付方式:1=现付,2=到付,3=月结,4=第三方付(仅SF支持)',
  `customer_name` varchar(100) DEFAULT NULL COMMENT '线下网点客户号',
  `customer_pwd` varchar(100) DEFAULT NULL COMMENT '线下网点密码',
  `send_site` varchar(100) DEFAULT NULL COMMENT '网点名称',
  `send_staff` varchar(100) DEFAULT NULL COMMENT '网点快递员',
  `month_code` varchar(100) DEFAULT NULL COMMENT '月结编号',
  `is_notice` tinyint(1) DEFAULT '0' COMMENT '是否通知揽件:0=通知揽件,1=不通知揽件',
  `is_return_temp` tinyint(1) DEFAULT NULL COMMENT '是否返回电子面单模板:0=不返回,1=返回',
  `is_send_message` tinyint(1) DEFAULT NULL COMMENT '是否需要短信提醒:0=否,1=是',
  `template_size` varchar(10) DEFAULT NULL COMMENT '模板尺寸',
  `operate_require` varchar(255) DEFAULT NULL COMMENT '签回单操作要求(如：签名、盖章、身份证复印件等)',
  `logistic_code` varchar(255) DEFAULT NULL COMMENT '快递单号(仅宅急送可用)',
  `start_date` int DEFAULT NULL COMMENT '上门揽件开始时间',
  `end_date` int DEFAULT NULL COMMENT '上门揽件结束时间',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `exp_type` tinyint(1) DEFAULT NULL COMMENT '快递类型:1=标准快件',
  `is_return_sign_bill` tinyint(1) DEFAULT NULL COMMENT '是否要签回单:0=否,1=是',
  `company` varchar(255) DEFAULT NULL COMMENT '发件人公司',
  `province_name` varchar(50) DEFAULT NULL COMMENT '发件人省',
  `city_name` varchar(50) DEFAULT NULL COMMENT '发件人市',
  `exp_area_name` varchar(50) DEFAULT NULL COMMENT '发件人区',
  `address` varchar(255) DEFAULT NULL COMMENT '发件人详细地址',
  `name` varchar(50) DEFAULT NULL COMMENT '发件人姓名',
  `tel` varchar(20) DEFAULT NULL COMMENT '发件人电话',
  `mobile` varchar(15) DEFAULT NULL COMMENT '发件人手机号码',
  `post_code` varchar(15) DEFAULT NULL COMMENT '发件地邮编',
  `title` varchar(255) DEFAULT NULL COMMENT '自定义名称',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='电子面单';

-- ----------------------------
-- Records of advn_shop_electronics_order
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_electronics_order` (`id`, `shipper_id`, `paytype`, `customer_name`, `customer_pwd`, `send_site`, `send_staff`, `month_code`, `is_notice`, `is_return_temp`, `is_send_message`, `template_size`, `operate_require`, `logistic_code`, `start_date`, `end_date`, `remark`, `exp_type`, `is_return_sign_bill`, `company`, `province_name`, `city_name`, `exp_area_name`, `address`, `name`, `tel`, `mobile`, `post_code`, `title`, `createtime`, `updatetime`) VALUES (1, 2, 1, '', '', '', '', '', 0, 1, 0, '130', '签名', '', 0, 0, '', 1, 0, '公司名称', '广东省', '深圳市', '罗湖区', '建设路', '阿良', '18333333333', '18333333333', '518000', '百世快递电子面单', 1623392763, 1625560310);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_exchange
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_exchange`;
CREATE TABLE `advn_shop_exchange` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('virtual','reality') DEFAULT NULL COMMENT '类型:virtual=虚拟物品,reality=实物商品',
  `title` varchar(255) DEFAULT NULL COMMENT '标题',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `content` mediumtext COMMENT '商品内容详情',
  `image` varchar(225) DEFAULT NULL COMMENT '图片',
  `score` int unsigned DEFAULT '0' COMMENT '积分',
  `stocks` int unsigned DEFAULT '0' COMMENT '库存',
  `sales` int unsigned DEFAULT '0' COMMENT '销量',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='积分兑换';

-- ----------------------------
-- Records of advn_shop_exchange
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_exchange_order
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_exchange_order`;
CREATE TABLE `advn_shop_exchange_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT '0' COMMENT '会员ID',
  `exchange_id` int unsigned DEFAULT '0' COMMENT '兑换ID',
  `type` varchar(50) DEFAULT NULL COMMENT '类型',
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `nums` int unsigned DEFAULT '0' COMMENT '数量',
  `score` int unsigned DEFAULT '0' COMMENT '兑换积分',
  `receiver` varchar(30) DEFAULT NULL COMMENT '收件人',
  `mobile` varchar(30) DEFAULT NULL COMMENT '电话',
  `address` varchar(100) DEFAULT NULL COMMENT '地址',
  `memo` varchar(255) DEFAULT NULL COMMENT '备注',
  `reason` varchar(100) DEFAULT NULL COMMENT '原因',
  `expressname` varchar(50) DEFAULT NULL COMMENT '快递名称',
  `expressno` varchar(50) DEFAULT NULL COMMENT '快递单号',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP',
  `useragent` varchar(255) DEFAULT NULL COMMENT 'UserAgent',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('created','inprogress','rejected','delivered','completed') DEFAULT 'created' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='积分兑换订单';

-- ----------------------------
-- Records of advn_shop_exchange_order
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_freight
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_freight`;
CREATE TABLE `advn_shop_freight` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '名称',
  `type` tinyint(1) DEFAULT NULL COMMENT '是否包邮:1=按件计,2=按重计',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开关',
  `num` decimal(10,2) DEFAULT '0.00' COMMENT '默认件/重',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '默认价格',
  `continue_num` decimal(10,2) DEFAULT '0.00' COMMENT '续件/续重',
  `continue_price` decimal(10,2) DEFAULT '0.00' COMMENT '续费',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='运费模板';

-- ----------------------------
-- Records of advn_shop_freight
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_freight` (`id`, `name`, `type`, `weigh`, `switch`, `num`, `price`, `continue_num`, `continue_price`, `createtime`, `updatetime`) VALUES (1, '按件', 1, 1, 1, 1.00, 12.00, 1.00, 5.00, 1623309170, 1625558947);
INSERT INTO `advn_shop_freight` (`id`, `name`, `type`, `weigh`, `switch`, `num`, `price`, `continue_num`, `continue_price`, `createtime`, `updatetime`) VALUES (2, '按重量', 2, 2, 1, 1.00, 12.00, 1.00, 5.00, 1623309192, 1625558936);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_freight_items
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_freight_items`;
CREATE TABLE `advn_shop_freight_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `freight_id` int DEFAULT NULL COMMENT '模板id',
  `first_num` decimal(10,2) DEFAULT '0.00' COMMENT '首件/首重',
  `first_price` decimal(10,2) DEFAULT '0.00' COMMENT '首费',
  `continue_num` decimal(10,2) DEFAULT '0.00' COMMENT '续件/续重',
  `continue_price` decimal(10,2) DEFAULT '0.00' COMMENT '续费',
  `area_ids` mediumtext COMMENT '地区ids',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '指定地区包邮:0=否,1=按件,2=按金额',
  `postage_area_ids` mediumtext COMMENT '包邮地区ids',
  `postage_num` decimal(10,2) DEFAULT '0.00' COMMENT '满几件包邮',
  `postage_price` decimal(10,2) DEFAULT '0.00' COMMENT '满金额包邮',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='运费模板字表';

-- ----------------------------
-- Records of advn_shop_freight_items
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_goods
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_goods`;
CREATE TABLE `advn_shop_goods` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `category_id` int unsigned NOT NULL DEFAULT '0' COMMENT '类别ID',
  `subtitle` varchar(255) DEFAULT NULL COMMENT '子标题',
  `attribute_ids` varchar(255) DEFAULT NULL COMMENT '属性值ids',
  `brand_id` int DEFAULT NULL COMMENT '品牌ID',
  `goods_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '货号',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '商品标题',
  `keywords` varchar(100) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `marketprice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场售价',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商城售价',
  `stocks` int unsigned NOT NULL DEFAULT '0' COMMENT '库存',
  `sales` int unsigned NOT NULL DEFAULT '0' COMMENT '销量',
  `guarantee_ids` varchar(50) DEFAULT NULL COMMENT '服务保障',
  `star` int DEFAULT '0' COMMENT '星级',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT '浏览次数',
  `comments` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
  `shares` int unsigned NOT NULL DEFAULT '0' COMMENT '分享次数',
  `image` varchar(255) DEFAULT '' COMMENT '缩略图',
  `images` varchar(1500) DEFAULT '' COMMENT '预览图',
  `content` mediumtext COMMENT '商品内容详情',
  `corner` varchar(10) DEFAULT NULL COMMENT '角标文字',
  `flag` varchar(50) DEFAULT '' COMMENT '标志',
  `spectype` tinyint(1) DEFAULT '0' COMMENT '规格类型:0=单规格,1=多规格',
  `weight` decimal(10,2) DEFAULT '0.00' COMMENT '重量(kg)',
  `isvirtual` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '虚拟物品',
  `freight_id` int DEFAULT NULL COMMENT '运费模板ID',
  `weigh` int NOT NULL DEFAULT '1' COMMENT '排序',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden','soldout') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品表';

-- ----------------------------
-- Records of advn_shop_goods
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (1, 4, '与家人一起静享田园请诗意般的生活', NULL, 1, '', '浅色系简约居家生活场景布局沙发椅子桌子', '', '', 1999.00, 999.00, 200, 0, '2,1', 0, 5, 0, 0, 'https://images.unsplash.com/photo-1544457070-4cd773b4d71e?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MXx8ZnVybml0dXJlfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1615471618985-97108e2ba478?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjR8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1618377384716-462f06a61706?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Nnx8ZnVybml0dXJlfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1544457070-4cd773b4d71e?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MXx8ZnVybml0dXJlfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1618220048045-10a6dbdf83e0?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NHx8ZnVybml0dXJlfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', '', 0, 1.20, 0, 2, 1, 1625025513, 1625042267, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (2, 4, '', NULL, 1, '1001', '原木色居家生活场景单人座沙发原木色茶几', '', '', 599.00, 299.00, 200, 0, '2,1', 0, 4, 0, 0, 'https://images.unsplash.com/photo-1596900749995-57cec6ddb861?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjF8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1567016515344-5e3b0d67bb75?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1616132205093-3158f3a65fb5?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjJ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1596900749995-57cec6ddb861?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjF8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 1, 1.23, 0, 2, 2, 1625025627, 1625560620, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (3, 4, '', NULL, 1, '', '简约设计原木色墙纸壁画挂件摆台阳光壁画', '', '', 499.00, 299.00, 200, 0, '1,2', 0, 5, 0, 0, 'https://images.unsplash.com/photo-1616048056617-93b94a339009?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjV8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1593085260707-5377ba37f868?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzh8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1606744857586-ec63a4683ca6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1616048056617-93b94a339009?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjV8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', '', 0, 1.88, 0, 2, 3, 1625025817, 1625025817, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (4, 4, '让简约设计无处不在', NULL, 1, '1003', '简约居家厨房卧室挂件摆件花卉设计原木色系白色绿色', '', '', 199.00, 188.00, 1000, 0, '2,1', 0, 3, 0, 0, 'https://images.unsplash.com/photo-1565791380709-49e529c8b073?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTV8fGhvbWV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1503011510-c0e00592713b?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTF8fGhvbWV8ZW58MHwyfDB8fA%3D%3D&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1558769132-92e717d613cd?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTZ8fGhvbWV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1501660034796-9860da6cb741?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTh8fGhvbWV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 1, 1.55, 0, 2, 4, 1625034321, 1625560625, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (5, 4, '', NULL, 1, '', '经典白色系厨房设计配以绿色风格搭配设计餐桌餐具', '', '', 166.00, 128.00, 200, 0, '1,2', 0, 1, 0, 0, 'https://images.unsplash.com/photo-1603053487159-157ef3e64cce?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Nnx8a2l0Y2hlbnxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1613827243412-d50750b182a6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTR8fGtpdGNoZW58ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1583777304100-a2a154c51f6b?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OHx8a2l0Y2hlbnxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1603053487159-157ef3e64cce?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Nnx8a2l0Y2hlbnxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'hot', 0, 2.55, 0, 2, 5, 1625034503, 1625034503, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (6, 4, '简约商务风格', NULL, 1, '1006', '办公室简约风格笔记本电脑桌办公沙发摆件原木色木地板', '', '', 299.00, 188.00, 3000, 0, '2,1', 0, 1, 0, 0, 'https://images.unsplash.com/photo-1562643439-cf97e2f6d189?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTEzfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1609959914470-d50dd6e5850d?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTYwfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1585125317747-64daaaf0fabc?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTczfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1555050891-f878b6570480?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTcyfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend,hot', 1, 2.18, 0, 2, 6, 1625036448, 1625560596, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (7, 4, '', NULL, 1, '', '家用电脑绿植办公椅沙发原木色壁画简约设计装修风格', '', '', 100.00, 88.00, 200, 0, '2,1', 0, 2, 0, 0, 'https://images.unsplash.com/photo-1597567918979-a06f2e60bd5a?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk5fHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1586326613981-d2fcd71d3c74?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjUxfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1622372738946-62e02505feb3?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjU0fHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1609980829355-b37d3a06f02c?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjY3fHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', '', 0, 2.30, 0, 2, 7, 1625036617, 1625036617, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (8, 4, '充满设计感的居家布局', NULL, 1, '', '客厅简约装修风格原木色木质休闲椅米色沙发绿植', '', '', 1880.00, 990.00, 200, 0, '2,1', 0, 3, 0, 0, 'https://images.unsplash.com/photo-1615875474908-f403116f5287?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzUxfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1553533432-a1cf4da1a74a?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzQ1fHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1615875474908-f403116f5287?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzUxfHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1583590965320-bc3a98dc1d88?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzQ2fHxmdXJuaXR1cmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593512828202-a5b036b628a9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NTZ8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1587280963766-6f31d3647a1f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NDN8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1618377385011-b861fcfb18f8?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzl8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1615529182904-14819c35db37?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjB8fGZ1cm5pdHVyZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 0, 2.10, 0, 2, 8, 1625036743, 1625036849, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (9, 10, '', NULL, 1, '', '2021年手机扁平化风格设计高速网络WIFI上网', '', '', 5999.00, 5888.00, 200, 0, '1,2', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MXx8YXBwbGUlMjBkZXZpY2VzfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1545063328-c8e3faffa16f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NzN8fGFwcGxlJTIwZGV2aWNlc3xlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1548094891-c4ba474efd16?ixid=MnwxMjA3fDB8MHxzZWFyY2h8N3x8YXBwbGUlMjBkZXZpY2VzfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1512054502232-10a0a035d672?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NXx8YXBwbGUlMjBkZXZpY2VzfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'hot', 0, 1.85, 0, 2, 9, 1625037188, 1625037407, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (10, 10, '', NULL, 1, '', '手机手表平板电脑游戏手柄耳机键盘钱包白色桌面', '', '', 9990.00, 8880.00, 200, 0, '1,2', 0, 4, 0, 0, 'https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'hot', 0, 1.28, 0, 2, 10, 1625037303, 1625042143, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (11, 10, '运动户外旅行必备电子产品', NULL, 1, '2001', '苹果手机手表书本黑色数码相机白色休闲简约', '', '', 8888.00, 7999.00, 3000, 0, '2,1', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1529607225807-563e0851ee7e?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzN8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1541085388148-a30647cab28f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mzd8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1529607225807-563e0851ee7e?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzN8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 1, 1.66, 0, 2, 11, 1625037687, 1625560586, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (12, 10, '', NULL, 1, '', '手机笔记本快充充电宝水杯耳机绿植桌子简约设计', '', '', 19999.00, 9999.00, 200, 0, '2,1', 0, 4, 0, 0, 'https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1512486130939-2c4f79935e4f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjJ8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 0, 1.68, 0, 2, 12, 1625037849, 1625037849, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (13, 10, '商务办公更便捷', NULL, 1, '', '红色手机黑色手机金色手机玫瑰金色手机键盘书本简约搭配', '', '', 10000.00, 9990.00, 200, 0, '2,1', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1567160895307-ebd008647a00?ixid=MnwxMjA3fDB8MHxzZWFyY2h8M3x8bW9iaWxlJTIwcGhvbmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1571126770037-2945244eea9c?ixid=MnwxMjA3fDB8MHxzZWFyY2h8ODh8fG1vYmlsZSUyMHBob25lfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1567160895364-7b5a4e08b8a1?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTU5fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', '', 0, 1.38, 0, 2, 13, 1625038012, 1625038012, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (14, 10, '', NULL, 1, '2002', '午后咖啡白色手机黑色数码相机简约设计搭配', '', '', 8888.00, 5999.00, 800, 0, '2,1', 0, 25, 0, 0, 'https://images.unsplash.com/photo-1558489107-04818e97f88c?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTgzfHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1558489107-04818e97f88c?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTgzfHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 1, 1.28, 0, 2, 14, 1625038120, 1625560578, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (15, 10, '一杯咖啡一首歌', NULL, 1, '', '阳光下午绿植手机咖啡耳机简约搭配设计风格', '', '', 9999.00, 9990.00, 200, 0, '2,1', 0, 2, 0, 0, 'https://images.unsplash.com/photo-1509395062183-67c5ad6faff9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTMzfHxpcGhvbmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1509395062183-67c5ad6faff9?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTMzfHxpcGhvbmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTU3fHxpcGhvbmV8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'recommend', 0, 1.40, 0, 2, 15, 1625038409, 1625038409, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (16, 10, '', NULL, 1, '2003', '紫色手机彩色桌布花瓶桌面摆饰简约搭配风格设计', '', '', 9999.00, 8888.00, 3000, 0, '2,1', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1612653030120-4acd623d351a?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTEwfHxpcGhvbmUlMjAxMnxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1621849244749-1db15a083a06?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NHx8aXBob25lJTIwMTJ8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60,https://images.unsplash.com/photo-1623830286332-463d3c91c855?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTJ8fGlwaG9uZSUyMDEyfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1513918596785-a2fcc5a5d302?ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mjh8fGFwcGxlJTIwaXBhZHxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1624823183493-ed5832f48f18?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OXx8YXBwbGUlMjBpcGFkfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1593259037198-c720f4420d7f?ixid=MnwxMjA3fDB8MHxzZWFyY2h8NjR8fG1vYmlsZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<br />\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1566999114730-1cd7a382f7e6?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTk1fHxtb2JpbGUlMjBwaG9uZXxlbnwwfDJ8MHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=60\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>', '', 'hot', 1, 1.22, 0, 2, 16, 1625038551, 1625560569, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (17, 9, '健康饮食每一天', NULL, 1, '', '全麦面包黄瓜片梨子麦片健康下午茶搭配设计', '', '', 99.00, 80.00, 200, 0, '1,2', 0, 1, 0, 0, 'https://images.unsplash.com/photo-1475565098700-683be99a9b61?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OHx8Zm9vZHxlbnwwfDJ8MHx3aGl0ZXw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1475565098700-683be99a9b61?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OHx8Zm9vZHxlbnwwfDJ8MHx3aGl0ZXw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'recommend', 0, 1.30, 0, 2, 17, 1625039084, 1625039084, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (18, 9, '', NULL, 1, '', '蓝莓果酱面板蓝莓下午茶简约设计搭配健康饮食', '', '', 90.00, 80.00, 200, 0, '1,2', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1616541828107-c9a4211e66a2?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTV8fGZvb2R8ZW58MHwyfDB8d2hpdGV8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1616541828107-c9a4211e66a2?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTV8fGZvb2R8ZW58MHwyfDB8d2hpdGV8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', '', 0, 2.00, 0, 2, 18, 1625039149, 1625039149, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (19, 9, '美味水果下午茶', NULL, 1, '3001', '零食下午茶必备牛油果小蕃茄简约设计搭配美味', '', '', 90.00, 80.00, 600, 0, '2,1', 0, 6, 0, 0, 'https://images.unsplash.com/photo-1583777304100-a2a154c51f6b?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjR8fGZvb2R8ZW58MHwyfDB8d2hpdGV8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1583777304100-a2a154c51f6b?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MjR8fGZvb2R8ZW58MHwyfDB8d2hpdGV8&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'recommend,hot', 1, 1.00, 0, 2, 19, 1625039270, 1625560561, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (20, 9, '', NULL, 1, '', '美味搭配下午茶零食蓝莓咖啡面包白色桌面', '', '', 90.00, 80.00, 198, 1, '2,1', 0, 44, 0, 0, 'https://images.unsplash.com/photo-1481931715705-36f5f79f1f3d?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTF8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1481931715705-36f5f79f1f3d?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MTF8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'recommend', 0, 1.24, 0, 2, 20, 1625039445, 1625039445, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (21, 9, '麦片水果零食混搭', NULL, 1, '3002', '蓝莓猕猴桃麦片美味零食下午茶简约搭配设计风格', '', '', 128.00, 90.00, 4500, 0, '2,1', 0, 12, 0, 0, 'https://images.unsplash.com/photo-1543363136-3fdb62e11be5?ixid=MnwxMjA3fDB8MHxzZWFyY2h8ODV8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1543363136-3fdb62e11be5?ixid=MnwxMjA3fDB8MHxzZWFyY2h8ODV8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'hot', 1, 2.20, 0, 2, 21, 1625039528, 1625560549, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (22, 9, '', NULL, 1, '', '零食下午茶现烤面包打蛋器咖啡简约搭配风格', '', '', 128.00, 99.00, 200, 0, '2,1', 0, 0, 0, 0, 'https://images.unsplash.com/photo-1532499016263-f2c3e89de9cd?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OTh8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1532499016263-f2c3e89de9cd?ixid=MnwxMjA3fDB8MHxzZWFyY2h8OTh8fGZvb2R8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'hot', 0, 1.22, 0, 2, 22, 1625039651, 1625039651, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (23, 9, '水果芝士下午茶', NULL, 1, '3003', '美味下午茶原味芝士披萨简约搭配水果下午茶', '', '', 88.00, 80.00, 4499, 1, '2,1', 0, 11, 0, 0, 'https://images.unsplash.com/photo-1618414466217-34f57f16c354?ixid=MnwxMjA3fDB8MHxzZWFyY2h8M3x8cGl6emF8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1618414466217-34f57f16c354?ixid=MnwxMjA3fDB8MHxzZWFyY2h8M3x8cGl6emF8ZW58MHwyfDB8fA%3D%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', 'recommend', 1, 1.20, 0, 2, 23, 1625041299, 1625560538, NULL, 'normal');
INSERT INTO `advn_shop_goods` (`id`, `category_id`, `subtitle`, `attribute_ids`, `brand_id`, `goods_sn`, `title`, `keywords`, `description`, `marketprice`, `price`, `stocks`, `sales`, `guarantee_ids`, `star`, `views`, `comments`, `shares`, `image`, `images`, `content`, `corner`, `flag`, `spectype`, `weight`, `isvirtual`, `freight_id`, `weigh`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES (24, 9, '', NULL, 1, '', '休闲美味下午茶咖啡芝士披萨下午茶简单搭配', '', '', 98.00, 86.00, 200, 0, '1,2', 0, 6, 0, 0, 'https://images.unsplash.com/photo-1508848484850-f696c839aabd?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzZ8fHBpenphfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', 'https://images.unsplash.com/photo-1508848484850-f696c839aabd?ixid=MnwxMjA3fDB8MHxzZWFyY2h8MzZ8fHBpenphfGVufDB8MnwwfHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60', '<div class=\"alert alert-warning\">\r\n	此页面所有商品、价格、介绍等仅用于站点功能演示，不作为真实商品销售。\r\n</div>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1504113888839-1c8eb50233d3?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<p>\r\n	<img src=\"https://images.unsplash.com/photo-1502114586089-b9480c602e18?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" /> \r\n</p>\r\n<p>\r\n	<br />\r\n</p>\r\n<img src=\"https://images.unsplash.com/photo-1459789034005-ba29c5783491?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1024&q=80\" alt=\"\" />', '', '', 0, 1.28, 0, 2, 24, 1625041374, 1625044492, NULL, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_goods_attr
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_goods_attr`;
CREATE TABLE `advn_shop_goods_attr` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `attribute_id` int DEFAULT NULL COMMENT '属性id',
  `value_id` int DEFAULT NULL COMMENT '属性值id',
  `goods_id` int DEFAULT NULL COMMENT '商品id',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `attribute` (`attribute_id`,`value_id`,`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品属性';

-- ----------------------------
-- Records of advn_shop_goods_attr
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_goods_sku
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_goods_sku`;
CREATE TABLE `advn_shop_goods_sku` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `goods_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '货号',
  `sku_id` varchar(100) NOT NULL DEFAULT '' COMMENT 'SKU',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '规格封面',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格',
  `marketprice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `stocks` int NOT NULL DEFAULT '0' COMMENT '库存数量',
  `sales` int unsigned NOT NULL DEFAULT '0' COMMENT '销量',
  `weigh` int unsigned NOT NULL DEFAULT '0' COMMENT '权重',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品SKU表';

-- ----------------------------
-- Records of advn_shop_goods_sku
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (1, 2, '1001', '1', '', 860.00, 1999.00, 100, 0, 0, 1625042669, 1625042669);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (2, 2, '1002', '2', '', 870.00, 1999.00, 100, 0, 0, 1625042669, 1625042669);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (3, 4, '1003', '3', '', 80.00, 199.00, 500, 0, 0, 1625042727, 1625042727);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (4, 4, '1004', '4', '', 80.00, 199.00, 500, 0, 0, 1625042727, 1625042727);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (5, 6, '1006', '5,7', '', 128.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (6, 6, '1006', '5,8', '', 126.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (7, 6, '1006', '5,9', '', 124.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (8, 6, '1006', '6,7', '', 120.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (9, 6, '1006', '6,8', '', 122.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (10, 6, '1006', '6,9', '', 123.00, 299.00, 500, 0, 0, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (11, 11, '2001', '10,13', '', 6999.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (12, 11, '2001', '10,14', '', 6988.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (13, 11, '2001', '11,13', '', 6977.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (14, 11, '2001', '11,14', '', 6966.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (15, 11, '2001', '12,13', '', 6955.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (16, 11, '2001', '12,14', '', 6944.00, 8888.00, 500, 0, 0, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (17, 14, '2002', '15,17', '', 7999.00, 9999.00, 200, 0, 0, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (18, 14, '2002', '15,18', '', 7998.00, 9999.00, 200, 0, 0, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (19, 14, '2002', '16,17', '', 7997.00, 9999.00, 200, 0, 0, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (20, 14, '2002', '16,18', '', 7996.00, 9999.00, 200, 0, 0, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (21, 16, '2003', '19,22', '', 8999.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (22, 16, '2003', '19,23', '', 8998.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (23, 16, '2003', '20,22', '', 8997.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (24, 16, '2003', '20,23', '', 8996.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (25, 16, '2003', '21,22', '', 8995.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (26, 16, '2003', '21,23', '', 8994.00, 9999.00, 500, 0, 0, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (27, 19, '3001', '24,26', '', 80.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (28, 19, '3001', '24,27', '', 81.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (29, 19, '3001', '24,28', '', 82.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (30, 19, '3001', '25,26', '', 83.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (31, 19, '3001', '25,27', '', 84.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (32, 19, '3001', '25,28', '', 85.00, 90.00, 100, 0, 0, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (33, 21, '3002', '29,32', '', 90.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (34, 21, '3002', '29,33', '', 91.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (35, 21, '3002', '29,34', '', 92.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (36, 21, '3002', '30,32', '', 93.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (37, 21, '3002', '30,33', '', 94.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (38, 21, '3002', '30,34', '', 95.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (39, 21, '3002', '31,32', '', 96.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (40, 21, '3002', '31,33', '', 97.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (41, 21, '3002', '31,34', '', 98.00, 128.00, 500, 0, 0, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (42, 23, '3003', '35,38', '', 60.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (43, 23, '3003', '35,39', '', 61.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (44, 23, '3003', '35,40', '', 62.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (45, 23, '3003', '36,38', '', 63.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (46, 23, '3003', '36,39', '', 64.00, 80.00, 499, 1, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (47, 23, '3003', '36,40', '', 65.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (48, 23, '3003', '37,38', '', 66.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (49, 23, '3003', '37,39', '', 67.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku` (`id`, `goods_id`, `goods_sn`, `sku_id`, `image`, `price`, `marketprice`, `stocks`, `sales`, `weigh`, `createtime`, `updatetime`) VALUES (50, 23, '3003', '37,40', '', 60.00, 80.00, 500, 0, 0, 1625043172, 1625043172);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_goods_sku_spec
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_goods_sku_spec`;
CREATE TABLE `advn_shop_goods_sku_spec` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商品ID',
  `spec_id` int unsigned NOT NULL DEFAULT '0' COMMENT '规格ID',
  `spec_value_id` int unsigned NOT NULL DEFAULT '0' COMMENT '规格值ID',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of advn_shop_goods_sku_spec
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (1, 2, 1, 1, 1625042669, 1625042669);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (2, 2, 1, 2, 1625042669, 1625042669);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (3, 4, 1, 1, 1625042727, 1625042727);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (4, 4, 1, 2, 1625042727, 1625042727);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (5, 6, 1, 1, 1625042814, 1625042814);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (6, 6, 1, 2, 1625042814, 1625042814);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (7, 6, 2, 3, 1625042814, 1625042814);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (8, 6, 2, 4, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (9, 6, 2, 5, 1625042815, 1625042815);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (10, 11, 3, 6, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (11, 11, 3, 7, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (12, 11, 3, 8, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (13, 11, 1, 9, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (14, 11, 1, 10, 1625042889, 1625042889);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (15, 14, 3, 7, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (16, 14, 3, 8, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (17, 14, 1, 9, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (18, 14, 1, 10, 1625042941, 1625042941);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (19, 16, 3, 6, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (20, 16, 3, 7, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (21, 16, 3, 8, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (22, 16, 1, 9, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (23, 16, 1, 10, 1625042988, 1625042988);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (24, 19, 4, 11, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (25, 19, 4, 12, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (26, 19, 5, 13, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (27, 19, 5, 14, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (28, 19, 5, 15, 1625043057, 1625043057);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (29, 21, 4, 11, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (30, 21, 4, 12, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (31, 21, 4, 16, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (32, 21, 5, 13, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (33, 21, 5, 14, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (34, 21, 5, 15, 1625043107, 1625043107);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (35, 23, 4, 11, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (36, 23, 4, 12, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (37, 23, 4, 16, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (38, 23, 5, 13, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (39, 23, 5, 14, 1625043172, 1625043172);
INSERT INTO `advn_shop_goods_sku_spec` (`id`, `goods_id`, `spec_id`, `spec_value_id`, `createtime`, `updatetime`) VALUES (40, 23, 5, 15, 1625043172, 1625043172);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_guarantee
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_guarantee`;
CREATE TABLE `advn_shop_guarantee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
  `intro` varchar(255) DEFAULT NULL COMMENT '服务保障',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='服务保障';

-- ----------------------------
-- Records of advn_shop_guarantee
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_guarantee` (`id`, `name`, `image`, `intro`, `createtime`, `updatetime`, `status`) VALUES (1, '放心购', '', '7天无理由退货', 1624071780, 1624071780, 'normal');
INSERT INTO `advn_shop_guarantee` (`id`, `name`, `image`, `intro`, `createtime`, `updatetime`, `status`) VALUES (2, '在线客服', '', '7*24小时在线客服', 1624071795, 1624071795, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_menu
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_menu`;
CREATE TABLE `advn_shop_menu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` int unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(50) DEFAULT '' COMMENT '名称',
  `url` varchar(255) DEFAULT '' COMMENT '链接',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '排序',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='菜单表';

-- ----------------------------
-- Records of advn_shop_menu
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (1, 0, '首页', '/shop', 5, 1625024797, 1625024797, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (2, 0, '会员中心', '/index/user', 4, 1625024797, 1625045590, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (3, 0, '外部链接', 'javascript:', 3, 1625024797, 1625120775, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (4, 3, '京东商城', 'https://www.jd.com', 2, 1625024797, 1625024845, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (5, 3, '淘宝', 'https://www.taobao.com', 1, 1625024797, 1625024774, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (6, 0, '关于我们', '/shop/p/aboutus', 1, 1625024797, 1625024774, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (7, 0, '优惠券', '/shop/coupon', 1, 1625024797, 1625024774, 'normal');
INSERT INTO `advn_shop_menu` (`id`, `pid`, `name`, `url`, `weigh`, `createtime`, `updatetime`, `status`) VALUES (8, 0, '积分兑换', '/shop/exchange', 1, 1625024797, 1625024774, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_navigation
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_navigation`;
CREATE TABLE `advn_shop_navigation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `size` int DEFAULT '60' COMMENT '图标大小',
  `image` varchar(255) DEFAULT NULL COMMENT '图片',
  `path` varchar(255) DEFAULT NULL COMMENT '路径',
  `switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开关',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='导航配置';

-- ----------------------------
-- Records of advn_shop_navigation
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (1, '我的评论', 60, '/assets/addons/shop/navigation/comment.svg', '/pages/remark/comment', 1, 13, 1609402165, 1625555529);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (2, '商品列表', 60, '/assets/addons/shop/navigation/commodity.svg', '/pages/goods/goods', 1, 2, 1609402235, 1625555571);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (3, '优惠券', 60, '/assets/addons/shop/navigation/coupon.svg', '/pages/coupon/coupon', 1, 2, 1609402235, 1625555584);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (4, '单页文章', 60, '/assets/addons/shop/navigation/page-template.svg', '/pages/page/page?id=2', 1, 2, 1609402235, 1625555594);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (5, '积分兑换', 60, '/assets/addons/shop/navigation/exchange.svg', '/pages/score/exchange', 1, 2, 1609402235, 1625555608);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (6, '订单列表', 60, '/assets/addons/shop/navigation/order.svg', '/pages/order/list', 1, 2, 1609402235, 1625555614);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (7, '外部链接', 60, '/assets/addons/shop/navigation/copy-link.svg', 'https://www.baidu.com', 1, 2, 1609402235, 1625555630);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (8, '签到排行', 60, '/assets/addons/shop/navigation/local.svg', '/pages/signin/ranking', 1, 2, 1609402235, 1625555641);
INSERT INTO `advn_shop_navigation` (`id`, `name`, `size`, `image`, `path`, `switch`, `weigh`, `createtime`, `updatetime`) VALUES (9, '测试导航', 60, '/assets/addons/shop/navigation/application-one.svg', '/pages/signin/signin', 1, 1, 1623308082, 1625555654);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_order
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_order`;
CREATE TABLE `advn_shop_order` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `order_sn` varchar(50) NOT NULL DEFAULT '' COMMENT ' 订单号,唯一',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户id,',
  `address_id` int unsigned DEFAULT NULL COMMENT '收款地址ID',
  `province_id` int DEFAULT NULL COMMENT '省id',
  `city_id` int DEFAULT NULL COMMENT '市id',
  `area_id` int DEFAULT NULL COMMENT '区域ID',
  `user_coupon_id` int DEFAULT NULL COMMENT '优惠券记录ID',
  `openid` varchar(100) DEFAULT NULL COMMENT 'Openid',
  `receiver` varchar(60) NOT NULL DEFAULT '' COMMENT '收货人的姓名',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人的地址',
  `zipcode` varchar(60) DEFAULT '' COMMENT '收货人的邮编',
  `mobile` varchar(60) DEFAULT '' COMMENT '收货人的手机',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '订单总金额',
  `discount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `shippingfee` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '配送费用',
  `goodsprice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品总费用',
  `saleamount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '应付款金额',
  `payamount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际付款金额',
  `paytype` varchar(100) NOT NULL DEFAULT '' COMMENT '用户选择的支付方式名称',
  `method` varchar(50) DEFAULT NULL COMMENT '支付方法',
  `transactionid` varchar(100) DEFAULT NULL COMMENT '交易流水号',
  `expressname` varchar(50) DEFAULT '' COMMENT '快递名称',
  `expressno` varchar(50) DEFAULT '' COMMENT '快递单号',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  `expiretime` bigint unsigned DEFAULT NULL COMMENT '过期时间',
  `paytime` bigint unsigned DEFAULT NULL COMMENT '支付时间',
  `refundtime` bigint unsigned DEFAULT NULL COMMENT '退货时间',
  `shippingtime` bigint unsigned DEFAULT NULL COMMENT '配送时间',
  `receivetime` bigint unsigned DEFAULT NULL COMMENT '收货时间',
  `canceltime` bigint unsigned DEFAULT NULL COMMENT '取消时间',
  `deletetime` bigint unsigned DEFAULT NULL COMMENT '删除时间',
  `orderstate` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '订单状态:0=正常,1=已取消,2=已失效,3=已完成,4=退货退款中',
  `shippingstate` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '配送状态:0=未发货,1=已发货,2=已收货',
  `paystate` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '支付状态:0=未付款,1=已付款',
  `memo` varchar(255) DEFAULT NULL COMMENT '备注',
  `status` enum('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态:normal=正常,hidden=隐藏',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_sn` (`order_sn`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `orderstate` (`orderstate`),
  KEY `paytime` (`paytime`),
  KEY `createtime` (`createtime`),
  KEY `province_city_area` (`province_id`,`city_id`,`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单表';

-- ----------------------------
-- Records of advn_shop_order
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_order_action
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_order_action`;
CREATE TABLE `advn_shop_order_action` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '流水号',
  `order_sn` varchar(50) NOT NULL DEFAULT '0' COMMENT '订单编号',
  `operator` varchar(30) NOT NULL DEFAULT '' COMMENT '操作人',
  `memo` varchar(255) NOT NULL DEFAULT '' COMMENT '操作记录(备注)',
  `createtime` bigint DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `order_id` (`order_sn`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单操作记录表';

-- ----------------------------
-- Records of advn_shop_order_action
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_order_aftersales
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_order_aftersales`;
CREATE TABLE `advn_shop_order_aftersales` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '编号',
  `order_id` int DEFAULT NULL COMMENT '订单id',
  `order_goods_id` int DEFAULT NULL COMMENT '订单商品id',
  `user_id` int unsigned DEFAULT NULL COMMENT '用户ID',
  `type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '售后类型:1=仅退款,2=退款退货',
  `nums` int unsigned DEFAULT '0' COMMENT '数量',
  `realprice` decimal(10,2) DEFAULT '0.00' COMMENT '商品实付金额',
  `shippingfee` tinyint(1) DEFAULT '0' COMMENT '邮费',
  `refund` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款金额',
  `reason` varchar(255) NOT NULL COMMENT '退款原因',
  `images` varchar(2500) DEFAULT NULL COMMENT '补充图片',
  `mark` varchar(255) DEFAULT NULL COMMENT '卖家备注',
  `status` tinyint unsigned DEFAULT '1' COMMENT '状态:1=待审核,2=审核通过,3=审核拒绝',
  `expressname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '' COMMENT '快递名称',
  `expressno` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '' COMMENT '快递单号',
  `createtime` bigint unsigned DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint unsigned DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='退货单表';

-- ----------------------------
-- Records of advn_shop_order_aftersales
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_order_electronics
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_order_electronics`;
CREATE TABLE `advn_shop_order_electronics` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '订单编号',
  `print_template` longtext COMMENT '模板',
  `kdn_order_code` varchar(100) DEFAULT NULL COMMENT 'KDNOrderCode',
  `logistic_code` varchar(50) DEFAULT NULL COMMENT '物流单号',
  `shipper_code` varchar(30) DEFAULT NULL COMMENT '快递编号',
  `order` varchar(255) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL COMMENT '线下网点客户号',
  `customer_pwd` varchar(100) DEFAULT NULL COMMENT '线下网点密码',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态:0=正常,1=已取消',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单电子面单记录';

-- ----------------------------
-- Records of advn_shop_order_electronics
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_order_goods
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_order_goods`;
CREATE TABLE `advn_shop_order_goods` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `order_sn` varchar(50) NOT NULL DEFAULT '0' COMMENT '订单号',
  `goods_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '货号',
  `goods_id` int unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_sku_id` int DEFAULT NULL COMMENT 'skuid',
  `title` varchar(255) DEFAULT NULL COMMENT '物品标题',
  `nums` smallint unsigned NOT NULL DEFAULT '1' COMMENT '购买商品数量',
  `marketprice` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '市场价',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商城售价',
  `realprice` decimal(10,2) DEFAULT '0.00' COMMENT '实付金额',
  `salestate` tinyint(1) DEFAULT '0' COMMENT '销售状态:0=待申请,1=已申请,2=退款中,3=退货中,4=已退款,5=已退货退款,6=已拒绝',
  `commentstate` tinyint unsigned DEFAULT '0' COMMENT '评论状态:0=未评论,1=已评论',
  `attrdata` varchar(1500) DEFAULT NULL COMMENT '属性信息',
  `image` varchar(255) DEFAULT NULL COMMENT '图片',
  `weight` decimal(10,2) DEFAULT '0.00' COMMENT '重量(kg)',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `order_id` (`order_sn`) USING BTREE,
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订单商品表';

-- ----------------------------
-- Records of advn_shop_order_goods
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_page
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_page`;
CREATE TABLE `advn_shop_page` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category_id` int NOT NULL DEFAULT '0' COMMENT '分类ID',
  `admin_id` int unsigned DEFAULT '0' COMMENT '管理员ID',
  `type` varchar(50) DEFAULT '' COMMENT '类型',
  `title` varchar(50) DEFAULT '' COMMENT '标题',
  `seotitle` varchar(255) DEFAULT '' COMMENT 'SEO标题',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `flag` varchar(100) DEFAULT '' COMMENT '标志',
  `image` varchar(255) DEFAULT '' COMMENT '头像',
  `content` longtext COMMENT '内容',
  `icon` varchar(50) DEFAULT '' COMMENT '图标',
  `views` int unsigned NOT NULL DEFAULT '0' COMMENT '点击',
  `likes` int unsigned NOT NULL DEFAULT '0' COMMENT '点赞',
  `dislikes` int unsigned NOT NULL DEFAULT '0' COMMENT '点踩',
  `diyname` varchar(100) DEFAULT '' COMMENT '自定义',
  `showtpl` varchar(50) DEFAULT '' COMMENT '视图模板',
  `parsetpl` tinyint unsigned DEFAULT '0' COMMENT '解析模板标签',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`),
  KEY `diyname` (`diyname`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='单页表';

-- ----------------------------
-- Records of advn_shop_page
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_page` (`id`, `category_id`, `admin_id`, `type`, `title`, `seotitle`, `keywords`, `description`, `flag`, `image`, `content`, `icon`, `views`, `likes`, `dislikes`, `diyname`, `showtpl`, `parsetpl`, `createtime`, `updatetime`, `deletetime`, `weigh`, `status`) VALUES (1, 0, 0, 'page', '关于我们', '', '', '', '', '', '<p>\r\n	关于我们的内容\r\n</p>', '', 1, 225, 0, 'aboutus', 'page', 0, 1508933935, 1625557767, NULL, 1, 'normal');
INSERT INTO `advn_shop_page` (`id`, `category_id`, `admin_id`, `type`, `title`, `seotitle`, `keywords`, `description`, `flag`, `image`, `content`, `icon`, `views`, `likes`, `dislikes`, `diyname`, `showtpl`, `parsetpl`, `createtime`, `updatetime`, `deletetime`, `weigh`, `status`) VALUES (2, 0, 0, 'page', '用户注册协议', '', '', '', '', '', '<p>\r\n	用户注册协议\r\n</p>', '', 11, 225, 0, 'agreement', 'page', 0, 1508933935, 1625557772, NULL, 1, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_search_log
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_search_log`;
CREATE TABLE `advn_shop_search_log` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `nums` int DEFAULT NULL,
  `keywords` varchar(100) DEFAULT NULL,
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of advn_shop_search_log
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (1, 1, '玩具', 1624936156, 'normal');
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (2, 1, 'iPhone', 1625818043, 'normal');
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (3, 1, '下午茶', 1625818048, 'normal');
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (4, 1, '手机', 1625818061, 'normal');
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (5, 1, 'Macbook', 1625818070, 'normal');
INSERT INTO `advn_shop_search_log` (`id`, `nums`, `keywords`, `createtime`, `status`) VALUES (6, 1, '简约', 1625818077, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_shipper
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_shipper`;
CREATE TABLE `advn_shop_shipper` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(120) DEFAULT NULL COMMENT '快递名称',
  `shipper_code` varchar(100) DEFAULT NULL COMMENT '快递公司编码',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=648 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='快递公司';

-- ----------------------------
-- Records of advn_shop_shipper
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (1, '顺丰速运', 'SF', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (2, '百世快递', 'HTKY', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (3, '中通快递', 'ZTO', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (4, '申通快递', 'STO', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (5, '圆通速递', 'YTO', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (6, '韵达速递', 'YD', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (7, '邮政快递包裹', 'YZPY', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (8, 'EMS', 'EMS', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (9, '天天快递', 'HHTT', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (10, '京东快递', 'JD', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (11, '优速快递', 'UC', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (12, '德邦快递', 'DBL', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (36, '百世快运', 'BTWL', 1623320171, 1623320171);
INSERT INTO `advn_shop_shipper` (`id`, `name`, `shipper_code`, `createtime`, `updatetime`) VALUES (135, '极兔速递', 'JTSD', 1623320171, 1623320171);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_sku_template
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_sku_template`;
CREATE TABLE `advn_shop_sku_template` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `spec_names` varchar(150) DEFAULT NULL COMMENT '规格名称',
  `spec_values` varchar(250) DEFAULT NULL COMMENT '规格值名称',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格模板';

-- ----------------------------
-- Records of advn_shop_sku_template
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_sku_template` (`id`, `name`, `spec_names`, `spec_values`, `createtime`, `updatetime`) VALUES (1, '手机', '容量;颜色', '32G,64G,128G;白色,黑色', 1625042399, 1625042399);
INSERT INTO `advn_shop_sku_template` (`id`, `name`, `spec_names`, `spec_values`, `createtime`, `updatetime`) VALUES (2, '家居', '颜色', '原木色,米白色', 1625042491, 1625042491);
INSERT INTO `advn_shop_sku_template` (`id`, `name`, `spec_names`, `spec_values`, `createtime`, `updatetime`) VALUES (3, '食物', '辣味;甜度', '不辣,微辣,超辣;少糖,半分糖,全糖', 1625042588, 1625042588);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_spec
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_spec`;
CREATE TABLE `advn_shop_spec` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='商品规格表';

-- ----------------------------
-- Records of advn_shop_spec
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_spec` (`id`, `name`, `createtime`, `updatetime`) VALUES (1, '颜色', 1625042669, 1625042669);
INSERT INTO `advn_shop_spec` (`id`, `name`, `createtime`, `updatetime`) VALUES (2, '风格', 1625042814, 1625042814);
INSERT INTO `advn_shop_spec` (`id`, `name`, `createtime`, `updatetime`) VALUES (3, '容量', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec` (`id`, `name`, `createtime`, `updatetime`) VALUES (4, '辣味', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec` (`id`, `name`, `createtime`, `updatetime`) VALUES (5, '甜度', 1625043057, 1625043057);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_spec_value
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_spec_value`;
CREATE TABLE `advn_shop_spec_value` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `spec_id` int NOT NULL COMMENT '规格ID',
  `value` varchar(255) NOT NULL COMMENT '规格值',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='规格数据表';

-- ----------------------------
-- Records of advn_shop_spec_value
-- ----------------------------
BEGIN;
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (1, 1, '原木色', 1625042669, 1625042669);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (2, 1, '米白色', 1625042669, 1625042669);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (3, 2, '简约', 1625042814, 1625042814);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (4, 2, '办公', 1625042814, 1625042814);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (5, 2, '居家', 1625042814, 1625042814);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (6, 3, '32G', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (7, 3, '64G', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (8, 3, '128G', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (9, 1, '白色', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (10, 1, '黑色', 1625042889, 1625042889);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (11, 4, '不辣', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (12, 4, '微辣', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (13, 5, '少糖', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (14, 5, '半分糖', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (15, 5, '全糖', 1625043057, 1625043057);
INSERT INTO `advn_shop_spec_value` (`id`, `spec_id`, `value`, `createtime`, `updatetime`) VALUES (16, 4, '超辣', 1625043107, 1625043107);
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_subscribe_log
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_subscribe_log`;
CREATE TABLE `advn_shop_subscribe_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT '用户id',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '订单id',
  `tpl_id` varchar(100) DEFAULT NULL COMMENT '模板id',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态:0=未发送,1=已发送',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='订阅记录';

-- ----------------------------
-- Records of advn_shop_subscribe_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_template_msg
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_template_msg`;
CREATE TABLE `advn_shop_template_msg` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) DEFAULT '1' COMMENT '类型:1=公众号,2=小程序,3=邮箱通知,4=短信通知',
  `event` tinyint(1) NOT NULL DEFAULT '0' COMMENT '事件:0=付款成功,1=发货通知,2=退款通知,3=售后拒绝,4=兑换通知',
  `title` varchar(150) DEFAULT NULL COMMENT '标题',
  `tpl_id` varchar(50) DEFAULT NULL COMMENT '模板ID',
  `content` varchar(500) DEFAULT NULL COMMENT '内容',
  `extend` longtext NOT NULL COMMENT '扩展属性',
  `page` varchar(100) DEFAULT NULL COMMENT '页面路径',
  `switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开关',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='模板消息';

-- ----------------------------
-- Records of advn_shop_template_msg
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_shop_user_coupon
-- ----------------------------
DROP TABLE IF EXISTS `advn_shop_user_coupon`;
CREATE TABLE `advn_shop_user_coupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` int DEFAULT NULL COMMENT '优惠券id',
  `user_id` int DEFAULT NULL COMMENT '用户id(领取)',
  `is_used` tinyint(1) DEFAULT '1' COMMENT '是否使用:1=未使用,2=已使用',
  `begin_time` bigint DEFAULT NULL COMMENT '开始时间',
  `expire_time` bigint DEFAULT NULL COMMENT '失效时间',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='优惠券领取记录表';

-- ----------------------------
-- Records of advn_shop_user_coupon
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_signin
-- ----------------------------
DROP TABLE IF EXISTS `advn_signin`;
CREATE TABLE `advn_signin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `successions` int unsigned NOT NULL DEFAULT '0' COMMENT '连续签到次数',
  `type` enum('normal','fillup') DEFAULT 'normal' COMMENT '签到类型',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='签到表';

-- ----------------------------
-- Records of advn_signin
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_sms
-- ----------------------------
DROP TABLE IF EXISTS `advn_sms`;
CREATE TABLE `advn_sms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `event` varchar(30) DEFAULT '' COMMENT '事件',
  `mobile` varchar(20) DEFAULT '' COMMENT '手机号',
  `code` varchar(10) DEFAULT '' COMMENT '验证码',
  `times` int unsigned NOT NULL DEFAULT '0' COMMENT '验证次数',
  `ip` varchar(30) DEFAULT '' COMMENT 'IP',
  `createtime` bigint unsigned DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='短信验证码表';

-- ----------------------------
-- Records of advn_sms
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_test
-- ----------------------------
DROP TABLE IF EXISTS `advn_test`;
CREATE TABLE `advn_test` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int DEFAULT '0' COMMENT '会员ID',
  `admin_id` int DEFAULT '0' COMMENT '管理员ID',
  `category_id` int unsigned DEFAULT '0' COMMENT '分类ID(单选)',
  `category_ids` varchar(100) DEFAULT NULL COMMENT '分类ID(多选)',
  `tags` varchar(255) DEFAULT '' COMMENT '标签',
  `week` enum('monday','tuesday','wednesday') DEFAULT NULL COMMENT '星期(单选):monday=星期一,tuesday=星期二,wednesday=星期三',
  `flag` set('hot','index','recommend') DEFAULT '' COMMENT '标志(多选):hot=热门,index=首页,recommend=推荐',
  `genderdata` enum('male','female') DEFAULT 'male' COMMENT '性别(单选):male=男,female=女',
  `hobbydata` set('music','reading','swimming') DEFAULT NULL COMMENT '爱好(多选):music=音乐,reading=读书,swimming=游泳',
  `title` varchar(100) DEFAULT '' COMMENT '标题',
  `content` text COMMENT '内容',
  `image` varchar(100) DEFAULT '' COMMENT '图片',
  `images` varchar(1500) DEFAULT '' COMMENT '图片组',
  `attachfile` varchar(100) DEFAULT '' COMMENT '附件',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键字',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `city` varchar(100) DEFAULT '' COMMENT '省市',
  `array` varchar(255) DEFAULT '' COMMENT '数组:value=值',
  `json` varchar(255) DEFAULT '' COMMENT '配置:key=名称,value=值',
  `multiplejson` varchar(1500) DEFAULT '' COMMENT '二维数组:title=标题,intro=介绍,author=作者,age=年龄',
  `price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '价格',
  `views` int unsigned DEFAULT '0' COMMENT '点击',
  `workrange` varchar(100) DEFAULT '' COMMENT '时间区间',
  `startdate` date DEFAULT NULL COMMENT '开始日期',
  `activitytime` datetime DEFAULT NULL COMMENT '活动时间(datetime)',
  `year` year DEFAULT NULL COMMENT '年',
  `times` time DEFAULT NULL COMMENT '时间',
  `refreshtime` bigint DEFAULT NULL COMMENT '刷新时间',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `weigh` int DEFAULT '0' COMMENT '权重',
  `switch` tinyint(1) DEFAULT '0' COMMENT '开关',
  `status` enum('normal','hidden') DEFAULT 'normal' COMMENT '状态',
  `state` enum('0','1','2') DEFAULT '1' COMMENT '状态值:0=禁用,1=正常,2=推荐',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='测试表';

-- ----------------------------
-- Records of advn_test
-- ----------------------------
BEGIN;
INSERT INTO `advn_test` (`id`, `user_id`, `admin_id`, `category_id`, `category_ids`, `tags`, `week`, `flag`, `genderdata`, `hobbydata`, `title`, `content`, `image`, `images`, `attachfile`, `keywords`, `description`, `city`, `array`, `json`, `multiplejson`, `price`, `views`, `workrange`, `startdate`, `activitytime`, `year`, `times`, `refreshtime`, `createtime`, `updatetime`, `deletetime`, `weigh`, `switch`, `status`, `state`) VALUES (1, 1, 1, 12, '12,13', '互联网,计算机', 'monday', 'hot,index', 'male', 'music,reading', '我是一篇测试文章', '<p>我是测试内容</p>', '/assets/img/avatar.png', '/assets/img/avatar.png,/assets/img/qrcode.png', '/assets/img/avatar.png', '关键字', '我是一篇测试文章描述，内容过多时将自动隐藏', '广西壮族自治区/百色市/平果县', '[\"a\",\"b\"]', '{\"a\":\"1\",\"b\":\"2\"}', '[{\"title\":\"标题一\",\"intro\":\"介绍一\",\"author\":\"小明\",\"age\":\"21\"}]', 0.00, 0, '2020-10-01 00:00:00 - 2021-10-31 23:59:59', '2017-07-10', '2017-07-10 18:24:45', 2017, '18:24:45', 1491635035, 1491635035, 1491635035, NULL, 0, 1, 'normal', '1');
COMMIT;

-- ----------------------------
-- Table structure for advn_third
-- ----------------------------
DROP TABLE IF EXISTS `advn_third`;
CREATE TABLE `advn_third` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int unsigned DEFAULT '0' COMMENT '会员ID',
  `platform` varchar(30) DEFAULT '' COMMENT '第三方应用',
  `apptype` varchar(50) DEFAULT '' COMMENT '应用类型',
  `unionid` varchar(100) DEFAULT '' COMMENT '第三方UNIONID',
  `openname` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方会员昵称',
  `openid` varchar(100) DEFAULT '' COMMENT '第三方OPENID',
  `access_token` varchar(255) DEFAULT '' COMMENT 'AccessToken',
  `refresh_token` varchar(255) DEFAULT 'RefreshToken',
  `expires_in` int unsigned DEFAULT '0' COMMENT '有效期',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `logintime` bigint DEFAULT NULL COMMENT '登录时间',
  `expiretime` bigint DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`,`openid`),
  KEY `user_id` (`user_id`,`platform`),
  KEY `unionid` (`platform`,`unionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='第三方登录表';

-- ----------------------------
-- Records of advn_third
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_user
-- ----------------------------
DROP TABLE IF EXISTS `advn_user`;
CREATE TABLE `advn_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `group_id` int unsigned NOT NULL DEFAULT '0' COMMENT '组别ID',
  `username` varchar(32) DEFAULT '' COMMENT '用户名',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `password` varchar(32) DEFAULT '' COMMENT '密码',
  `salt` varchar(30) DEFAULT '' COMMENT '密码盐',
  `email` varchar(100) DEFAULT '' COMMENT '电子邮箱',
  `mobile` varchar(11) DEFAULT '' COMMENT '手机号',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `level` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '等级',
  `gender` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `bio` varchar(100) DEFAULT '' COMMENT '格言',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `score` int NOT NULL DEFAULT '0' COMMENT '积分',
  `successions` int unsigned NOT NULL DEFAULT '1' COMMENT '连续登录天数',
  `maxsuccessions` int unsigned NOT NULL DEFAULT '1' COMMENT '最大连续登录天数',
  `prevtime` bigint DEFAULT NULL COMMENT '上次登录时间',
  `logintime` bigint DEFAULT NULL COMMENT '登录时间',
  `loginip` varchar(50) DEFAULT '' COMMENT '登录IP',
  `loginfailure` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '失败次数',
  `loginfailuretime` bigint DEFAULT NULL COMMENT '最后登录失败时间',
  `joinip` varchar(50) DEFAULT '' COMMENT '加入IP',
  `jointime` bigint DEFAULT NULL COMMENT '加入时间',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `token` varchar(50) DEFAULT '' COMMENT 'Token',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  `verification` varchar(255) DEFAULT '' COMMENT '验证',
  `recomno` char(10) NOT NULL DEFAULT '' COMMENT '推荐码',
  `pid` int NOT NULL DEFAULT '0' COMMENT '上级ID',
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员表';

-- ----------------------------
-- Records of advn_user
-- ----------------------------
BEGIN;
INSERT INTO `advn_user` (`id`, `group_id`, `username`, `nickname`, `password`, `salt`, `email`, `mobile`, `avatar`, `level`, `gender`, `birthday`, `bio`, `money`, `score`, `successions`, `maxsuccessions`, `prevtime`, `logintime`, `loginip`, `loginfailure`, `loginfailuretime`, `joinip`, `jointime`, `createtime`, `updatetime`, `token`, `status`, `verification`, `recomno`, `pid`) VALUES (1, 1, 'admin', 'admin', 'dd178d656770c6a8e2d4e4e3f4540c87', '5dsNlj', 'admin@163.com', '13000000000', '/assets/img/avatar.png', 0, 0, '2017-04-08', '', 0.00, 0, 2, 2, 1770466095, 1770563047, '127.0.0.1', 0, 1770562922, '127.0.0.1', 1491635035, 0, 1770563047, '', 'normal', '', '', 0);
COMMIT;

-- ----------------------------
-- Table structure for advn_user_group
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_group`;
CREATE TABLE `advn_user_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT '' COMMENT '组名',
  `rules` text COMMENT '权限节点',
  `createtime` bigint DEFAULT NULL COMMENT '添加时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员组表';

-- ----------------------------
-- Records of advn_user_group
-- ----------------------------
BEGIN;
INSERT INTO `advn_user_group` (`id`, `name`, `rules`, `createtime`, `updatetime`, `status`) VALUES (1, '默认组', '1,2,3,4,5,6,7,8,9,10,11,12', 1491635035, 1491635035, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_user_money_log
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_money_log`;
CREATE TABLE `advn_user_money_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更余额',
  `before` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更前余额',
  `after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变更后余额',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员余额变动表';

-- ----------------------------
-- Records of advn_user_money_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_user_rule
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_rule`;
CREATE TABLE `advn_user_rule` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pid` int DEFAULT NULL COMMENT '父ID',
  `name` varchar(50) DEFAULT NULL COMMENT '名称',
  `title` varchar(50) DEFAULT '' COMMENT '标题',
  `remark` varchar(100) DEFAULT NULL COMMENT '备注',
  `ismenu` tinyint(1) DEFAULT NULL COMMENT '是否菜单',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int DEFAULT '0' COMMENT '权重',
  `status` enum('normal','hidden') DEFAULT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员规则表';

-- ----------------------------
-- Records of advn_user_rule
-- ----------------------------
BEGIN;
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (1, 0, 'index', 'Frontend', '', 1, 1491635035, 1491635035, 1, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (2, 0, 'api', 'API Interface', '', 1, 1491635035, 1491635035, 2, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (3, 1, 'user', 'User Module', '', 1, 1491635035, 1491635035, 12, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (4, 2, 'user', 'User Module', '', 1, 1491635035, 1491635035, 11, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (5, 3, 'index/user/login', 'Login', '', 0, 1491635035, 1491635035, 5, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (6, 3, 'index/user/register', 'Register', '', 0, 1491635035, 1491635035, 7, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (7, 3, 'index/user/index', 'User Center', '', 0, 1491635035, 1491635035, 9, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (8, 3, 'index/user/profile', 'Profile', '', 0, 1491635035, 1491635035, 4, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (9, 4, 'api/user/login', 'Login', '', 0, 1491635035, 1491635035, 6, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (10, 4, 'api/user/register', 'Register', '', 0, 1491635035, 1491635035, 8, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (11, 4, 'api/user/index', 'User Center', '', 0, 1491635035, 1491635035, 10, 'normal');
INSERT INTO `advn_user_rule` (`id`, `pid`, `name`, `title`, `remark`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES (12, 4, 'api/user/profile', 'Profile', '', 0, 1491635035, 1491635035, 3, 'normal');
COMMIT;

-- ----------------------------
-- Table structure for advn_user_score_log
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_score_log`;
CREATE TABLE `advn_user_score_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `score` int NOT NULL DEFAULT '0' COMMENT '变更积分',
  `before` int NOT NULL DEFAULT '0' COMMENT '变更前积分',
  `after` int NOT NULL DEFAULT '0' COMMENT '变更后积分',
  `memo` varchar(255) DEFAULT '' COMMENT '备注',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员积分变动表';

-- ----------------------------
-- Records of advn_user_score_log
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for advn_user_token
-- ----------------------------
DROP TABLE IF EXISTS `advn_user_token`;
CREATE TABLE `advn_user_token` (
  `token` varchar(50) NOT NULL COMMENT 'Token',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `expiretime` bigint DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='会员Token表';

-- ----------------------------
-- Records of advn_user_token
-- ----------------------------
BEGIN;
INSERT INTO `advn_user_token` (`token`, `user_id`, `createtime`, `expiretime`) VALUES ('142bd96e6c70fa11a8bb3dc9c6cd120a951c4cd9', 1, 1770271053, 1772863053);
INSERT INTO `advn_user_token` (`token`, `user_id`, `createtime`, `expiretime`) VALUES ('9881509842670c2db73045d2b3f692cba6f00b2a', 1, 1770563047, 1773155047);
INSERT INTO `advn_user_token` (`token`, `user_id`, `createtime`, `expiretime`) VALUES ('ed5a8c8e9127a145e3783c61ac9e40514ef23e91', 1, 1770466095, 1773058095);
INSERT INTO `advn_user_token` (`token`, `user_id`, `createtime`, `expiretime`) VALUES ('f66cc359790d7c0500dcfe781fb706057e28d271', 1, 1770192140, 1772784140);
COMMIT;

-- ----------------------------
-- Table structure for advn_version
-- ----------------------------
DROP TABLE IF EXISTS `advn_version`;
CREATE TABLE `advn_version` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `oldversion` varchar(30) DEFAULT '' COMMENT '旧版本号',
  `newversion` varchar(30) DEFAULT '' COMMENT '新版本号',
  `packagesize` varchar(30) DEFAULT '' COMMENT '包大小',
  `content` varchar(500) DEFAULT '' COMMENT '升级内容',
  `downloadurl` varchar(255) DEFAULT '' COMMENT '下载地址',
  `enforce` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '强制更新',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `weigh` int NOT NULL DEFAULT '0' COMMENT '权重',
  `status` varchar(30) DEFAULT '' COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='版本表';

-- ----------------------------
-- Records of advn_version
-- ----------------------------
BEGIN;
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
