-- ============================================================================
-- 数据迁移配置 - 写入 advn_config 配置表
-- ============================================================================
-- 说明：
-- 1. 将数据迁移相关配置写入系统配置表
-- 2. 可通过后台管理系统动态修改配置
-- 3. 修改配置后需清除缓存生效
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. 更新配置分组（添加 migration 分组）
-- ============================================================================
UPDATE `advn_config` 
SET `value` = JSON_SET(
    `value`,
    '$.migration', '数据迁移'
)
WHERE `name` = 'configgroup' AND `group` = 'dictionary';

-- 如果上面的JSON_SET不兼容，使用以下方式
-- 先查询当前值，然后替换
-- UPDATE `advn_config` 
-- SET `value` = '{"basic":"基础配置","email":"邮件配置","dictionary":"字典配置","user":"用户配置","example":"示例配置","migration":"数据迁移"}'
-- WHERE `name` = 'configgroup' AND `group` = 'dictionary';

-- ============================================================================
-- 2. 插入数据迁移配置项
-- ============================================================================
INSERT INTO `advn_config` (`name`, `group`, `title`, `tip`, `type`, `visible`, `value`, `content`, `rule`, `extend`, `setting`) VALUES

-- ==================== 基础配置 ====================
('migration_enabled', 'migration', '启用数据迁移', '是否启用数据迁移功能', 'switch', '', '1', '', '', '', ''),
('migration_batch_size', 'migration', '批量处理数量', '每批次处理的数据量，默认1000条', 'number', '', '1000', '', 'required|integer|>=:100|<=:10000', '', ''),
('migration_auto_archive', 'migration', '自动归档', '是否启用定时自动归档', 'switch', '', '0', '', '', '', ''),
('migration_schedule', 'migration', '归档计划', 'Cron表达式，如: 0 3 * * * (每天凌晨3点)', 'string', '', '0 3 * * *', '', '', '', ''),
('migration_delete_source', 'migration', '删除源数据', '迁移后是否删除源数据（谨慎开启）', 'switch', '', '0', '', '', '', ''),
('migration_log_retention', 'migration', '日志保留天数', '迁移日志保留天数', 'number', '', '365', '', 'required|integer|>=:30|<=:1095', '', ''),

-- ==================== 各表迁移天数配置 ====================
('migration_coin_log_days', 'migration', '金币流水归档天数', '金币流水数据归档天数（超过此天数的数据将被归档）', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_watch_record_days', 'migration', '观看记录归档天数', '视频观看记录归档天数', 'number', '', '180', '', 'required|integer|>=:60|<=:365', '', ''),
('migration_watch_session_days', 'migration', '观看会话归档天数', '观看会话记录归档天数', 'number', '', '30', '', 'required|integer|>=:7|<=:90', '', ''),
('migration_risk_log_days', 'migration', '风控日志归档天数', '风控日志归档天数', 'number', '', '180', '', 'required|integer|>=:60|<=:365', '', ''),
('migration_user_behavior_days', 'migration', '用户行为归档天数', '用户行为记录归档天数', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_anticheat_days', 'migration', '防刷日志归档天数', '防刷日志归档天数', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),
('migration_red_packet_days', 'migration', '红包记录归档天数', '红包领取记录归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_commission_days', 'migration', '分佣日志归档天数', '邀请分佣日志归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_transfer_days', 'migration', '打款日志归档天数', '微信打款日志归档天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),

-- ==================== 清理配置 ====================
('migration_daily_stats_keep', 'migration', '每日统计保留天数', '用户每日收益统计保留天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_behavior_stats_keep', 'migration', '行为统计保留天数', '用户行为统计保留天数', 'number', '', '365', '', 'required|integer|>=:90|<=:730', '', ''),
('migration_inactive_days', 'migration', '未活跃用户天数', '超过此天数未登录的用户标记为未活跃', 'number', '', '90', '', 'required|integer|>=:30|<=:365', '', ''),

-- ==================== 性能配置 ====================
('migration_sleep_ms', 'migration', '批次间隔(毫秒)', '每批次处理后的休眠时间，避免锁表', 'number', '', '10', '', 'required|integer|>=:0|<=:1000', '', ''),
('migration_transaction', 'migration', '启用事务', '每批次是否使用事务处理', 'switch', '', '1', '', '', '', ''),
('migration_max_runtime', 'migration', '最大运行时间(秒)', '单次迁移最大运行时间，0表示不限制', 'number', '', '3600', '', 'required|integer|>=:0|<=:86400', '', ''),

-- ==================== 通知配置 ====================
('migration_notify_enabled', 'migration', '启用通知', '迁移完成后是否发送通知', 'switch', '', '0', '', '', '', ''),
('migration_notify_email', 'migration', '通知邮箱', '接收通知的邮箱地址', 'string', '', '', '', '', '', ''),
('migration_notify_webhook', 'migration', '通知Webhook', '接收通知的Webhook地址', 'string', '', '', '', '', '', '');

-- ============================================================================
-- 3. 插入后台菜单
-- ============================================================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'migration', '数据迁移', 'fa fa-database', '', '', '数据迁移管理', 1, NULL, '', 'sjqy', 'shujuqianyi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @parent_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'migration/config', '迁移配置', 'fa fa-cog', '', '', '', 1, NULL, '', 'qypz', 'qianyipeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'migration/log', '迁移日志', 'fa fa-list', '', '', '', 1, NULL, '', 'qyrz', 'qianyirizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'migration/stats', '数据统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'sjtj', 'shujutongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'migration/execute', '执行迁移', 'fa fa-play', '', '', '', 1, NULL, '', 'zxqy', 'zhixingqianyi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

-- ============================================================================
-- 4. 插入迁移配置表初始数据（替代之前的 advn_data_migration_config 表）
-- ============================================================================
-- 注意：这里使用 advn_config 表存储配置，不再需要单独的配置表
-- 如果需要更复杂的配置管理，可以使用下面的SQL创建单独的配置表

-- ============================================================================
-- 使用说明
-- ============================================================================
-- 1. 在后台管理系统中，进入"常规管理 > 系统配置 > 数据迁移"即可修改配置
-- 2. 修改配置后，执行以下命令清除缓存：
--    php think clear
-- 3. 使用命令行执行迁移：
--    php think data:migrate --action=stats
--    php think data:migrate --action=all
-- ============================================================================
