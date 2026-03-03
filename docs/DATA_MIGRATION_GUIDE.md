# 数据迁移工具使用指南

## 概述

数据迁移工具包含两个主要功能：
1. **SQL迁移文件管理** - 类似Laravel迁移机制，管理数据库结构变更
2. **数据归档迁移** - 将历史冷数据迁移到归档表，优化主表性能

## 命令速查表

### SQL迁移文件命令

| 命令 | 说明 |
|------|------|
| `php think data:migrate --action=file:sync` | 同步迁移文件到数据库 |
| `php think data:migrate --action=file:status` | 查看迁移状态统计 |
| `php think data:migrate --action=file:pending` | 查看待执行的迁移 |
| `php think data:migrate --action=file:run` | 执行所有待执行的迁移 |
| `php think data:migrate --action=file:run --file=xxx.sql` | 执行单个迁移文件 |
| `php think data:migrate --action=file:reset` | 重置失败的迁移 |
| `php think data:migrate --action=file:rollback --rollback=N` | 回滚指定批次 |

### 数据归档迁移命令

| 命令 | 说明 |
|------|------|
| `php think data:migrate --action=stats` | 查看所有表数据统计 |
| `php think data:migrate --action=stats --table=coin_log` | 查看指定表统计 |
| `php think data:migrate --action=coin_log --days=90` | 迁移金币流水(90天前) |
| `php think data:migrate --action=all` | 执行所有归档迁移 |
| `php think data:migrate --action=all --delete` | 执行归档并删除源数据 |
| `php think data:migrate --action=inactive --days=90` | 标记未活跃用户 |
| `php think data:migrate --action=clean --days=365` | 清理过期统计 |

### 常用选项

| 选项 | 说明 | 示例 |
|------|------|------|
| `--days=N` | 迁移N天前的数据 | `--days=180` |
| `--batch=N` | 批量处理数量 | `--batch=500` |
| `--delete` | 迁移后删除源数据 | `--delete` |
| `--table=表名` | 指定单个表 | `--table=coin_log` |
| `--file=文件名` | 指定迁移文件 | `--file=20250115_001.sql` |
| `--rollback=N` | 回滚批次号 | `--rollback=1` |

## 文件结构

```
application/
├── common/library/
│   ├── DataMigrationService.php    # 数据归档迁移服务类
│   └── MigrationFileService.php    # SQL迁移文件管理服务
├── command/
│   └── DataMigration.php           # 数据迁移命令
└── command.php                     # 命令注册配置

sql/
├── migrations/                     # SQL迁移文件目录
│   ├── 000_create_migration_tables.sql    # 迁移记录表
│   ├── 20250115_001_add_user_source_field.sql
│   └── 20250115_002_fix_tables_and_fields.sql
├── data_migration_archive_tables.sql  # 归档表结构SQL
└── migration_config.sql               # 迁移配置SQL（写入advn_config表）

docs/
└── DATA_MIGRATION_GUIDE.md            # 本使用指南
```

---

## 一、SQL迁移文件管理

### 1.1 概述

SQL迁移文件管理系统类似于Laravel的迁移机制，用于：
- 跟踪数据库结构变更
- 记录已执行的迁移文件
- 避免重复执行迁移

### 1.2 迁移文件命名规范

迁移文件放置在 `sql/migrations/` 目录，命名格式：

```
YYYYMMDD_序号_描述.sql
```

示例：
- `000_create_migration_tables.sql` - 创建迁移记录表
- `20250115_001_add_user_source_field.sql` - 添加用户来源字段
- `20250115_002_fix_tables_and_fields.sql` - 修复表和字段

### 1.3 迁移记录表

系统自动创建 `advn_migration_record` 表记录迁移状态：

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT | 主键 |
| migration_name | VARCHAR(255) | 迁移文件名 |
| migration_path | VARCHAR(500) | 文件路径 |
| batch | INT | 批次号 |
| status | ENUM | pending/running/completed/failed |
| executed_at | INT | 执行时间 |
| execution_time | DECIMAL | 执行耗时(秒) |
| error_message | TEXT | 错误信息 |
| checksum | VARCHAR(64) | 文件MD5校验 |

### 1.4 迁移命令

#### 基本语法

```bash
php think data:migrate --action=文件操作 [选项]
```

#### 可用的文件操作

| 操作 | 说明 |
|------|------|
| `file:sync` | 扫描并同步迁移文件到数据库 |
| `file:status` | 查看迁移状态统计 |
| `file:pending` | 查看待执行的迁移文件 |
| `file:run` | 执行迁移文件 |
| `file:reset` | 重置失败的迁移状态 |
| `file:rollback` | 回滚指定批次的迁移 |

### 1.5 使用示例

