# 邀请分佣系统功能说明

## 一、系统概述

邀请分佣系统是一个 **2级分佣** 体系，用户通过邀请码邀请新用户注册，当下级用户产生提现行为时，上级用户按比例获得佣金奖励。系统支持8级代理等级、延迟结算、风控集成和后台管理。

**核心文件：**
- 后端API控制器：`application/api/controller/Invite.php`
- 核心服务：`application/common/library/InviteCommissionService.php`
- 定时任务：`application/common/library/InviteCommissionTask.php`
- CLI命令：`application/command/InviteCommission.php`
- 前端分销中心：`mashangzhuan-app/pages/agent/index.vue`
- 前端收益明细：`mashangzhuan-app/pages/agent/earnings.vue`
- 前端团队列表：`mashangzhuan-app/pages/agent/teams.vue`
- 前端排行榜：`mashangzhuan-app/pages/agent/ranking.vue`

---

## 二、分佣层级结构

```
用户 A（一级邀请人 / parent_id）
  └─ 用户 B（被邀请人 / 二级邀请人 / grandparent_id）
       └─ 用户 C（被邀请人）
```

- **用户B** 邀请 **用户C** 注册时：
  - **用户B** 是用户C的 **一级邀请人（parent_id）**
  - **用户A** 是用户C的 **二级邀请人（grandparent_id）**
  - 用户C产生提现时 → B获得一级佣金，A获得二级佣金

- 系统最大支持 **2级分佣**（`invite_max_level = 2`）

---

## 三、核心流程

### 3.1 邀请绑定流程

```
新用户注册 → POST /api/invite/bind（携带邀请码）
  → InviteCommissionService::bindInvite()
  → 风控检查（RiskControlService）
  → 查找邀请人（通过 invite_code）
  → 创建 invite_relation 记录
  → 更新 user 表的 parent_id / grandparent_id
  → 更新邀请统计（一级/二级邀请人数）
  → 发放注册奖励金币
```

**注册奖励配置：**
| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `invite_register_reward` | 500 | 新用户注册奖励金币 |
| `invite_level1_reward` | 1000 | 一级邀请人获得金币 |
| `invite_level2_reward` | 500 | 二级邀请人获得金币 |

### 3.2 分佣触发流程（唯一入口：提现）

```
用户C发起提现 → WithdrawService::triggerInviteCommission()
  → InviteCommissionService::triggerWithdrawCommission()
  → 检查分佣开关（commission_enabled）
  → 查找用户C的邀请关系（parent_id, grandparent_id）
  → 计算一级佣金：提现金额 × level1_commission_rate
  → 计算二级佣金：提现金额 × level2_commission_rate
  → 创建 2 条 invite_commission_log 记录（status=0 待结算）
  → 更新 user_commission_stat（待结算金额增加）
```

**注意：** 当前分佣**仅在提现时触发**，暂未实现视频观看、红包领取等其他触发场景（`advn_invite_commission_config` 表中已预留 video/recharge/red_packet/game 配置）。

### 3.3 分佣结算流程

```
定时任务（cron）→ InviteCommissionTask::settlePendingCommission()
  → 查询所有 status=0 且超过延迟时间的分佣记录
  → 逐条调用 InviteCommissionLog::settle()
  → 通过 CoinService::addCoin() 将佣金金币发放到上级用户 balance
  → 更新 user_commission_stat（减少 pending，增加 total）
  → 记录 coin_log 流水
```

**结算延迟**：分佣创建后不会立即到账，需等待定时任务结算（延迟时间由系统配置决定，防止提现撤回导致佣金需要追回）。

### 3.4 完整数据流

```
[用户C注册] ──邀请码──→ [用户B（一级）]
                       └──→ [用户A（二级）]

[用户C提现 100元]
  → 一级佣金：100 × 10% = 10元 = 100000金币 → 用户B
  → 二级佣金：100 × 5% = 5元 = 50000金币 → 用户A

[定时任务结算]
  → 用户B balance += 100000金币
  → 用户A balance += 50000金币
```

---

## 四、分佣比例配置

