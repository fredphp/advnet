# 邀请分佣系统 - 完整设计文档

## 1. 系统概述

### 1.1 核心功能

| 功能 | 说明 |
|------|------|
| 邀请绑定 | 用户通过邀请码绑定上下级关系，支持二级 |
| 分佣结算 | 下级产生收益时，上级自动获得佣金 |
| 比例配置 | 后台可配置各级分佣比例 |
| 多级扩展 | 数据结构支持未来扩展至3级 |
| 邀请统计 | 统计邀请人数、有效邀请数 |
| 收益统计 | 按来源分类统计佣金收益 |

### 1.2 分佣来源

| 来源类型 | 触发时机 | 默认比例 |
|----------|----------|----------|
| withdraw | 下级提现成功 | 一级20%，二级10% |
| video | 下级看视频得金币 | 一级1%，二级0.5% |
| red_packet | 下级抢红包得金币 | 一级1%，二级0.5% |
| game | 下级玩游戏得金币 | 一级1%，二级0.5% |

### 1.3 金币换算

```
10000 金币 = 1 元人民币
```

---

## 2. 数据库设计

### 2.1 表结构清单

| 表名 | 说明 |
|------|------|
| advn_invite_commission_config | 分佣配置表 |
| advn_invite_commission_log | 分佣记录表 |
| advn_user_invite_stat | 用户邀请统计表 |
| advn_user_commission_stat | 用户佣金统计表 |
| advn_daily_commission_stat | 每日佣金统计表 |

### 2.2 关系图

```
┌─────────────────┐     ┌─────────────────┐
│     User        │     │ InviteRelation  │
│─────────────────│     │─────────────────│
│ id              │◄────│ user_id         │
│ invite_code     │     │ parent_id       │──────┐
│ parent_id       │     │ grandparent_id  │──┐   │
│ grandparent_id  │     └─────────────────┘  │   │
└─────────────────┘                          │   │
         ▲                                   │   │
         │                                   │   │
         │                                   │   │
┌─────────────────┐     ┌─────────────────┐  │   │
│UserInviteStat   │     │UserCommissionStat│ │   │
│─────────────────│     │─────────────────│  │   │
│ user_id         │────►│ user_id         │◄─┘   │
│ total_invite    │     │ total_commission│      │
│ level1_count    │     │ withdraw_comm.. │      │
│ level2_count    │     │ video_commission│      │
└─────────────────┘     └─────────────────┘      │
                                                │
┌─────────────────┐                              │
│CommissionLog    │                              │
│─────────────────│                              │
│ user_id         │──────────────────────────────┘
│ parent_id       │──────────────────────────────┘
│ source_type     │
│ commission_amt  │
└─────────────────┘
```

---

## 3. 核心结算逻辑

### 3.1 分佣流程

```
┌──────────────────────────────────────────────────────────────────┐
│                        分佣结算流程                               │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. 触发事件 (提现/视频/红包/游戏)                               │
│          │                                                       │
│          ▼                                                       │
│  2. 检查邀请关系                                                  │
│          │                                                       │
│     [有上级吗?]──否──► 结束(不分佣)                              │
│          │是                                                     │
│          ▼                                                       │
│  3. 获取分佣配置                                                  │
│          │                                                       │
│     [配置启用?]──否──► 结束                                      │
│          │是                                                     │
│          ▼                                                       │
│  4. 检查触发门槛                                                  │
│          │                                                       │
│     [金额达标?]──否──► 结束                                      │
│          │是                                                     │
│          ▼                                                       │
│  5. 计算佣金金额                                                  │
│          │                                                       │
│          ▼                                                       │
│  6. 创建分佣记录(状态:待结算)                                     │
│          │                                                       │
│          ▼                                                       │
│  7. 延迟结算 (可配置延迟时间)                                     │
│          │                                                       │
│          ▼                                                       │
│  8. 发放金币到上级账户                                            │
│          │                                                       │
│          ▼                                                       │
│  9. 更新统计数据                                                  │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

### 3.2 佣金计算公式

```php
// 比例计算
$commission = $sourceAmount * $rate;

// 固定金额
$commission = $fixed;

// 比例+固定
$commission = $sourceAmount * $rate + $fixed;

// 最大限制
if ($maxCommission > 0 && $commission > $maxCommission) {
    $commission = $maxCommission;
}

// 转金币
$coinAmount = $commission * 10000;
```

### 3.3 核心服务方法

```php
// InviteCommissionService 主要方法

// 绑定邀请关系
bindInvite($userId, $inviteCode, $options)

// 提现触发分佣
triggerWithdrawCommission($userId, $cashAmount, $orderNo, $withdrawId)

// 视频收益触发分佣
triggerVideoCommission($userId, $coinAmount, $videoId, $watchRecordId)

// 红包收益触发分佣
triggerRedPacketCommission($userId, $coinAmount, $packetId, $recordId)

// 游戏收益触发分佣
triggerGameCommission($userId, $coinAmount, $gameRecordId)

// 结算分佣
settleCommission($logId)

// 获取邀请统计
getInviteOverview($userId)
```

---

## 4. 定时任务设计

### 4.1 任务列表

| 任务 | 执行频率 | 说明 |
|------|----------|------|
| settlePendingCommission | 每分钟 | 结算待处理的分佣记录 |
| resetDailyStats | 每日0点 | 重置每日统计 |
| resetWeeklyStats | 每周一0点 | 重置每周统计 |
| resetMonthlyStats | 每月1日0点 | 重置每月统计 |
| summaryDailyCommission | 每日凌晨 | 汇总每日佣金统计 |
| cleanExpiredLogs | 每周 | 清理90天前的记录 |

### 4.2 CLI 命令

```bash
# 结算待处理分佣
php think invite:commission --action=settle