#### 1. 同步迁移文件

首次使用或添加新迁移文件后，需要同步：

```bash
php think data:migrate --action=file:sync
```

输出示例：
```
========================================
数据迁移工具
时间: 2025-01-15 10:00:00
========================================

扫描并同步迁移文件...

同步完成:
  - 扫描文件数: 3
  - 新增记录: 1
  - 更新记录: 0
  - 跳过记录: 2

========================================
执行完成，总耗时: 0.05 秒
========================================
```

#### 2. 查看迁移状态

```bash
php think data:migrate --action=file:status
```

输出示例：
```
迁移文件状态:

总文件数: 3
记录总数: 3
待执行: 1
执行中: 0
已完成: 2
失败: 0

最近的迁移记录:
--------------------------------------------------------------------------------
20250115_002_fix_tables_and_fields.sql     | 完成     | 1 | 2025-01-15 10:05:30
20250115_001_add_user_source_field.sql     | 完成     | 1 | 2025-01-15 10:05:29
000_create_migration_tables.sql            | 完成     | 1 | 2025-01-15 10:05:28
```

#### 3. 查看待执行的迁移

```bash
php think data:migrate --action=file:pending
```

输出示例：
```
待执行的迁移文件:

--------------------------------------------------------------------------------
文件名                                     | 状态       | 校验和
--------------------------------------------------------------------------------
20250115_003_add_new_field.sql             | 新文件     | a1b2c3d4

提示: 执行 php think data:migrate --action=file:run 来执行所有待执行的迁移
```

#### 4. 执行所有待执行的迁移

```bash
php think data:migrate --action=file:run
```

输出示例：
```
执行所有待执行的迁移文件...

执行结果:
  - 总数: 1
  - 成功: 1
  - 失败: 0
```

#### 5. 执行单个迁移文件

```bash
php think data:migrate --action=file:run --file=20250115_001_add_user_source_field.sql
```

#### 6. 重置失败的迁移

如果迁移执行失败，修复问题后可重置状态：

```bash
php think data:migrate --action=file:reset
```

#### 7. 回滚指定批次

```bash
php think data:migrate --action=file:rollback --rollback=1
```

### 1.6 迁移文件编写规范

迁移文件支持标准SQL语法，可以使用占位符：

```sql
-- 使用表前缀占位符
ALTER TABLE `__PREFIX__user` ADD COLUMN `source` VARCHAR(50) DEFAULT '' COMMENT '来源';

-- 或直接使用表前缀
ALTER TABLE `advn_user` ADD COLUMN `source` VARCHAR(50) DEFAULT '' COMMENT '来源';
```

### 1.7 迁移流程

```
开始迁移
    ↓
检查迁移记录表是否存在
    ↓ (不存在)
自动创建迁移记录表和配置表
    ↓
扫描 sql/migrations/ 目录
    ↓
对比迁移记录表
    ↓
执行未完成/新增的迁移文件
    ↓
更新迁移状态为 completed/failed
    ↓
完成
```

---

## 二、数据归档迁移

### 2.1 概述

数据归档迁移用于将历史冷数据迁移到归档表，优化主表性能，提高查询效率。

### 2.2 支持迁移的数据表

| 表名 | 说明 | 默认迁移天数 |
|------|------|-------------|
| `coin_log` | 金币流水 | 90天 |
| `video_watch_record` | 视频观看记录 | 180天 |
| `video_watch_session` | 观看会话 | 30天 |
| `risk_log` | 风控日志 | 180天 |
| `user_behavior` | 用户行为记录 | 90天 |
| `anticheat_log` | 防刷日志 | 90天 |
| `red_packet_record` | 红包领取记录 | 365天 |
| `invite_commission_log` | 邀请分佣日志 | 365天 |
| `wechat_transfer_log` | 微信打款日志 | 365天 |

### 2.3 归档迁移命令

#### 基本语法

```bash
php think data:migrate [选项]
```

#### 选项说明

| 选项 | 简写 | 说明 | 默认值 |
|------|------|------|--------|
| `--action` | `-a` | 执行动作 | stats |
| `--days` | `-d` | 迁移多少天前的数据 | 90 |
| `--batch` | `-b` | 批量处理数量 | 1000 |
| `--delete` | 无 | 迁移后删除源数据 | false |
| `--table` | `-t` | 指定单个表（用于stats） | null |

#### 可用的归档操作 (action)

| 操作 | 说明 |
|------|------|
| `stats` | 查看数据统计 |
| `coin_log` | 迁移金币流水 |
| `watch_record` | 迁移视频观看记录 |
| `watch_session` | 迁移观看会话 |
| `risk_log` | 迁移风控日志 |
| `user_behavior` | 迁移用户行为记录 |
| `anticheat` | 迁移防刷日志 |
| `red_packet` | 迁移红包领取记录 |
| `commission` | 迁移邀请分佣日志 |
| `transfer` | 迁移微信打款日志 |
| `inactive` | 标记未活跃用户 |
| `clean` | 清理过期统计数据 |
| `all` | 执行所有归档迁移任务 |

