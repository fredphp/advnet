-- =====================================================
-- 帮助中心文章数据（动态模板版本）
-- 使用 {{config_key}} 占位符，API返回时动态替换为实际配置值
-- 当系统配置变更时，文章内容自动同步更新，无需重新迁移
-- 执行时间: 2026-07-17
-- =====================================================

-- 确保"帮助中心"分类存在
SET @help_cat_id = (SELECT `id` FROM `advn_singlepage_category` WHERE `name` = '帮助中心' AND `deletetime` IS NULL LIMIT 1);

INSERT INTO `advn_singlepage_category` (`name`, `description`, `weigh`, `status`, `createtime`, `updatetime`)
SELECT '帮助中心', '用户帮助中心，常见问题与使用指南', 90, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `advn_singlepage_category` WHERE `name` = '帮助中心' AND `deletetime` IS NULL);

SET @help_cat_id = (SELECT `id` FROM `advn_singlepage_category` WHERE `name` = '帮助中心' AND `deletetime` IS NULL LIMIT 1);

-- 先删除旧的帮助中心文章（保持id不变以确保前端缓存失效）
DELETE FROM `advn_singlepage` WHERE `category_id` = @help_cat_id;

-- =====================================================
-- 文章1：新手入门指南
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '新手入门指南',
  '新手,入门,教程,指南',
  '新用户快速了解平台功能与基本操作',
  '',
  '<h3>欢迎加入平台！</h3><p>本平台提供丰富的赚钱方式，以下是你需要了解的基本功能：</p><p><br/></p><h4>一、每日签到</h4><p>每天打开App点击签到即可获得金币奖励，连续签到天数越多奖励越丰厚。漏签还可以使用补签功能。</p><p><br/></p><h4>二、观看视频赚金币</h4><p>在视频页面观看完整视频即可获得金币奖励，每观看一个完整视频可获得 <b>{{video_coin_reward}}</b> 金币，每日最多可观看 <b>{{daily_video_limit}}</b> 个视频，达到上限后次日重置。</p><p><br/></p><h4>三、抢红包任务</h4><p>系统不定期发放红包任务，完成指定条件即可领取随机金额的金币红包，单个红包最高可获 <b>{{red_packet_max_reward}}</b> 金币。</p><p><br/></p><h4>四、邀请好友</h4><p>分享你的邀请码给好友，好友注册后你和好友都能获得金币奖励。每成功邀请一位好友注册可获得 <b>{{invite_register_reward}}</b> 金币，好友后续的消费还能给你带来佣金收入。</p><p><br/></p><h4>五、金币提现</h4><p>积累一定数量的金币后，可以在「我的 → 钱包 → 去提现」中申请提现到微信零钱。当前兑换比例为 <b>{{coin_rate}}</b> 金币 = 1元，最低提现 <b>{{min_withdraw}}</b> 元。</p><p><br/></p><h4>六、新人奖励</h4><p>新用户注册成功后，系统会自动发放 <b>{{new_user_coin}}</b> 金币的新人注册奖励。</p><p><br/></p><p style="color:#E62129;font-weight:bold;">小提示：坚持每天签到和观看视频，日积月累金币会很可观哦！</p>',
  '',
  100,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章2：金币获取方式
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '金币获取方式',
  '金币,赚取,奖励,收入',
  '了解平台中所有获取金币的途径和规则',
  '',
  '<h3>金币获取途径一览</h3><p>平台提供多种金币获取方式，以下是详细说明：</p><p><br/></p><h4>1. 每日签到</h4><p>每日签到可获得基础金币奖励，连续签到可获得递增奖励：</p><ul><li>连续签到1天：获得基础奖励金币</li><li>连续签到3天：额外获得加签奖励</li><li>连续签到7天：获得周签额外奖励</li><li>漏签可使用金币补签（补签消耗以签到配置为准）</li></ul><p><br/></p><h4>2. 观看视频</h4><p>观看完整视频即可获得金币奖励，注意以下规则：</p><ul><li>需观看达到 <b>{{video_watch_duration}}</b> 秒以上才算有效</li><li>每观看一个完整视频可获得 <b>{{video_coin_reward}}</b> 金币</li><li>每日最多可观看 <b>{{daily_video_limit}}</b> 个视频</li><li>每小时金币获取上限 <b>{{hourly_coin_limit}}</b> 金币</li><li>每日金币获取上限 <b>{{daily_coin_limit}}</b> 金币</li><li>切勿使用模拟器或自动脚本，会被风控系统检测</li></ul><p><br/></p><h4>3. 邀请好友</h4><ul><li>每成功邀请1位好友注册：获得 <b>{{invite_register_reward}}</b> 金币奖励</li><li>好友完成首次提现：你还能额外获得 <b>{{invite_first_withdraw_reward}}</b> 金币</li></ul><p><br/></p><h4>4. 抢红包</h4><p>参与平台红包活动，有机会获得随机金币奖励，单个红包最高 <b>{{red_packet_max_reward}}</b> 金币。</p><p><br/></p><h4>5. 新人奖励</h4><p>新用户注册成功后，系统会自动发放 <b>{{new_user_coin}}</b> 金币的新人注册奖励。</p><p><br/></p><p style="color:#999;">提示：每日金币获取设有上限（<b>{{daily_coin_limit}}</b> 金币/天），达到上限后请次日再来。</p>',
  '',
  90,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章3：金币提现说明
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '金币提现说明',
  '提现,提款,微信,到账',
  '了解金币提现的规则、流程和注意事项',
  '',
  '<h3>金币提现规则</h3><p><br/></p><h4>一、兑换比例</h4><p>当前金币与人民币的兑换比例为：<b style="color:#E62129;font-size:16px;">{{coin_rate}} 金币 = 1.00 元</b></p><p>（例如：你有 <b>{{coin_rate}}</b> 金币即可提现 1 元）</p><p><br/></p><h4>二、提现条件</h4><ul><li>账户需完成实名认证（绑定手机号）</li><li>可提现金币余额需达到最低提现金额要求（<b>{{min_withdraw}}</b> 元）</li><li>新注册用户需满足注册天数要求（<b>{{new_user_withdraw_days}}</b> 天）后才能提现</li><li>账户状态正常，无违规记录</li></ul><p><br/></p><h4>三、提现流程</h4><ol><li>进入「我的」页面，查看钱包中的可提现金额</li><li>点击「去提现」按钮</li><li>选择提现金额（可选金额：<b>{{withdraw_amounts}}</b> 元）</li><li>确认提现信息，提交申请</li><li>等待审核：<b>{{auto_audit_amount}}</b> 元以下自动审核，<b>{{manual_audit_amount}}</b> 元以上需要人工审核</li><li>审核通过后自动打款到微信零钱</li></ol><p><br/></p><h4>四、提现限制</h4><ul><li>提现金额范围：<b>{{min_withdraw}}</b> 元 ~ <b>{{max_withdraw}}</b> 元/次</li><li>每日提现次数上限：<b>{{daily_withdraw_limit}}</b> 次</li><li>提现手续费：<b>{{fee_rate}}</b>%（手续费从提现金额中扣除）</li></ul><p><br/></p><h4>五、常见问题</h4><p><b>Q：提现多久到账？</b><br/>A：审核通过后通常即时到账，高峰期可能稍有延迟。</p><p><b>Q：提现被拒绝怎么办？</b><br/>A：请查看拒绝原因，修改后重新提交，或联系客服咨询。</p><p><b>Q：可以提现到支付宝吗？</b><br/>A：目前仅支持提现到微信零钱。</p><p><b>Q：为什么可提现金额和金币余额不一致？</b><br/>A：提现金额 = 金币余额 ÷ {{coin_rate}}，请以钱包页面显示的可提现金额为准。</p>',
  '',
  80,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章4：邀请好友赚佣金
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '邀请好友赚佣金',
  '邀请,推荐,分销,佣金,团队',
  '了解邀请好友奖励机制和佣金规则',
  '',
  '<h3>邀请好友奖励机制</h3><p>邀请好友加入平台，不仅能获得金币奖励，还能通过分销佣金持续收益！</p><p><br/></p><h4>一、如何邀请好友</h4><ol><li>进入「我的 → 邀请好友」页面</li><li>复制你的专属邀请码分享给好友</li><li>好友注册时填写你的邀请码即可绑定关系</li></ol><p><br/></p><h4>二、邀请奖励</h4><ul><li><b>一级邀请：</b>好友注册成功后，你获得 <b>{{invite_level1_reward}}</b> 金币</li><li><b>二级邀请：</b>好友的好友注册成功后，你获得 <b>{{invite_level2_reward}}</b> 金币</li><li><b>首次提现奖励：</b>你的一级好友首次成功提现后，你额外获得 <b>{{invite_first_withdraw_reward}}</b> 金币</li></ul><p><br/></p><h4>三、分销佣金</h4><p>好友在平台上消费或完成任务时，你可以获得一定比例的佣金收入：</p><ul><li><b>一级佣金：</b>好友消费金额的 <b>{{level1_commission_rate}}%</b></li><li><b>二级佣金：</b>二级好友消费金额的 <b>{{level2_commission_rate}}%</b></li></ul><p>佣金会自动计入你的账户，可在「邀请好友」页面查看佣金明细。</p><p><br/></p><h4>四、等级晋升</h4><p>累计佣金越高，你的分销等级越高，享受的权益也越多：</p><ul><li>普通会员 → 青铜代理 → 白银代理 → 黄金代理 → 铂金代理 → 钻石代理 → 星耀代理 → 王者代理</li></ul><p><br/></p><p style="color:#E62129;font-weight:bold;">小技巧：多分享你的邀请码，建立自己的团队，被动收入更可观！</p>',
  '',
  70,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章5：签到规则说明
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '签到规则说明',
  '签到,打卡,补签,连续签到',
  '了解每日签到的奖励规则和补签机制',
  '',
  '<h3>每日签到规则</h3><p>每天签到是获取金币最简单的方式之一！</p><p><br/></p><h4>一、签到方式</h4><p>进入「首页 → 签到」页面，点击「立即签到」按钮即可完成当天签到。</p><p><br/></p><h4>二、签到奖励</h4><p>签到奖励分为以下几类：</p><ul><li><b>每日签到：</b>基础金币奖励，每天固定</li><li><b>连续签到加成：</b>连续签到天数越多，每日额外奖励越高</li><li><b>周签奖励：</b>连续签到满7天可获得周签额外奖励</li><li><b>月签奖励：</b>连续签到满整月可获得月签大额奖励</li></ul><p><br/></p><h4>三、补签规则</h4><ul><li>如果某天忘记签到，可以使用补签功能</li><li>补签需要消耗一定数量的金币</li><li>每月补签次数有限制（具体天数以签到页面显示为准）</li><li>只能补签最近几天漏签的日期，不支持跨月补签</li></ul><p><br/></p><h4>四、签到排行榜</h4><p>平台设有签到排行榜，展示连续签到天数最长的用户。坚持签到，说不定你就能上榜！</p><p><br/></p><h4>五、注意事项</h4><ul><li>每天只能签到一次，签到后无法撤销</li><li>签到时间以服务器时间为准</li><li>请勿使用第三方工具自动签到，违规将被风控系统处罚</li></ul>',
  '',
  60,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章6：红包任务玩法
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '红包任务玩法',
  '红包,奖励,任务,福利',
  '了解红包任务的参与方式和奖励规则',
  '',
  '<h3>红包任务玩法</h3><p>红包任务是平台提供的额外金币获取途径，轻松好玩，奖励丰厚！</p><p><br/></p><h4>一、红包类型</h4><ul><li><b>定时红包：</b>每天固定时间点发放，先到先得</li><li><b>任务红包：</b>完成指定任务后解锁领取</li><li><b>随机红包：</b>不定期出现的惊喜红包</li></ul><p><br/></p><h4>二、参与方式</h4><ol><li>在首页或任务页面找到红包入口</li><li>点击红包进行抢夺</li><li>成功抢到后金币自动到账</li></ol><p><br/></p><h4>三、红包规则</h4><ul><li>每个红包有有效期，过期自动失效</li><li>每日抢红包次数有上限</li><li>两次抢红包之间有一定时间间隔</li><li>红包金额随机，运气越好奖励越多</li><li>单个红包最高可获 <b>{{red_packet_max_reward}}</b> 金币</li></ul><p><br/></p><h4>四、温馨提示</h4><p>红包数量有限，看到红包记得第一时间点击领取哦！</p>',
  '',
  50,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章7：账户安全与风控说明
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '账户安全与风控说明',
  '安全,封号,风控,违规,申诉',
  '了解平台风控规则和账户安全注意事项',
  '',
  '<h3>账户安全与风控说明</h3><p>为维护平台健康生态，保障所有用户的公平权益，平台设有完善的风控体系。</p><p><br/></p><h4>一、风控规则</h4><p>以下行为会被风控系统检测并处罚：</p><ul><li>使用模拟器、脚本、外挂等自动化工具</li><li>使用VPN/代理/修改器等作弊手段</li><li>同一设备或IP注册多个账号</li><li>恶意刷金币、刷提现等行为</li><li>利用系统漏洞获取不当利益</li></ul><p><br/></p><h4>二、处罚措施</h4><ul><li><b>轻度违规（风控分数达到 <b>{{risk_freeze_threshold}}</b> 分）：</b>警告 + 冻结部分功能（如提现）</li><li><b>中度违规（风控分数达到 <b>{{risk_ban_threshold}}</b> 分）：</b>冻结账户，限制操作</li><li><b>重度违规：</b>永久封禁账号</li></ul><p><br/></p><h4>三、账户安全建议</h4><ol><li>绑定手机号，提高账户安全性</li><li>不要将账号借给他人使用</li><li>不要在非官方渠道下载App</li><li>定期修改密码</li><li>发现异常及时联系客服</li></ol><p><br/></p><h4>四、申诉方式</h4><p>如果你的账户被误封或对处罚有异议，可以通过以下方式申诉：</p><ul><li>在App内联系在线客服</li><li>发送邮件至客服邮箱（详见联系客服页面）</li><li>提供你的用户ID和相关说明，客服会在1-3个工作日内处理</li></ul>',
  '',
  40,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);

