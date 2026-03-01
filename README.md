# 短视频金币平台 - FastAdmin 版本

## 项目结构

```
/home/z/my-project/
├── application/
│   ├── common/
│   │   ├── model/                      # 数据模型 (11个新增)
│   │   │   ├── VideoRewardRule.php     # 视频收益规则模型
│   │   │   ├── VideoWatchRecord.php    # 视频观看记录模型
│   │   │   ├── UserDailyRewardStat.php # 用户每日收益统计模型
│   │   │   ├── VideoCollection.php     # 视频合集模型
│   │   │   ├── VideoCollectionItem.php # 合集视频关联模型
│   │   │   ├── Video.php               # 视频模型
│   │   │   ├── CoinAccount.php         # 金币账户模型
│   │   │   ├── CoinLog.php             # 金币流水模型
│   │   │   ├── InviteRelation.php      # 邀请关系模型
│   │   │   ├── WithdrawOrder.php       # 提现订单模型
│   │   │   └── AnticheatLog.php        # 防刷日志模型
│   │   │
│   │   └── library/                    # 服务类 (3个新增)
│   │       ├── VideoRewardService.php  # 视频收益服务
│   │       ├── CoinService.php         # 金币服务
│   │       └── AntiCheatService.php    # 防刷服务
│   │
│   ├── admin/controller/videoreward/   # 后台控制器
│   │   ├── VideoRewardRule.php         # 收益规则管理
│   │   ├── VideoCollection.php         # 视频合集管理
│   │   ├── VideoWatchRecord.php        # 观看记录管理
│   │   ├── RewardStat.php              # 收益统计
│   │   └── AnticheatLog.php            # 防刷日志
│   │
│   └── api/controller/                 # API控制器
│       ├── VideoReward.php             # 视频收益API
│       └── Coin.php                    # 金币API
│
├── sql/
│   ├── advnet.sql                      # FastAdmin 原始数据库
│   └── video_coin.sql                  # 视频金币模块数据库 (18张表)
│
├── web/                                # UniApp 前端代码
│   ├── api/
│   │   ├── video.js                    # 视频收益API封装
│   │   └── coin.js                     # 金币API封装
│   ├── components/
│   │   └── video-player.vue            # 视频播放器组件
│   ├── utils/
│   │   └── request.js                  # HTTP请求封装
│   └── package.json
│
├── public/                             # 静态资源
├── extend/                             # 扩展类库
├── runtime/                            # 运行时目录
└── README.md                           # 项目文档
```

## 安装步骤

### 1. 数据库安装

```bash
# 1. 先导入基础数据库
mysql -u root -p your_database < sql/advnet.sql

# 2. 再导入视频金币模块表
mysql -u root -p your_database < sql/video_coin.sql
```

### 2. 配置修改

修改 `application/database.php`:

```php
return [
    'type'     => 'mysql',
    'hostname' => 'localhost',
    'database' => 'your_database',
    'username' => 'root',
    'password' => 'your_password',
    'hostport' => '3306',
    'prefix'   => 'advn_',
];
```

### 3. 后台访问

- 后台地址: `/BgYmdTvqpf.php`
- 默认账号: admin
- 默认密码: admin

## API 接口

### 视频收益接口

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/video_reward/watch` | POST | 上报观看进度 |
| `/api/video_reward/claim` | POST | 领取奖励 |
| `/api/video_reward/status` | POST | 批量获取奖励状态 |
| `/api/video_reward/collection` | GET | 获取合集进度 |
| `/api/video_reward/daily` | GET | 获取今日统计 |
| `/api/video_reward/config` | GET | 获取奖励配置 |

### 金币接口

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/coin/balance` | GET | 获取金币余额 |
| `/api/coin/account` | GET | 获取账户详情 |
| `/api/coin/logs` | GET | 获取金币流水 |
| `/api/coin/types` | GET | 获取流水类型 |

## 核心功能

### 1. 视频观看奖励

- 完整观看奖励（进度 >= 95%）
- 时长奖励（观看满N秒）
- 集数奖励（合集类视频）

### 2. 防刷机制

- 同视频奖励间隔限制
- 同IP/设备每日奖励次数限制
- 观看速度检测
- 行为模式检测
- 风险评分模型

### 3. 邀请分佣

- 二级邀请关系
- 观看视频佣金
- 提现佣金

## 配置项

后台「系统配置」-「基础配置」：

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| watch_complete_threshold | 完成观看阈值 | 95 |
| daily_watch_limit | 每日奖励上限 | 50 |
| watch_interval | 同视频奖励间隔 | 300秒 |
| default_reward_coin | 默认奖励金币 | 100 |
| level1_watch_commission | 一级观看佣金比例 | 1% |
| level2_watch_commission | 二级观看佣金比例 | 0.5% |

## 金币比例

- **10000 金币 = 1 元人民币**

## 技术栈

- **后端**: FastAdmin + ThinkPHP 5
- **数据库**: MySQL 8.0
- **缓存**: Redis
- **前端**: UniApp (Vue3)
