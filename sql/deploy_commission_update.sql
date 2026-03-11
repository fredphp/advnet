-- =============================================
-- 分佣系统简化部署脚本
-- 只保留提现分佣，配置从 advn_invite_commission_config 移到 advn_config
-- =============================================

-- 1. 删除 commissionconfig 菜单
DELETE FROM `advn_auth_rule` WHERE `name` = 'invite/commissionconfig';

-- 2. 添加/更新分佣配置到 advn_config 表
-- 先删除旧配置（如果存在）
DELETE FROM `advn_config` WHERE `name` LIKE '%commission%' OR (`group` = 'invite' AND `name` LIKE '%invite%');

-- 插入新配置
INSERT INTO `advn_config` (`group`, `name`, `value`, `type`, `title`, `remark`, `status`, `sort`, `createtime`, `updatetime`) VALUES
('invite', 'commission_enabled', '1', 'switch', '分佣开关', '是否开启邀请分佣（仅提现分佣）', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level1_commission_rate', '0.10', 'number', '一级分佣比例', '直接邀请的分佣比例（如0.10表示10%）', 1, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'level2_commission_rate', '0.05', 'number', '二级分佣比例', '间接邀请的分佣比例（如0.05表示5%）', 1, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_min_amount', '0', 'number', '最低分佣金额', '触发分佣的最低提现金额（元）', 1, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'commission_max_amount', '0', 'number', '最高分佣金额', '单次分佣最高金额（0表示不限）', 1, 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_register_reward', '100', 'number', '注册奖励金币', '新用户注册奖励金币数量', 1, 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('invite', 'invite_register_reward_parent', '50', 'number', '邀请注册奖励', '邀请新用户注册给邀请人的奖励金币', 1, 7, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 3. 删除视频、红包、游戏分佣配置（如果存在）
DELETE FROM `advn_config` WHERE `name` IN (
    'level1_watch_commission',
    'level2_watch_commission', 
    'level1_red_packet_commission',
    'level2_red_packet_commission',
    'level1_game_commission',
    'level2_game_commission',
    'video_commission_enabled',
    'red_packet_commission_enabled',
    'game_commission_enabled'
);

-- 4. 更新已存在的分佣记录，只保留提现分佣
-- 将非提现分佣记录标记为已取消
UPDATE `advn_invite_commission_log` 
SET `status` = 3, `remark` = CONCAT(IFNULL(`remark`, ''), '[系统取消-非提现分佣已禁用]')
WHERE `source_type` != 'withdraw' AND `status` = 0;

-- 5. 显示结果
SELECT '部署完成！' as message;
SELECT '当前分佣配置：' as info;
SELECT `name`, `value`, `title`, `remark` FROM `advn_config` WHERE `group` = 'invite' ORDER BY `sort`;
