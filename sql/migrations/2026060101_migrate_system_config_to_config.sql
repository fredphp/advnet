-- =====================================================
-- 配置迁移: advn_system_config -> advn_config
-- 将所有业务配置从 system_config 表迁移到 config 表
-- 执行时间: 2026-06-01
-- =====================================================

-- 1. 更新配置分组字典，添加缺失的分组
UPDATE `advn_config` 
SET `value` = '{"basic":"基础配置","coin":"金币配置","video":"视频配置","user":"用户配置","withdraw":"提现配置","invite":"邀请配置","risk":"风控配置","redpacket":"红包配置","migration":"数据迁移","email":"邮件配置","dictionary":"字典配置","wechat":"微信配置","system":"系统配置"}'
WHERE `name` = 'configgroup';

-- 2. 金币配置 (coin)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('coin_rate', 'coin', '金币汇率', '多少金币等于1元人民币', 'number', '', '10000', '', 'required|integer', '', ''),
('new_user_coin', 'coin', '新用户注册奖励', '新用户注册奖励金币数量', 'number', '', '1000', '', 'required|integer', '', ''),
('video_coin_reward', 'coin', '视频观看奖励', '视频观看奖励金币数量', 'number', '', '100', '', 'required|integer', '', ''),
('video_watch_duration', 'coin', '有效观看时长', '有效观看时长(秒)', 'number', '', '30', '', 'required|integer', '', ''),
('daily_video_limit', 'coin', '每日视频上限', '每日视频观看上限', 'number', '', '500', '', 'required|integer', '', ''),
('daily_coin_limit', 'coin', '每日金币上限', '每日金币获取上限', 'number', '', '50000', '', 'required|integer', '', ''),
('hourly_coin_limit', 'coin', '每小时金币上限', '每小时金币获取上限', 'number', '', '10000', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 3. 提现配置 (withdraw)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('withdraw_enabled', 'withdraw', '提现开关', '是否开启用户提现功能', 'switch', '', '1', '', '', '', ''),
('min_withdraw', 'withdraw', '最低提现金额', '最低提现金额(元)', 'number', '', '1', '', 'required|numeric', '', ''),
('max_withdraw', 'withdraw', '最高提现金额', '最高提现金额(元)', 'number', '', '500', '', 'required|numeric', '', ''),
('withdraw_amounts', 'withdraw', '可选提现金额', '可选提现金额(元)，逗号分隔', 'string', '', '10,20,50,100', '', '', '', ''),
('daily_withdraw_limit', 'withdraw', '每日提现次数限制', '每日提现次数限制', 'number', '', '3', '', 'required|integer', '', ''),
('daily_withdraw_amount', 'withdraw', '每日提现金额限制', '每日提现金额限制(元)', 'number', '', '500', '', 'required|numeric', '', ''),
('fee_rate', 'withdraw', '提现手续费率', '提现手续费率(0-100)', 'number', '', '0', '', 'required|numeric|>=:0|<=:100', '', ''),
('auto_audit_amount', 'withdraw', '自动审核金额阈值', '自动审核金额阈值(元)', 'number', '', '10', '', 'required|numeric', '', ''),
('manual_audit_amount', 'withdraw', '人工审核金额阈值', '人工审核金额阈值(元)', 'number', '', '50', '', 'required|numeric', '', ''),
('new_user_withdraw_days', 'withdraw', '新用户提现天数限制', '新用户注册多少天后才能提现', 'number', '', '3', '', 'required|integer', '', ''),
('same_ip_limit', 'withdraw', '同IP提现次数限制', '同IP提现次数限制', 'number', '', '5', '', 'required|integer', '', ''),
('same_device_limit', 'withdraw', '同设备提现次数限制', '同设备提现次数限制', 'number', '', '3', '', 'required|integer', '', ''),
('transfer_retry_count', 'withdraw', '提现重试次数', '提现失败重试次数', 'number', '', '3', '', 'required|integer', '', ''),
('transfer_retry_interval', 'withdraw', '提现重试间隔', '提现重试间隔(秒)', 'number', '', '300', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 4. 邀请配置 (invite)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('invite_enabled', 'invite', '邀请开关', '是否开启邀请功能', 'switch', '', '1', '', '', '', ''),
('invite_level1_reward', 'invite', '一级邀请奖励', '一级邀请奖励金币', 'number', '', '1000', '', 'required|integer', '', ''),
('invite_level2_reward', 'invite', '二级邀请奖励', '二级邀请奖励金币', 'number', '', '500', '', 'required|integer', '', ''),
('commission_enabled', 'invite', '分佣开关', '是否开启分佣功能', 'switch', '', '1', '', '', '', ''),
('level1_commission_rate', 'invite', '一级分佣比例', '一级分佣比例(0-100)', 'number', '', '10', '', 'required|numeric|>=:0|<=:100', '', ''),
('level2_commission_rate', 'invite', '二级分佣比例', '二级分佣比例(0-100)', 'number', '', '5', '', 'required|numeric|>=:0|<=:100', '', ''),
('daily_invite_limit', 'invite', '每日邀请次数限制', '每日邀请次数限制', 'number', '', '50', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 5. 视频配置 (video)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('min_watch_ratio', 'video', '最小观看比例', '视频最小观看比例(0-1)', 'number', '', '0.3', '', 'required|numeric|>=:0|<=:1', '', ''),
('repeat_watch_limit', 'video', '重复观看限制', '同一视频重复观看奖励次数', 'number', '', '5', '', 'required|integer', '', ''),
('skip_ratio_threshold', 'video', '跳过率阈值', '视频跳过率阈值(0-1)', 'number', '', '0.9', '', 'required|numeric|>=:0|<=:1', '', ''),
('list_cache_ttl', 'video', '视频列表缓存时间', '视频列表缓存时间(秒)', 'number', '', '300', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 6. 红包配置 (redpacket)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('daily_grab_limit', 'redpacket', '每日抢红包次数', '每日抢红包次数限制', 'number', '', '50', '', 'required|integer', '', ''),
('min_grab_interval', 'redpacket', '抢红包最小间隔', '抢红包最小间隔(秒)', 'number', '', '0.5', '', 'required|numeric', '', ''),
('expire_time', 'redpacket', '红包过期时间', '红包过期时间(秒)', 'number', '', '86400', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 7. 风控配置 (risk)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('risk_enabled', 'risk', '风控开关', '是否开启风控检测', 'switch', '', '1', '', '', '', ''),
('auto_ban_enabled', 'risk', '自动封禁开关', '是否开启自动封禁', 'switch', '', '1', '', '', '', ''),
('ban_threshold', 'risk', '封禁阈值', '自动封禁阈值(风险分)', 'number', '', '700', '', 'required|integer', '', ''),
('freeze_threshold', 'risk', '冻结阈值', '自动冻结阈值(风险分)', 'number', '', '300', '', 'required|integer', '', ''),
('score_decay_rate', 'risk', '风险分衰减率', '风险分每日衰减率(0-1)', 'number', '', '0.1', '', 'required|numeric|>=:0|<=:1', '', ''),
('max_risk_score', 'risk', '风险分最大值', '风险分最大值', 'number', '', '1000', '', 'required|integer', '', ''),
('emulator_block', 'risk', '模拟器拦截', '是否拦截模拟器设备', 'switch', '', '1', '', '', '', ''),
('hook_block', 'risk', 'Hook拦截', '是否拦截Hook框架', 'switch', '', '1', '', '', '', ''),
('proxy_detect', 'risk', '代理检测', '是否检测代理IP', 'switch', '', '1', '', '', '', ''),
('ip_multi_account_threshold', 'risk', 'IP多账户阈值', '同一IP账户数阈值', 'number', '', '5', '', 'required|integer', '', ''),
('device_multi_account_threshold', 'risk', '设备多账户阈值', '同一设备账户数阈值', 'number', '', '3', '', 'required|integer', '', ''),
('rule_cache_ttl', 'risk', '风控规则缓存时间', '风控规则缓存时间(秒)', 'number', '', '300', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 8. 系统配置 (system)
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('api_rate_limit', 'system', 'API限流', 'API每分钟请求次数限制', 'number', '', '60', '', 'required|integer', '', ''),
('user_rate_limit', 'system', '用户限流', '用户每分钟请求次数限制', 'number', '', '30', '', 'required|integer', '', ''),
('high_risk_rate_limit', 'system', '高风险操作限流', '高风险操作每5分钟次数限制', 'number', '', '5', '', 'required|integer', '', ''),
('session_timeout', 'system', '会话超时时间', '会话超时时间(秒)', 'number', '', '86400', '', 'required|integer', '', ''),
('token_ttl', 'system', 'Token有效期', 'Token有效期(秒)', 'number', '', '604800', '', 'required|integer', '', ''),
('log_retention_days', 'system', '日志保留天数', '日志保留天数', 'number', '', '30', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 9. 微信配置 (wechat) - 如果之前未执行wechat_config.sql
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('wechat_app_enabled', 'wechat', '微信App登录开关', '是否开启微信App登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_app_appid', 'wechat', '微信App AppID', '微信开放平台移动应用AppID', 'string', '', '', '', '', '', ''),
('wechat_app_secret', 'wechat', '微信App Secret', '微信开放平台移动应用Secret', 'password', '', '', '', '', '', ''),
('wechat_mini_enabled', 'wechat', '微信小程序登录开关', '是否开启微信小程序登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_mini_appid', 'wechat', '小程序AppID', '微信小程序AppID', 'string', '', '', '', '', '', ''),
('wechat_mini_secret', 'wechat', '小程序Secret', '微信小程序Secret', 'password', '', '', '', '', '', ''),
('wechat_official_enabled', 'wechat', '微信公众号登录开关', '是否开启微信公众号登录功能', 'switch', '', '0', '', '', '', ''),
('wechat_official_appid', 'wechat', '公众号AppID', '微信公众号AppID', 'string', '', '', '', '', '', ''),
('wechat_official_secret', 'wechat', '公众号Secret', '微信公众号Secret', 'password', '', '', '', '', '', ''),
('wechat_pay_enabled', 'wechat', '微信支付开关', '是否开启微信支付功能', 'switch', '', '0', '', '', '', ''),
('wechat_pay_mchid', 'wechat', '商户号', '微信支付商户号', 'string', '', '', '', '', '', ''),
('wechat_pay_key', 'wechat', '支付密钥', '微信支付API密钥', 'password', '', '', '', '', '', ''),
('wechat_pay_cert_pem', 'wechat', '支付证书cert', '微信支付证书cert内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_pay_key_pem', 'wechat', '支付证书key', '微信支付证书key内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_pay_notify_url', 'wechat', '支付回调地址', '微信支付异步回调地址', 'string', '', '', '', '', '', ''),
('wechat_transfer_enabled', 'wechat', '企业付款开关', '是否开启微信企业付款功能（用于提现）', 'switch', '', '0', '', '', '', ''),
('wechat_transfer_mchid', 'wechat', '企业付款商户号', '企业付款商户号（可与支付商户号相同）', 'string', '', '', '', '', '', ''),
('wechat_transfer_key', 'wechat', '企业付款密钥', '企业付款API密钥', 'password', '', '', '', '', '', ''),
('wechat_transfer_cert_pem', 'wechat', '企业付款证书cert', '企业付款证书cert内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_transfer_key_pem', 'wechat', '企业付款证书key', '企业付款证书key内容（pem格式）', 'textarea', '', '', '', '', '', ''),
('wechat_auto_register', 'wechat', '自动注册', '微信登录时如果用户不存在是否自动注册', 'switch', '', '1', '', '', '', ''),
('wechat_bind_mobile', 'wechat', '强制绑定手机', '微信登录后是否强制绑定手机号', 'switch', '', '0', '', '', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);


-- =====================================================
-- 数据迁移完成后，删除 advn_system_config 表
-- 注意：请在确认配置迁移成功后再执行此步骤
-- =====================================================

-- 10. 删除不再需要的独立后台微信配置菜单（配置已整合到系统配置）
DELETE FROM `advn_auth_rule` WHERE `name` = 'general/wechat_config';

-- 11. 删除 advn_system_config 表（请确认配置迁移成功后再执行）
-- DROP TABLE IF EXISTS `advn_system_config`;
