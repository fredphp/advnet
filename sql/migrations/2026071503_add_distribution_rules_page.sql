-- =====================================================
-- 分销规则单页数据迁移（使用自定义singlepage系统）
-- 在 advn_singlepage_category 新增"分销规则"分类
-- 在 advn_singlepage 插入分销规则内容
-- tpl=distribution-rules 供前端按标识查询
-- 执行时间: 2026-07-15
-- =====================================================

-- 1. 新增"分销规则"分类
INSERT IGNORE INTO `advn_singlepage_category` (`name`, `description`, `weigh`, `status`, `createtime`, `updatetime`)
VALUES ('分销规则', '平台分销佣金规则和说明', 150, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 获取分类ID
SET @rule_cat_id = (SELECT `id` FROM `advn_singlepage_category` WHERE `name` = '分销规则' LIMIT 1);

-- 3. 插入分销规则页面内容
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @rule_cat_id,
  '分销规则',
  '分销规则,分销说明,佣金规则',
  '平台分销佣金的规则和说明',
  '',
  '<h2 style=\"text-align:center;color:#E62129;\">平台分销规则</h2>\n<p><br/></p>\n<h3 style=\"color:#333;\">一、分销等级</h3>\n<p>平台分销共设有以下等级，根据累计佣金金额自动升级：</p>\n<table style=\"width:100%;border-collapse:collapse;margin:10px 0;\">\n<thead>\n<tr style=\"background:#E62129;color:#fff;\">\n<th style=\"padding:8px 12px;border:1px solid #ddd;\">等级</th>\n<th style=\"padding:8px 12px;border:1px solid #ddd;\">名称</th>\n<th style=\"padding:8px 12px;border:1px solid #ddd;\">累计佣金要求</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv1</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">普通会员</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">0元</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv2</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">青铜代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">100元</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv3</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">白银代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">500元</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv4</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">黄金代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">2,000元</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv5</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">铂金代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">5,000元</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv6</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">钻石代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">10,000元</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv7</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">星耀代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">50,000元</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;text-align:center;\">Lv8</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">王者代理</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">100,000元</td></tr>\n</tbody>\n</table>\n<p><br/></p>\n<h3 style=\"color:#333;\">二、佣金规则</h3>\n<p>通过邀请好友注册成为您的下级，当下级在平台产生消费行为时，您可获得相应的佣金奖励：</p>\n<p><strong>1. 一级佣金（直接邀请）</strong></p>\n<ul>\n<li>您直接邀请的好友在平台消费（提现、观看视频、领取红包、游戏等），您可获得一定比例的一级佣金奖励。</li>\n<li>一级佣金比例根据消费类型不同而有所差异，具体以平台公布为准。</li>\n</ul>\n<p><strong>2. 二级佣金（间接邀请）</strong></p>\n<ul>\n<li>您直接邀请的好友再邀请其他好友，这些好友产生消费时，您也可获得一定比例的二级佣金奖励。</li>\n<li>二级佣金比例通常低于一级佣金。</li>\n</ul>\n<p><br/></p>\n<h3 style=\"color:#333;\">三、佣金来源</h3>\n<table style=\"width:100%;border-collapse:collapse;margin:10px 0;\">\n<thead>\n<tr style=\"background:#E62129;color:#fff;\">\n<th style=\"padding:8px 12px;border:1px solid #ddd;\">来源类型</th>\n<th style=\"padding:8px 12px;border:1px solid #ddd;\">说明</th>\n</tr>\n</thead>\n<tbody>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;\">提现分佣</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">下级用户成功提现时，上级获得佣金</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;\">视频分佣</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">下级用户观看视频产生收益时，上级获得佣金</td></tr>\n<tr><td style=\"padding:8px 12px;border:1px solid #ddd;\">红包分佣</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">下级用户领取红包时，上级获得佣金</td></tr>\n<tr style=\"background:#f9f9f9;\"><td style=\"padding:8px 12px;border:1px solid #ddd;\">游戏分佣</td><td style=\"padding:8px 12px;border:1px solid #ddd;\">下级用户参与游戏消费时，上级获得佣金</td></tr>\n</tbody>\n</table>\n<p><br/></p>\n<h3 style=\"color:#333;\">四、佣金结算</h3>\n<p>1. 佣金产生后进入<strong>待结算</strong>状态，系统将在一定时间后自动结算为可用余额。</p>\n<p>2. 已结算的佣金将以金币形式发放至您的金币账户，可在钱包中申请提现。</p>\n<p>3. 提现金额 = 金币余额 ÷ 汇率，具体汇率以平台实时公示为准。</p>\n<p><br/></p>\n<h3 style=\"color:#333;\">五、邀请方式</h3>\n<p>您可以通过以下方式邀请好友：</p>\n<ul>\n<li><strong>邀请链接</strong>：将您的专属邀请链接发送给好友，好友点击链接注册即成为您的下级。</li>\n<li><strong>邀请码</strong>：将您的邀请码告知好友，好友在注册时输入邀请码即可绑定关系。</li>\n<li><strong>二维码海报</strong>：生成专属邀请海报，好友扫码即可注册。</li>\n</ul>\n<p><br/></p>\n<h3 style=\"color:#333;\">六、注意事项</h3>\n<ol>\n<li>每位用户只能绑定一位邀请人（一级），绑定后不可更改。</li>\n<li>禁止通过虚假交易、刷单等违规方式获取佣金，一经发现将取消佣金并封禁账号。</li>\n<li>平台有权根据运营情况调整佣金比例和规则，调整前会提前公告。</li>\n<li>如有疑问请联系客服咨询。</li>\n</ol>\n<p><br/></p>\n<p style=\"color:#999;font-size:12px;text-align:right;\">—— 平台运营团队</p>',
  'distribution-rules',
  100,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);