-- =====================================================
-- 文章8：常见问题FAQ
-- =====================================================
INSERT INTO `advn_singlepage` (
  `category_id`, `title`, `keywords`, `description`,
  `image`, `content`, `tpl`, `weigh`, `status`,
  `createtime`, `updatetime`
) VALUES (
  @help_cat_id,
  '常见问题FAQ',
  'FAQ,常见问题,疑问,解答',
  '用户常见问题汇总与解答',
  '',
  '<h3>常见问题FAQ</h3><p><br/></p><h4>Q1：如何注册账号？</h4><p>A：打开App点击登录页面，支持微信一键登录和手机号登录两种方式。新用户注册成功可获 <b>{{new_user_coin}}</b> 金币新人奖励。</p><p><br/></p><h4>Q2：忘记密码怎么办？</h4><p>A：在登录页面点击「忘记密码」，通过手机验证码重置密码即可。</p><p><br/></p><h4>Q3：金币有有效期吗？</h4><p>A：金币没有有效期，可以一直累积使用，不会被清零。</p><p><br/></p><h4>Q4：为什么签到奖励不一样？</h4><p>A：签到奖励与连续签到天数有关，连续签到天数越长奖励越丰厚。中断签到后连续天数会重新计算。</p><p><br/></p><h4>Q5：邀请码在哪里查看？</h4><p>A：进入「我的」页面，在用户名下方即可看到你的邀请码，点击可以复制。每成功邀请一位好友注册可获得 <b>{{invite_register_reward}}</b> 金币。</p><p><br/></p><h4>Q6：提现多久能到账？</h4><p>A：<b>{{auto_audit_amount}}</b> 元以下自动审核后即时到账。<b>{{manual_audit_amount}}</b> 元以上需要人工审核，一般在1-3个工作日内到账。</p><p><br/></p><h4>Q7：为什么我无法提现？</h4><p>A：可能的原因有：①金币余额不足（最低需 <b>{{min_withdraw}}</b> 元）；②新账号还在注册保护期（需注册满 <b>{{new_user_withdraw_days}}</b> 天）；③账户存在异常被冻结。请根据具体提示进行操作。</p><p><br/></p><h4>Q8：如何联系客服？</h4><p>A：在「我的」页面中点击「联系客服」即可查看客服联系方式和在线咨询入口。</p><p><br/></p><h4>Q9：可以修改绑定的手机号吗？</h4><p>A：可以，在「我的 → 系统设置」中可以更换绑定的手机号，需要验证原手机号和新手机号。</p><p><br/></p><h4>Q10：金币兑换比例是多少？</h4><p>A：当前兑换比例为 <b>{{coin_rate}}</b> 金币 = 1 元，具体以钱包页面显示为准。</p><p><br/></p><h4>Q11：每日金币获取有上限吗？</h4><p>A：有上限的。每日金币获取上限为 <b>{{daily_coin_limit}}</b> 金币，每小时上限 <b>{{hourly_coin_limit}}</b> 金币。每日最多可观看 <b>{{daily_video_limit}}</b> 个视频。</p><p><br/></p><h4>Q12：分销等级有什么用？</h4><p>A：分销等级根据累计佣金自动升级，等级越高代表你的推广能力越强。一级佣金比例为 <b>{{level1_commission_rate}}%</b>，二级佣金比例为 <b>{{level2_commission_rate}}%</b>。</p><p><br/></p><p style="color:#999;text-align:center;">以上是常见问题汇总，如未解决您的问题请联系在线客服</p>',
  '',
  30,
  1,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
);
