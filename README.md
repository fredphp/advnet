# 广告网络管理系统 (AdNetwork)

## 项目简介

广告网络管理系统是一个基于 ThinkPHP 5.0 + FastAdmin 框架开发的综合性广告分发与用户激励平台。系统支持视频观看奖励、红包任务、邀请分佣、金币提现等核心功能，具备完善的风控体系和数据统计分析能力。

### 技术栈

| 类别 | 技术 |
|------|------|
| 后端框架 | ThinkPHP 5.0 |
| 后台框架 | FastAdmin 1.6.1 |
| 数据库 | MySQL 8.0+ |
| 缓存 | Redis |
| PHP版本 | >= 7.4.0 |
| 队列 | think-queue 1.1.6 |

### 项目结构

```
├── application/                 # 应用目录
│   ├── admin/                   # 后台管理模块
│   │   ├── controller/          # 控制器
│   │   │   ├── auth/           # 权限管理
│   │   │   ├── coin/           # 金币管理
│   │   │   ├── general/        # 常规管理
│   │   │   ├── invite/         # 邀请分佣
│   │   │   ├── member/         # 会员管理
│   │   │   ├── migration/      # 数据迁移
│   │   │   ├── redpacket/      # 红包管理
│   │   │   ├── risk/           # 风控管理
│   │   │   ├── user/           # 用户管理
│   │   │   ├── video/          # 视频管理
│   │   │   ├── videoreward/    # 视频奖励
│   │   │   └── withdraw/       # 提现管理
│   │   ├── command/            # 命令行工具
│   │   ├── lang/               # 语言包
│   │   ├── model/              # 模型
│   │   └── view/               # 视图
│   ├── api/                     # API接口模块
│   │   ├── controller/         # API控制器
│   │   └── middleware/         # 中间件
│   ├── common/                  # 公共模块
│   │   ├── library/            # 类库
│   │   ├── model/              # 公共模型
│   │   └── service/            # 服务类
│   ├── member/                  # 会员模块
│   └── video/                   # 视频模块
├── public/                      # 公共资源
│   ├── assets/                  # 静态资源
│   └── uploads/                 # 上传文件
├── runtime/                     # 运行时目录
├── sql/                         # SQL文件
│   └── migrations/              # 迁移脚本
├── web/                         # 前端应用（uni-app）
└── addons/                      # 插件目录
```

---

## 功能模块

### 1. 用户系统
- 用户注册/登录（支持手机号、用户名）
- 用户信息管理
- 用户等级与分组
- 设备指纹识别

### 2. 金币系统
- 金币账户管理
- 金币收入/支出流水
- 金币充值/扣除
- 分表存储（按月分表）

### 3. 提现系统
- 提现申请（微信/支付宝/银行卡）
- 提现审核流程
- 提现配置管理
- 每日提现限额

### 4. 视频收益
- 视频观看奖励
- 视频合集管理
- 观看时长统计
- 反作弊检测

### 5. 红包系统
- 红包任务创建
- 红包资源管理（APP/小程序/游戏/视频）
- 红包点击与领取
- 参与记录统计

### 6. 邀请分佣
- 邀请关系绑定
- 多级分佣机制
- 佣金结算
- 统计报表

### 7. 风控系统
- 风险评分模型
- 黑白名单管理
- 自动封禁机制
- 风险日志记录
- 设备/IP风险评估

### 8. 消息推送
- 站内消息
- 系统通知
- 任务消息推送

---

## 系统配置

### 环境要求

- PHP >= 7.4.0
- MySQL >= 8.0
- Redis >= 5.0
- Nginx / Apache
- Composer

### PHP扩展要求

```
- json
- curl
- pdo
- pdo_mysql
- bcmath
- redis
- mbstring
- openssl
```

### 数据库配置

编辑 `application/database.php` 或创建 `.env` 文件：

```php
return [
    'type'            => 'mysql',
    'hostname'        => '127.0.0.1',
    'database'        => 'advnet',
    'username'        => 'root',
    'password'        => 'your_password',
    'hostport'        => '3306',
    'charset'         => 'utf8mb4',
    'prefix'          => 'advn_',
];
```

### Redis配置

编辑 `application/extra/queue.php`：

```php
return [
    'connector'  => 'Redis',
    'expire'     => 0,
    'default'    => 'default',
    'host'       => '127.0.0.1',
    'port'       => 6379,
    'password'   => '',
    'select'     => 0,
    'timeout'    => 0,
    'persistent' => false,
];
```

### 系统配置项

系统主要配置存储在 `advn_config` 表中，按分组管理：

