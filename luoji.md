# 红包群页面（red-bag）功能逻辑整理

## 一、页面概览

红包群页面是一个模拟微信群聊的界面，通过信息流广告和激励视频广告让用户"赚金币"。页面整体伪装成微信群聊体验，在聊天消息中穿插广告卡片和红包消息。

**核心文件：**
- 前端页面：`mashangzhuan-app/pages/index/red-bag.vue`
- 广告观看页：`mashangzhuan-app/pages/ad/watch.vue`
- 信息流广告组件：`mashangzhuan-app/components/chat/adFeedMessage.vue`
- 激励视频组件：`mashangzhuan-app/components/chat/rewardedVideoMessage.vue`
- 红包消息组件：`mashangzhuan-app/components/chat/redbagMessage.vue`
- 广告红包列表：`mashangzhuan-app/components/ad/adRedPacketList.vue`
- 后端广告API：`application/api/controller/Ad.php`
- 后端广告收益服务：`application/common/library/AdIncomeService.php`
- 后端红包API：`application/api/controller/RedPacket.php`
- 后端广告红包API：`application/api/controller/AdRedPacket.php`

---

## 二、页面初始化流程

页面加载时（`onLoad`）执行以下步骤：

1. **并行加载广告配置和聊天资源**（`Promise.all`）：
   - `loadChatResources()`：从后端 `/api/ad/chatResources` 获取系统用户列表（头像+昵称）和聊天消息模板，支持版本号增量缓存
   - `loadAdOverview()`：从后端 `/api/ad/overview` 获取广告配置（adpid、奖励金额、间隔时间等）、待释放金币余额、金币余额等

2. **生成初始消息**：
   - 1条系统欢迎语："欢迎来到红包群，观看广告即可赚金币！"
   - 3~5条随机聊天消息（从后端获取的模板中随机选取）

3. **启动持续推送定时器**（`startContinuousPush`）

4. **记录激励视频推送基准时间**（`rewardedVideoLastPushTime = Date.now()`）

5. **启动红包结算轮询**（`startAdRedPacketPolling`）：每10秒调用一次后端结算检查

6. **立即执行一次结算检查和红包推送**（不等轮询定时器）

7. **启动用户活跃度检测**（`startUserIdleDetection`）：防止挂机刷广告

---

## 三、消息推送系统（模拟聊天流）

### 3.1 持续推送机制

- **启动方式**：页面加载后延迟1秒启动
- **推送频率**：每 2~5 秒随机推送一条消息（递归setTimeout，永不停止）
- **消息列表上限**：150条，超出时删除最旧的消息
- **在线人数**：初始128~327随机，每推送一条消息有30%概率随机波动（±5，最低50人）

### 3.2 消息类型分配

采用**计数器机制**决定推送什么类型的消息：

1. 初始随机设定 `pushChatCountBeforeAd = 2~4`（表示还需推几条聊天消息才插一条广告）
2. 每推送一条消息，计数器 `pushCounter++`
3. 当 `pushCounter >= pushChatCountBeforeAd` 时：
   - **先判断是否该推激励视频**（如果距上次激励视频已超过 `rewarded_video_interval` 秒）
   - 如果不需要推激励视频 → 推送一条**信息流广告消息**
   - 重置计数器，随机设下一次 `pushChatCountBeforeAd = 2~4`
4. 否则推送一条**普通聊天消息**

**总结**：大约每 4~15 条消息中出现一条广告（信息流广告或激励视频交替），平均约每 8~30 秒出现一条广告。

---

## 四、信息流广告（Feed Ad）

### 4.1 推送频率

- 通过消息推送系统自动插入，约每 4~15 条消息中出现一条
- 具体取决于随机间隔 `pushChatCountBeforeAd`（2~4条聊天后插入）

### 4.2 展示逻辑

- 组件：`adFeedMessage.vue`
- 展示为伪装聊天消息卡片（带头像、昵称、广告图片占位符、奖励标签"浏览即可获得金币奖励"）
- **展示即计费**：组件创建后（`created`），300ms全局节流后自动调用 `silentReportView()` 上报
- 防挂机检测：如果用户超过 `ad_idle_timeout`（默认30秒）未操作，跳过上报

### 4.3 奖励发放逻辑

**阈值奖励机制**（后端 `recordAdViewAndCheckReward`）：

1. 每次信息流广告展示时，前端调用 `/api/ad/recordView` 接口
2. 后端将当日信息流广告浏览次数 `view_count + 1`（存储在 `ad_view_counter` 表中，按天自动重置）
3. **检查是否达到阈值**（`feed_reward_threshold`，默认5次）：
   - **未达阈值**：仅记录浏览次数，返回"再浏览X次可获得奖励"
   - **达到阈值**：
     - 计算批量奖励金额 = `threshold × reward_per_feed`（默认：5 × 50 = 250 金币）
     - 调用 `handleAdCallback` 写入广告收益日志 + 增加 `ad_freeze_balance`（待释放金币）
     - 重置浏览计数 `view_count = 0`，累加领奖次数 `reward_count + 1`
     - 返回奖励金额