### 2.4 使用示例

#### 1. 查看数据统计

```bash
# 查看所有表的数据统计
php think data:migrate --action=stats

# 查看指定表的统计
php think data:migrate --action=stats --table=coin_log

# 查看30天前的数据统计
php think data:migrate --action=stats --days=30
```

输出示例：
```
========================================
数据迁移工具
时间: 2026-03-01 12:00:00
========================================

数据表统计信息 (统计30天前的数据)
--------------------------------------------------------------------------------
表名                     | 总数据量     | 待归档       | 近期数据     | 归档表     
--------------------------------------------------------------------------------
coin_log                |    1,000,000 |      800,000 |      200,000 | 不存在    
video_watch_record      |    5,000,000 |    4,000,000 |    1,000,000 | 存在(500000)
...
```

### 2. 迁移单个表

```bash
# 迁移金币流水（90天前的数据）
php think data:migrate --action=coin_log --days=90

# 迁移观看记录（180天前的数据）
php think data:migrate --action=watch_record --days=180

# 迁移并删除源数据
php think data:migrate --action=coin_log --days=90 --delete

# 设置批量处理数量为500
php think data:migrate --action=coin_log --batch=500
```

### 3. 标记未活跃用户

```bash
# 标记90天未登录的用户
php think data:migrate --action=inactive --days=90
```

### 4. 清理过期统计

```bash
# 清理过期的用户每日收益统计和行为统计（保留365天）
php think data:migrate --action=clean --days=365
```

### 5. 执行所有迁移

```bash
# 执行所有迁移任务（默认参数）
php think data:migrate --action=all

# 执行所有迁移并删除源数据
php think data:migrate --action=all --delete

# 执行所有迁移（迁移180天前的数据）
php think data:migrate --action=all --days=180
```

### 2.5 定时任务配置

建议配置定时任务自动执行数据迁移，在业务低峰期（如凌晨3点）执行：

#### Crontab 配置

```bash
# 每天凌晨3点执行数据迁移
0 3 * * * cd /path/to/project && php think data:migrate --action=all >> /var/log/data_migration.log 2>&1
```

#### Supervisor 配置

创建 `/etc/supervisor/conf.d/data_migration.conf`：

```ini
[program:data_migration]
command=php /path/to/project/think data:migrate --action=all
autostart=false
autorestart=false
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/data_migration.log
```

### 2.6 归档表命名规则

归档表命名格式：`原表名_archive`

例如：
- `advn_coin_log` → `advn_coin_log_archive`
- `advn_video_watch_record` → `advn_video_watch_record_archive`

### 2.7 迁移流程说明

#### 迁移流程

```
开始迁移
    ↓
检查归档表是否存在
    ↓ (不存在)
自动创建归档表（复制原表结构）
    ↓
统计待迁移数据量
    ↓
分批读取数据（每批1000条）
    ↓
写入归档表
    ↓
(可选) 删除源数据
    ↓
提交事务
    ↓
继续下一批，直到完成
```

#### 事务保护

每批数据迁移都在独立事务中执行：
- 写入归档表成功 → 提交事务
- 写入失败 → 回滚事务，记录错误
- 删除源数据仅在写入成功后执行

#### 性能优化

- 分批处理，避免大表锁表
- 每批处理后短暂休眠（10ms）
- 使用事务保证数据一致性
- 支持调整批量大小

---

## 三、系统配置管理

数据迁移工具使用 `advn_config` 配置表管理所有迁移参数，可通过后台管理系统动态修改。

### 3.1 配置分组

配置项位于 `migration` 分组，可在后台「常规管理 → 系统配置 → 数据迁移」中修改。

### 3.2 配置项列表

#### 基础配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| `migration_enabled` | 启用数据迁移 | 1 |
| `migration_batch_size` | 批量处理数量 | 1000 |
| `migration_auto_archive` | 自动归档 | 0 |
| `migration_schedule` | 归档计划(Cron) | 0 3 * * * |
| `migration_delete_source` | 删除源数据 | 0 |
| `migration_log_retention` | 日志保留天数 | 365 |