| 分组 | 配置项 | 说明 |
|------|--------|------|
| 基础配置 | coin_rate | 金币汇率（多少金币=1元） |
| 用户配置 | new_user_reward | 新用户奖励金币 |
| 用户配置 | video_watch_reward | 视频观看奖励 |
| 用户配置 | daily_video_limit | 每日视频上限 |
| 用户配置 | daily_coin_limit | 每日金币上限 |
| 提现配置 | min_withdraw | 最低提现金额 |
| 提现配置 | max_withdraw | 最高提现金额 |
| 提现配置 | withdraw_amounts | 可选提现金额列表 |
| 提现配置 | daily_withdraw_limit | 每日提现次数限制 |
| 邀请配置 | invite_commission_rate | 邀请佣金比例 |
| 邀请配置 | invite_commission_levels | 分佣层级 |
| 风控配置 | risk_score_threshold | 风险分数阈值 |
| 风控配置 | auto_ban_enabled | 是否启用自动封禁 |

---

## 系统启动步骤

### 1. 安装依赖

```bash
# 安装PHP依赖
composer install

# 安装前端依赖（如需要）
cd web && npm install
```

### 2. 导入数据库

```bash
# 导入数据库结构
mysql -u root -p advnet < sql/advnet.sql
```

### 3. 配置环境

```bash
# 复制环境配置
cp .env.example .env

# 编辑配置文件
vim application/database.php
vim application/extra/queue.php
```

### 4. 设置目录权限

```bash
chmod -R 755 runtime/
chmod -R 755 public/uploads/
```

### 5. 配置Web服务器

**Nginx配置示例：**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/advnet/public;
    index index.php index.html;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
        }
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 6. 启动队列监听（后台运行）

```bash
# 启动队列监听
php think queue:listen --daemon

# 或者使用 Supervisor 管理
```

### 7. 启动 WebSocket 服务（可选）

如果需要实时推送功能（如红包任务推送、在线人数统计），需要启动 WebSocket 服务。

#### 7.1 安装 Swoole 扩展

Swoole 是一个高性能的 PHP 协程框架，需要作为 PHP 扩展安装。

**方法一：使用 pecl 安装（推荐）**

```bash
# 安装 Swoole 扩展
pecl install swoole

# 启用扩展
echo "extension=swoole.so" >> /etc/php/7.4/cli/php.ini
echo "extension=swoole.so" >> /etc/php/7.4/fpm/php.ini

# 验证安装
php --ri swoole
```

**方法二：编译安装**

```bash
# 下载源码
cd /tmp
wget https://github.com/swoole/swoole-src/archive/refs/tags/v4.8.13.tar.gz
tar -xzf v4.8.13.tar.gz
cd swoole-src-4.8.13

# 编译安装
phpize
./configure --enable-openssl --enable-http2 --enable-sockets
make && make install

# 启用扩展
echo "extension=swoole.so" >> /etc/php/7.4/cli/php.ini

# 验证安装
php --ri swoole
```

**方法三：Docker 环境**

```dockerfile
# Dockerfile
FROM php:7.4-cli

# 安装 Swoole
RUN pecl install swoole && docker-php-ext-enable swoole
```

**验证 Swoole 安装成功：**

```bash
$ php --ri swoole

swoole

Swoole => enabled
Author => Swoole Team <team@swoole.com>
Version => 4.8.13
Built => Mar 15 2026 10:00:00
coroutine => enabled
openssl => OpenSSL 1.1.1f  31 Mar 2020
http2 => enabled
```

#### 7.2 启动 WebSocket 服务

```bash
# 前台运行（调试用，Ctrl+C 停止）
php think websocket start

# 指定端口启动
php think websocket start --port=3002 --api=3003

# 后台运行（守护进程模式）
php think websocket start -d
```

#### 7.3 管理 WebSocket 服务

```bash
# 停止服务
php think websocket stop

# 重启服务
php think websocket restart

# 查看服务状态
php think websocket status
```

#### 7.4 WebSocket 服务配置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| WebSocket 端口 | 3002 | 客户端 WebSocket 连接入口 |
| API 端口 | 3003 | 内部 HTTP API，用于后端推送消息 |
| API 密钥 | your-secret-api-key | 内部 API 认证密钥（请在代码中修改） |
| PID 文件 | runtime/websocket.pid | 进程 ID 文件 |
| 日志文件 | runtime/log/websocket.log | 服务日志文件 |

**修改配置（在代码中）：**

```php
// application/common/library/WebSocketService.php

class WebSocketService
{
    // 修改端口
    const WS_PORT = 3002;
    const API_PORT = 3003;
    
    // 修改 API 密钥（生产环境必须修改！）
    const API_KEY = 'your-production-api-key';
}
```

#### 7.5 生产环境部署

