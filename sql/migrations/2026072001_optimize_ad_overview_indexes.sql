-- ============================================================
-- 优化 ad/overview 接口性能 - 添加复合索引
-- 1. advn_ad_income_log: 覆盖 getTodayIncome() 的 (user_id, status, createtime) 查询
-- 2. 移除旧的冗余单列索引 idx_user_status（被新复合索引完全覆盖）
-- ============================================================

-- ad_income_log: 添加覆盖 getTodayIncome 的复合索引
ALTER TABLE `advn_ad_income_log`
    ADD INDEX `idx_user_status_createtime` (`user_id`, `status`, `createtime`);

-- ad_income_log: 添加覆盖 coin_account 按日统计的复合索引
ALTER TABLE `advn_ad_income_log`
    ADD INDEX `idx_createtime_status` (`createtime`, `status`);

-- coin_account: 确保 user_id 有唯一索引（用于 find 查询）
-- ALTER TABLE `advn_coin_account` ADD UNIQUE INDEX `uk_user_id` (`user_id`);
-- 注：user_id 通常是主键或已有唯一索引，请根据实际情况确认
