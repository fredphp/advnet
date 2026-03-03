-- ----------------------------
-- 创建发布者/作者表
-- ----------------------------
DROP TABLE IF EXISTS `advn_author`;
CREATE TABLE `advn_author` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '作者名称',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '头像',
  `description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '简介',
  `createtime` bigint DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint DEFAULT NULL COMMENT '更新时间',
  `deletetime` bigint DEFAULT NULL COMMENT '删除时间',
  `status` enum('normal','hidden') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'normal' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='发布者/作者表';

-- 插入示例数据
INSERT INTO `advn_author` (`id`, `name`, `avatar`, `description`, `createtime`, `updatetime`, `deletetime`, `status`) VALUES
(1, '张三', '', '资深视频创作者', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL, 'normal'),
(2, '李四', '', '知名博主', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL, 'normal'),
(3, '王五', '', '视频达人', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL, 'normal');
