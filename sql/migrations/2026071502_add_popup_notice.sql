-- =====================================================
-- 首页弹窗公告功能
-- 在单页管理中新增"弹窗公告"分类，并插入一条示例公告
-- 执行时间: 2026-07-15
-- =====================================================

-- 1. 新增"弹窗公告"分类（weigh=200 排最前面）
INSERT INTO `advn_singlepage_category` (`name`, `description`, `weigh`, `status`, `createtime`, `updatetime`)
VALUES ('弹窗公告', '首页弹窗公告，该分类下的最新一条已启用单页将作为弹窗展示', 200, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 获取刚插入的分类ID
SET @notice_cat_id = LAST_INSERT_ID();

-- 3. 插入一条示例弹窗公告（content 用 UEditor 富文本编辑）
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @notice_cat_id,
  '欢迎使用',
  '公告,弹窗,通知',
  '首页弹窗公告示例',
  '',
  '<p style=''text-align:center;''><img src=''/assets/img/avatar.png'' width=''80'' /></p><h2 style=''text-align:center;color:#333;''>欢迎使用本平台</h2><p style=''text-align:center;color:#666;font-size:14px;''>感谢您的支持与信任！</p><p><br/></p><p>1、本平台提供丰富多样的短剧内容，观看视频即可获得金币奖励；</p><p>2、金币可在钱包中申请提现，支持微信自动打款；</p><p>3、邀请好友加入还可获得额外奖励，多邀多得。</p><p><br/></p><p style=''color:#999;font-size:12px;text-align:right;''>—— 系统公告</p>',
  '',
  100,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);
