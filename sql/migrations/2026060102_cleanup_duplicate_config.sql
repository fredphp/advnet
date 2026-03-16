-- =====================================================
-- 清理重复配置，统一使用 advn_config 表
-- 执行时间: 2026-06-01
-- =====================================================

-- 1. 删除 advn_config 表中的重复配置项
-- 保留 coin_rate，删除 withdraw_coin_rate（重复）
DELETE FROM `advn_config` WHERE `name` = 'withdraw_coin_rate';

-- 保留 min_withdraw, max_withdraw，删除 withdraw_min_amount, withdraw_max_amount（重复）
DELETE FROM `advn_config` WHERE `name` = 'withdraw_min_amount';
DELETE FROM `advn_config` WHERE `name` = 'withdraw_max_amount';

-- 保留 daily_withdraw_limit, daily_withdraw_amount，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'withdraw_daily_limit';

-- 保留 fee_rate，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'withdraw_fee_rate';

-- 保留 auto_audit_amount, manual_audit_amount，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'withdraw_auto_audit_amount';
DELETE FROM `advn_config` WHERE `name` = 'withdraw_manual_audit_amount';

-- 保留 new_user_withdraw_days，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'new_user_days';

-- 保留 same_ip_limit, same_device_limit，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'same_ip_reward_limit';
DELETE FROM `advn_config` WHERE `name` = 'same_device_reward_limit';

-- 保留 transfer_retry_count, transfer_retry_interval，删除重复项
DELETE FROM `advn_config` WHERE `name` = 'withdraw_retry_count';
DELETE FROM `advn_config` WHERE `name` = 'withdraw_retry_interval';

-- 删除提现模块单独的审核开关（使用统一的审核流程）
DELETE FROM `advn_config` WHERE `name` = 'withdraw_audit_enabled';
DELETE FROM `advn_config` WHERE `name` = 'withdraw_auto_pay';

-- 2. 更新配置分组，确保配置项正确分组
-- 金币相关配置归入 coin 分组
UPDATE `advn_config` SET `group` = 'coin' WHERE `name` IN (
    'coin_rate',
    'new_user_coin',
    'video_coin_reward',
    'video_watch_duration',
    'daily_video_limit',
    'daily_coin_limit',
    'hourly_coin_limit'
);

-- 提现相关配置归入 withdraw 分组
UPDATE `advn_config` SET `group` = 'withdraw' WHERE `name` IN (
    'withdraw_enabled',
    'min_withdraw',
    'max_withdraw',
    'withdraw_amounts',
    'daily_withdraw_limit',
    'daily_withdraw_amount',
    'fee_rate',
    'auto_audit_amount',
    'manual_audit_amount',
    'new_user_withdraw_days',
    'same_ip_limit',
    'same_device_limit',
    'transfer_retry_count',
    'transfer_retry_interval'
);

-- 风控相关配置归入 risk 分组
UPDATE `advn_config` SET `group` = 'risk' WHERE `name` IN (
    'risk_enabled',
    'auto_ban_enabled',
    'ban_threshold',
    'freeze_threshold',
    'score_decay_rate',
    'max_risk_score',
    'emulator_block',
    'hook_block',
    'proxy_detect',
    'ip_multi_account_threshold',
    'device_multi_account_threshold',
    'rule_cache_ttl'
);

-- 邀请相关配置归入 invite 分组
UPDATE `advn_config` SET `group` = 'invite' WHERE `name` IN (
    'invite_enabled',
    'invite_level1_reward',
    'invite_level2_reward',
    'commission_enabled',
    'level1_commission_rate',
    'level2_commission_rate',
    'daily_invite_limit'
);

-- 视频相关配置归入 video 分组
UPDATE `advn_config` SET `group` = 'video' WHERE `name` IN (
    'min_watch_ratio',
    'repeat_watch_limit',
    'skip_ratio_threshold',
    'list_cache_ttl'
);

-- 红包相关配置归入 redpacket 分组
UPDATE `advn_config` SET `group` = 'redpacket' WHERE `name` IN (
    'daily_grab_limit',
    'min_grab_interval',
    'expire_time'
);

