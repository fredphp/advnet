-- =====================================================
-- 微信配置数据库更新脚本
-- 用于添加微信App授权登录支持
-- =====================================================

-- =====================================================
-- 第一部分：更新 advn_config 表 (后台系统配置页面使用)
-- =====================================================

-- 1. 更新配置分组，添加"微信配置"
UPDATE `advn_config` 
SET `value` = '{"basic":"基础配置","coin":"金币配置","video":"视频配置","user":"用户配置","withdraw":"提现配置","invite":"邀请配置","risk":"风控配置","redpacket":"红包配置","migration":"数据迁移","email":"邮件配置","dictionary":"字典配置","wechat":"微信配置"}'
WHERE `name` = 'configgroup';

-- 2. 添加微信配置项到 advn_config 表

-- 微信App配置
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_app_enabled', 'wechat', '微信App登录开关', '是否开启微信App登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_app_appid', 'wechat', '微信App AppID', '微信开放平台移动应用AppID', 'string', '', '', '', '', '', ''),
('wechat_app_secret', 'wechat', '微信App Secret', '微信开放平台移动应用Secret', 'password', '', '', '', '', '', '');

-- 微信小程序配置
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_mini_enabled', 'wechat', '微信小程序登录开关', '是否开启微信小程序登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_mini_appid', 'wechat', '小程序AppID', '微信小程序AppID', 'string', '', '', '', '', '', ''),
('wechat_mini_secret', 'wechat', '小程序Secret', '微信小程序Secret', 'password', '', '', '', '', '', '');

-- 微信公众号配置
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_official_enabled', 'wechat', '微信公众号登录开关', '是否开启微信公众号登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_official_appid', 'wechat', '公众号AppID', '微信公众号AppID', 'string', '', '', '', '', '', ''),
('wechat_official_secret', 'wechat', '公众号Secret', '微信公众号Secret', 'password', '', '', '', '', '', '');

-- 微信支付配置
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_pay_enabled', 'wechat', '微信支付开关', '是否开启微信支付功能', 'switch', '', '0', '', '', '', ''),
('wechat_pay_mchid', 'wechat', '商户号', '微信支付商户号', 'string', '', '', '', '', '', ''),
('wechat_pay_key', 'wechat', '支付密钥', '微信支付API密钥', 'password', '', '', '', '', '', ''),
('wechat_pay_cert_pem', 'wechat', '支付证书cert', '微信支付证书cert内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_pay_key_pem', 'wechat', '支付证书key', '微信支付证书key内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_pay_notify_url', 'wechat', '支付回调地址', '微信支付异步回调地址', 'string', '', '', '', '', '', '');

-- 企业付款配置（用于提现）
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_transfer_enabled', 'wechat', '企业付款开关', '是否开启微信企业付款功能（用于提现）', 'switch', '', '0', '', '', '', ''),
('wechat_transfer_mchid', 'wechat', '企业付款商户号', '企业付款商户号（可与支付商户号相同）', 'string', '', '', '', '', '', ''),
('wechat_transfer_key', 'wechat', '企业付款密钥', '企业付款API密钥', 'password', '', '', '', '', '', ''),
('wechat_transfer_cert_pem', 'wechat', '企业付款证书cert', '企业付款证书cert内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_transfer_key_pem', 'wechat', '企业付款证书key', '企业付款证书key内容（pem格式）', 'textarea', '', '', '', '', '', '');

-- 登录配置
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_auto_register', 'wechat', '自动注册', '微信登录时如果用户不存在是否自动注册', 'switch', '', '1', '', '', '', ''),
('wechat_bind_mobile', 'wechat', '强制绑定手机', '微信登录后是否强制绑定手机号', 'switch', '', '0', '', '', '', '');


-- =====================================================
-- 第二部分：用户表添加微信相关字段 (advn_user)
-- =====================================================

-- 添加微信相关字段
ALTER TABLE `advn_user` 
ADD COLUMN `wechat_openid` varchar(64) DEFAULT NULL COMMENT '微信App OpenID' AFTER `device_id`,
ADD COLUMN `wechat_unionid` varchar(64) DEFAULT NULL COMMENT '微信UnionID' AFTER `wechat_openid`,
ADD COLUMN `wechat_mini_openid` varchar(64) DEFAULT NULL COMMENT '微信小程序OpenID' AFTER `wechat_unionid`,
ADD COLUMN `wechat_official_openid` varchar(64) DEFAULT NULL COMMENT '微信公众号OpenID' AFTER `wechat_mini_openid`,
ADD COLUMN `wechat_nickname` varchar(100) DEFAULT NULL COMMENT '微信昵称' AFTER `wechat_official_openid`,
ADD COLUMN `wechat_avatar` varchar(500) DEFAULT NULL COMMENT '微信头像' AFTER `wechat_nickname`,
ADD COLUMN `wechat_gender` tinyint unsigned DEFAULT 0 COMMENT '微信性别:0未知,1男,2女' AFTER `wechat_avatar`,
ADD COLUMN `wechat_city` varchar(50) DEFAULT NULL COMMENT '微信城市' AFTER `wechat_gender`,
ADD COLUMN `wechat_province` varchar(50) DEFAULT NULL COMMENT '微信省份' AFTER `wechat_city`,
ADD COLUMN `wechat_country` varchar(50) DEFAULT NULL COMMENT '微信国家' AFTER `wechat_province`,
ADD COLUMN `wechat_bindtime` bigint DEFAULT NULL COMMENT '微信绑定时间' AFTER `wechat_country`;

-- 添加索引
ALTER TABLE `advn_user` 
ADD UNIQUE KEY `uk_wechat_openid` (`wechat_openid`),
ADD UNIQUE KEY `uk_wechat_unionid` (`wechat_unionid`),
ADD KEY `idx_wechat_mini_openid` (`wechat_mini_openid`),
ADD KEY `idx_wechat_official_openid` (`wechat_official_openid`);