### 4.4 金币流向

```
信息流广告展示 → recordView API → 达到阈值 → handleAdCallback
  → 写入 ad_income_log（广告收益日志）
  → coin_account.ad_freeze_balance += userCoin（增加待释放金币）
  → coin_account.total_ad_income += rewardCoin（增加累计广告收益）
```

**注意**：信息流广告奖励**不直接发放到 balance**，而是进入 `ad_freeze_balance`（待释放金币）。

### 4.5 相关配置参数（从 overview 接口获取）

| 参数 | 默认值 | 说明 |
|------|--------|------|
| `feed_adpid` | '' | 信息流广告位ID |
| `feed_ad_count` | 3 | 每批信息流广告数量 |
| `reward_per_feed` | 50 | 每次信息流广告基础奖励金币 |
| `feed_reward_threshold` | 5 | 每浏览多少次信息流广告发放一次奖励 |

---

## 五、激励视频广告（Rewarded Video）

### 5.1 推送频率

- 每 `rewarded_video_interval` 秒（默认 **120秒**）在信息流广告位置插入一条激励视频
- 如果时间未到，仍然推送信息流广告
- **首次推送**需要等待一个完整的间隔时间（页面加载后120秒才第一次出现）

### 5.2 展示与交互

- 组件：`rewardedVideoMessage.vue`
- 全宽广告卡片，显示视频封面+播放按钮+"观看视频赚金币"+奖励金额
- 点击后跳转到广告观看页 `/pages/ad/watch?type=reward&adpid=xxx&rewardCoin=200&watchSeconds=30`

### 5.3 观看页逻辑（watch.vue）

1. **倒计时**：30秒倒计时，底部显示进度条和剩余时间
2. **强制观看**：禁用返回手势，物理返回键需二次确认
3. **观看完成**后：
   - **reward 类型**：倒计时结束**自动发放**奖励
     - 调用 `/api/ad/recordView`（ad_type=reward）
     - 后端逻辑：阈值为1（`video_reward_threshold`），每次观看都触发奖励
     - 奖励金额 = `reward_per_video`（默认200金币）
     - 自动返回上一页，通过 `uni.$emit('ad-watch-result')` 通知红包群页
4. **观看完成后冷却**：该激励视频卡片进入冷却状态，倒计时120秒后可再次观看

### 5.4 奖励发放逻辑

与信息流广告相同的阈值机制，但 `video_reward_threshold` 默认为 **1**（即每次观看都发放奖励）：
- 每次观看 → view_count + 1 → 立即达到阈值(1) → 发放 `reward_per_video`(200) 金币 → 写入 ad_freeze_balance

---

## 六、广告红包系统（待释放金币 → 红包 → 领取）

### 6.1 核心概念

| 概念 | 说明 |
|------|------|
| `ad_freeze_balance` | 待释放金币，广告收益先进入这里 |
| `coin_balance` | 可提现金币余额 |
| `redpacket_threshold` | 红包基数额度，默认 **1000金币** |
| `settle_interval` | 后端结算检查间隔，默认 **30分钟** |
| `ad_idle_timeout` | 用户空闲超时，默认 **30秒** |
| `daily_reward_limit` | 每日广告收益上限，默认 **50000金币** |

### 6.2 金币流转全流程

```
广告展示/观看 → handleAdCallback → ad_freeze_balance 增加
  ↓
freeze_balance >= redpacket_threshold(1000)
  ↓
checkAndAutoSettle → 创建"通知红包"（amount=0, source=freeze_notify）
  ↓
前端轮询 → 发现通知红包 → 推送红包消息到信息流
  ↓
用户点击红包 → 弹出"待释放金币红包"弹窗
  ↓
用户点击"开" → 跳转激励视频观看页（30秒）
  ↓
观看完成 → 调用 claimFreezeBalance API
  ↓
ad_freeze_balance → coin_balance（待释放 → 可提现）
```

### 6.3 自动结算检查（前端轮询 + 后端接口）

**前端**（`startAdRedPacketPolling`）：
- 每 **10秒** 调用 `/api/ad/checkSettle`
- 同时检查是否到了推送红包到信息流的随机间隔

**后端**（`checkAndAutoSettle`）：
1. 读取用户 `ad_freeze_balance`
2. 如果 < `redpacket_threshold`(1000) → 不操作
3. 如果 >= `redpacket_threshold` 且无未领取红包 → 创建通知红包
4. 通知红包不消费 freeze_balance，仅作通知标识