### 4.1 advn_config 表中的配置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `commission_enabled` | 1 | 分佣总开关（0=关闭，1=开启） |
| `level1_commission_rate` | 10 | 一级分佣比例（百分比），即10% |
| `level2_commission_rate` | 5 | 二级分佣比例（百分比），即5% |
| `commission_min_amount` | 0 | 最低分佣门槛金额（元） |
| `invite_enabled` | 1 | 邀请功能开关 |
| `daily_invite_limit` | 50 | 每日邀请次数限制 |

### 4.2 advn_invite_commission_config 表（预留，未完全使用）

| code | level1_rate | level2_rate | min_amount | max_commission | 说明 |
|------|-------------|-------------|------------|----------------|------|
| video | 10% | 5% | ¥0 | ¥100 | 视频分佣配置（预留） |
| recharge | 5% | 2% | ¥10 | ¥200 | 充值分佣配置（预留） |
| withdraw | 1% | 0.5% | ¥0 | ¥50 | 提现分佣配置（预留） |

**注意：** 实际分佣逻辑使用的是 `advn_config` 中的 `level1_commission_rate`/`level2_commission_rate`，`advn_invite_commission_config` 表目前仅用于视频分佣场景，提现分佣走的是 config 配置。

---

## 五、金币转换比例

- **10000 金币 = 1 元**（常量 `InviteCommissionService::COIN_RATE = 10000`）
- 佣金金额（元）→ 金币：`coin_amount = commission_amount × 10000`

---

## 六、代理等级系统

系统有 **8 级代理等级**，根据累计佣金金额自动升级：

| 等级 | 名称 | 累计佣金要求 |
|------|------|-------------|
| 1 | 普通会员 | ¥0 |
| 2 | 青铜代理 | ¥100 |
| 3 | 白银代理 | ¥500 |
| 4 | 黄金代理 | ¥2,000 |
| 5 | 铂金代理 | ¥5,000 |
| 6 | 钻石代理 | ¥20,000 |
| 7 | 大师代理 | ¥50,000 |
| 8 | 王者代理 | ¥100,000 |

等级在前端 `/pages/agent/index.vue` 中根据 `total_commission` 自动判断显示，暂无后台等级特权配置。

---

## 七、数据库表结构

### 7.1 advn_invite_relation（邀请关系表）

| 字段 | 类型 | 说明 |
|------|------|------|
| user_id | int | 被邀请人ID（唯一） |
| parent_id | int | 一级邀请人ID |
| grandparent_id | int | 二级邀请人ID |
| invite_code | varchar(20) | 使用的邀请码 |
| invite_channel | varchar(50) | 邀请渠道（link/qrcode/share） |
| register_reward_status | tinyint | 注册奖励状态（0=未发放，1=已发放） |
| invite_ip | varchar(50) | 邀请IP |
| invite_device_id | varchar(100) | 邀请设备ID |

### 7.2 advn_invite_commission_log（佣金记录表）

| 字段 | 类型 | 说明 |
|------|------|------|
| order_no | varchar(32) | 佣金订单号（CM...前缀） |
| source_type | varchar(30) | 来源类型（withdraw/video/red_packet/game） |
| source_id | int | 来源ID |
| user_id | int | 触发佣金的用户（下线） |
| parent_id | int | 获得佣金的用户（上线） |
| level | tinyint | 层级（1=一级，2=二级） |
| source_amount | decimal | 来源金额（元） |
| commission_rate | decimal | 佣金比例 |
| commission_amount | decimal | 佣金金额（元） |
| coin_amount | decimal | 佣金金币数 |
| status | tinyint | 状态（0=待结算，1=已结算，2=已取消，3=已冻结） |

### 7.3 advn_user_invite_stat（用户邀请统计表）

| 字段 | 说明 |
|------|------|
| total_invite_count | 总邀请人数 |
| level1_count | 一级下线人数 |
| level2_count | 二级下线人数 |
| valid_invite_count | 有效邀请（产生过佣金） |
| new_invite_today / yesterday / week / month | 各时段新增邀请 |

### 7.4 advn_user_commission_stat（用户佣金统计表）

