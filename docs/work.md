# 马上赚（advnet/AdNetwork）——项目流程逻辑文档

> **广告网络管理与用户激励平台** · ThinkPHP 5.0 + FastAdmin 1.6.1 + uni-app (Vue 2 + uView UI)  
> **目标读者**：新加入项目的后端/前端开发者，需要快速理解全平台业务逻辑与数据流转。

---

## 目录

- [一、项目概述与技术栈](#一项目概述与技术栈)
- [二、目录结构与模块划分](#二目录结构与模块划分)
- [三、核心业务流程详解](#三核心业务流程详解)
  - [3.1 用户系统](#31-用户系统)
  - [3.2 金币系统](#32-金币系统-coinservice)
  - [3.3 提现系统](#33-提现系统-withdrawservice)
  - [3.4 广告收益系统](#34-广告收益系统-adincomeservice)
  - [3.5 红包系统](#35-红包系统-redpacketservice--adredpacket)
  - [3.6 视频奖励系统](#36-视频奖励系统-videorewardservice)
  - [3.7 邀请分佣系统](#37-邀请分佣系统-invitecommissionservice)
  - [3.8 签到系统](#38-签到系统)
  - [3.9 风控系统](#39-风控系统-riskcontrolservice--autobanservice)
  - [3.10 消息系统](#310-消息系统)
- [四、数据架构与分表策略](#四数据架构与分表策略)
- [五、定时任务调度](#五定时任务调度)
- [六、前端页面流转](#六前端页面流转)
- [七、后台管理系统（27 个模块）](#七后台管理系统27-个模块)
- [八、关键设计决策与约定](#八关键设计决策与约定)

---

## 一、项目概述与技术栈

| 层级 | 技术选型 | 说明 |
|------|---------|------|
| **后端框架** | ThinkPHP 5.0 + FastAdmin 1.6.1 | MVC架构，多模块设计 |
| **移动端** | uni-app (Vue 2 + uView UI) | 位于 `mashangzhuan-app/` 目录 |
| **数据库** | MySQL 8.0+ | 表前缀 `advn_` |
| **缓存/队列** | Redis | 分布式锁、限流计数、Session |
| **数据库表前缀** | `advn_` | 所有业务表均以此开头 |

### 系统定位

广告网络管理系统是一个综合性**广告分发与用户激励平台**。系统支持视频观看奖励、红包任务、邀请分佣、金币提现等核心功能，具备完善的风控体系和数据统计分析能力。

**核心业务闭环**：

```
用户观看广告/完成视频任务 → 获得金币奖励 → 金币累积 → 提现到账
              ↑                                              |
              └──── 邀请好友获得分佣 ←──── 分佣结算 ←───────┘
```

---

## 二、目录结构与模块划分

### 后端目录结构

```
advnet/
├── application/
│   ├── admin/          # 后台管理模块（管理员操作界面）
│   ├── api/            # API接口模块（移动端调用入口）
│   ├── index/          # 前台模块（Web端入口）
│   ├── member/         # 会员模块（会员中心）
│   ├── video/          # 视频模块（短剧管理）
│   ├── common/
│   │   ├── controller/  # 基类控制器（Backend/Frontend/Api）
│   │   ├── library/     # ★ 核心业务服务层
│   │   │   ├── CoinService.php
│   │   │   ├── WithdrawService.php
│   │   │   ├── AdIncomeService.php
│   │   │   ├── RedPacketService.php
│   │   │   ├── VideoRewardService.php
│   │   │   ├── InviteCommissionService.php
│   │   │   ├── RiskControlService.php
│   │   │   ├── AutoBanService.php
│   │   │   ├── AntiCheatService.php
│   │   │   ├── DeviceFingerprintService.php
│   │   │   ├── SplitTableService.php
│   │   │   └── ...
│   │   ├── model/       # ★ 数据模型层
│   │   ├── validate/    # 数据验证器
│   │   ├── behavior/    # 行为钩子
│   │   └── service/     # 外部服务（推送等）
│   └── command/         # CLI定时任务
│       ├── AdSettle.php          # 广告结算
│       ├── DataMigration.php     # 数据归档迁移
│       ├── AutoPush.php          # 自动推送
│       └── Websocket.php         # WebSocket服务
├── mashangzhuan-app/   # ★ 前端 uni-app 项目
│   ├── pages/
│   │   ├── index/      # 首页、红包页、游戏页
│   │   ├── login/      # 登录、注册、微信登录
│   │   ├── signin/     # 签到、排行榜
│   │   ├── my/         # 个人中心、钱包、提现
│   │   ├── agent/      # 邀请中心
│   │   ├── game/       # 砸蛋、转盘
│   │   ├── ad/         # 广告观看页
│   │   └── ...
│   ├── components/     # 自定义组件
│   └── common/         # 公共工具（HTTP拦截、加密、WebSocket）
└── sql/                # 数据库脚本与迁移
```

### 模块职责

| 模块 | 路径 | 职责 |
|------|------|------|
| `admin` | `application/admin/` | 后台管理系统，管理员操作所有业务数据 |
| `api` | `application/api/` | 移动端 API 入口，所有 App 请求由此进入 |
| `index` | `application/index/` | 前台 Web 入口（PC端/H5） |
| `member` | `application/member/` | 会员中心相关功能 |
| `video` | `application/video/` | 短剧视频管理与播放 |

### 核心代码分层

```
Controller → Library(Service) → Model → MySQL
                  ↓
               Redis (缓存/锁/计数器)
```

- **Controller 层**：参数校验、调用 Service、返回 JSON
- **Library 层**：业务逻辑编排、事务管理、并发控制
- **Model 层**：数据访问、分表路由、基础 CRUD
- **Redis**：分布式锁、限流计数、防重、冻结余额缓存

---

## 三、核心业务流程详解

### 3.1 用户系统

#### 3.1.1 注册/登录方式

| 方式 | 入口 | 流程 |
|------|------|------|
| **账号密码** | `/api/user/login` | 用户名/手机号 + 密码 → 验证 → 签发Token |
| **手机验证码** | `/api/sms/send` + `/api/user/mobilelogin` | 发送短信 → 验证码校验 → 自动注册/登录 |
| **微信OAuth** | `/api/wechat/*` | 微信授权 → 获取openid/unionid → 绑定/注册 → 签发Token |

#### 3.1.2 微信三端登录

```
┌─────────────────────────────────────────────────────────┐
│                    微信三端登录统一流程                     │
├──────────┬──────────────────────────────────────────────┤
│  App端   │ wx.login() → code → 后端换取openid/unionid   │
│  小程序   │ wx.login() → code → 后端换取openid/unionid   │
│  公众号   │ OAuth2.0 授权 → code → 后端换取openid/unionid │
└──────────┴──────────────────────────────────────────────┘
                        ↓
              WechatService::loginByCode()
                        ↓
         ┌──────────────┴──────────────┐
         │ unionid是否存在？              │
         └──────┬───────┬───────────────┘
           是(已绑定)    否(新用户)
                ↓          ↓
          更新登录信息   创建用户 + 绑定关系
                ↓          ↓
          ┌────┴──────────┘
          ↓
     签发 Token（MySQL驱动）
```

**关键点**：unionid 用于跨端统一用户身份；同一 unionid 绑定多个 openid（App/小程序/公众号各一个）。

#### 3.1.3 Token 认证机制

```
请求 → RiskControlMiddleware → Token校验（MySQL驱动）
                                    ↓
                            advn_user_token 表
                          (user_id, token, expiretime)
                                    ↓
                            有效 → 放行
                            过期 → 返回401
```

- Token 驱动：`application/common/library/token/driver/Mysql.php`
- Token 存储于 MySQL（`advn_user_token` 表），支持 Redis 驱动切换
- 每次请求验证 Token 有效性，过期自动清理

#### 3.1.4 设备指纹识别

```
客户端上报 → DeviceFingerprintService
                   ↓
           ┌───────┴───────┐
           │ 提取特征：      │
           │ - device_id    │
           │ - platform     │
           │ - OS版本       │
           │ - app版本      │
           │ - 屏幕分辨率    │
           └───────┬───────┘
                   ↓
         advn_device_fingerprint 表
                   ↓
         关联用户 → 风控评估
```

设备指纹用于：
- **多设备检测**：同一用户多设备登录预警
- **风控评分**：设备维度风险判定
- **反作弊**：设备维度频率限制

---

### 3.2 金币系统 (CoinService)

> 核心服务：`application/common/library/CoinService.php`  
> 数据模型：`application/common/model/CoinAccount.php` / `CoinLog.php`

#### 3.2.1 金币账户结构

```
advn_coin_account
├── user_id          # 用户ID
├── balance          # 可用余额
├── frozen_balance   # 冻结余额（待结算/待提现）
└── version          # 乐观锁版本号
```

#### 3.2.2 金币类型枚举

| 类型常量 | 说明 | 方向 |
|---------|------|------|
| `video_watch` | 视频观看奖励 | 收入 |
| `task_reward` | 任务奖励 | 收入 |
| `sign_in` | 签到奖励 | 收入 |
| `invite` | 邀请奖励 | 收入 |
| `commission` | 分佣收入 | 收入 |
| `red_packet` | 红包收入 | 收入 |
| `game_reward` | 游戏奖励 | 收入 |
| `withdraw` | 提现支出 | 支出 |
| `ad_reward` | 广告奖励 | 收入 |
| `ad_freeze` | 广告冻结 | 冻结 |
| `system` | 系统调整 | 收/支 |

#### 3.2.3 金币操作核心流程

```
调用方（如 VideoRewardService）
        ↓
CoinService::addCoins($userId, $amount, $type, $remark)
        ↓
┌───────────────────────────────────┐
│ 1. 获取 Redis 分布式锁             │
│    key: coin:lock:{user_id}       │
│    TTL: 10秒                       │
├───────────────────────────────────┤
│ 2. 检查每日上限                     │
│    key: coin:daily:{user_id}:{type}│
│    TTL: 当日 23:59:59             │
├───────────────────────────────────┤
│ 3. 开启数据库事务                   │
├───────────────────────────────────┤
│ 4. 乐观锁更新 coin_account         │
│    UPDATE ... SET balance = ...    │
│    WHERE version = $oldVersion     │
│    失败则重试（最多3次）             │
├───────────────────────────────────┤
│ 5. 写入分表 coin_log_YYYYMM       │
├───────────────────────────────────┤
│ 6. 更新每日 Redis 计数器           │
├───────────────────────────────────┤
│ 7. 提交事务 → 释放锁               │
└───────────────────────────────────┘
```

#### 3.2.4 汇率配置

```
默认汇率：10000 金币 = 1 元人民币
配置位置：advn_config 表（key = 'coin_exchange_rate'）
```

#### 3.2.5 金币流水分表

```
coin_log_202501   ← 2025年1月流水
coin_log_202502   ← 2025年2月流水
coin_log_202503   ← 2025年3月流水
...
```

- 按月分表，`SplitTableService` 自动创建
- 查询时根据时间范围路由到对应分表

---

### 3.3 提现系统 (WithdrawService)

> 核心服务：`application/common/library/WithdrawService.php`  
> 数据模型：`application/common/model/WithdrawOrder.php` / `WithdrawOrderSplit.php`  
> API控制器：`application/api/controller/Withdraw.php`

#### 3.3.1 提现状态流转

```
                    ┌──────────────────────────┐
                    │   Pending (0) 待审核      │
                    │   用户提交提现申请          │
                    └────────┬─────────────────┘
                             ↓
                    ┌────────┴─────────────────┐
                    │   Approved (1) 已通过     │
                    │   管理员审核通过            │
                    └────────┬─────────────────┘
                             ↓
                    ┌────────┴─────────────────┐
                    │ Transferring (2) 打款中    │
                    │   系统发起打款              │
                    └──┬───────────┬───────────┘
                       ↓           ↓
            ┌──────────┴──┐  ┌────┴───────────┐
            │ Success (3) │  │ Failed (5)     │
            │ 打款成功     │  │ 打款失败        │
            └─────────────┘  └────┬───────────┘
                                  ↓
                         ┌────────┴──────────┐
                         │ 退回用户金币余额     │
                         └───────────────────┘

        ┌────────────────┐
        │ Rejected (4)   │ ← 审核阶段拒绝
        │ Canceled (6)   │ ← 用户取消 / 系统取消
        └────────────────┘
```

#### 3.3.2 提现申请流程

```
用户发起提现 /api/withdraw/apply
        ↓
┌──────────────────────────────────────┐
│ 1. 参数校验                           │
│    - 提现金额 ≥ 最低提现额             │
│    - 可用余额 ≥ 提现金额               │
│    - 提现渠道（微信/支付宝/银行卡）      │
├──────────────────────────────────────┤
│ 2. 风控检查（RiskControlService）      │
│    - IP提现频率限制                    │
│    - 设备提现频率限制                  │
│    - 金额异常检测                      │
│    - 新用户提现限制                    │
│    - 账号风险评分判定                  │
├──────────────────────────────────────┤
│ 3. 冻结金币（乐观锁）                  │
│    balance -= amount                 │
│    frozen_balance += amount          │
├──────────────────────────────────────┤
│ 4. 创建提现订单（分表）                │
│    → withdraw_order_YYYY            │
├──────────────────────────────────────┤
│ 5. 触发邀请分佣（延迟结算）            │
│    → InviteCommissionService         │
└──────────────────────────────────────┘
```

#### 3.3.3 管理员审核流程

```
后台 → withdraw/order → 查看/审核
        ↓
┌──────────────────────┐
│ 通过 → status = 1    │ → 系统发起打款 → status = 2 → 成功/失败
│ 拒绝 → status = 4    │ → 退回冻结金币到 balance
└──────────────────────┘
```

#### 3.3.4 提现分表

```
withdraw_order_2025   ← 2025年所有提现订单
withdraw_order_2026   ← 2026年所有提现订单
```

- 按年分表，通过 `WithdrawOrderSplit` 模型路由

---

### 3.4 广告收益系统 (AdIncomeService)

> 核心服务：`application/common/library/AdIncomeService.php`  
> 数据模型：`application/common/model/AdIncomeLog.php` / `AdIncomeLogSplit.php`  
> API控制器：`application/api/controller/Ad.php` / `AdRedPacket.php`

#### 3.4.1 广告回调机制

```
┌───────────────────────────────────────────────────────────┐
│                    DCloud 广告回调                         │
├───────────────────────┬───────────────────────────────────┤
│   serverNotify        │   callback                        │
│   （服务端可信回调）    │   （客户端记录）                    │
├───────────────────────┼───────────────────────────────────┤
│ DCloud服务器直接通知   │ 用户端SDK触发                      │
│ → 用于金币结算         │ → 用于辅助记录                     │
│ → transaction_id 去重  │ → 观看时长记录                     │
└───────────────────────┴───────────────────────────────────┘
```

#### 3.4.2 广告收益结算流程

```
DCloud serverNotify → /api/ad/serverNotify
        ↓
┌──────────────────────────────────────┐
│ 1. 验签（确保来源可信）                │
├──────────────────────────────────────┤
│ 2. transaction_id 去重               │
│    Redis key: ad:txn:{transaction_id}│
├──────────────────────────────────────┤
│ 3. 获取 Redis 分布式锁               │
├──────────────────────────────────────┤
│ 4. 计算用户收益                       │
│    用户收益 = 广告收入 × (1 - 平台抽成) │
│    平台抽成默认 30%                   │
├──────────────────────────────────────┤
│ 5. 写入分表 ad_income_log_YYYYMM     │
├──────────────────────────────────────┤
│ 6. 冻结余额模式：                     │
│    → 先记入 frozen_balance            │
│    → 达到阈值后生成通知红包            │
├──────────────────────────────────────┤
│ 7. 检查冻结余额阈值                   │
│    → 达到阈值 → 生成 AdRedPacket      │
│    → 未达到 → 继续累积                │
├──────────────────────────────────────┤
│ 8. 更新每日观看计数器                 │
│    advn_ad_view_counter 表            │
└──────────────────────────────────────┘
```

#### 3.4.3 冻结余额 → 红包转化

```
广告收益 → frozen_balance 累积
                ↓
        定时任务 AdSettle（每30分钟）
                ↓
        检查 frozen_balance ≥ 通知阈值
                ↓
        ┌───────┴───────┐
        │ 是            │ 否
        ↓               ↓
  生成 AdRedPacket   等待下次检查
  → 推送通知用户      → 继续累积
  → 冻结→可用
```

#### 3.4.4 每日观看奖励

```
advn_ad_view_counter
├── user_id
├── view_date         # 日期
├── view_count        # 当日观看次数
├── reward_count      # 已奖励次数
└── last_reward_time  # 上次奖励时间
```

---

### 3.5 红包系统 (RedPacketService + AdRedPacket)

> 核心服务：`application/common/library/RedPacketService.php` / `RedPacketClickService.php`  
> 数据模型：`application/common/model/RedPacketTask.php` / `RedPacketRecord.php` / `AdRedPacket.php` / `UserRedPacketAccumulate.php`  
> API控制器：`application/api/controller/RedPacket.php` / `AdRedPacket.php`

#### 3.5.1 红包类型总览

```
┌─────────────────────────────────────────────────────┐
│                    红包体系                           │
├─────────────────────────────┬───────────────────────┤
│      任务型红包               │    累积型红包          │
│  (RedPacketTask)            │  (UserRedPacket       │
│                              │   Accumulate)         │
│  - download  下载类          │  Redis存储             │
│  - miniapp   小程序类        │  2小时TTL              │
│  - chat      聊天类          │  达到阈值自动生成       │
│  - adv       广告类          │  (广告冻结余额转化)     │
│  - video     视频类          │                       │
├─────────────────────────────┼───────────────────────┤
│      广告通知红包             │                       │
│  (AdRedPacket)              │                       │
│  冻结余额达到阈值自动生成     │                       │
└─────────────────────────────┴───────────────────────┘
```

#### 3.5.2 任务型红包领取流程

```
用户浏览红包列表 /api/redpacket/index
        ↓
选择红包任务 → 查看任务要求
        ↓
完成任务（下载/打开小程序/聊天等）
        ↓
┌──────────────────────────────────────┐
│ 1. 验证任务完成条件                   │
├──────────────────────────────────────┤
│ 2. 防作弊检查                        │
│    - IP限制：50次/天                  │
│    - 设备限制：20次/天                │
│    - Redis计数器：                    │
│      rp:ip:{date}:{ip}              │
│      rp:device:{date}:{device_id}    │
├──────────────────────────────────────┤
│ 3. 随机红包金额                       │
│    min ~ max（后台配置）              │
├──────────────────────────────────────┤
│ 4. 增加金币（CoinService）           │
├──────────────────────────────────────┤
│ 5. 写入领取记录                       │
│    → red_packet_record               │
│    → task_participation_YYYY         │
├──────────────────────────────────────┤
│ 6. 更新累积金额（Redis）              │
│    key: rp:acc:{user_id}            │
│    TTL: 2小时                         │
├──────────────────────────────────────┤
│ 7. 检查累积金额是否达到阈值           │
│    → 达到 → 生成累积型红包            │
│    → 领取额外奖励                     │
└──────────────────────────────────────┘
```

#### 3.5.3 累积型红包机制

```
用户每次领取任务红包
        ↓
Redis 累加: rp:acc:{user_id} += 金额
        ↓
检查 rp:acc:{user_id} ≥ 累积阈值？
        ↓
┌───────┴───────┐
│ 否           │ 是
↓              ↓
继续累积      生成累积红包通知
(2h TTL)      → 用户领取额外奖励
              → 清零累积计数
```

---

### 3.6 视频奖励系统 (VideoRewardService)

> 核心服务：`application/common/library/VideoRewardService.php` / `VideoAntiCheatService.php`  
> 数据模型：`application/common/model/VideoWatchRecord.php` / `VideoRewardRule.php` / `VideoCollection.php` / `VideoCollectionItem.php`  
> API控制器：`application/api/controller/VideoReward.php`

#### 3.6.1 视频观看 → 奖励流程

```
用户打开短剧 → 选择剧集 → 开始观看
        ↓
前端定时上报观看进度 /api/video_reward/report
        ↓
后端记录 VideoWatchRecord
        ↓
前端请求检查奖励 /api/video_reward/check
        ↓
┌──────────────────────────────────────┐
│ 1. 查询视频奖励规则                    │
│    advn_video_reward_rule            │
├──────────────────────────────────────┤
│ 2. 检查是否满足奖励条件：              │
│    ✓ 完成率 ≥ 95%                    │
│    ✓ 观看时长 ≥ N秒（规则配置）        │
│    ✓ 合集观看 ≥ N集（规则配置）        │
├──────────────────────────────────────┤
│ 3. 检查每日上限（默认50次/天）         │
├──────────────────────────────────────┤
│ 4. 反作弊检测（VideoAntiCheatService）│
│    - 观看速度异常                     │
│    - 频繁切换视频                     │
│    - 设指纹异常                      │
├──────────────────────────────────────┤
│ 5. 发放金币奖励（CoinService）        │
├──────────────────────────────────────┤
│ 6. 更新观看记录状态                   │
│    VideoWatchRecord.rewarded = true  │
└──────────────────────────────────────┘
```

#### 3.6.2 奖励规则配置

```
advn_video_reward_rule
├── video_id / collection_id  # 关联视频或合集
├── min_completion_rate       # 最低完成率（默认95%）
├── min_watch_duration        # 最低观看时长（秒）
├── required_episodes         # 需观看集数（合集模式）
├── coin_reward               # 奖励金币数量
├── daily_limit               # 每日上限
└── status                    # 规则状态（启用/禁用）
```

#### 3.6.3 短剧分类体系

```
首页 → 短剧浏览
├── 都市
├── 悬疑
├── 现言（现代言情）
├── 古言（古代言情）
├── 甜宠
├── 战争
└── ...
```

---

### 3.7 邀请分佣系统 (InviteCommissionService)

> 核心服务：`application/common/library/InviteCommissionService.php` / `InviteCommissionTask.php`  
> 数据模型：`application/common/model/InviteRelation.php` / `InviteCommissionLog.php` / `UserInviteStat.php` / `UserCommissionStat.php` / `DailyCommissionStat.php`  
> API控制器：`application/api/controller/Invite.php`  
> 定时任务：`application/command/InviteCommission.php`

#### 3.7.1 两级分佣体系

```
        邀请人A（L0）
        /            \
   被邀请人B(L1)    被邀请人C(L1)
      |                  |
   被邀请人D(L2)      被邀请人E(L2)

分佣规则：
- L1（直接邀请）：被邀请人收益 × 10%
- L2（间接邀请）：被邀请人收益 × 5%
```

#### 3.7.2 分佣来源类型

| 类型 | 说明 | 触发时机 |
|------|------|---------|
| `register_reward` | 注册奖励 | 新用户注册完成 |
| `withdraw` | 提现分佣 | 被邀请人提现成功 |
| `red_packet` | 红包分佣 | 被邀请人领取红包 |
| `game` | 游戏分佣 | 被邀请人游戏奖励 |
| `video` | 视频分佣 | 被邀请人视频奖励 |
| `ad` | 广告分佣 | 被邀请人广告收益 |

#### 3.7.3 分佣结算流程

```
被邀请人产生收益（如提现）
        ↓
InviteCommissionService::createCommission()
        ↓
┌──────────────────────────────────────┐
│ 1. 查询邀请关系链                     │
│    invite_relation 表                 │
│    B → A (L1), D → B → A (L2)       │
├──────────────────────────────────────┤
│ 2. 计算分佣金额                       │
│    L1 = 金额 × 10%                   │
│    L2 = 金额 × 5%                    │
├──────────────────────────────────────┤
│ 3. 冻结分佣（延迟结算）                │
│    → invite_commission_log           │
│    status = 'frozen'                 │
├──────────────────────────────────────┤
│ 4. 定时任务结算（每5分钟）             │
│    InviteCommission 命令             │
│    → frozen → settled                │
│    → 增加邀请人金币                   │
└──────────────────────────────────────┘
```

#### 3.7.4 等级体系

```
等级名称     所需积分      分佣加成
─────────────────────────────────
普通         0            基础
青铜         100          +2%
白银         500          +5%
黄金         2,000        +10%
铂金         5,000        +15%
钻石         10,000       +20%
星耀         50,000       +30%
王者         100,000      +50%
```

#### 3.7.5 统计重置周期

```
┌─────────────────────────────────────────┐
│          统计重置调度                      │
├────────────┬────────────────────────────┤
│   每日重置  │ 每天 0:00                   │
│            │ → UserDailyRewardStat       │
│            │ → DailyCommissionStat       │
├────────────┼────────────────────────────┤
│   每周重置  │ 每周一 0:00                  │
│            │ → UserCommissionStat.week   │
├────────────┼────────────────────────────┤
│   每月重置  │ 每月1号 0:00                 │
│            │ → UserCommissionStat.month  │
└────────────┴────────────────────────────┘
```

---

### 3.8 签到系统

> API控制器：`application/api/controller/Signin.php`  
> 后台管理：`application/admin/controller/signin/Config.php`  
> 后台管理：`application/admin/controller/signin/Config.php`

#### 3.8.1 签到流程

```
用户点击签到 /api/signin/sign
        ↓
┌──────────────────────────────────────┐
│ 1. 检查今日是否已签到                  │
│    Redis key: signin:{user_id}:{date} │
├──────────────────────────────────────┤
│ 2. 检查连续签到天数                    │
│    → 连续签到 N 天                    │
├──────────────────────────────────────┤
│ 3. 计算签到奖励（递增）                │
│    Day1: 100金币                      │
│    Day2: 150金币                      │
│    Day3: 200金币                      │
│    ...递增...                         │
├──────────────────────────────────────┤
│ 4. 发放金币（CoinService）            │
├──────────────────────────────────────┤
│ 5. 更新签到记录                       │
└──────────────────────────────────────┘
```

#### 3.8.2 补签机制

```
用户查看签到日历 → 发现漏签
        ↓
点击补签（消耗金币）
        ↓
┌────────────────────────────────┐
│ 1. 检查补签日期（仅限近7天）     │
│ 2. 检查金币余额 ≥ 补签费用       │
│ 3. 扣除金币                     │
│ 4. 补签成功 → 更新连续签到天数   │
└────────────────────────────────┘
```

#### 3.8.3 签到排行榜

```
/api/signin/ranking
        ↓
Redis Sorted Set: signin:ranking:{date}
        ↓
按连续签到天数排序 → 返回 Top N
```

---

### 3.9 风控系统 (RiskControlService + AutoBanService)

> 核心服务：`application/common/library/RiskControlService.php` / `AutoBanService.php` / `AntiCheatService.php` / `TaskAntiCheatService.php` / `VideoAntiCheatService.php` / `DeviceFingerprintService.php`  
> 中间件：`application/api/middleware/RiskControlMiddleware.php`  
> 数据模型：`application/common/model/UserRiskScore.php` / `RiskRule.php` / `RiskBlacklist.php` / `RiskWhitelist.php` / `BanRecord.php` / `RiskLog.php` / `IpRisk.php` / `DeviceFingerprint.php`

#### 3.9.1 风险评分模型

```
                    风险评分 (0-1000+)
                    ┌────────────────┐
                    │   总分计算：     │
                    │                │
                    │   设备指纹  ×0.25│
                    │ + IP风险    ×0.20│
                    │ + 行为模式  ×0.20│
                    │ + 历史记录  ×0.15│
                    │ + 账号特征  ×0.10│
                    │ + 关联风险  ×0.10│
                    └───────┬────────┘
                            ↓
        ┌───────┬───────┬───────┬───────┬───────┐
        │ 0-49  │50-149 │150-299│300-499│ 500+  │
        │ 安全  │ 低风险 │ 中风险 │ 高风险 │ 危险  │
        └───────┴───────┴───────┴───┬───┴───────┘
                                    ↓
                        ┌───────────┴───────────┐
                        │   自动封禁策略          │
                        ├───────────────────────┤
                        │ ≥ 300 → 临时封禁7天     │
                        │ ≥ 700 → 永久封禁        │
                        │ 封禁到期 → 自动解封      │
                        └───────────────────────┘
```

#### 3.9.2 规则引擎（~20条规则）

| 类别 | 规则示例 | 权重 |
|------|---------|------|
| **设备指纹** | 多账号同设备 | +30 |
| **设备指纹** | 模拟器/ Root 检测 | +50 |
| **IP风险** | 高风险IP段 | +20 |
| **IP风险** | 代理/VPN检测 | +40 |
| **行为模式** | 短时间大量操作 | +25 |
| **行为模式** | 观看视频倍速异常 | +35 |
| **行为模式** | 任务完成时间过短 | +30 |
| **历史记录** | 曾被封禁 | +40 |
| **历史记录** | 多次触发规则 | +20 |
| **账号特征** | 新账号大额提现 | +35 |
| **账号特征** | 资料不完整 | +10 |
| **关联风险** | 邀请链集中注册 | +30 |
| **关联风险** | 黑名单关联 | +50 |

#### 3.9.3 中间件级限流

```
RiskControlMiddleware::handle()
        ↓
┌───────────────────────────────────────────────────────┐
│                    限流策略                             │
├───────────────┬───────────────────┬───────────────────┤
│     维度       │     限制          │     Redis Key     │
├───────────────┼───────────────────┼───────────────────┤
│   IP 维度     │ 60 次/分钟        │ rl:ip:{ip}       │
│   用户维度    │ 30 次/分钟        │ rl:user:{uid}    │
│   高风险用户  │ 5 次/5分钟        │ rl:risk:{uid}    │
└───────────────┴───────────────────┴───────────────────┘
        ↓
    超出限制 → 返回 429 Too Many Requests
```

#### 3.9.4 黑白名单管理

```
黑名单 → 直接拦截，不处理任何业务请求
白名单 → 跳过风控检查，直接放行（白名单优先级高于黑名单）

管理入口：后台 → 风控 → 黑名单/白名单
```

#### 3.9.5 封禁到期自动解封

```
定时任务扫描 advn_ban_record
        ↓
WHERE status = 'banned' AND unban_time <= NOW()
        ↓
更新 status = 'unbanned'
        ↓
清除用户风险标记
        ↓
通知用户解封
```

---

### 3.10 消息系统

> API控制器：`application/api/controller/Message.php`  
> 数据模型：`application/common/model/TaskMessage.php`  
> 推送服务：`application/common/service/PushService.php` / `AutoPushService.php`

#### 3.10.1 消息类型

| 类型 | 说明 | 触发方式 |
|------|------|---------|
| **站内消息** | 系统通知、活动公告 | 手动发送/自动触发 |
| **任务推送** | 新红包通知、提现结果 | 事件驱动 |
| **未读计数** | 实时未读消息数 | Redis计数器 |
| **标记已读** | 用户点击消息 | 手动操作 |

#### 3.10.2 消息流程

```
事件触发（如：红包到达）
        ↓
TaskMessage::create([...])
        ↓
┌──────────────────────────────┐
│ 1. 写入 advn_task_message 表 │
│ 2. Redis 未读计数 +1         │
│    key: msg:unread:{user_id} │
│ 3. 触发推送通知（如配置）      │
│    → PushService             │
│    → 极光/个推/微信模板消息    │
└──────────────────────────────┘

用户查询消息 /api/message/index
        ↓
返回消息列表 + 未读总数

用户标记已读 /api/message/read
        ↓
Redis 未读计数清零
```

---

## 四、数据架构与分表策略

> 分表服务：`application/common/library/SplitTableService.php`  
> 分表模型基类：`application/common/model/SplitTableModel.php`

### 4.1 表分类总览

#### 核心表（不分表）

| 表名 | 说明 |
|------|------|
| `advn_user` | 用户主表 |
| `advn_coin_account` | 金币账户 |
| `advn_config` | 系统配置 |
| `advn_video` | 视频主表 |
| `advn_video_collection` | 视频合集 |
| `advn_video_collection_item` | 合集子项 |
| `advn_video_reward_rule` | 视频奖励规则 |
| `advn_invite_relation` | 邀请关系 |
| `advn_user_invite_stat` | 用户邀请统计 |
| `advn_user_commission_stat` | 用户分佣统计 |
| `advn_user_risk_score` | 用户风险评分 |
| `advn_risk_rule` | 风控规则 |
| `advn_risk_blacklist` | 黑名单 |
| `advn_risk_whitelist` | 白名单 |
| `advn_ban_record` | 封禁记录 |
| `advn_device_fingerprint` | 设备指纹 |
| `advn_ip_risk` | IP风险库 |
| `advn_version` | 版本管理 |
| `advn_category` | 分类 |

#### 月分表 (YYYYMM)

```
advn_coin_log_202501          # 金币流水
advn_coin_log_202502          # 金币流水
advn_ad_income_log_202501     # 广告收益日志
advn_ad_income_log_202502     # 广告收益日志
advn_ad_red_packet_202501     # 广告通知红包
advn_ad_red_packet_202502     # 广告通知红包
```

#### 年分表 (YYYY)

```
advn_withdraw_order_2025                  # 提现订单
advn_withdraw_order_2026                  # 提现订单
advn_red_packet_task_2025                 # 红包任务
advn_red_packet_task_2026                 # 红包任务
advn_user_red_packet_accumulate_2025      # 用户红包累积
advn_user_red_packet_accumulate_2026      # 用户红包累积
advn_task_participation_2025              # 任务参与记录
advn_task_participation_2026              # 任务参与记录
```

#### 归档表（冷数据迁移）

```
advn_*_archive     # 各业务表的冷数据归档
```

### 4.2 分表自动创建

```
SplitTableService::ensureTable($tableName, $suffix)
        ↓
检查表是否存在（SHOW TABLES LIKE ...）
        ↓
┌───────┴───────┐
│ 存在          │ 不存在
↓              ↓
直接使用      自动创建表（复制主表结构）
              → 写入 migration_log 记录
```

分表预创建由每日 0 点的定时任务触发，提前创建次月/次年分表。

### 4.3 分表路由机制

```php
// SplitTableModel 自动路由
class CoinLog extends SplitTableModel
{
    protected $tableName = 'coin_log';       // 基础表名
    protected $splitType = 'month';          // 按月分表

    // 查询时自动路由到 coin_log_202501
}
```

---

## 五、定时任务调度

> 命令入口：`php think`  
> 配置文件：`application/command.php`

### 5.1 定时任务一览

| 频率 | 任务 | 命令 | 说明 |
|------|------|------|------|
| **每5分钟** | 邀请分佣结算 | `InviteCommission` | 冻结→已结算，增加邀请人金币 |
| **每30分钟** | 广告结算 | `AdSettle` | 检查冻结余额阈值，生成通知红包 |
| **每小时** | 过期红包处理 | — | 清理过期未领取的红包 |
| **每天0:00** | 每日统计重置 | — | 重置日统计计数器 |
| **每天0:00** | 分表预创建 | `CreateSplitTables` | 创建次月/次年分表 |
| **每周一0:00** | 每周统计重置 | — | 重置周统计 |
| **每月1号0:00** | 每月统计重置 | — | 重置月统计 |
| **每天2:00** | 每日汇总 | — | 生成日报数据 |
| **每天3:00** | 数据迁移归档 | `DataMigration` | 冷数据迁移到归档表 |
| **每天4:00(周日)** | 过期记录清理 | — | 清理过期日志 |

### 5.2 Crontab 配置参考

```crontab
# 邀请分佣结算（每5分钟）
*/5 * * * * cd /home/z/advnet && php think invite_commission >> /dev/null 2>&1

# 广告结算（每30分钟）
*/30 * * * * cd /home/z/advnet && php think ad_settle >> /dev/null 2>&1

# 每日统计重置 + 分表预创建（每天0点）
0 0 * * * cd /home/z/advnet && php think daily_reset >> /dev/null 2>&1

# 数据迁移归档（每天3点）
0 3 * * * cd /home/z/advnet && php think data_migration >> /dev/null 2>&1

# 过期记录清理（每周日4点）
0 4 * * 0 cd /home/z/advnet && php think cleanup_expired >> /dev/null 2>&1
```

---

## 六、前端页面流转

> 项目路径：`mashangzhuan-app/`  
> 框架：uni-app (Vue 2 + uView UI)

### 6.1 启动流程

```
App启动（App.vue → onLaunch）
        ↓
┌──────────────────────────────────────────┐
│ 1. 加载系统配置                           │
│    /api/common/config                     │
│    → 获取广告位、提现配置、功能开关等       │
├──────────────────────────────────────────┤
│ 2. 检查Token有效性                        │
│    /api/user/check                       │
├──────────────────────────────────────────┤
│ 3. 路由判断                               │
│    ┌───────┴───────┐                     │
│    │ 有效Token     │ 无效Token           │
│    ↓              ↓                     │
│  首页           登录页                    │
└──────────────────────────────────────────┘
```

### 6.2 首页与内容浏览

```
首页（pages/index/index.vue）
├── Banner轮播
├── 功能入口
│   ├── 红包 → pages/index/red-bag.vue
│   ├── 签到 → pages/signin/signin.vue
│   ├── 游戏 → pages/index/games.vue
│   └── 邀请 → pages/agent/index.vue
└── 短剧浏览（分类Tab）
    ├── 都市
    ├── 悬疑
    ├── 现言
    ├── 古言
    ├── 甜宠
    └── ...
        ↓
    短剧详情页（封面/简介/选集）
        ↓
    播放页面 → 观看进度上报 → 奖励领取
```

### 6.3 赚钱路径（红包 → 广告 → 金币）

```
赚钱入口（底部Tab或首页入口）
        ↓
红包页（pages/index/red-bag.vue）
├── 可领红包列表
├── 任务红包（下载/小程序/聊天/广告/视频）
└── 累积红包进度
        ↓
点击红包 → 跳转广告观看页（pages/ad/watch.vue）
        ↓
┌──────────────────────────────────────┐
│         广告观看页                     │
│                                      │
│  ┌────────────────────────────┐      │
│  │     广告内容（激励视频）      │      │
│  │     30秒倒计时              │      │
│  │     ████████░░░░  20/30s   │      │
│  └────────────────────────────┘      │
│                                      │
│  倒计时结束 → 领取按钮激活             │
│  点击领取 → 调用 /api/redpacket/claim │
│      ↓                              │
│  金币入账 → 弹出领取成功动画           │
└──────────────────────────────────────┘
```

### 6.4 签到与游戏

```
签到页（pages/signin/signin.vue）
├── 签到日历（标记已签/漏签）
├── 连续签到天数 & 奖励预览
├── 补签按钮（消耗金币）
└── 排行榜入口 → pages/signin/ranking.vue

游戏页（pages/index/games.vue）
├── 砸蛋 → pages/game/egg.vue
└── 转盘 → pages/game/wheel.vue
```

### 6.5 我的 → 钱包 → 提现

```
我的页面（pages/my/my.vue）
├── 头像/昵称/等级
├── 金币余额展示
├── 功能列表
│   ├── 我的钱包 → pages/my/withdraw/index.vue
│   ├── 提现记录 → pages/my/withdraw/log.vue
│   ├── 我的收藏 → pages/my/collect.vue
│   ├── 个人资料 → pages/my/profile.vue
│   └── 消息中心
└── 退出登录

钱包页（pages/my/withdraw/index.vue）
├── 可用余额
├── 冻结余额
├── 提现渠道选择（微信/支付宝/银行卡）
├── 提现金额输入
├── 提现按钮 → /api/withdraw/apply
└── 提现记录列表

提现流程：
用户申请 → 等待审核 → 审核通过 → 打款中 → 到账/失败
```

### 6.6 邀请中心

```
邀请页（pages/agent/index.vue）
├── 邀请海报/分享链接
├── 我的团队 → pages/agent/teams.vue
│   ├── L1 直属好友列表
│   └── L2 间接好友列表
├── 收益统计 → pages/agent/earnings.vue
│   ├── 今日收益
│   ├── 累计收益
│   ├── 提现统计
│   └── 分佣明细
└── 排行榜 → pages/agent/ranking.vue
    ├── 邀请排行榜
    └── 收益排行榜
```

### 6.7 登录注册流程

```
登录页（pages/login/login.vue）
├── 账号密码登录
├── 手机验证码登录 → pages/login/mobilelogin.vue
├── 微信一键登录 → pages/login/wxlogin.vue
├── 忘记密码 → pages/login/forgetpwd.vue
└── 新用户注册 → pages/login/register.vue
    ├── 手机号 + 验证码
    └── 设置密码
```

---

## 七、后台管理系统（27 个模块）

> 入口：`application/admin/`  
> 路由前缀：`/admin`  
> 权限控制：RBAC（基于 `advn_auth_rule` + `advn_auth_group`）

### 7.1 模块清单

| 序号 | 模块 | 路径 | 功能说明 |
|------|------|------|---------|
| 1 | **仪表盘** | `admin/Index` | 后台首页、数据概览 |
| 2 | **数据看板** | `admin/Dashboard` | 运营数据统计面板 |
| 3 | **系统配置** | `admin/general/Config` | 全局配置项管理 |
| 4 | **附件管理** | `admin/general/Attachment` | 文件上传与管理 |
| 5 | **个人资料** | `admin/general/Profile` | 管理员个人信息 |
| 6 | **管理员管理** | `admin/auth/Admin` | 管理员账号CRUD |
| 7 | **管理员组** | `admin/auth/Group` | 角色权限组管理 |
| 8 | **权限规则** | `admin/auth/Rule` | 菜单与权限节点 |
| 9 | **操作日志** | `admin/auth/Adminlog` | 管理员操作审计 |
| 10 | **会员管理** | `admin/member/User` | 全部用户列表、详情、编辑、封禁、设备查看、行为分析、统计 |
| 11 | **用户管理** | `admin/user/User` | 用户基本信息（FastAdmin默认） |
| 12 | **用户组** | `admin/user/Group` | 用户分组管理 |
| 13 | **用户规则** | `admin/user/Rule` | 用户权限规则 |
| 14 | **金币账户** | `admin/coin/Account` | 查看金币账户、手动调账（增加/扣减金币） |
| 15 | **金币日志** | `admin/coin/Log` | 金币流水查询、统计 |
| 16 | **提现订单** | `admin/withdraw/Order` | 提现审核、打款操作、状态管理 |
| 17 | **提现配置** | `admin/withdraw/Config` | 最低提现额、提现渠道、限额配置 |
| 18 | **提现统计** | `admin/withdraw/Stat` | 提现数据统计报表 |
| 19 | **提现风控日志** | `admin/withdraw/Risklog` | 提现风控拦截记录 |
| 20 | **红包任务** | `admin/redpacket/Task` | 任务红包创建、编辑、推送、审核、查看详情 |
| 21 | **红包资源** | `admin/redpacket/Resource` | 红包跳转资源管理 |
| 22 | **红包奖励配置** | `admin/redpacket/Rewardconfig` | 红包金额区间、累积阈值配置 |
| 23 | **红包领取记录** | `admin/redpacket/Record` | 领取明细查看 |
| 24 | **红包参与记录** | `admin/redpacket/Participation` | 任务参与记录 |
| 25 | **红包统计** | `admin/redpacket/Stat` | 红包数据统计 |
| 26 | **红包审核** | `admin/redpacket/Audit` | 红包内容审核 |
| 27 | **视频管理** | `admin/video/Video` | 短剧视频CRUD |
| 28 | **视频合集** | `admin/video/Collection` | 短剧合集管理 |
| 29 | **视频作者** | `admin/video/Author` | 作者信息管理 |
| 30 | **视频观看记录** | `admin/video/Watchrecord` | 观看数据查看 |
| 31 | **视频奖励规则** | `admin/video/Rewardrule` | 视频奖励条件配置 |
| 32 | **视频奖励统计** | `admin/videoreward/RewardStat` | 视频奖励数据 |
| 33 | **视频反作弊** | `admin/videoreward/AnticheatLog` | 视频反作弊日志 |
| 34 | **邀请关系** | `admin/invite/Relation` | 邀请链查看 |
| 35 | **邀请统计** | `admin/invite/invitestat` | 用户邀请数据统计 |
| 36 | **分佣记录** | `admin/invite/Commissionlog` | 分佣明细查看 |
| 37 | **分佣统计** | `admin/invite/Commissionstat` | 分佣数据汇总 |
| 38 | **邀请数据** | `admin/invite/Statistic` | 邀请整体数据报表 |
| 39 | **广告收益日志** | `admin/adincome/Log` | 广告收益明细 |
| 40 | **广告红包** | `admin/adincome/Redpacket` | 广告通知红包管理 |
| 41 | **广告统计** | `admin/adincome/Stat` | 广告收益统计报表 |
| 42 | **签到配置** | `admin/signin/Config` | 签到奖励、连续天数配置 |
| 43 | **风控仪表盘** | `admin/risk/Dashboard` | 风控数据概览 |
| 44 | **用户风险** | `admin/risk/UserRisk` | 单用户风险评分查看 |
| 45 | **风控规则** | `admin/risk/Rule` | 规则引擎配置（~20条规则） |
| 46 | **黑名单** | `admin/risk/Blacklist` | 黑名单管理（IP/设备/用户） |
| 47 | **白名单** | `admin/risk/Whitelist` | 白名单管理 |
| 48 | **封禁记录** | `admin/risk/BanRecord` | 封禁/解封记录 |
| 49 | **风控初始化** | `admin/risk/Initdata` | 风控数据初始化工具 |
| 50 | **分类管理** | `admin/Category` | 通用分类管理 |
| 51 | **单页管理** | `admin/singlepage/Page` | 自定义页面管理 |
| 52 | **单页分类** | `admin/singlepage/Category` | 页面分类 |
| 53 | **数据迁移** | `admin/migration/Config` | 迁移配置 |
| 54 | **迁移日志** | `admin/migration/Log` | 迁移执行记录 |
| 55 | **执行迁移** | `admin/migration/Execute` | 手动触发数据迁移 |
| 56 | **迁移统计** | `admin/migration/Stats` | 迁移数据统计 |

---

## 八、关键设计决策与约定

### 8.1 并发控制

```
┌─────────────────────────────────────────────────┐
│              三层并发控制                         │
├─────────────────────────────────────────────────┤
│                                                  │
│  第一层：Redis 分布式锁                           │
│  ├── 用途：防止同一用户并发操作                    │
│  ├── Key格式：{业务}:{user_id}                   │
│  └── TTL：10秒（防止死锁）                        │
│                                                  │
│  第二层：数据库乐观锁                             │
│  ├── 用途：防止并发更新覆盖                       │
│  ├── 实现：coin_account.version 字段             │
│  └── 重试：最多3次                               │
│                                                  │
│  第三层：唯一约束去重                             │
│  ├── 用途：防止重复创建记录                       │
│  ├── 实现：transaction_id / unique index         │
│  └── Redis辅助：幂等性缓存                        │
│                                                  │
└─────────────────────────────────────────────────┘
```

### 8.2 安全防护

| 层级 | 措施 |
|------|------|
| **传输层** | HTTPS + 参数签名 |
| **认证层** | Token机制 + 过期控制 |
| **业务层** | 风控评分 + 规则引擎 |
| **接口层** | 限流中间件 + 参数校验 |
| **数据层** | 敏感数据加密（DataEncryptService） |
| **设备层** | 指纹识别 + 多设备检测 |

### 8.3 数据一致性

```
关键操作必须遵循：

1. 获取分布式锁
2. 开启数据库事务
3. 乐观锁更新
4. 写入流水日志
5. 更新 Redis 缓存/计数器
6. 提交事务
7. 释放分布式锁
8. 失败时事务回滚 + 释放锁
```

### 8.4 错误处理

```
API 统一返回格式：
{
    "code": 0,          // 0=成功，非0=错误码
    "msg": "success",   // 提示信息
    "data": {}          // 业务数据
}

常见错误码：
- 401: Token无效/过期
- 429: 请求过于频繁
- 1001: 余额不足
- 1002: 超出每日上限
- 1003: 风控拦截
- 1004: 账号已封禁
```

### 8.5 Redis Key 命名规范

```
coin:lock:{user_id}                    # 金币操作分布式锁
coin:daily:{user_id}:{type}:{date}     # 每日金币计数
rp:ip:{date}:{ip}                      # 红包IP限制计数
rp:device:{date}:{device_id}           # 红包设备限制计数
rp:acc:{user_id}                       # 红包累积金额
ad:txn:{transaction_id}               # 广告回调去重
signin:{user_id}:{date}               # 签到状态
signin:ranking:{date}                  # 签到排行榜
msg:unread:{user_id}                   # 未读消息计数
rl:ip:{ip}                             # IP限流
rl:user:{uid}                          # 用户限流
rl:risk:{uid}                          # 高风险用户限流
user:risk:{user_id}                    # 用户风险评分缓存
```

---

> **文档版本**：v1.0  
> **更新日期**：2025年7月  
> **维护者**：开发团队  
> **备注**：本文档基于项目当前代码结构编写，后续功能迭代请同步更新。
