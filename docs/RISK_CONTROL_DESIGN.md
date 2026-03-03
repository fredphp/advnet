# 风控系统设计文档

## 1. 系统概述

本风控系统专为短视频金币平台设计，提供多层次、多维度的风险检测与防护能力。

### 1.1 核心功能

- **视频防刷**：检测虚假观看、快速刷视频等行为
- **任务防刷**：检测虚假任务完成、脚本自动化等行为
- **IP限制**：IP风险评估、代理检测、多账户关联
- **设备指纹**：设备唯一标识、模拟器检测、Root/Hook检测
- **用户行为检测**：行为模式分析、操作规律检测
- **风控评分系统**：综合风险评分、分级管理
- **自动封号机制**：自动决策、分级封禁、解封处理

## 2. 数据库设计

### 2.1 表结构

| 表名 | 说明 |
|------|------|
| advn_risk_rule | 风控规则配置表 |
| advn_user_risk_score | 用户风控评分表 |
| advn_risk_log | 风控日志表 |
| advn_ip_risk | IP风控表 |
| advn_device_fingerprint | 设备指纹表 |
| advn_user_behavior | 用户行为记录表 |
| advn_user_behavior_stat | 行为统计表(按天) |
| advn_ban_record | 封禁记录表 |
| advn_risk_whitelist | 风控白名单表 |
| advn_risk_blacklist | 风控黑名单表 |
| advn_risk_stat | 风控统计表 |

### 2.2 风险评分说明

```
风险分范围：0 - 1000

风险等级：
- safe:      0 - 50     (安全)
- low:       50 - 150   (低风险)
- medium:    150 - 300  (中风险)
- high:      300 - 500  (高风险)
- dangerous: 500+       (危险)

评分衰减：每天自动衰减10%
```

## 3. 风控规则

### 3.1 视频相关规则

| 规则编码 | 规则名称 | 阈值 | 风险分 |
|---------|---------|------|--------|
| VIDEO_WATCH_SPEED | 视频观看速度异常 | 3秒 | 30 |
| VIDEO_WATCH_REPEAT | 重复观看同一视频 | 5次 | 20 |
| VIDEO_DAILY_LIMIT | 视频观看次数超限 | 500次 | 40 |
| VIDEO_REWARD_SPEED | 金币获取速度异常 | 10000/小时 | 50 |
| VIDEO_SKIP_RATIO | 视频跳过率过高 | 90% | 25 |

### 3.2 任务相关规则

| 规则编码 | 规则名称 | 阈值 | 风险分 |
|---------|---------|------|--------|
| TASK_COMPLETE_SPEED | 任务完成速度异常 | 5秒 | 35 |
| TASK_DAILY_LIMIT | 任务完成次数超限 | 100次 | 30 |
| TASK_REPEAT_SUBMIT | 重复提交任务 | 3次 | 40 |
| TASK_FAKE_BEHAVIOR | 任务行为异常 | 检测阈值 | 80 |

### 3.3 提现相关规则

| 规则编码 | 规则名称 | 阈值 | 风险分 |
|---------|---------|------|--------|
| WITHDRAW_FREQUENCY | 提现频率异常 | 3次/天 | 45 |
| WITHDRAW_AMOUNT_ANOMALY | 提现金额异常 | 倍数阈值 | 60 |
| WITHDRAW_NEW_ACCOUNT | 新账户大额提现 | 10元 | 50 |

### 3.4 全局规则

| 规则编码 | 规则名称 | 阈值 | 风险分 |
|---------|---------|------|--------|
| IP_MULTI_ACCOUNT | IP多账户关联 | 5个 | 50 |
| DEVICE_MULTI_ACCOUNT | 设备多账户关联 | 3个 | 60 |
| BEHAVIOR_PATTERN | 行为模式异常 | 偏差阈值 | 70 |

## 4. 风控评分算法

### 4.1 评分计算公式

```
总风险分 = 基础分 + 视频风险分 + 任务风险分 + 提现风险分 + 红包风险分 + 邀请风险分 + 全局风险分

实际风险分 = 原始分 × 衰减因子 + 设备风险加权 + IP风险加权

衰减因子 = (1 - 0.1)^天数
```

### 4.2 设备风险评估

```php
设备风险分 = 0;

if (检测到Root/越狱) 风险分 += 0.3;
if (检测到模拟器) 风险分 += 0.5;
if (检测到Hook框架) 风险分 += 0.4;
if (检测到代理/VPN) 风险分 += 0.2;
if (多账户关联 > 3) 风险分 += min((关联数-3) × 0.1, 0.3);

最终风险分 = min(设备风险分, 1) × 50;
```

### 4.3 IP风险评估

```php
IP风险分 = 0;

if (检测到代理) 风险分 += 0.3;
if (风险等级 == dangerous) 风险分 += 0.5;
if (风险等级 == suspicious) 风险分 += 0.2;
if (风险等级 == blacklist) 风险分 += 0.8;
if (多账户关联 > 5) 风险分 += min((关联数-5) × 0.05, 0.3);

最终风险分 = min(IP风险分, 1) × 50;
```

## 5. 中间件实现

### 5.1 请求处理流程

```
请求进入
    ↓
检查白名单 → 命中 → 放行
    ↓
检查黑名单 → 命中 → 拦截
    ↓
IP基础检查 → 异常 → 拦截
    ↓
设备指纹检查 → 异常 → 拦截
    ↓
用户状态检查 → 异常 → 拦截
    ↓
路由风控检查 → 异常 → 拦截
    ↓
频率限制检查 → 超限 → 拦截
    ↓
执行业务逻辑
    ↓
记录行为日志
    ↓
更新统计数据
    ↓
返回响应
```

