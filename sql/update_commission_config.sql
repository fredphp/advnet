-- =============================================
-- 更新分佣配置 - 只保留提现分佣
-- =============================================

-- 1. 确保基础分佣配置存在
INSERT IGNORE INTO `advn_config` (`group`, `name`, `value`, `type`, `title`, `remark`, `status`, `sort`, `createtime`, `updatetime`) VALUES
('invite', 'commission_enabled', '1', 'switch', '分佣开关', '是否开启邀请分佣（仅提现分佣）', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level1_commission_rate', '0.10', 'number', '一级分佣比例', '直接邀请的分佣比例（如0.10表示10%）', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level2_commission_rate', '0.05', 'number', '二级分佣比例', '间接邀请的分佣比例（如0.05表示5%）', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_min_amount', '0', 'number', '最低分佣金额', '触发分佣的最低提现金额（元）', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_max_amount', '0', 'number', '最高分佣金额', '单次分佣最高金额（0表示不限）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_register_reward', '100', 'number', '注册奖励金币', '新用户注册奖励金币数量', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_register_reward_parent', '50', 'number', '邀请注册奖励', '邀请新用户注册给邀请人的奖励金币', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 删除视频、红包、游戏相关的分佣配置（如果存在）
DELETE FROM `advn_config` WHERE `name` IN (
    'level1_watch_commission',
    'level2_watch_commission', 
    'level1_red_packet_commission',
    'level2_red_packet_commission',
    'level1_game_commission',
    'level2_game_commission'
);

-- 3. 删除 commissionconfig 菜单
DELETE FROM `advn_auth_rule` WHERE `name` = 'invite/commissionconfig';

SELECT '分佣配置更新完成！' as message;
SELECT * FROM `advn_config` WHERE `group` = 'invite' ORDER BY `sort`;
