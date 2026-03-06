-- ============================================================================
-- 分表迁移脚本
-- 创建时间：2026-03-06
-- 功能：提现订单、红包任务、领取记录按月分表
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. 提现订单分表（按月）
-- 表名格式：advn_withdraw_order_YYYYMM
-- ============================================================================

-- 创建当月分表
CREATE TABLE IF NOT EXISTS `advn_withdraw_order_202603` LIKE `advn_withdraw_order`;

-- ============================================================================
-- 2. 红包任务分表（按月）
-- 表名格式：advn_red_packet_task_YYYYMM
-- ============================================================================

-- 创建当月分表
CREATE TABLE IF NOT EXISTS `advn_red_packet_task_202603` LIKE `advn_red_packet_task`;

-- ============================================================================
-- 3. 用户红包领取记录分表（按月）
-- 表名格式：advn_user_red_packet_accumulate_YYYYMM
-- ============================================================================

-- 创建当月分表
CREATE TABLE IF NOT EXISTS `advn_user_red_packet_accumulate_202603` LIKE `advn_user_red_packet_accumulate`;

-- ============================================================================
-- 4. 创建统计汇总表（可选，用于快速查询统计数据）
-- ============================================================================

-- 每日提现统计表
CREATE TABLE IF NOT EXISTS `advn_withdraw_daily_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `total_count` INT UNSIGNED DEFAULT 0 COMMENT '总订单数',
    `total_coin` DECIMAL(18,2) DEFAULT 0 COMMENT '总金币',
    `total_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '总金额',
    `success_count` INT UNSIGNED DEFAULT 0 COMMENT '成功订单数',
    `success_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '成功金额',
    `pending_count` INT UNSIGNED DEFAULT 0 COMMENT '待审核数',
    `pending_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '待审核金额',
    `alipay_count` INT UNSIGNED DEFAULT 0 COMMENT '支付宝提现数',
    `alipay_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '支付宝提现金额',
    `wechat_count` INT UNSIGNED DEFAULT 0 COMMENT '微信提现数',
    `wechat_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '微信提现金额',
    `bank_count` INT UNSIGNED DEFAULT 0 COMMENT '银行卡提现数',
    `bank_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '银行卡提现金额',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日提现统计表';

-- 每日红包统计表
CREATE TABLE IF NOT EXISTS `advn_redpacket_daily_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `task_count` INT UNSIGNED DEFAULT 0 COMMENT '任务创建数',
    `task_pushed_count` INT UNSIGNED DEFAULT 0 COMMENT '任务推送数',
    `task_finished_count` INT UNSIGNED DEFAULT 0 COMMENT '任务完成数',
    `participation_count` INT UNSIGNED DEFAULT 0 COMMENT '参与人数',
    `participation_amount` DECIMAL(18,2) DEFAULT 0 COMMENT '参与金额',
    `collected_count` INT UNSIGNED DEFAULT 0 COMMENT '领取人数',
    `collected_amount` DECIMAL(18,2) DEFAULT 0 COMMENT '领取金额',
    `new_user_count` INT UNSIGNED DEFAULT 0 COMMENT '新用户数',
    `new_user_amount` DECIMAL(18,2) DEFAULT 0 COMMENT '新用户金额',
    `old_user_count` INT UNSIGNED DEFAULT 0 COMMENT '老用户数',
    `old_user_amount` DECIMAL(18,2) DEFAULT 0 COMMENT '老用户金额',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每日红包统计表';

-- 每月提现统计表
CREATE TABLE IF NOT EXISTS `advn_withdraw_monthly_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stat_month` VARCHAR(6) NOT NULL COMMENT '统计月份 YYYYMM',
    `total_count` INT UNSIGNED DEFAULT 0 COMMENT '总订单数',
    `total_coin` DECIMAL(18,2) DEFAULT 0 COMMENT '总金币',
    `total_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '总金额',
    `success_count` INT UNSIGNED DEFAULT 0 COMMENT '成功订单数',
    `success_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '成功金额',
    `user_count` INT UNSIGNED DEFAULT 0 COMMENT '提现用户数',
    `avg_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '平均提现金额',
    `max_amount` DECIMAL(12,4) DEFAULT 0 COMMENT '最大提现金额',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stat_month` (`stat_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='每月提现统计表';

-- ============================================================================
-- 5. 添加后台菜单
-- ============================================================================

-- 添加数据统计菜单
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', id, 'withdraw/statistics', '提现统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'txtj', 'tixiantongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM `advn_auth_rule` WHERE `name` = 'withdraw' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
