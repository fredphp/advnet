# SQL 文件执行说明

## 文件说明

已将所有 SQL 文件整理为两个主文件：

| 文件 | 说明 | 执行顺序 |
|------|------|---------|
| `01_tables.sql` | 所有数据库表结构 | 第一步执行 |
| `02_data.sql` | 所有初始数据 | 第二步执行 |

## 执行步骤

### 方式一：命令行执行

```bash
# 1. 进入项目目录
cd /path/to/advnet

# 2. 执行表结构SQL（创建所有表）
mysql -u用户名 -p 数据库名 < sql/01_tables.sql

# 3. 执行初始数据SQL（插入默认数据）
mysql -u用户名 -p 数据库名 < sql/02_data.sql
```

### 方式二：数据库管理工具执行

使用 Navicat、phpMyAdmin、DBeaver 等工具：

1. 连接到数据库
2. 选择目标数据库
3. 打开并执行 `sql/01_tables.sql`
4. 打开并执行 `sql/02_data.sql`

### 方式三：MySQL 客户端执行

```bash
# 登录MySQL
mysql -u用户名 -p

# 选择数据库
use advnet;

# 执行SQL文件
source /path/to/sql/01_tables.sql;
source /path/to/sql/02_data.sql;
```

## 注意事项

### 1. 数据库需先创建

执行SQL前，确保数据库已创建：

```sql
CREATE DATABASE IF NOT EXISTS advnet DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 2. 如果表已存在

- 使用 `CREATE TABLE IF NOT EXISTS`，不会覆盖已有表
- 如需重建表，请先手动删除旧表

### 3. 如果数据已存在

- 使用 `INSERT IGNORE INTO`，重复数据会自动跳过
- 不会覆盖已有数据

### 4. FastAdmin 基础表

如果已安装 FastAdmin，以下表可能已存在，执行时会自动跳过：
- `advn_admin`
- `advn_admin_log`
- `advn_area`
- `advn_attachment`
- `advn_auth_group`
- `advn_auth_group_access`
- `advn_auth_rule`
- `advn_config`
- `advn_user`

### 5. 用户表扩展字段

用户表 `advn_user` 的扩展字段（邀请码、上级ID等）已注释，需要手动执行 ALTER 语句：

```sql
-- 取消注释后执行，或手动添加字段
ALTER TABLE `advn_user` ADD COLUMN `invite_code` VARCHAR(20) DEFAULT NULL COMMENT '我的邀请码' AFTER `password`;
ALTER TABLE `advn_user` ADD COLUMN `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '直接上级用户ID' AFTER `invite_code`;
ALTER TABLE `advn_user` ADD COLUMN `grandparent_id` INT UNSIGNED DEFAULT 0 COMMENT '间接上级用户ID' AFTER `parent_id`;
ALTER TABLE `advn_user` ADD COLUMN `device_id` VARCHAR(100) DEFAULT NULL COMMENT '设备ID' AFTER `level`;
ALTER TABLE `advn_user` ADD COLUMN `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP' AFTER `device_id`;
ALTER TABLE `advn_user` ADD UNIQUE KEY `uk_invite_code` (`invite_code`);
ALTER TABLE `advn_user` ADD KEY `idx_parent_id` (`parent_id`);
ALTER TABLE `advn_user` ADD KEY `idx_device_id` (`device_id`);
```

## 表结构概览

### 核心业务表

| 表名 | 说明 |
|------|------|
| `advn_coin_account` | 金币账户表 |
| `advn_coin_log` | 金币流水表 |
| `advn_cash_account` | 人民币账户表 |
| `advn_withdraw_order` | 提现订单表 |
| `advn_video` | 视频表 |
| `advn_video_reward_rule` | 视频收益规则表 |
| `advn_video_watch_record` | 视频观看记录表 |
| `advn_video_watch_session` | 观看会话表 |
| `advn_user_daily_reward_stat` | 用户每日收益统计表 |

### 红包任务表

| 表名 | 说明 |
|------|------|
| `advn_red_packet_task` | 红包任务表 |
| `advn_red_packet_record` | 红包领取记录表 |
| `advn_task_participation` | 任务参与记录表 |
| `advn_task_category` | 任务分类表 |
| `advn_task_device_log` | 任务设备记录表 |
| `advn_task_audit_log` | 任务审核日志表 |
| `advn_user_task_stat` | 用户任务统计表 |
| `advn_game_task_record` | 游戏任务记录表 |

### 提现系统表

| 表名 | 说明 |
|------|------|
| `advn_withdraw_config` | 提现配置表 |
| `advn_withdraw_risk_log` | 提现风控记录表 |
| `advn_withdraw_stat` | 提现统计表 |
| `advn_wechat_transfer_log` | 微信打款日志表 |

### 风控系统表

| 表名 | 说明 |
|------|------|
| `advn_risk_rule` | 风控规则配置表 |
| `advn_user_risk_score` | 用户风控评分表 |
| `advn_risk_log` | 风控日志表 |
| `advn_ip_risk` | IP风控表 |
| `advn_device_fingerprint` | 设备指纹表 |
| `advn_user_behavior` | 用户行为记录表 |
| `advn_user_behavior_stat` | 用户行为统计表 |
| `advn_ban_record` | 封禁记录表 |
| `advn_risk_whitelist` | 风控白名单表 |
| `advn_risk_blacklist` | 风控黑名单表 |
| `advn_risk_stat` | 风控统计表 |
| `advn_anticheat_log` | 防刷日志表 |

### 邀请分佣表

| 表名 | 说明 |
|------|------|
| `advn_invite_relation` | 邀请关系表 |
| `advn_invite_commission_config` | 分佣配置表 |
| `advn_invite_commission_log` | 分佣记录表 |
| `advn_user_invite_stat` | 用户邀请统计表 |
| `advn_user_commission_stat` | 用户佣金统计表 |
| `advn_daily_commission_stat` | 每日佣金统计表 |

### 数据迁移表

| 表名 | 说明 |
|------|------|
| `advn_coin_log_archive` | 金币流水归档表 |
| `advn_video_watch_record_archive` | 视频观看记录归档表 |
| `advn_video_watch_session_archive` | 观看会话归档表 |
| `advn_risk_log_archive` | 风控日志归档表 |
| `advn_user_behavior_archive` | 用户行为记录归档表 |
| `advn_anticheat_log_archive` | 防刷日志归档表 |
| `advn_red_packet_record_archive` | 红包领取记录归档表 |
| `advn_invite_commission_log_archive` | 邀请分佣日志归档表 |
| `advn_wechat_transfer_log_archive` | 微信打款日志归档表 |
| `advn_data_migration_config` | 数据迁移配置表 |
| `advn_data_migration_log` | 数据迁移日志表 |

## 执行数据迁移

SQL 执行完成后，可以运行数据迁移命令：

```bash
# 查看数据统计
php think data:migrate --action=stats