### 5.2 频率限制

```
IP级别：60次/分钟
用户级别：30次/分钟
高风险路由：5次/5分钟
```

## 6. 自动封号机制

### 6.1 封号决策

```php
if (风险分 >= 700) {
    永久封禁;
} elseif (违规次数 >= 10) {
    永久封禁;
} elseif (风险分 >= 500) {
    临时封禁，时长根据违规次数决定;
}
```

### 6.2 封号时长

| 违规次数 | 封禁时长 |
|---------|---------|
| 首次 | 1天 |
| 二次 | 7天 |
| 三次 | 30天 |
| 四次及以上 | 永久 |

### 6.3 封号动作

1. 更新用户状态为 banned
2. 更新风控状态
3. 创建封禁记录
4. 加入黑名单
5. 冻结金币账户
6. 取消待处理提现
7. 清除登录Token

## 7. 设备指纹

### 7.1 指纹生成

```
设备ID = SHA256(品牌 + 型号 + 系统版本 + 屏幕分辨率 + 设备标识)
设备哈希 = MD5(品牌 + 型号 + 系统版本 + 屏幕分辨率 + CPU信息 + 内存信息)
```

### 7.2 检测项目

- Root/越狱检测
- 模拟器检测（设备特征、CPU架构）
- Hook框架检测（Xposed、Magisk、Frida等）
- 代理/VPN检测
- 多开应用检测
- 调试模式检测

## 8. 行为检测

### 8.1 点击行为分析

- 点击位置分布（过于集中异常）
- 点击间隔规律性（过于规律异常）
- 触摸压力一致性（完全一致异常）

### 8.2 时间戳分析

- 操作时间间隔方差（过小异常）
- 设备时间偏差（过大异常）
- 上报延迟检测

### 8.3 传感器数据

- 陀螺仪数据（完全静止异常）
- 加速度计数据（无自然运动异常）

## 9. 后台管理

### 9.1 风控仪表盘

- 今日统计数据
- 风险用户分布
- 违规趋势图
- 规则触发排名
- 风险预警列表

### 9.2 规则管理

- 规则列表/新增/编辑
- 阈值配置
- 启用/禁用
- 规则测试

### 9.3 用户风险管理

- 用户风险列表
- 风险详情查看
- 手动调整风险分
- 手动封禁/解封
- 加入/移出白名单

### 9.4 黑名单管理

- 黑名单列表
- 批量添加IP
- IP查询
- 移除黑名单

## 10. API接口

### 10.1 客户端上报

```
POST /api/risk/device/register
- 设备信息上报
- 返回设备ID和风险评估

POST /api/risk/behavior/report
- 行为数据上报
- 点击位置、时间戳、传感器数据等
```

### 10.2 风控检查

```
GET /api/risk/check
- 返回用户风险状态
- 用于前端展示提示
```

## 11. 定时任务

```php
// 每分钟执行
- 解冻过期冻结用户
- 解封过期临时封禁用户

// 每小时执行
- 清理过期缓存
- 更新IP风险评分

// 每天执行
- 生成风控统计报表
- 风险分自动衰减
- 清理过期日志
```

## 12. 文件清单

### 12.1 数据库

- `/sql/risk_control_system.sql` - 风控系统数据库表

### 12.2 核心服务

- `/application/common/library/RiskControlService.php` - 风控评分服务
- `/application/common/library/AutoBanService.php` - 自动封号服务
- `/application/common/library/DeviceFingerprintService.php` - 设备指纹服务
- `/application/common/library/VideoAntiCheatService.php` - 视频防刷服务
- `/application/common/library/TaskAntiCheatService.php` - 任务防刷服务

### 12.3 中间件

- `/application/api/middleware/RiskControlMiddleware.php` - 风控中间件

### 12.4 模型

- `/application/common/model/RiskControl.php` - 风控相关模型

### 12.5 后台控制器

- `/application/admin/controller/risk/Dashboard.php` - 风控仪表盘
- `/application/admin/controller/risk/Rule.php` - 规则管理
- `/application/admin/controller/risk/UserRisk.php` - 用户风险管理
- `/application/admin/controller/risk/BanRecord.php` - 封禁记录
- `/application/admin/controller/risk/Blacklist.php` - 黑白名单管理

## 13. 使用示例

### 13.1 视频观看检查

```php
use app\common\library\VideoAntiCheatService;

$antiCheat = new VideoAntiCheatService($userId);
$result = $antiCheat->checkWatch([
    'video_id' => 123,
    'watch_duration' => 25,
    'video_duration' => 30,
    'coin_reward' => 100,
]);

if (!$result['passed']) {
    // 拦截或降低奖励
}
```

### 13.2 风控检查

```php
use app\common\library\RiskControlService;

$result = RiskControlService::quickCheck(
    $userId,
    'video',
    'api/video.reward/watch',
    ['video_id' => 123, 'watch_duration' => 25]
);

if (!$result['passed']) {
    return json(['code' => 429, 'msg' => $result['message']]);
}
```

### 13.3 设备注册

```php
use app\common\library\DeviceFingerprintService;

$deviceService = new DeviceFingerprintService();
$result = $deviceService->register($userId, [
    'brand' => 'iPhone',
    'model' => 'iPhone 14 Pro',
    'os_version' => 'iOS 16.0',
    'platform' => 'ios',
    'screen_resolution' => '1170x2532',
    // ... 其他设备信息
]);

if ($result['risk_level'] == 'dangerous') {
    // 高风险设备处理
}
```
