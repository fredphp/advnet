-- ============================================================
-- 广告变现闭环系统 - 后台菜单 & 系统配置
-- 执行前请备份数据库
-- 执行顺序：先执行 add_ad_income_tables.sql，再执行本文件
-- ============================================================

-- ============================================================
-- 一、后台菜单
-- ============================================================

-- 1. 顶级菜单：广告管理 (weigh=52, 排在风控管理65和数据迁移60之间)
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'adincome', '广告管理', 'fa fa-bullhorn', '', '广告变现闭环系统管理', 1, 'addtabs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 52, 'normal');

SET @ad_menu_id = LAST_INSERT_ID();

-- 2. 子菜单：收益记录
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @ad_menu_id, 'adincome/log', '收益记录', 'fa fa-list-alt', 'adincome/log', '', '广告收益记录', 1, 'addtabs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 10, 'normal');
SET @ad_log_id = LAST_INSERT_ID();

-- 收益记录 - 权限规则
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `status`) VALUES
('file', @ad_log_id, 'adincome/log/index', '查看', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_log_id, 'adincome/log/detail', '详情', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_log_id, 'adincome/log/del', '删除', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_log_id, 'adincome/log/multi', '批量更新', 'fa fa-circle-o', 0, 'normal');

-- 3. 子菜单：广告红包
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @ad_menu_id, 'adincome/redpacket', '广告红包', 'fa fa-envelope', 'adincome/redpacket', '', '广告红包管理', 1, 'addtabs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 20, 'normal');
SET @ad_rp_id = LAST_INSERT_ID();

-- 广告红包 - 权限规则
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `status`) VALUES
('file', @ad_rp_id, 'adincome/redpacket/index', '查看', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_rp_id, 'adincome/redpacket/detail', '详情', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_rp_id, 'adincome/redpacket/del', '删除', 'fa fa-circle-o', 0, 'normal'),
('file', @ad_rp_id, 'adincome/redpacket/multi', '批量更新', 'fa fa-circle-o', 0, 'normal');

-- 4. 子菜单：广告统计
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @ad_menu_id, 'adincome/stat', '广告统计', 'fa fa-bar-chart', 'adincome/stat', '', '广告收益统计', 1, 'addtabs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 30, 'normal');
SET @ad_stat_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `status`) VALUES
('file', @ad_stat_id, 'adincome/stat/index', '查看', 'fa fa-circle-o', 0, 'normal');

-- ============================================================
-- 二、系统配置项
-- ============================================================

-- 2.1 在 configgroup 中添加广告配置分组
-- configgroup 的 value 是 JSON 格式，需要追加 "ad":"广告配置"
-- 方法1: 使用 JSON_SET (MySQL 5.7+)
-- 方法2: 手动在后台管理面板 → 常规管理 → 系统配置 → 配置分组 中添加

-- 尝试安全追加（如果 ad 分组尚不存在）
UPDATE `advn_config`
SET `value` = TRIM(TRAILING '}') || ',"ad":"广告配置"}'
WHERE `name` = 'configgroup'
  AND `type` = 'array'
  AND `value` NOT LIKE '%"ad"%';

-- 2.2 广告系统配置项
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES
('ad_income_enabled', 'ad', '启用广告变现', '是否启用广告收益功能', 'switch', '', '1', '', '', '', ''),
('platform_rate', 'ad', '平台抽成比例', '广告收益中平台抽取的比例，如0.30表示30%', 'number', '', '0.30', '', 'required|between:0,1', '', ''),
('settle_interval', 'ad', '红包结算间隔(分钟)', '每隔多少分钟将待释放余额结算为红包', 'number', '', '30', '', 'required|integer|gt:0', '', ''),
('min_redpacket_amount', 'ad', '最小红包金额(金币)', '待释放余额低于此值时不生成红包', 'number', '', '100', '', 'required|integer|gt:0', '', ''),
('redpacket_expire_hours', 'ad', '红包过期时间(小时)', '广告红包未被领取的过期时间', 'number', '', '48', '', 'required|integer|gt:0', '', ''),
('daily_reward_limit', 'ad', '每日广告收益上限(金币)', '单个用户每天通过广告可获得的最大金币数', 'number', '', '50000', '', 'required|integer|gt:0', '', ''),
('reward_per_feed', 'ad', '信息流广告奖励(金币)', '每次观看信息流广告获得的金币数(无真实金额回调时使用)', 'number', '', '50', '', 'required|integer|gt:0', '', ''),
('reward_per_video', 'ad', '激励视频奖励(金币)', '每次观看激励视频广告获得的金币数(无真实金额回调时使用)', 'number', '', '200', '', 'required|integer|gt:0', '', ''),
('callback_secret', 'ad', '回调签名密钥', '广告联盟回调验证密钥，留空则不验证', 'password', '', '', '', '', '', ''),
('enabled_providers', 'ad', '启用的广告平台', '启用的广告平台，逗号分隔: uniad=uni-ad, csj=穿山甲, ylh=优量汇', 'string', '', 'uniad', '', '', '', '');

-- ============================================================
-- 三、权限分配（给管理员组分配新菜单权限）
-- ============================================================
-- 如果需要给超级管理员(admin组)分配权限，执行以下语句
-- 需要先获取 admin 角色组的 ID 和 规则 ID

-- 给 admin 用户组 (id=1) 添加新菜单的权限
-- 先获取刚插入的所有规则 ID
SELECT GROUP_CONCAT(id) INTO @new_rule_ids
FROM `advn_auth_rule`
WHERE `name` LIKE 'adincome%'
  AND `id` > @ad_menu_id;

-- 如果 admin 组的 rules 字段包含已选中的规则，则追加
-- 注意：实际操作建议在后台管理面板 → 权限管理 → 菜单规则 中手动勾选
-- 或执行以下动态 SQL（请根据实际情况调整 pid）：

-- UPDATE `advn_auth_group_access`
-- SET `rules` = CONCAT(`rules`, ',', @new_rule_ids)
-- WHERE `uid` = 1
--   AND FIND_IN_SET(@ad_menu_id, `rules`) = 0;

-- ============================================================
-- 四、执行后操作
-- ============================================================
-- 1. 清除后台缓存：
--    rm -rf runtime/cache/*
--    rm -rf runtime/temp/*
-- 2. 在后台管理面板中：
--    a) 登录后台 → 权限管理 → 菜单规则 → 刷新
--    b) 给管理员组分配"广告管理"下的所有权限
--    c) 常规管理 → 系统配置 → 切换到"广告配置"标签 → 保存
-- 3. 访问后台左侧菜单应能看到"广告管理"入口
-- ============================================================
