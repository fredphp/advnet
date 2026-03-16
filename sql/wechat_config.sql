-- =====================================================
-- 微信配置相关数据库更新脚本
-- 用于添加微信App授权登录支持
-- =====================================================

-- 1. 在advn_system_config表中添加微信配置项
INSERT INTO `advn_system_config` (`group`, `name`, `value`, `type`, `title`, `tip`, `status`, `sort`, `createtime`, `updatetime`) VALUES
-- 微信App配置
('wechat', 'wechat_app_enabled', '0', 'boolean', '微信App登录开关', '是否开启微信App登录功能', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_app_appid', '', 'string', '微信App AppID', '微信开放平台移动应用AppID', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_app_secret', '', 'string', '微信App Secret', '微信开放平台移动应用Secret', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 微信小程序配置
('wechat', 'wechat_mini_enabled', '0', 'boolean', '微信小程序登录开关', '是否开启微信小程序登录功能', 1, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_mini_appid', '', 'string', '小程序AppID', '微信小程序AppID', 1, 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_mini_secret', '', 'string', '小程序Secret', '微信小程序Secret', 1, 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 微信公众号配置
('wechat', 'wechat_official_enabled', '0', 'boolean', '微信公众号登录开关', '是否开启微信公众号登录功能', 1, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_official_appid', '', 'string', '公众号AppID', '微信公众号AppID', 1, 21, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_official_secret', '', 'string', '公众号Secret', '微信公众号Secret', 1, 22, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 微信支付配置
('wechat', 'wechat_pay_enabled', '0', 'boolean', '微信支付开关', '是否开启微信支付功能', 1, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_pay_mchid', '', 'string', '商户号', '微信支付商户号', 1, 31, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_pay_key', '', 'string', '支付密钥', '微信支付API密钥', 1, 32, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_pay_cert_pem', '', 'string', '支付证书cert', '微信支付证书cert内容', 1, 33, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_pay_key_pem', '', 'string', '支付证书key', '微信支付证书key内容', 1, 34, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_pay_notify_url', '', 'string', '支付回调地址', '微信支付异步回调地址', 1, 35, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 微信企业付款配置（用于提现）
('wechat', 'wechat_transfer_enabled', '0', 'boolean', '企业付款开关', '是否开启微信企业付款功能（用于提现）', 1, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_transfer_mchid', '', 'string', '企业付款商户号', '企业付款商户号（可与支付商户号相同）', 1, 41, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_transfer_key', '', 'string', '企业付款密钥', '企业付款API密钥', 1, 42, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_transfer_cert_pem', '', 'string', '企业付款证书cert', '企业付款证书cert内容', 1, 43, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_transfer_key_pem', '', 'string', '企业付款证书key', '企业付款证书key内容', 1, 44, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 登录配置
('wechat', 'wechat_auto_register', '1', 'boolean', '自动注册', '微信登录时如果用户不存在是否自动注册', 1, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('wechat', 'wechat_bind_mobile', '0', 'boolean', '强制绑定手机', '微信登录后是否强制绑定手机号', 1, 51, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();

-- 2. 在用户表中添加微信相关字段
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
ADD COLUMN `wechat_bindtime` bigint DEFAULT NULL COMMENT '微信绑定时间' AFTER `wechat_country`,
ADD UNIQUE KEY `uk_wechat_openid` (`wechat_openid`),
ADD UNIQUE KEY `uk_wechat_unionid` (`wechat_unionid`),
ADD KEY `idx_wechat_mini_openid` (`wechat_mini_openid`),
ADD KEY `idx_wechat_official_openid` (`wechat_official_openid`);

-- 3. 更新配置分组（添加到advn_config的configgroup）
UPDATE `advn_config` 
SET `value` = JSON_SET(
    `value`, 
    '$.wechat', '微信配置'
) 
WHERE `name` = 'configgroup';