-- 微信相关配置归入 wechat 分组
UPDATE `advn_config` SET `group` = 'wechat' WHERE `name` LIKE 'wechat_%';

-- 系统相关配置归入 system 分组
UPDATE `advn_config` SET `group` = 'system' WHERE `name` IN (
    'api_rate_limit',
    'user_rate_limit',
    'high_risk_rate_limit',
    'session_timeout',
    'token_ttl',
    'log_retention_days'
);

-- 3. 删除不再需要的独立配置表
-- 删除 advn_withdraw_config 表（配置已迁移到 advn_config）
DROP TABLE IF EXISTS `advn_withdraw_config`;

-- 删除 advn_system_config 表（配置已迁移到 advn_config）
DROP TABLE IF EXISTS `advn_system_config`;

-- 4. 删除独立的提现配置后台菜单（配置已整合到系统配置）
DELETE FROM `advn_auth_rule` WHERE `name` = 'withdraw/config';

-- 5. 更新 configgroup，清理不需要的分组
UPDATE `advn_config` 
SET `value` = '{"basic":"基础配置","coin":"金币配置","video":"视频配置","user":"用户配置","withdraw":"提现配置","invite":"邀请配置","risk":"风控配置","redpacket":"红包配置","wechat":"微信配置","system":"系统配置","email":"邮件配置","dictionary":"字典配置"}'
WHERE `name` = 'configgroup';

-- 6. 确保核心配置项存在
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
-- 金币配置（统一）
('coin_rate', 'coin', '金币汇率', '多少金币等于1元人民币（全局统一）', 'number', '', '10000', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `tip` = VALUES(`tip`), `group` = VALUES(`group`);

-- 提现配置（使用统一汇率）
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('withdraw_enabled', 'withdraw', '提现开关', '是否开启用户提现功能', 'switch', '', '1', '', '', '', ''),
('min_withdraw', 'withdraw', '最低提现金额', '最低提现金额（元）', 'number', '', '1', '', 'required|numeric|>=:0.1', '', ''),
('max_withdraw', 'withdraw', '最高提现金额', '最高提现金额（元）', 'number', '', '500', '', 'required|numeric', '', ''),
('withdraw_amounts', 'withdraw', '可选提现金额', '可选提现金额（元），逗号分隔', 'string', '', '10,20,50,100', '', '', '', ''),
('daily_withdraw_limit', 'withdraw', '每日提现次数', '用户每日提现次数上限', 'number', '', '3', '', 'required|integer', '', ''),
('daily_withdraw_amount', 'withdraw', '每日提现金额', '用户每日提现金额上限（元）', 'number', '', '500', '', 'required|numeric', '', ''),
('fee_rate', 'withdraw', '提现手续费率', '提现手续费率（百分比，如5表示5%）', 'number', '', '0', '', 'required|numeric|>=:0|<=:100', '', ''),
('auto_audit_amount', 'withdraw', '自动审核金额', '低于此金额自动审核通过（元）', 'number', '', '10', '', 'required|numeric', '', ''),
('manual_audit_amount', 'withdraw', '人工审核金额', '高于此金额需要人工审核（元）', 'number', '', '50', '', 'required|numeric', '', ''),
('new_user_withdraw_days', 'withdraw', '新用户提现限制', '新用户注册多少天后才能提现', 'number', '', '3', '', 'required|integer', '', ''),
('same_ip_limit', 'withdraw', '同IP提现限制', '同一IP每日最大提现次数', 'number', '', '5', '', 'required|integer', '', ''),
('same_device_limit', 'withdraw', '同设备提现限制', '同一设备每日最大提现次数', 'number', '', '3', '', 'required|integer', '', ''),
('transfer_retry_count', 'withdraw', '打款重试次数', '打款失败后重试次数', 'number', '', '3', '', 'required|integer', '', ''),
('transfer_retry_interval', 'withdraw', '重试间隔', '打款重试间隔（秒）', 'number', '', '300', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);