### 6.4 红包消息推送到信息流

- 每 **5~30秒**随机推送一条红包消息到聊天信息流（`pushRedBagToFeedIfNeeded`）
- 使用随机系统用户头像和昵称
- 5秒后自动标记为"已领完"（模拟其他群成员抢完）
- 文案显示当前待释放金币金额或"有新红包待领取，手慢无！"

### 6.5 待释放金币领取流程

**方式一：通过红包弹窗（主要入口）**
1. 用户点击信息流中的红包消息 → 打开"待释放金币红包"弹窗
2. 弹窗显示当前 `freezeBalance` 金额
3. 用户点击"开" → 跳转到观看页（type=freeze_claim, 30秒倒计时）
4. 观看完成后自动调用 `claimFreezeBalance` API → 领取成功返回红包群页
5. 弹出红包弹窗显示"领取红包"按钮 → 用户手动点击领取
6. 调用 `claimFreezeBalance(max_amount=快照金额)` → 金币从 ad_freeze_balance 转入 balance

**方式二：通过广告红包面板**
1. 点击顶部导航栏的🧧图标 → 打开广告红包面板
2. 显示待释放金币进度条 + 红包列表
3. 点击"领取"按钮 → 同样跳转观看页 → claimFreezeBalance

**金额快照机制**：用户点击红包时捕获当时的 freezeBalance 作为快照金额，领取时只领取快照金额（防止领取期间新增的金币被一并领取导致金额不一致）。

### 6.6 后端 claimFreezeBalance 逻辑

- 频率限制：同一用户每10秒最多一次
- 支持 `max_amount` 参数：只领取指定金额
- 乐观锁更新 `coin_account`：
  - `ad_freeze_balance -= amount`
  - `balance += amount`
- 写入 `coin_log` 记录流水

---

## 七、传统红包系统（任务红包）

### 7.1 红包点击/累加机制

**设计**：per-user 共享累加（不按 taskId 隔离）

1. **第一次点击**任意红包：
   - 根据当前时段配置生成**基础金额**（`generateBaseAmount`）
   - 存入 Redis 缓存（key: `red_packet:click:{userId}`，TTL: 2小时）
2. **后续每次点击**任意红包：
   - 生成**累加金额**（`generateAccumulateAmount`），加到 total 上
   - 受封顶额度限制（`red_packet_max_reward`，默认10000）
3. **领取**：
   - 调用 `/api/redpacket/claim` → 发放 total_amount 金币到 balance
   - 清零 Redis 缓存，下一轮从基础金额重新开始

### 7.2 金额生成逻辑（RedPacketRewardConfig）

**基础/累加金额都受两个维度配置影响**：

1. **时段配置**（start_hour ~ end_hour）：不同时段有不同的金额区间
2. **当日累计金额配置**（min_today_amount ~ max_today_amount）：根据用户今日已领取金额匹配不同档位

**生成流程**：
1. 根据当日累计金额匹配配置 → 随机生成金额
2. 检查时段配置：如果金额超时段最大值 → 在时段范围内重新生成；低于最小值 → 直接使用
3. 新用户（注册7天内）使用单独的新用户金额区间

### 7.3 红包领取后行为

- 领取成功 → 跳转到小程序/外部链接（`jumpToMiniapp`）
- 红包弹窗有 **5秒关闭锁**（防止快速关闭弹窗）
- 离开页面时调用 `/api/redpacket/reset` 重置累加数据

---

## 八、DCloud 广告联盟服务端回调

### 8.1 回调接口

- URL: `/api/ad/serverNotify`（无需登录）
- DCloud 在用户完成广告观看后自动回调

### 8.2 回调参数

| 参数 | 说明 |
|------|------|
| `adpid` | 广告位ID |
| `trans_id` | 交易ID（唯一标识一次广告观看） |
| `sign` | 签名 = sha256(secret:trans_id) |
| `user_id` | 用户ID |
| `cpm` | 千次曝光收益（分），cpm/1000 = 本次收益（元） |

### 8.3 处理流程

1. 签名验证 → 2. 用户识别 → 3. 判断广告类型（feed/reward） → 4. 计算收益金额
5. 防重复（transaction_id + Cache 24小时） → 6. 调用 `handleAdCallback` 写入收益
7. 成功后检查是否需要生成通知红包（`checkAndAutoSettle`）

### 8.4 与客户端上报的关系

- **有真实CPM收益**（DCloud服务端回调）：使用实际金额，扣除平台分成（`platform_rate`，默认30%）
- **H5模拟广告**（客户端调用 `/api/ad/callback`）：不传amount，使用固定奖励（不扣平台分成），用户获得完整奖励
- **安全性**：客户端上报时**不使用客户端提交的amount**，仅通过服务端回调处理真实金额

