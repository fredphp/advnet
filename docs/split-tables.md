# 分表管理文档

## 概述

本项目实现了按月自动分表功能，支持提现订单、红包任务、领取记录等数据量大的表自动按月分表存储。

## 分表配置

### 已配置的分表

| 主表名 | 分表格式 | 说明 |
|-------|---------|------|
| `withdraw_order` | `withdraw_order_YYYYMM` | 提现订单分表 |
| `red_packet_task` | `red_packet_task_YYYYMM` | 红包任务分表 |
| `user_red_packet_accumulate` | `user_red_packet_accumulate_YYYYMM` | 红包领取记录分表 |
| `task_participation` | `task_participation_YYYYMM` | 任务参与记录分表 |

### 分表示例

```
advn_withdraw_order          # 主表（模板表）
advn_withdraw_order_202601   # 2026年1月分表
advn_withdraw_order_202602   # 2026年2月分表
advn_withdraw_order_202603   # 2026年3月分表
```

---

## 命令行工具

### 创建分表命令

```bash
# 创建当月和下月分表（默认）
php think split:create-tables

# 创建未来3个月的分表
php think split:create-tables --months=3

# 创建未来6个月的分表
php think split:create-tables --months=6

# 只创建提现订单分表
php think split:create-tables --type=withdraw

# 只创建红包任务分表
php think split:create-tables --type=redpacket

# 只创建领取记录分表
php think split:create-tables --type=participation

# 预览模式（显示将要创建的表，不实际创建）
php think split:create-tables --months=3 --dry-run

# 创建所有类型的分表
php think split:create-tables --type=all --months=2
```

### 命令参数说明

| 参数 | 简写 | 默认值 | 说明 |
|-----|------|-------|------|
| `--type` | `-t` | `all` | 分表类型：withdraw/redpacket/participation/all |
| `--months` | `-m` | `2` | 创建未来N个月的分表 |
| `--dry-run` | `-d` | 否 | 预览模式，不实际创建表 |

### 命令输出示例

```
========== 分表自动创建 ==========
时间: 2026-03-06 03:15:00
类型: all
月数: 2
模式: 执行模式

--- 创建 withdraw 分表 ---
  [跳过] advn_withdraw_order_202603 - 已存在
  [创建成功] advn_withdraw_order_202604

--- 创建 redpacket 分表 ---
  [创建成功] advn_red_packet_task_202603
  [创建成功] advn_red_packet_task_202604

--- 创建 participation 分表 ---
  [创建成功] advn_user_red_packet_accumulate_202603
  [创建成功] advn_user_red_packet_accumulate_202604

========== 执行完成 ==========
创建: 5 个分表
跳过: 1 个分表（已存在）
```

---

## 定时任务配置

### 方式一：Crontab（推荐）

编辑 crontab：

```bash
crontab -e
```

添加以下配置：

```bash
# 每月1号凌晨0点自动创建下两个月的分表
0 0 1 * * cd /path/to/advnet && php think split:create-tables --months=2 >> /var/log/split-tables.log 2>&1

# 或者每周检查一次（更保险）
0 0 * * 0 cd /path/to/advnet && php think split:create-tables --months=2 >> /var/log/split-tables.log 2>&1

# 或者每天凌晨检查（最保险，有缓存控制不会重复创建）
0 0 * * * cd /path/to/advnet && php think split:create-tables --months=2 >> /var/log/split-tables.log 2>&1
```

### 方式二：系统服务（Systemd）

创建服务文件 `/etc/systemd/system/split-tables.service`：

```ini
[Unit]
Description=Create split tables for advnet
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/path/to/advnet
ExecStart=/usr/bin/php think split:create-tables --months=2
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

创建定时器文件 `/etc/systemd/system/split-tables.timer`：

```ini
[Unit]
Description=Run split-tables service monthly

[Timer]
OnCalendar=*-*-01 00:00:00
Persistent=true

[Install]
WantedBy=timers.target
```

启用定时器：

```bash
systemctl daemon-reload
systemctl enable split-tables.timer
systemctl start split-tables.timer
```

### 方式三：应用启动自动创建

系统已内置应用启动时自动检查创建分表的功能：

- 每天只检查一次（通过缓存控制）
- 自动创建当月和下月分表
- 配置位置：`application/common/behavior/Common.php`

---

## 代码中使用

### 插入数据（自动路由到分表）

```php
use app\common\model\WithdrawOrderSplit;

// 方式一：使用 createOrder 方法（推荐）
$result = WithdrawOrderSplit::createOrder([
    'order_no' => 'WD20260306001',
    'user_id' => 1,
    'coin_amount' => 10000,
    'cash_amount' => 1.00,
    'withdraw_type' => 'alipay',
    'createtime' => time(), // 会根据此字段路由到对应月份分表
]);