#### 各表迁移天数

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| `migration_coin_log_days` | 金币流水归档天数 | 90 |
| `migration_watch_record_days` | 观看记录归档天数 | 180 |
| `migration_watch_session_days` | 观看会话归档天数 | 30 |
| `migration_risk_log_days` | 风控日志归档天数 | 180 |
| `migration_user_behavior_days` | 用户行为归档天数 | 90 |
| `migration_anticheat_days` | 防刷日志归档天数 | 90 |
| `migration_red_packet_days` | 红包记录归档天数 | 365 |
| `migration_commission_days` | 分佣日志归档天数 | 365 |
| `migration_transfer_days` | 打款日志归档天数 | 365 |

#### 清理配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| `migration_daily_stats_keep` | 每日统计保留天数 | 365 |
| `migration_behavior_stats_keep` | 行为统计保留天数 | 365 |
| `migration_inactive_days` | 未活跃用户天数 | 90 |

#### 性能配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| `migration_sleep_ms` | 批次间隔(毫秒) | 10 |
| `migration_transaction` | 启用事务 | 1 |
| `migration_max_runtime` | 最大运行时间(秒) | 3600 |

### 3.3 修改配置

#### 方式1：后台管理

进入「常规管理 → 系统配置 → 数据迁移」，直接修改配置值。

#### 方式2：SQL修改

```sql
-- 查看迁移配置
SELECT * FROM advn_config WHERE `group` = 'migration';

-- 修改金币流水归档天数为120天
UPDATE advn_config SET value = '120' WHERE name = 'migration_coin_log_days';

-- 启用自动归档
UPDATE advn_config SET value = '1' WHERE name = 'migration_auto_archive';

-- 启用删除源数据（谨慎操作）
UPDATE advn_config SET value = '1' WHERE name = 'migration_delete_source';
```

#### 方式3：代码中获取配置

```php
$service = new \app\common\library\DataMigrationService();

// 获取单个配置
$days = $service->getConfig('migration_coin_log_days', 90);

// 获取所有配置
$allConfig = $service->getAllConfig();

// 清除配置缓存（修改配置后需要调用）
\app\common\library\DataMigrationService::clearConfigCache();
```

### 3.4 配置优先级

1. 命令行参数（最高优先级）
2. 配置表 `advn_config` 中的值
3. 代码中的默认值（最低优先级）

例如：
```bash
# 配置表中 migration_coin_log_days = 90
# 命令行传入 --days=180
# 实际使用 180 天
php think data:migrate --action=coin_log --days=180
```

---

## 四、迁移日志

所有迁移操作都记录在日志表中：

```sql
-- 查看迁移日志
SELECT * FROM advn_data_migration_log ORDER BY id DESC LIMIT 20;

-- 统计迁移记录
SELECT 
    table_name,
    COUNT(*) as migrate_count,
    SUM(migrated_count) as total_migrated
FROM advn_data_migration_log 
GROUP BY table_name;
```

---

## 五、注意事项

### 5.1 迁移前

- **备份数据**：执行迁移前建议备份相关表
- **测试环境**：先在测试环境验证
- **业务低峰**：在业务低峰期执行

### 5.2 迁移中

- **监控进度**：观察迁移进度和错误日志
- **避免中断**：不要强制中断迁移进程
- **磁盘空间**：确保有足够磁盘空间

### 5.3 迁移后

- **验证数据**：检查归档表数据完整性
- **更新统计**：更新表统计信息
- **优化表**：执行 OPTIMIZE TABLE 回收空间

```sql
-- 更新表统计信息
ANALYZE TABLE advn_coin_log;

-- 优化表（回收空间）
OPTIMIZE TABLE advn_coin_log;
```

---

## 六、常见问题

### Q1: 迁移过程中出现锁表怎么办？

减小批量处理数量：
```bash
php think data:migrate --action=coin_log --batch=100
```

### Q2: 迁移失败如何处理？

1. 查看错误日志
2. 检查归档表结构是否正确
3. 重新执行迁移（已迁移的数据会自动跳过）

### Q3: 如何查询归档数据？

```sql
-- 查询归档表数据
SELECT * FROM advn_coin_log_archive WHERE user_id = 123;

-- 联合查询主表和归档表
SELECT * FROM advn_coin_log WHERE user_id = 123
UNION ALL
SELECT * FROM advn_coin_log_archive WHERE user_id = 123;
```

### Q4: 如何恢复误删的数据？

如果使用了 `--delete` 选项误删数据：
1. 从归档表重新插入数据
2. 或从备份恢复

```sql
-- 从归档表恢复数据
INSERT INTO advn_coin_log 
SELECT * FROM advn_coin_log_archive 
WHERE createtime >= UNIX_TIMESTAMP('2026-01-01');
```

## 相关文档

- [系统架构设计](./ARCHITECTURE.md)
- [数据库设计](./DATABASE_DESIGN.md)
- [风控系统设计](./RISK_CONTROL_DESIGN.md)
- [部署架构文档](../deploy/DEPLOYMENT_ARCHITECTURE.md)
