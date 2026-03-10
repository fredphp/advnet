-- ============================================================================
-- 提现系统 - 数据库表结构
-- ============================================================================
-- 表前缀: advn_
-- 功能: 金币提现、审核、微信打款、风控
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 提现配置表 (advn_withdraw_config)
-- ============================================================================
DROP TABLE IF EXISTS `advn_withdraw_config`;
CREATE TABLE `advn_withdraw_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
    `code` VARCHAR(50) NOT NULL COMMENT '配置代码',
    `value` VARCHAR(500) DEFAULT NULL COMMENT '配置值',
    `type` VARCHAR(20) DEFAULT 'string' COMMENT '类型: string/number/switch/json',
    `title` VARCHAR(100) DEFAULT NULL COMMENT '配置标题',
    `remark` VARCHAR(200) DEFAULT NULL COMMENT '配置说明',
    `group` VARCHAR(50) DEFAULT 'basic' COMMENT '配置分组',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现配置表';

-- ============================================================================
-- 2. 提现订单表 (advn_withdraw_order) - 已存在，扩展字段
-- ============================================================================
-- 检查表是否存在，如不存在则创建
CREATE TABLE IF NOT EXISTS `advn_withdraw_order` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号(唯一)',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `coin_amount` DECIMAL(18,2) UNSIGNED NOT NULL COMMENT '提现金币数量',
    `exchange_rate` DECIMAL(10,4) UNSIGNED DEFAULT 10000.0000 COMMENT '兑换比例',
    `cash_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '提现金额(元)',
    `fee_amount` DECIMAL(10,4) UNSIGNED DEFAULT 0.0000 COMMENT '手续费(元)',
    `actual_amount` DECIMAL(10,4) UNSIGNED NOT NULL COMMENT '实际到账金额(元)',
    `withdraw_type` VARCHAR(20) NOT NULL COMMENT '提现方式: alipay/wechat/bank',
    `withdraw_account` VARCHAR(100) NOT NULL COMMENT '提现账号',
    `withdraw_name` VARCHAR(50) NOT NULL COMMENT '收款人姓名',
    `bank_name` VARCHAR(50) DEFAULT NULL COMMENT '银行名称(银行卡提现)',
    `bank_branch` VARCHAR(100) DEFAULT NULL COMMENT '开户行',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待审核, 1=审核通过, 2=打款中, 3=提现成功, 4=审核拒绝, 5=打款失败, 6=已取消',
    `audit_type` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核类型: 0=自动审核, 1=人工审核',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_admin_name` VARCHAR(50) DEFAULT NULL COMMENT '审核管理员名称',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_remark` VARCHAR(200) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '打款流水号',
    `transfer_time` INT UNSIGNED DEFAULT NULL COMMENT '打款时间',
    `transfer_result` TEXT DEFAULT NULL COMMENT '打款结果(JSON)',
    `fail_reason` VARCHAR(200) DEFAULT NULL COMMENT '失败原因',
    `retry_count` TINYINT UNSIGNED DEFAULT 0 COMMENT '重试次数',
    `next_retry_time` INT UNSIGNED DEFAULT NULL COMMENT '下次重试时间',
    `risk_score` TINYINT UNSIGNED DEFAULT 0 COMMENT '风控评分(0-100)',
    `risk_tags` VARCHAR(200) DEFAULT NULL COMMENT '风控标签(JSON)',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '申请IP',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户UA',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间戳',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间戳',
    `complete_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间戳',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_transfer_no` (`transfer_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现订单表';

-- ============================================================================
-- 3. 提现风控记录表 (advn_withdraw_risk_log)
-- ============================================================================
DROP TABLE IF EXISTS `advn_withdraw_risk_log`;
CREATE TABLE `advn_withdraw_risk_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) DEFAULT NULL COMMENT '提现订单号',
    `risk_type` VARCHAR(50) NOT NULL COMMENT '风险类型',
    `risk_level` TINYINT UNSIGNED DEFAULT 1 COMMENT '风险等级: 1=低, 2=中, 3=高',
    `risk_score` TINYINT UNSIGNED DEFAULT 0 COMMENT '风险评分(0-100)',
    `risk_detail` TEXT DEFAULT NULL COMMENT '风险详情(JSON)',
    `handle_action` VARCHAR(50) DEFAULT NULL COMMENT '处理动作: pass/review/reject/freeze',
    `handle_remark` VARCHAR(200) DEFAULT NULL COMMENT '处理备注',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_risk_type` (`risk_type`),
    KEY `idx_create_time` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现风控记录表';

-- ============================================================================
-- 4. 提现统计表 (advn_withdraw_stat)
-- ============================================================================
DROP TABLE IF EXISTS `advn_withdraw_stat`;
CREATE TABLE `advn_withdraw_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '统计ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `total_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '累计提现次数',
    `total_withdraw_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计提现金额(元)',
    `total_withdraw_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0.00 COMMENT '累计提现金币',
    `total_fee_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '累计手续费(元)',
    `success_count` INT UNSIGNED DEFAULT 0 COMMENT '成功次数',
    `fail_count` INT UNSIGNED DEFAULT 0 COMMENT '失败次数',
    `today_withdraw_count` INT UNSIGNED DEFAULT 0 COMMENT '今日提现次数',
    `today_withdraw_amount` DECIMAL(12,4) UNSIGNED DEFAULT 0.0000 COMMENT '今日提现金额',
    `today_withdraw_date` DATE DEFAULT NULL COMMENT '今日统计日期',
    `first_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '首次提现时间',
    `last_withdraw_time` INT UNSIGNED DEFAULT NULL COMMENT '最后提现时间',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='提现统计表';