# 每日统计重置
php think invite:commission --action=daily

# 每周统计重置
php think invite:commission --action=weekly

# 清理过期记录
php think invite:commission --action=clean

# 汇总每日统计
php think invite:commission --action=summary

# 执行所有任务
php think invite:commission --action=all
```

### 4.3 Crontab 配置

```bash
# 每分钟结算分佣
* * * * * cd /path/to/project && php think invite:commission -a settle >> /dev/null 2>&1

# 每日0点5分重置统计
5 0 * * * cd /path/to/project && php think invite:commission -a daily >> /dev/null 2>&1

# 每周一0点10分重置周统计
10 0 * * 1 cd /path/to/project && php think invite:commission -a weekly >> /dev/null 2>&1

# 每月1日0点15分重置月统计
15 0 1 * * cd /path/to/project && php think invite:commission -a monthly >> /dev/null 2>&1
```

---

## 5. API 接口设计

### 5.1 客户端接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/invite/bind | POST | 绑定邀请关系 |
| /api/invite/overview | GET | 获取邀请统计概览 |
| /api/invite/myCode | GET | 获取我的邀请码 |
| /api/invite/list | GET | 获取邀请列表 |
| /api/invite/commissionList | GET | 获取佣金明细 |
| /api/invite/chart | GET | 获取图表数据 |
| /api/invite/ranking | GET | 获取邀请排行 |

### 5.2 后台接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /admin/invite/commissionconfig | GET | 分佣配置列表 |
| /admin/invite/commissionconfig/add | POST | 添加配置 |
| /admin/invite/commissionconfig/edit | POST | 编辑配置 |
| /admin/invite/commissionlog | GET | 分佣记录列表 |
| /admin/invite/commissionlog/settle | POST | 手动结算 |
| /admin/invite/commissionlog/cancel | POST | 取消分佣 |
| /admin/invite/invitestat | GET | 邀请统计列表 |
| /admin/invite/commissionstat | GET | 佣金统计列表 |

---

## 6. 文件清单

### 6.1 数据库

| 文件 | 说明 |
|------|------|
| /sql/invite_commission.sql | 分佣系统建表SQL |

### 6.2 模型

| 文件 | 说明 |
|------|------|
| /application/common/model/InviteCommissionConfig.php | 分佣配置模型 |
| /application/common/model/InviteCommissionLog.php | 分佣记录模型 |
| /application/common/model/UserInviteStat.php | 用户邀请统计模型 |
| /application/common/model/UserCommissionStat.php | 用户佣金统计模型 |
| /application/common/model/DailyCommissionStat.php | 每日佣金统计模型 |

### 6.3 服务类

| 文件 | 说明 |
|------|------|
| /application/common/library/InviteCommissionService.php | 核心分佣服务 |
| /application/common/library/InviteCommissionTask.php | 定时任务服务 |

### 6.4 控制器

| 文件 | 说明 |
|------|------|
| /application/api/controller/Invite.php | 客户端API |
| /application/admin/controller/invite/Commissionconfig.php | 配置管理 |
| /application/admin/controller/invite/Commissionlog.php | 记录管理 |
| /application/admin/controller/invite/Invitestat.php | 邀请统计 |
| /application/admin/controller/invite/Commissionstat.php | 佣金统计 |

### 6.5 命令

| 文件 | 说明 |
|------|------|
| /application/command/InviteCommission.php | CLI定时任务命令 |

### 6.6 前端

| 文件 | 说明 |
|------|------|
| /web/api/invite.js | UniApp API封装 |
| /docs/INVITE_FRONTEND_DESIGN.md | 前端数据结构设计 |

---

## 7. 使用示例

### 7.1 绑定邀请关系

```php
$service = new InviteCommissionService();
$result = $service->bindInvite($userId, 'ABCD001234', ['channel' => 'link']);
```

### 7.2 提现触发分佣

```php
// 提现成功后调用
$service = new InviteCommissionService();
$result = $service->triggerWithdrawCommission($userId, 50.00, 'WD20240101001', 123);
```

### 7.3 视频收益触发分佣

```php
// 视频奖励发放后调用
$service = new InviteCommissionService();
$result = $service->triggerVideoCommission($userId, 100, $videoId, $watchRecordId);
```

### 7.4 手动结算

```php
$service = new InviteCommissionService();
$result = $service->settleCommission($logId);
```

---

## 8. 扩展说明

### 8.1 扩展到三级

1. 修改数据库表，添加 `great_grandparent_id` 字段
2. 修改 `InviteRelation` 模型，添加三级关系处理
3. 修改 `InviteCommissionService`，添加三级分佣逻辑
4. 修改配置表，添加 `level3_rate` 和 `level3_fixed` 字段

### 8.2 新增分佣来源

1. 在 `advn_invite_commission_config` 表添加新配置
2. 在服务中添加新的触发方法
3. 在统计中添加新来源字段

---

## 9. 注意事项

1. **并发控制**: 使用分布式锁防止重复分佣
2. **事务处理**: 分佣操作在事务中执行
3. **延迟结算**: 可配置延迟时间防止撤单
4. **金额限制**: 支持最低触发金额和单笔上限
5. **数据统计**: 定时任务定期汇总统计数据