# 迁移金币流水（90天前的数据）
php think data:migrate --action=coin_log --days=90

# 迁移观看记录（180天前的数据）
php think data:migrate --action=watch_record --days=180

# 执行所有迁移
php think data:migrate --action=all
```

## 常见问题

### Q: 执行时报错 "Table already exists"

A: 表已存在，SQL 使用了 `IF NOT EXISTS`，此错误可忽略。

### Q: 执行时报错 "Duplicate entry"

A: 数据已存在，SQL 使用了 `INSERT IGNORE`，此错误可忽略。

### Q: 执行数据迁移时报错 "Table doesn't exist"

A: 请先执行 `01_tables.sql` 创建表结构。

### Q: 如何清空数据库重新初始化？

A: 
```sql
-- 警告：此操作会清空所有数据！
SET FOREIGN_KEY_CHECKS = 0;

-- 删除所有 advn_ 开头的表
DROP TABLE IF EXISTS advn_admin;
DROP TABLE IF EXISTS advn_admin_log;
-- ... 删除其他表

SET FOREIGN_KEY_CHECKS = 1;

-- 然后重新执行SQL文件
```

## 原始SQL文件（参考）

以下原始文件仍保留，仅供参考：

- `advnet.sql` - FastAdmin 完整导出
- `video_coin.sql` - 视频金币核心表
- `red_packet_task.sql` - 红包任务系统
- `withdraw_system.sql` - 提现系统
- `risk_control_system.sql` - 风控系统
- `system_config.sql` - 系统配置
- `admin_menu_permission.sql` - 后台菜单权限
- `invite_commission.sql` - 邀请分佣系统
- `migration_config.sql` - 数据迁移配置
- `data_migration_archive_tables.sql` - 归档表结构
