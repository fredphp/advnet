-- ============================================================
-- 清理不合规的广告红包记录
-- 背景：settleToRedPacket() 定时任务缺少 redpacket_threshold 检查，
--       可能导致 ad_freeze_balance 未达到红包基数额度时就生成了红包。
--       此脚本将未领取的红包金额退回到 ad_freeze_balance。
-- 执行前请备份数据库
-- ============================================================

-- 1. 查看当前有多少未领取的广告红包（执行前先查看）
SELECT COUNT(*) AS unclaimed_count,
       IFNULL(SUM(amount), 0) AS unclaimed_total_amount
FROM `advn_ad_red_packet`
WHERE `status` = 0;

-- 2. 查看每个用户的未领取红包详情
SELECT p.user_id, u.username, p.id AS packet_id, p.amount, p.createtime, p.expire_time
FROM `advn_ad_red_packet` p
LEFT JOIN `advn_user` u ON u.id = p.user_id
WHERE p.`status` = 0
ORDER BY p.createtime DESC;

-- ============================================================
-- ★ 以下为修复操作，请确认上面查询结果后再执行 ★
-- ============================================================

-- 3. 将未领取红包金额退回到用户的 ad_freeze_balance
-- 使用 CASE WHEN 防止 amount 为 NULL
UPDATE `advn_coin_account` ca
INNER JOIN (
    SELECT user_id, IFNULL(SUM(amount), 0) AS total_refund
    FROM `advn_ad_red_packet`
    WHERE `status` = 0
    GROUP BY user_id
) refunds ON ca.user_id = refunds.user_id
SET ca.ad_freeze_balance = ca.ad_freeze_balance + refunds.total_refund,
    ca.updatetime = UNIX_TIMESTAMP();

-- 4. 将未领取红包标记为已过期（保留记录，不物理删除）
UPDATE `advn_ad_red_packet`
SET `status` = 2,
    updatetime = UNIX_TIMESTAMP()
WHERE `status` = 0;

-- ============================================================
-- 执行后操作：
-- 1. 清除后台缓存：rm -rf runtime/cache/*
-- 2. 验证结果：再次执行第1步查询，unclaimed_count 应为 0
-- 3. 前端刷新页面，红包标识应消失
-- ============================================================