**使用 Supervisor 管理（推荐）：**

```ini
# /etc/supervisor/conf.d/websocket.conf
[program:websocket]
command=php /path/to/advnet/think websocket:start
directory=/path/to/advnet
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/websocket.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
```

```bash
# 重新加载 Supervisor 配置
supervisorctl reread
supervisorctl update

# 管理服务
supervisorctl start websocket
supervisorctl stop websocket
supervisorctl restart websocket
supervisorctl status websocket
```

**使用 systemd 管理：**

```ini
# /etc/systemd/system/websocket.service
[Unit]
Description=WebSocket Service for AdNetwork
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/advnet
ExecStart=/usr/bin/php /path/to/advnet/think websocket:start
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
# 启用并启动服务
systemctl daemon-reload
systemctl enable websocket
systemctl start websocket
systemctl status websocket
```

#### 7.6 Nginx 代理配置（可选）

如果需要通过 Nginx 代理 WebSocket：

```nginx
# WebSocket 代理配置
upstream websocket {
    server 127.0.0.1:3002;
}

server {
    listen 443 ssl;
    server_name ws.your-domain.com;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    location / {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }
}
```

#### 7.7 前端连接示例

```javascript
// 直接连接（内网或直连）
const wsUrl = `ws://127.0.0.1:3002?userId=${userId}&token=${token}`;

// 通过网关连接（必须使用 XTransformPort 参数）
const wsUrl = `wss://your-domain.com/?XTransformPort=3002&userId=${userId}&token=${token}`;

// uni-app 连接
uni.connectSocket({
    url: wsUrl,
    success: () => console.log('连接中...')
});

// 监听连接打开
uni.onSocketOpen(() => {
    console.log('WebSocket 连接成功');
    // 发送认证消息
    uni.sendSocketMessage({
        data: JSON.stringify({
            type: 'auth',
            userId: userId,
            token: token
        })
    });
});