---

## 九、用户活跃度检测（防挂机）

### 9.1 检测机制

- 监听 touchstart/touchmove/click 事件 → 更新 `lastUserActivityTime`
- 每 **5秒** 检查一次：如果用户超过 `ad_idle_timeout`（默认30秒）未操作 → 标记为不活跃
- 恢复操作 → 自动标记为活跃

### 9.2 不活跃时的影响

- 页面显示"💤 滑动屏幕继续赚金币"提示
- **信息流广告跳过上报**（`adFeedMessage.silentReportView` 中检查活跃时间）
- 用户活跃后自动恢复

---

## 十、广告收益概览接口（/api/ad/overview）

### 10.1 缓存策略

- 同一用户 **30秒**内返回缓存数据
- 使用**版本化缓存key**：数据变更时递增版本号使旧缓存失效（比直接delete更安全，避免并发问题）

### 10.2 返回数据

| 字段 | 说明 |
|------|------|
| `today_income` | 今日广告收益（金币） |
| `total_ad_income` | 累计广告收益（金币） |
| `ad_freeze_balance` | 待释放金币 |
| `coin_balance` | 可提现金币余额 |
| `unclaimed_packet_count` | 未领取红包数 |
| `unclaimed_packet_amount` | 未领取红包总金额（含通知红包） |
| `feed_adpid` | 信息流广告位ID |
| `rewarded_video_adpid` | 激励视频广告位ID |
| `reward_per_feed` | 信息流广告奖励金币 |
| `reward_per_video` | 激励视频奖励金币 |
| `rewarded_video_interval` | 激励视频推送间隔（秒） |
| `settle_interval` | 红包结算间隔（分钟） |
| `ad_idle_timeout` | 空闲超时（秒） |
| `redpacket_threshold` | 红包基数额度 |
| `feed_view_progress` | 信息流广告浏览进度 |
| `reward_view_progress` | 激励视频浏览进度 |

---

## 十一、风控机制

1. **广告回调频率限制**：同一用户每10秒最多一次 `/api/ad/callback`
2. **广告浏览频率限制**：同一用户每5秒最多一次 `/api/ad/recordView`
3. **待释放金币领取限制**：同一用户每10秒最多一次 `claimFreezeBalance`
4. **红包点击/领取风控**：通过 `RiskControlService` 统一风控
5. **防重复回调**：transaction_id + Redis 24小时去重
6. **防并发**：分布式锁 + 乐观锁（version字段）
7. **每日上限**：`daily_reward_limit`（默认50000金币）
8. **防挂机**：用户空闲超时检测（30秒）

---

## 十二、关键配置汇总（后台可配置）

| 配置项 | 分组 | 默认值 | 说明 |
|--------|------|--------|------|
| `ad_income_enabled` | ad | 1 | 广告收益功能开关 |
| `platform_rate` | ad | 0.30 | 平台抽成比例 |
| `reward_per_feed` | ad | 50 | 信息流广告奖励金币 |
| `reward_per_video` | ad | 200 | 激励视频奖励金币 |
| `feed_reward_threshold` | ad | 5 | 信息流广告奖励阈值（次） |
| `video_reward_threshold` | ad | 1 | 激励视频奖励阈值（次） |
| `rewarded_video_interval` | ad | 120 | 激励视频推送间隔（秒） |
| `redpacket_threshold` | ad | 1000 | 红包基数额度（金币） |
| `settle_interval` | ad | 30 | 红包结算检查间隔（分钟） |
| `ad_idle_timeout` | ad | 30 | 空闲超时（秒） |
| `daily_reward_limit` | ad | 50000 | 每日广告收益上限 |
| `min_redpacket_amount` | ad | 100 | 最小红包金额 |
| `redpacket_expire_hours` | ad | 48 | 红包过期时间（小时） |
| `feed_adpid` | ad | '' | 信息流广告位ID |
| `rewarded_video_adpid` | ad | '' | 激励视频广告位ID |
| `callback_secret` | ad | '' | DCloud回调密钥 |
| `red_packet_max_reward` | config | 10000 | 红包最高金额限制 |

---

## 十三、数据表概览

| 表名 | 说明 |
|------|------|
| `coin_account` | 用户金币账户（balance, frozen, ad_freeze_balance, total_ad_income等） |
| `coin_log` | 金币流水日志（按月分表） |
| `ad_income_log` | 广告收益日志（按月分表） |
| `ad_red_packet` | 广告红包（按月分表） |
| `ad_view_counter` | 广告浏览计数器（按天+按类型自动重置） |
| `red_packet_reward_config` | 红包奖励配置（时段+金额区间） |
| `red_packet_resource` | 红包资源（聊天消息模板等） |
| `config` | 系统配置（广告相关配置存储在 ad 分组） |
