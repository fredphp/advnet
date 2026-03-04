-- ============================================================================
-- 红包任务系统 - 数据库表结构
-- ============================================================================
-- 表前缀: advn_
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. 红包任务表 (扩展原表)
-- ============================================================================
DROP TABLE IF EXISTS `advn_red_packet_task`;
CREATE TABLE `advn_red_packet_task` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '红包ID',
    
    -- 基本信息
    `name` VARCHAR(100) NOT NULL COMMENT '红包名称',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '红包描述',
    `icon` VARCHAR(500) DEFAULT NULL COMMENT '红包图标',
    `images` TEXT DEFAULT NULL COMMENT '任务宣传图(JSON数组)',
    
    -- 任务类型
    `task_type` VARCHAR(30) NOT NULL COMMENT '任务类型: download_app=下载App, mini_program=跳转小程序, play_game=玩游戏时长, watch_video=观看视频, share_link=分享链接, sign_in=签到',
    `task_url` VARCHAR(500) DEFAULT NULL COMMENT '任务跳转链接',
    `task_params` TEXT DEFAULT NULL COMMENT '任务参数(JSON): {app_id, package_name, mini_app_id, game_id, video_id等}',
    
    -- 金额设置
    `total_amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包总金额(金币)',
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包总数量',
    `remain_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '剩余金额',
    `remain_count` INT UNSIGNED DEFAULT 0 COMMENT '剩余数量',
    `single_amount` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '单个红包金额(固定)',
    `min_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最小金额(随机)',
    `max_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '最大金额(随机)',
    `amount_type` VARCHAR(20) DEFAULT 'fixed' COMMENT '金额类型: fixed=固定, random=随机',
    
    -- 任务要求
    `required_duration` INT UNSIGNED DEFAULT 0 COMMENT '要求时长(秒), 玩游戏/看视频类必填',
    `required_progress` TINYINT UNSIGNED DEFAULT 100 COMMENT '要求进度(%), 下载类使用',
    `required_count` INT UNSIGNED DEFAULT 1 COMMENT '要求次数, 分享类使用',
    `verify_method` VARCHAR(30) DEFAULT 'auto' COMMENT '验证方式: auto=自动, manual=人工, third_party=第三方',
    `verify_params` TEXT DEFAULT NULL COMMENT '验证参数(JSON): {api_url, secret_key等}',
    
    -- 领取限制
    `user_limit` INT UNSIGNED DEFAULT 1 COMMENT '每人可领取次数',
    `daily_limit` INT UNSIGNED DEFAULT 0 COMMENT '每日限制领取次数(0=不限)',
    `new_user_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限新用户: 0=否, 1=是',
    `new_user_days` INT UNSIGNED DEFAULT 7 COMMENT '新用户定义(注册天数)',
    `user_level_min` TINYINT UNSIGNED DEFAULT 1 COMMENT '最低用户等级',
    `user_level_max` TINYINT UNSIGNED DEFAULT 255 COMMENT '最高用户等级',
    `vip_only` TINYINT UNSIGNED DEFAULT 0 COMMENT '仅限VIP: 0=否, 1=是',
    
    -- 审核设置
    `audit_type` VARCHAR(20) DEFAULT 'auto' COMMENT '审核方式: auto=自动, manual=人工',
    `audit_timeout` INT UNSIGNED DEFAULT 86400 COMMENT '审核超时时间(秒), 超时自动通过',
    `need_screenshot` TINYINT UNSIGNED DEFAULT 0 COMMENT '需要上传截图: 0=否, 1=是',
    `need_device_info` TINYINT UNSIGNED DEFAULT 1 COMMENT '需要设备信息: 0=否, 1=是',
    
    -- 有效时间
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '结束时间',
    `expire_hours` INT UNSIGNED DEFAULT 24 COMMENT '领取后有效期(小时)',
    
    -- 关联信息
    `relation_type` VARCHAR(30) DEFAULT NULL COMMENT '关联类型: app/game/video/activity',
    `relation_id` INT UNSIGNED DEFAULT NULL COMMENT '关联ID',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '任务分类ID',
    
    -- 统计
    `view_count` INT UNSIGNED DEFAULT 0 COMMENT '浏览次数',
    `join_count` INT UNSIGNED DEFAULT 0 COMMENT '参与次数',
    `complete_count` INT UNSIGNED DEFAULT 0 COMMENT '完成次数',
    `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '已领取人数',
    `receive_amount` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '已发放金额',
    `audit_pending_count` INT UNSIGNED DEFAULT 0 COMMENT '待审核数量',
    `audit_reject_count` INT UNSIGNED DEFAULT 0 COMMENT '已拒绝数量',
    
    -- 排序和状态
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `is_hot` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否热门: 0=否, 1=是',
    `is_recommend` TINYINT UNSIGNED DEFAULT 0 COMMENT '是否推荐: 0=否, 1=是',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用, 2=已结束, 3=已抢完',
    
    -- 时间戳
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    
    PRIMARY KEY (`id`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_status` (`status`),
    KEY `idx_time_range` (`start_time`, `end_time`),
    KEY `idx_category` (`category_id`),
    KEY `idx_is_hot` (`is_hot`),
    KEY `idx_is_recommend` (`is_recommend`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='红包任务表';

-- ============================================================================
-- 2. 任务参与记录表
-- ============================================================================
DROP TABLE IF EXISTS `advn_task_participation`;
CREATE TABLE `advn_task_participation` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    
    -- 基本信息
    `task_id` INT UNSIGNED NOT NULL COMMENT '红包任务ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `order_no` VARCHAR(32) NOT NULL COMMENT '参与订单号',
    
    -- 任务信息快照
    `task_type` VARCHAR(30) NOT NULL COMMENT '任务类型',
    `task_name` VARCHAR(100) DEFAULT NULL COMMENT '任务名称快照',
    `task_url` VARCHAR(500) DEFAULT NULL COMMENT '任务链接快照',
    
    -- 状态
    `status` TINYINT UNSIGNED DEFAULT 0 COMMENT '状态: 0=已领取待完成, 1=已完成待审核, 2=审核通过待发放, 3=已发放, 4=审核拒绝, 5=已过期, 6=已取消',
    
    -- 任务进度
    `start_time` INT UNSIGNED DEFAULT NULL COMMENT '开始时间',
    `end_time` INT UNSIGNED DEFAULT NULL COMMENT '完成时间',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '实际耗时(秒)',
    `progress` TINYINT UNSIGNED DEFAULT 0 COMMENT '完成进度(%)',
    
    -- 审核信息
    `audit_type` VARCHAR(20) DEFAULT 'auto' COMMENT '审核方式',
    `audit_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '审核状态: 0=待审核, 1=通过, 2=拒绝',
    `audit_time` INT UNSIGNED DEFAULT NULL COMMENT '审核时间',
    `audit_admin_id` INT UNSIGNED DEFAULT NULL COMMENT '审核管理员ID',
    `audit_admin_name` VARCHAR(50) DEFAULT NULL COMMENT '审核管理员名称',
    `audit_remark` VARCHAR(500) DEFAULT NULL COMMENT '审核备注',
    `reject_reason` VARCHAR(200) DEFAULT NULL COMMENT '拒绝原因',
    
    -- 奖励信息
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '奖励金币',
    `reward_status` TINYINT UNSIGNED DEFAULT 0 COMMENT '发放状态: 0=未发放, 1=已发放',
    `reward_time` INT UNSIGNED DEFAULT NULL COMMENT '发放时间',
    
    -- 设备信息
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID',
    `device_info` TEXT DEFAULT NULL COMMENT '设备信息(JSON)',
    `app_version` VARCHAR(20) DEFAULT NULL COMMENT 'APP版本',
    `platform` VARCHAR(20) DEFAULT NULL COMMENT '平台: ios/android/h5',
    
    -- 证明材料
    `screenshot_urls` TEXT DEFAULT NULL COMMENT '截图URLs(JSON数组)',
    `proof_data` TEXT DEFAULT NULL COMMENT '证明数据(JSON): 游戏时长、下载完成证明等',
    `extra_data` TEXT DEFAULT NULL COMMENT '额外数据(JSON)',
    
    -- 时间戳
    `createtime` INT UNSIGNED DEFAULT NULL COMMENT '创建时间',
    `updatetime` INT UNSIGNED DEFAULT NULL COMMENT '更新时间',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_no` (`order_no`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_user_task` (`user_id`, `task_id`),
    KEY `idx_audit_status` (`audit_status`),
    KEY `idx_create_time` (`createtime`),
    KEY `idx_device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务参与记录表';

-- ============================================================================
-- 3. 任务分类表
-- ============================================================================
DROP TABLE IF EXISTS `advn_task_category`;
CREATE TABLE `advn_task_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `icon` VARCHAR(200) DEFAULT NULL COMMENT '分类图标',
    `description` VARCHAR(200) DEFAULT NULL COMMENT '分类描述',
    `sort` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `status` TINYINT UNSIGNED DEFAULT 1 COMMENT '状态: 0=禁用, 1=启用',
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务分类表';

-- ============================================================================
-- 4. 任务设备记录表 (防作弊)
-- ============================================================================
DROP TABLE IF EXISTS `advn_task_device_log`;
CREATE TABLE `advn_task_device_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `device_id` VARCHAR(100) NOT NULL COMMENT '设备ID',
    `device_name` VARCHAR(100) DEFAULT NULL COMMENT '设备名称',
    `device_brand` VARCHAR(50) DEFAULT NULL COMMENT '设备品牌',
    `device_model` VARCHAR(50) DEFAULT NULL COMMENT '设备型号',
    `os_version` VARCHAR(20) DEFAULT NULL COMMENT '系统版本',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
    `ip_location` VARCHAR(100) DEFAULT NULL COMMENT 'IP归属地',
    `network_type` VARCHAR(20) DEFAULT NULL COMMENT '网络类型',
    `participation_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '参与记录ID',
    `action` VARCHAR(30) DEFAULT NULL COMMENT '动作: receive/complete/submit',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task_device` (`task_id`, `device_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_device_id` (`device_id`),
    KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务设备记录表';

-- ============================================================================
-- 5. 任务审核日志表
-- ============================================================================
DROP TABLE IF EXISTS `advn_task_audit_log`;
CREATE TABLE `advn_task_audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `task_id` INT UNSIGNED NOT NULL COMMENT '任务ID',
    `participation_id` BIGINT UNSIGNED NOT NULL COMMENT '参与记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `action` VARCHAR(30) NOT NULL COMMENT '操作: submit/audit_pass/audit_reject/reward',
    `old_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '原状态',
    `new_status` TINYINT UNSIGNED DEFAULT NULL COMMENT '新状态',
    `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
    `operator_type` VARCHAR(20) DEFAULT NULL COMMENT '操作者类型: system/admin/user',
    `operator_id` INT UNSIGNED DEFAULT NULL COMMENT '操作者ID',
    `createtime` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_participation_id` (`participation_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务审核日志表';

-- ============================================================================
-- 6. 用户任务统计表
-- ============================================================================
DROP TABLE IF EXISTS `advn_user_task_stat`;
CREATE TABLE `advn_user_task_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `date_key` DATE NOT NULL COMMENT '日期',
    
    -- 统计
    `receive_count` INT UNSIGNED DEFAULT 0 COMMENT '领取次数',
    `complete_count` INT UNSIGNED DEFAULT 0 COMMENT '完成次数',
    `reward_count` INT UNSIGNED DEFAULT 0 COMMENT '奖励次数',
    `reward_coin` DECIMAL(18,2) UNSIGNED DEFAULT 0 COMMENT '获得金币',
    `reject_count` INT UNSIGNED DEFAULT 0 COMMENT '被拒绝次数',
    
    -- 各类型统计
    `download_count` INT UNSIGNED DEFAULT 0 COMMENT '下载任务数',
    `mini_program_count` INT UNSIGNED DEFAULT 0 COMMENT '小程序任务数',
    `game_count` INT UNSIGNED DEFAULT 0 COMMENT '游戏任务数',
    `video_count` INT UNSIGNED DEFAULT 0 COMMENT '视频任务数',
    `share_count` INT UNSIGNED DEFAULT 0 COMMENT '分享任务数',
    
    `createtime` INT UNSIGNED DEFAULT NULL,
    `updatetime` INT UNSIGNED DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`user_id`, `date_key`),
    KEY `idx_date_key` (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户任务统计表';

-- ============================================================================
-- 插入默认分类
-- ============================================================================
INSERT INTO `advn_task_category` (`name`, `icon`, `description`, `sort`, `status`, `createtime`) VALUES
('下载任务', 'fa fa-download', '下载指定App获得奖励', 1, 1, UNIX_TIMESTAMP()),
('小程序任务', 'fa fa-rocket', '跳转小程序完成任务', 2, 1, UNIX_TIMESTAMP()),
('游戏任务', 'fa fa-gamepad', '玩游戏达到指定时长', 3, 1, UNIX_TIMESTAMP()),
('视频任务', 'fa fa-video-camera', '观看视频获得奖励', 4, 1, UNIX_TIMESTAMP()),
('分享任务', 'fa fa-share-alt', '分享链接获得奖励', 5, 1, UNIX_TIMESTAMP()),
('签到任务', 'fa fa-calendar-check-o', '每日签到获得奖励', 6, 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 插入示例任务
-- ============================================================================
INSERT INTO `advn_red_packet_task` (`name`, `description`, `task_type`, `task_url`, `task_params`, `total_amount`, `total_count`, `single_amount`, `required_duration`, `verify_method`, `user_limit`, `audit_type`, `start_time`, `end_time`, `status`, `createtime`) VALUES
('下载抖音极速版', '下载并安装抖音极速版App，打开运行30秒即可获得奖励', 'download_app', 'https://example.com/download/douyin', '{"package_name":"com.ss.android.ugc.aweme.lite","app_name":"抖音极速版"}', 100000.00, 100, 1000.00, 30, 'auto', 1, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 7, 1, UNIX_TIMESTAMP()),
('跳转拼多多小程序', '跳转拼多多小程序浏览商品30秒', 'mini_program', '', '{"mini_app_id":"wxapp_pdd","mini_app_name":"拼多多"}', 50000.00, 200, 250.00, 30, 'auto', 1, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 3, 1, UNIX_TIMESTAMP()),
('玩游戏领红包', '玩游戏达到指定时长(5分钟)即可领取红包', 'play_game', '', '{"game_id":1,"game_name":"消消乐"}', 500000.00, 1000, 500.00, 300, 'auto', 3, 'auto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400 * 30, 1, UNIX_TIMESTAMP());

-- ============================================================================
-- 添加后台菜单
-- ============================================================================
INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', 0, 'redpacket', '红包任务', 'fa fa-gift', '', '', '红包任务管理', 1, NULL, '', 'hbrw', 'hongbaorenwu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET @parent_id = LAST_INSERT_ID();

INSERT INTO `advn_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `url`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @parent_id, 'redpacket/task', '任务列表', 'fa fa-list', '', '', '', 1, NULL, '', 'rwlb', 'renwuliebiao', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/category', '任务分类', 'fa fa-th-large', '', '', '', 1, NULL, '', 'rwfl', 'renwufenlei', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/participation', '参与记录', 'fa fa-history', '', '', '', 1, NULL, '', 'cyjl', 'canyujilu', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/audit', '审核管理', 'fa fa-check-circle', '', '', '', 1, NULL, '', 'shgl', 'shenheguanli', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @parent_id, 'redpacket/stat', '数据统计', 'fa fa-bar-chart', '', '', '', 1, NULL, '', 'sjtj', 'shujutongji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');

SET FOREIGN_KEY_CHECKS = 1;