// 监听消息
uni.onSocketMessage((res) => {
    const data = JSON.parse(res.data);
    switch (data.type) {
        case 'task_notification':
            console.log('收到红包任务:', data);
            break;
        case 'online_count':
            console.log('在线人数:', data.count);
            break;
    }
});
```

#### 7.8 后端推送消息示例

```php
// 推送红包任务通知
function pushTaskNotification($taskId, $taskName, $reward) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3003/api/push-task');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: your-secret-api-key',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'taskId' => $taskId,
        'taskName' => $taskName,
        'taskType' => 'lucky',
        'reward' => $reward,
        'content' => "【红包任务】{$taskName}，完成可获得 {$reward} 金币奖励！",
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// 发送系统消息
function sendSystemMessage($title, $content, $targetUsers = null) {
    $data = [
        'title' => $title,
        'content' => $content,
        'level' => 'info',
    ];
    if ($targetUsers) {
        $data['targetUsers'] = $targetUsers;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3003/api/system-message');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: your-secret-api-key',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// 获取在线人数
function getOnlineCount() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:3003/api/online-count');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: your-secret-api-key',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
```

#### 7.9 常见问题

**问题1: Swoole 扩展安装失败**

```bash
# 确保安装了必要的依赖
apt-get install -y php7.4-dev php-pear build-essential libssl-dev

# 重新安装
pecl install swoole
```

**问题2: 端口被占用**

```bash
# 查看端口占用
netstat -tlnp | grep 3002

# 杀掉占用进程
kill -9 <PID>
```

**问题3: 服务无法启动**

```bash
# 查看错误日志
tail -f runtime/log/websocket.log

# 检查 PHP 错误日志
tail -f /var/log/php-errors.log
```

### 8. 配置定时任务（Crontab）

编辑 crontab：

```bash
crontab -e
```

添加以下任务：

```bash
# 每分钟结算待处理分佣
* * * * * cd /path/to/advnet && php think invite:commission --action=settle >> /var/log/invite_settle.log 2>&1

# 每天凌晨0点重置每日统计
0 0 * * * cd /path/to/advnet && php think invite:commission --action=daily >> /var/log/invite_daily.log 2>&1

# 每周一凌晨0点重置每周统计
0 0 * * 1 cd /path/to/advnet && php think invite:commission --action=weekly >> /var/log/invite_weekly.log 2>&1

# 每月1号凌晨0点重置每月统计
0 0 1 * * cd /path/to/advnet && php think invite:commission --action=monthly >> /var/log/invite_monthly.log 2>&1

# 每天凌晨1点清理过期记录（保留90天）
0 1 * * * cd /path/to/advnet && php think invite:commission --action=clean >> /var/log/invite_clean.log 2>&1

# 每天凌晨2点汇总每日统计
0 2 * * * cd /path/to/advnet && php think invite:commission --action=summary >> /var/log/invite_summary.log 2>&1

# 每小时更新周期统计
0 * * * * cd /path/to/advnet && php think invite:commission --action=period >> /var/log/invite_period.log 2>&1
```

### 9. 启动开发服务器（开发环境）

```bash
php think run -p 8080
```

---

## 命令行工具

### 邀请分佣定时任务

```bash
# 结算待处理分佣
php think invite:commission --action=settle

# 每日统计重置
php think invite:commission --action=daily

# 每周统计重置
php think invite:commission --action=weekly

# 每月统计重置
php think invite:commission --action=monthly

# 清理过期记录
php think invite:commission --action=clean

# 汇总每日统计
php think invite:commission --action=summary

# 更新周期统计
php think invite:commission --action=period

# 处理冻结分佣
php think invite:commission --action=frozen

# 执行所有任务
php think invite:commission --action=all
```

### 数据迁移

```bash
# 执行数据迁移
php think data:migration
```

### 创建分表

```bash
# 创建金币日志分表
php think create:split:tables
```

### 生成测试数据

```bash
# 生成模拟数据
php think generate:mock:data
```

---

## API接口

详细的API接口文档请参考 [api.html](api.html)

### API基础地址

```
https://your-domain.com/api/
```

### 认证方式

使用 Token 认证，在请求头中携带：

```
token: your_user_token
```

### 主要接口

| 模块 | 接口 | 说明 |
|------|------|------|
| 用户 | POST /api/user/login | 用户登录 |
| 用户 | POST /api/user/register | 用户注册 |
| 用户 | POST /api/user/profile | 修改资料 |
| 金币 | GET /api/coin/balance | 获取余额 |
| 金币 | GET /api/coin/logs | 金币流水 |
| 提现 | GET /api/withdraw/config | 提现配置 |
| 提现 | POST /api/withdraw/apply | 申请提现 |
| 红包 | POST /api/redpacket/click | 点击红包 |
| 红包 | POST /api/redpacket/claim | 领取红包 |
| 视频 | POST /api/videoreward/watch | 上报观看 |
| 视频 | POST /api/videoreward/claim | 领取奖励 |
| 邀请 | POST /api/invite/bind | 绑定邀请 |
| 邀请 | GET /api/invite/overview | 邀请统计 |

---

## 核心业务流程

### 1. 用户观看视频收益流程

```
用户打开视频 → 记录观看开始时间 → 持续上报进度 
→ 达到奖励时长 → 调用领取奖励接口 → 系统验证 
→ 发放金币 → 记录流水
```

### 2. 提现流程

```
用户申请提现 → 检查风控评分 → 检查每日限额 
→ 冻结金币 → 创建提现订单 → 管理员审核 
→ 审核通过 → 打款 → 更新订单状态 → 触发分佣
```

### 3. 邀请分佣流程

```
新用户注册 → 绑定邀请关系 → 完成首次提现 
→ 创建分佣记录 → 延迟结算 → 结算到邀请人账户
```

### 4. 红包任务流程

```
创建红包任务 → 关联资源(APP/小程序等) → 推送给用户 
→ 用户点击红包 → 完成任务(下载APP/打开小程序等) 
→ 领取红包奖励 → 发放金币
```

---

## 风控机制

### 风险评分维度

| 维度 | 权重 | 说明 |
|------|------|------|
| 设备指纹 | 25% | 设备唯一性检测 |
| IP风险 | 20% | IP归属地、代理检测 |
| 行为模式 | 20% | 操作频率、时长分布 |
| 历史记录 | 15% | 历史违规记录 |
| 账号特征 | 10% | 注册时间、活跃度 |
| 关联风险 | 10% | 关联账号风险 |

### 自动封禁规则

- 风险评分超过阈值自动封禁
- 同一设备多账号自动标记
- 异常行为模式自动预警
- 可配置白名单豁免

---

## 常见问题

### 1. 队列不执行

检查 Redis 连接和队列监听状态：
```bash
php think queue:status
```

### 2. 分表不存在

运行分表创建命令：
```bash
php think create:split:tables --month=202603
```

### 3. Token失效

检查 Token 配置和数据库连接：
```php
// application/config.php
'token' => [
    'type'     => 'Mysql',
    'expire'   => 0,
]
```

---

## 更新日志

### v1.0.0 (2026-03-15)
- 初始版本发布
- 完成用户、金币、提现核心功能
- 实现视频奖励、红包任务
- 完善邀请分佣机制
- 集成风控系统

---

## 开发团队

- 框架：ThinkPHP 5.0 / FastAdmin
- 数据库：MySQL 8.0

---

## 许可证

Apache-2.0 License
