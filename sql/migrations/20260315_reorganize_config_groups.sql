-- =====================================================
-- 配置项分组优化迁移
-- 将所有配置项按模块分组，统一管理
-- 执行时间: 2026-03-15
-- =====================================================

-- 1. 更新配置分组字典 (configgroup)
UPDATE `advn_config` SET `value` = '{"basic":"基础配置","user":"用户配置","video":"视频配置","invite":"邀请配置","withdraw":"提现配置","risk":"风控配置","email":"邮件配置","dictionary":"字典配置","migration":"迁移配置"}' WHERE `name` = 'configgroup';

-- 2. 将配置项按模块重新分组

-- 基础配置 (basic) - 保留站点基础设置
UPDATE `advn_config` SET `group` = 'basic' WHERE `name` IN ('name', 'beian', 'cdnurl', 'version', 'timezone', 'forbiddenip', 'languages', 'fixedpage');

-- 用户配置 (user) - 用户相关配置
UPDATE `advn_config` SET `group` = 'user' WHERE `name` IN (
    'coin_rate',           -- 金币汇率
    'new_user_coin',       -- 新用户奖励
    'new_user_days',       -- 新用户定义天数
    'new_user_reward_coin' -- 新用户首次观看奖励
);

-- 视频配置 (video) - 视频观看相关配置
UPDATE `advn_config` SET `group` = 'video' WHERE `name` IN (
    'video_coin_reward',       -- 视频观看奖励
    'video_watch_duration',    -- 有效观看时长
    'daily_video_limit',       -- 每日视频上限
    'daily_coin_limit',        -- 每日金币上限
    'hourly_coin_limit',       -- 每小时金币上限
    'watch_complete_threshold',-- 完成观看阈值
    'daily_watch_limit',       -- 每日奖励上限
    'watch_interval',          -- 同视频奖励间隔
    'default_reward_coin',     -- 默认奖励金币
    'same_ip_reward_limit',    -- 同IP奖励限制
    'same_device_reward_limit',-- 同设备奖励限制
    'max_watch_speed',         -- 最大观看速度
    'hourly_watch_limit'       -- 每小时观看限制
);

-- 邀请配置 (invite) - 邀请分佣相关配置
UPDATE `advn_config` SET `group` = 'invite' WHERE `name` IN (
    'invite_max_level',            -- 最大分佣层级
    'invite_register_reward',      -- 邀请注册奖励
    'invite_first_withdraw_reward' -- 首提奖励
);

-- 风控配置 (risk) - 风控相关配置
UPDATE `advn_config` SET `group` = 'risk' WHERE `name` IN (
    'risk_score_threshold' -- 风控拦截阈值
);

-- 提现配置 (withdraw) - 提现相关配置 (如需要可添加)
-- UPDATE `advn_config` SET `group` = 'withdraw' WHERE `name` IN ();

-- 3. 删除冗余的菜单项

-- 删除独立的"系统设置"菜单 (setting)
DELETE FROM `advn_auth_rule` WHERE `name` = 'setting';

-- 删除提现模块下的"提现配置"子菜单 (withdraw/config)
DELETE FROM `advn_auth_rule` WHERE `name` = 'withdraw/config';

-- 删除迁移模块下的"迁移配置"子菜单 (migration/config)
DELETE FROM `advn_auth_rule` WHERE `name` = 'migration/config';

-- 4. 添加缺失的提现配置项
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('withdraw_enabled', 'withdraw', '提现开关', '是否开启用户提现功能', 'switch', '', '1', '', '', '', ''),
('withdraw_min_amount', 'withdraw', '最低提现金额', '用户最低提现金额(元)', 'number', '', '1', '', 'required|numeric|>=:0.3', '', ''),
('withdraw_max_amount', 'withdraw', '最大提现金额', '用户单次最大提现金额(元)', 'number', '', '500', '', 'required|numeric', '', ''),
('withdraw_daily_limit', 'withdraw', '每日提现次数', '用户每日提现次数上限', 'number', '', '3', '', 'required|integer', '', ''),
('withdraw_fee_rate', 'withdraw', '提现手续费率', '提现手续费率(0-100)', 'number', '', '0', '', 'required|numeric|>=:0|<=:100', '', ''),
('withdraw_audit_enabled', 'withdraw', '提现审核', '是否开启提现审核', 'switch', '', '1', '', '', '', ''),
('withdraw_auto_pay', 'withdraw', '自动打款', '是否开启自动打款', 'switch', '', '0', '', '', '', ''),
('withdraw_coin_rate', 'withdraw', '提现金币汇率', '多少金币等于1元(提现专用)', 'number', '', '10000', '', 'required|integer', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 5. 添加缺失的风控配置项
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('risk_enabled', 'risk', '风控开关', '是否开启风控检测', 'switch', '', '1', '', '', '', ''),
('risk_auto_ban', 'risk', '自动封禁', '风控评分过高时自动封禁用户', 'switch', '', '1', '', '', '', ''),
('risk_ban_threshold', 'risk', '封禁阈值', '风控评分超过此值自动封禁(0-100)', 'number', '', '80', '', 'required|integer|>=:0|<=:100', '', ''),
('risk_freeze_threshold', 'risk', '冻结阈值', '风控评分超过此值自动冻结(0-100)', 'number', '', '50', '', 'required|integer|>=:0|<=:100', '', ''),
('emulator_block', 'risk', '模拟器拦截', '是否拦截模拟器设备', 'switch', '', '1', '', '', '', ''),
('hook_block', 'risk', 'Hook拦截', '是否拦截Hook框架', 'switch', '', '1', '', '', '', ''),
('proxy_detect', 'risk', '代理检测', '是否检测代理IP', 'switch', '', '1', '', '', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 6. 添加缺失的邀请配置项
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('invite_enabled', 'invite', '邀请开关', '是否开启邀请功能', 'switch', '', '1', '', '', '', ''),
('invite_level1_rate', 'invite', '一级分佣比例', '一级邀请分佣比例(0-100)', 'number', '', '10', '', 'required|numeric|>=:0|<=:100', '', ''),
('invite_level2_rate', 'invite', '二级分佣比例', '二级邀请分佣比例(0-100)', 'number', '', '5', '', 'required|numeric|>=:0|<=:100', '', '')
ON DUPLICATE KEY UPDATE `group` = VALUES(`group`), `title` = VALUES(`title`), `tip` = VALUES(`tip`);

-- 7. 更新配置项标题为中文
UPDATE `advn_config` SET `title` = '站点名称' WHERE `name` = 'name';
UPDATE `advn_config` SET `title` = '备案号' WHERE `name` = 'beian';
UPDATE `advn_config` SET `title` = 'CDN地址' WHERE `name` = 'cdnurl';
UPDATE `advn_config` SET `title` = '版本号' WHERE `name` = 'version';
UPDATE `advn_config` SET `title` = '时区' WHERE `name` = 'timezone';
UPDATE `advn_config` SET `title` = '禁用IP' WHERE `name` = 'forbiddenip';
UPDATE `advn_config` SET `title` = '语言设置' WHERE `name` = 'languages';
UPDATE `advn_config` SET `title` = '固定页面' WHERE `name` = 'fixedpage';