// 方式二：使用 insertSplit 方法
$model = new WithdrawOrderSplit();
$id = $model->insertSplit([
    'order_no' => 'WD20260306002',
    'user_id' => 1,
    'coin_amount' => 5000,
    'cash_amount' => 0.50,
]);
```

### 查询数据

```php
use app\common\model\WithdrawOrderSplit;

// 查询当月数据
$model = new WithdrawOrderSplit();
$model->useTable(); // 使用当月分表
$list = $model->where('user_id', 1)->select();

// 查询指定月份数据
$model = new WithdrawOrderSplit();
$model->useTable(strtotime('2026-02-01')); // 使用2月份分表
$list = $model->where('user_id', 1)->select();
```

### 跨表查询统计

```php
use app\common\model\WithdrawOrderSplit;

// 获取今日统计
$stats = WithdrawOrderSplit::getTodayStats();

// 获取本月统计
$stats = WithdrawOrderSplit::getMonthStats('2026-03');

// 获取综合面板数据
$stats = WithdrawOrderSplit::getDashboardStats();

// 获取指定日期范围的每日统计
$dailyStats = WithdrawOrderSplit::getDailyStats('2026-03-01', '2026-03-31');

// 获取状态分布
$distribution = WithdrawOrderSplit::getStatusDistribution();
```

### 返回数据结构

```php
// getDashboardStats() 返回结构
[
    'today' => [
        'count' => 150,           // 今日订单数
        'coin_amount' => 1500000, // 今日金币
        'cash_amount' => 150.00,  // 今日金额
        'success_count' => 120,   // 今日成功订单数
        'success_amount' => 120.00, // 今日成功金额
    ],
    'yesterday' => [...],  // 昨日统计
    'month' => [...],      // 本月统计
    'last_month' => [...], // 上月统计
    'pending' => [
        'count' => 30,    // 待审核数量
        'amount' => 30.00, // 待审核金额
    ],
    'status_distribution' => [
        ['status' => 0, 'label' => '待审核', 'count' => 30, 'amount' => 30.00],
        ['status' => 3, 'label' => '提现成功', 'count' => 120, 'amount' => 120.00],
        ...
    ],
]
```

---

## 维护操作

### 查看分表状态

```php
use app\common\library\SplitTableService;

// 获取所有分表统计
$stats = SplitTableService::getStats();

// 返回结构
[
    'withdraw_order' => [
        'description' => '提现订单分表',
        'tables' => ['withdraw_order_202601', 'withdraw_order_202602', ...],
        'count' => 3,
        'records' => [
            'withdraw_order_202601' => 1500,
            'withdraw_order_202602' => 2300,
            'withdraw_order_202603' => 1800,
        ],
    ],
    ...
]
```

### 清理过期分表

```php
use app\common\library\SplitTableService;

// 清理12个月前的空分表
$result = SplitTableService::cleanOldTables(12);

// 返回结构
[
    'deleted' => ['withdraw_order_202401', 'withdraw_order_202402'],
    'skipped' => ['withdraw_order_202403 (有500条记录)'],
    'failed' => [],
]
```

---

## 注意事项

1. **主表必须存在**：分表是基于主表结构创建的，确保主表已创建

2. **缓存控制**：自动创建功能每天只检查一次，通过 Redis 缓存控制

3. **分表命名**：分表后缀格式为 `_YYYYMM`，如 `_202603`

4. **数据迁移**：历史数据需要单独迁移到对应月份的分表

5. **查询性能**：跨表查询会扫描多个表，建议指定时间范围

---

## 故障排查

### 分表创建失败

1. 检查主表是否存在
2. 检查数据库用户权限
3. 查看日志文件

```bash
# 查看应用日志
tail -f /path/to/advnet/runtime/log/$(date +%Y%m)/$(date +%d).log
```

### 分表查询失败

1. 确认分表已创建
2. 检查时间范围是否正确
3. 查看是否有缓存问题

```bash
# 清除缓存
php think clear
```

---

## 相关文件

| 文件 | 说明 |
|-----|------|
| `application/common/model/SplitTableModel.php` | 分表模型基类 |
| `application/common/model/WithdrawOrderSplit.php` | 提现订单分表模型 |
| `application/common/model/RedPacketTaskSplit.php` | 红包任务分表模型 |
| `application/common/model/UserRedPacketAccumulateSplit.php` | 领取记录分表模型 |
| `application/common/library/SplitTableService.php` | 分表管理服务 |
| `application/admin/command/CreateSplitTables.php` | 创建分表命令 |
| `application/admin/command/GenerateMockData.php` | 模拟数据生成命令 |
| `sql/migrations/20260306_create_split_tables.sql` | SQL迁移脚本 |