-- ============================================================================
-- 5. 微信打款日志表 (advn_wechat_transfer_log)
-- ============================================================================
DROP TABLE IF EXISTS `advn_wechat_transfer_log`;
CREATE TABLE `advn_wechat_transfer_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '提现订单号',
    `transfer_no` VARCHAR(64) DEFAULT NULL COMMENT '微信转账单号',
    `partner_trade_no` VARCHAR(64) DEFAULT NULL COMMENT '商户订单号',
    `openid` VARCHAR(100) DEFAULT NULL COMMENT '收款用户openid',
    `amount` INT UNSIGNED DEFAULT 0 COMMENT '转账金额(分)',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '转账描述',
    `request_data` TEXT DEFAULT NULL COMMENT '请求数据(JSON)',
    `response_data` TEXT DEFAULT NULL COMMENT '响应数据(JSON)',
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=待处理, 1=处理中, 2=成功, 3=失败',
    `error_code` VARCHAR(50) DEFAULT NULL COMMENT '错误码',
    `error_msg` VARCHAR(200) DEFAULT NULL COMMENT '错误信息',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_transfer_no` (`transfer_no`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信打款日志表';

-- ============================================================================
-- 插入默认配置数据
-- ============================================================================
INSERT INTO `advn_withdraw_config` (`name`, `code`, `value`, `type`, `title`, `remark`, `group`, `sort`, `createtime`, `updatetime`) VALUES
('兑换比例', 'exchange_rate', '10000', 'number', '金币兑换比例', '多少金币等于1元', 'basic', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('最低提现', 'min_withdraw', '10000', 'number', '最低提现金币', '最低提现金币数量', 'basic', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('最高提现', 'max_withdraw', '1000000', 'number', '最高提现金币', '单次最高提现金币数量', 'basic', 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('每日提现次数', 'daily_withdraw_limit', '3', 'number', '每日提现次数限制', '每个用户每日提现次数上限', 'basic', 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('每日提现金额', 'daily_withdraw_amount', '100', 'number', '每日提现金额限制', '每个用户每日提现金额上限(元)', 'basic', 5, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('手续费率', 'fee_rate', '0', 'string', '手续费率', '提现手续费率(0表示免费)', 'basic', 6, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('自动审核金额', 'auto_audit_amount', '10', 'number', '自动审核金额', '低于此金额自动审核(元)', 'audit', 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('需人工审核金额', 'manual_audit_amount', '50', 'number', '需人工审核金额', '高于此金额需人工审核(元)', 'audit', 11, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('新用户提现限制', 'new_user_withdraw_days', '3', 'number', '新用户提现限制', '注册多少天后才能提现', 'audit', 12, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('风控拦截阈值', 'risk_reject_threshold', '80', 'number', '风控拦截阈值', '风控评分超过此值直接拒绝', 'risk', 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('风控人工阈值', 'risk_manual_threshold', '50', 'number', '风控人工阈值', '风控评分超过此值需人工审核', 'risk', 21, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('同IP提现限制', 'same_ip_limit', '5', 'number', '同IP提现限制', '同一IP每日提现次数上限', 'risk', 22, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('同设备提现限制', 'same_device_limit', '3', 'number', '同设备提现限制', '同一设备每日提现次数上限', 'risk', 23, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('打款重试次数', 'transfer_retry_count', '3', 'number', '打款重试次数', '打款失败后重试次数', 'transfer', 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('打款重试间隔', 'transfer_retry_interval', '300', 'number', '打款重试间隔', '打款重试间隔时间(秒)', 'transfer', 31, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- 插入后台菜单
-- ============================================================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'withdraw', '提现管理', 'fa fa-money', '', '', '提现管理', 1, NULL, '', 'txgl', 'tixianguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @parent_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'withdraw/order', '提现订单', 'fa fa-list', '', '', '', 1, NULL, '', 'txdd', 'tixiandingdan', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'withdraw/config', '提现配置', 'fa fa-cog', '', '', '', 1, NULL, '', 'txpz', 'tixianpeizhi', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'withdraw/risklog', '风控记录', 'fa fa-shield', '', '', '', 1, NULL, '', 'fkjl', 'fengkongjilu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'withdraw/stat', '提现统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'txtj', 'tixiantongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET FOREIGN_KEY_CHECKS = 1;
