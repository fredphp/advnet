-- 红包任务资源表
-- 用于存储App、小程序、游戏、视频等资源信息

CREATE TABLE IF NOT EXISTS `advn_red_packet_resource` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '资源ID',
    `type` VARCHAR(30) NOT NULL COMMENT '资源类型: app=App下载, mini_program=小程序, game=游戏, video=视频, link=分享链接',
    `name` VARCHAR(100) NOT NULL COMMENT '资源名称',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '资源描述',
    `logo` VARCHAR(500) DEFAULT NULL COMMENT '资源图标/封面',
    `images` TEXT DEFAULT NULL COMMENT '宣传图片(JSON数组)',
    `url` VARCHAR(500) DEFAULT NULL COMMENT '跳转链接',
    `package_name` VARCHAR(100) DEFAULT NULL COMMENT 'App包名',
    `app_id` VARCHAR(100) DEFAULT NULL COMMENT '小程序AppID/游戏AppID',
    `video_id` INT UNSIGNED DEFAULT NULL COMMENT '视频ID',
    `params` TEXT DEFAULT NULL COMMENT '扩展参数(JSON)',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_type` (`type`),
    KEY `idx_status` (`status`),
    KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包任务资源表';

-- 插入示例数据
INSERT INTO `advn_red_packet_resource` (`type`, `name`, `description`, `logo`, `url`, `package_name`, `app_id`, `status`, `createtime`) VALUES
('app', '抖音', '记录美好生活', '/uploads/app/douyin.png', 'https://www.douyin.com', 'com.ss.android.ugc.aweme', NULL, 1, UNIX_TIMESTAMP()),
('app', '快手', '拥抱每一种生活', '/uploads/app/kuaishou.png', 'https://www.kuaishou.com', 'com.smile.gifmaker', NULL, 1, UNIX_TIMESTAMP()),
('mini_program', '拼多多小程序', '拼着买更便宜', '/uploads/mini/pdd.png', NULL, NULL, 'wxaa4e50a071e4de34', 1, UNIX_TIMESTAMP()),
('mini_program', '京东小程序', '多快好省', '/uploads/mini/jd.png', NULL, NULL, 'wx91d27f914b0e3345', 1, UNIX_TIMESTAMP()),
('game', '消消乐', '休闲益智游戏', '/uploads/game/xiaoxiaole.png', NULL, NULL, 'game_001', 1, UNIX_TIMESTAMP()),
('game', '跑酷达人', '跑酷竞技游戏', '/uploads/game/paoku.png', NULL, NULL, 'game_002', 1, UNIX_TIMESTAMP()),
('video', '搞笑视频合集', '精选搞笑短视频', '/uploads/video/funny.png', NULL, NULL, NULL, 1, UNIX_TIMESTAMP()),
('link', '每日签到', '每日签到领金币', '/uploads/link/signin.png', '/pages/signin/index', NULL, NULL, 1, UNIX_TIMESTAMP());
