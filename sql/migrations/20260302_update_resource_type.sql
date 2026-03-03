-- 红包资源类型迁移SQL
-- 将旧的资源类型更新为与任务类型一致的类型名称
-- 执行前请先备份数据

-- 更新资源类型字段值
UPDATE `advn_red_packet_resource` SET `type` = 'download_app' WHERE `type` = 'app';
UPDATE `advn_red_packet_resource` SET `type` = 'play_game' WHERE `type` = 'game';
UPDATE `advn_red_packet_resource` SET `type` = 'watch_video' WHERE `type` = 'video';
UPDATE `advn_red_packet_resource` SET `type` = 'share_link' WHERE `type` = 'link';
-- mini_program 保持不变

-- 修改type字段的ENUM定义
ALTER TABLE `advn_red_packet_resource` 
MODIFY COLUMN `type` enum('download_app','mini_program','play_game','watch_video','share_link','sign_in') 
NOT NULL DEFAULT 'download_app' COMMENT '资源类型: download_app=下载App, mini_program=小程序, play_game=玩游戏, watch_video=观看视频, share_link=分享链接, sign_in=签到任务';

-- 如果之前有测试数据，可以删除并重新插入
-- DELETE FROM `advn_red_packet_resource`;

-- 插入新的测试数据（可选）
INSERT INTO `advn_red_packet_resource` (`type`, `name`, `description`, `logo`, `url`, `package_name`, `app_id`, `status`, `sort`, `createtime`) VALUES
('download_app', '抖音', '记录美好生活', '/uploads/app/douyin.png', 'https://www.douyin.com', 'com.ss.android.ugc.aweme', NULL, 1, 100, UNIX_TIMESTAMP()),
('download_app', '快手', '拥抱每一种生活', '/uploads/app/kuaishou.png', 'https://www.kuaishou.com', 'com.smile.gifmaker', NULL, 1, 90, UNIX_TIMESTAMP()),
('mini_program', '拼多多小程序', '拼着买更便宜', '/uploads/mini/pdd.png', NULL, NULL, 'wxaa4e50a071e4de34', 1, 100, UNIX_TIMESTAMP()),
('mini_program', '京东小程序', '多快好省', '/uploads/mini/jd.png', NULL, NULL, 'wx91d27f914b0e3345', 1, 90, UNIX_TIMESTAMP()),
('play_game', '消消乐', '休闲益智游戏', '/uploads/game/xiaoxiaole.png', NULL, NULL, 'game_001', 1, 100, UNIX_TIMESTAMP()),
('play_game', '跑酷达人', '跑酷竞技游戏', '/uploads/game/paoku.png', NULL, NULL, 'game_002', 1, 90, UNIX_TIMESTAMP()),
('watch_video', '搞笑视频合集', '精选搞笑短视频', '/uploads/video/funny.png', NULL, NULL, NULL, 1, 100, UNIX_TIMESTAMP()),
('share_link', '每日签到', '每日签到领金币', '/uploads/link/signin.png', '/pages/signin/index', NULL, NULL, 1, 100, UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `updatetime` = UNIX_TIMESTAMP();