| 字段 | 说明 |
|------|------|
| total_commission | 累计佣金（元） |
| total_coin | 累计佣金金币 |
| level1_commission | 一级累计佣金 |
| level2_commission | 二级累计佣金 |
| withdraw_commission | 提现分佣累计 |
| today/yesterday/week/month_commission | 各时段佣金 |
| pending_commission | 待结算佣金 |
| frozen_commission | 已冻结佣金 |

### 7.5 advn_daily_commission_stat（每日佣金汇总表）

平台级每日佣金统计，包含总佣金、各来源分佣金额、分佣笔数、涉及用户数等。

### 7.6 advn_invite_relation_migration_log（邀请关系迁移日志）

后台管理员手动调整邀请关系时的审计日志（记录 old_parent_id、new_parent_id、操作管理员、原因等）。

---

## 八、API接口一览

### 8.1 前端API

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/invite/overview` | GET | 分销概览（等级、统计、钱包、邀请码） |
| `/api/invite/teamList` | GET | 团队成员列表（支持 level 筛选） |
| `/api/invite/commissionList` | GET | 佣金明细列表（支持来源/层级/月份筛选） |
| `/api/invite/ranking` | GET | 排行榜（邀请人数或佣金金额排行） |
| `/api/invite/myCode` | GET | 获取用户邀请码+分享链接 |
| `/api/invite/bind` | POST | 绑定邀请关系（无需登录） |

### 8.2 后台管理API

| 控制器 | 说明 |
|--------|------|
| `invite/Relation` | 邀请关系管理（查看、重新绑定、迁移日志） |
| `invite/Commission` | 佣金总览统计 |
| `invite/Commissionlog` | 佣金记录管理（结算、取消、冻结、批量操作） |
| `invite/Commissionstat` | 用户佣金统计（排名、趋势图、用户详情） |
| `invite/Invitestat` | 用户邀请统计（邀请人列表、钻取） |
| `invite/Statistic` | 平台级邀请+佣金总览 |

---

## 九、定时任务

通过 CLI 命令 `php think invite:commission` 管理：

| 动作 | 说明 |
|------|------|
| `settle` | 结算待处理佣金 |
| `daily` | 重置每日统计 |
| `weekly` | 重置每周统计 |
| `monthly` | 重置每月统计 |
| `clean` | 清理过期记录（90天） |
| `summary` | 生成每日佣金汇总 |
| `period` | 更新周/月累计统计 |
| `frozen` | 处理冻结佣金（30天自动取消） |
| `all` | 执行以上所有 |

---

## 十、安全机制

1. **风控集成**：邀请绑定时调用 `RiskControlService` 进行设备/IP维度的异常检测
2. **防自己邀请自己**：检查 `inviter.id != userId`
3. **防重复绑定**：`invite_relation.user_id` 有唯一索引
4. **分布式锁**：结算操作使用 Redis 分布式锁防并发
5. **事务保护**：绑定邀请关系和发放注册奖励在同一事务内
6. **管理员迁移审计**：后台手动调整邀请关系时记录完整审计日志
7. **冻结机制**：异常佣金可冻结，30天后自动取消

---

## 十一、当前存在的问题与建议

### 11.1 配置重复问题

`advn_config` 和 `advn_invite_commission_config` 存在重复的佣金比例配置：
- `advn_config.level1_commission_rate` = 10（实际使用的）
- `advn_config.level2_commission_rate` = 5（实际使用的）
- `advn_invite_commission_config` 中 withdraw/video/recharge 配置（预留未完全使用）

**建议**：统一使用 `advn_invite_commission_config` 表管理佣金配置，使不同来源（提现/视频/充值）有独立的佣金比例。

### 11.2 分佣触发场景单一

当前**仅在提现时触发分佣**。`invite_commission_config` 表中已预留 video、recharge、red_packet、game 配置，但 `InviteCommissionService` 中仅实现了 `triggerWithdrawCommission()`。

**建议**：在广告收益、视频观看等场景也触发分佣，提升邀请裂变动力。

### 11.3 结算延迟

分佣创建后需等定时任务结算才到账，期间用户看不到佣金变化，可能影响体验。

**建议**：可考虑小金额实时结算，大金额延迟结算的分级策略。
