# 提现订单分表修改日志

## 修改时间
2026-01-15

## 修改内容

### 1. 页面统计功能
**文件**: `application/admin/view/withdraw/order/index.html`
- 在列表顶部添加统计卡片区域，显示：
  - 今日提现金额（人民币）+ 笔数
  - 待审核金额（人民币）+ 笔数
  - 待打款金额（人民币）+ 笔数
  - 已拒绝金额（人民币）+ 笔数
  - 已成功金额（人民币）+ 笔数

**文件**: `public/assets/js/backend/withdraw/order.js`
- 添加 `loadStats()` 方法，调用 `/withdraw/order/getStats` 接口获取统计数据

**文件**: `application/admin/controller/withdraw/Order.php`
- 添加 `getStats()` 方法，统计当年分表中的各状态金额和笔数

### 2. 分表逻辑修改：从按月分表改为按年分表

#### 2.1 WithdrawOrder 模型修改
**文件**: `application/common/model/WithdrawOrder.php`
- `getTableByMonth()` 改为 `getTableByYear()`，返回格式从 `withdraw_order_202603` 改为 `withdraw_order_2026`
- `getTablesByRange()` 方法从按月遍历改为按年遍历
- `getTableByOrderNo()` 方法从提取年月改为提取年份

#### 2.2 WithdrawOrderSplit 模型修改
**文件**: `application/common/model/WithdrawOrderSplit.php`
- `$splitType` 属性从 `'month'` 改为 `'year'`

#### 2.3 SplitTableModel 基类修改
**文件**: `application/common/model/SplitTableModel.php`
- 默认 `$splitType` 属性从 `'month'` 改为 `'year'`
- `autoCreateTables()` 方法支持按年创建分表（创建当年和下一年分表）

#### 2.4 后台 Order 控制器修改
**文件**: `application/admin/controller/withdraw/Order.php`
- `index()` 方法默认查询当年数据（从 `Y-m-01` 改为 `Y-01-01`）
- `export()` 方法默认导出当年数据
- `getOrderById()` 方法查询范围从6个月改为6年
- `getOrderByNo()` 方法优先从订单号提取年份查询对应分表
- `getUserTotalStats()` 方法查询范围从12个月改为12年
- `getRecentOrders()` 方法查询范围从3个月改为3年
- `pending()` 方法查询范围从当月改为当年

#### 2.5 API 端 Withdraw 控制器修改
**文件**: `application/api/controller/Withdraw.php`
- `detail()` 方法使用服务层的分表查询方法
- `stat()` 方法使用分表查询获取统计数据

#### 2.6 WithdrawService 服务修改
**文件**: `application/common/library/WithdrawService.php`
- 添加 `getOrderByIdAndUserId()` 方法支持分表查询
- 修改 `getUserOrders()` 方法使用分表查询（查询最近3年数据）
- 新增订单自动写入当年分表

### 3. 表名格式变更
| 变更前 | 变更后 |
|--------|--------|
| withdraw_order_202603 | withdraw_order_2026 |
| withdraw_order_202604 | withdraw_order_2026 |
| withdraw_order_202501 | withdraw_order_2025 |

### 4. 查询范围变更
| 场景 | 变更前 | 变更后 |
|------|--------|--------|
| 默认查询 | 当月数据 | 当年数据 |
| 列表查询 | 最近3个月 | 最近3年 |
| 订单查找 | 最近6个月 | 最近6年 |
| 累计统计 | 最近12个月 | 最近12年 |

### 5. 数据迁移建议
如果已有按月分表的数据，需要执行数据迁移：
```sql
-- 创建年度分表
CREATE TABLE withdraw_order_2025 LIKE withdraw_order;
CREATE TABLE withdraw_order_2026 LIKE withdraw_order;

-- 将各月数据合并到年度表
INSERT INTO withdraw_order_2025 SELECT * FROM withdraw_order_202501;
INSERT INTO withdraw_order_2025 SELECT * FROM withdraw_order_202502;
-- ... 以此类推

INSERT INTO withdraw_order_2026 SELECT * FROM withdraw_order_202601;
INSERT INTO withdraw_order_2026 SELECT * FROM withdraw_order_202602;
-- ... 以此类推
```

## 测试要点
1. 验证页面统计卡片数据显示正确
2. 验证列表默认加载当年数据
3. 验证新增提现订单写入当年分表
4. 验证跨年查询能正确获取历史数据
5. 验证API端提现统计、详情等接口正常工作

---
Task ID: commission-simplify
Agent: Z.ai Code
Task: 简化分佣系统，只保留提现分佣

Work Log:
- 分析现有分佣逻辑，发现有多处分佣触发点
- 删除 invite/commissionconfig 控制器、视图、JS文件
- 简化 InviteCommissionService，只保留 triggerWithdrawCommission
- 注释 VideoRewardService 中的分佣调用
- 创建 SQL 部署脚本 remove_commissionconfig.sql
- 创建 SQL 部署脚本 update_commission_config.sql
- 创建完整部署脚本 deploy_commission_update.sql
- 删除多余的分佣配置项
- 推送所有更改到 GitHub

Stage Summary:
- 分佣系统已简化，只保留提现分佣
- 配置从 advn_invite_commission_config 表移到 advn_config 表
- 相关菜单已删除
- 代码已推送到 GitHub

---
Task ID: 2
Agent: Main Agent
Task: 将重新绑定上级功能更新到GitHub项目

Work Log:
- 检查项目状态，确认功能已实现
- 验证Relation控制器包含完整的重新绑定上级方法
- 验证前端JS包含showRebindModal、doRebind等功能
- 验证rebind.html视图文件完整
- 验证migrationlog.html视图文件完整
- 验证数据库迁移文件已创建
- 推送代码到GitHub (commit: 4e1fe95)

Stage Summary:
- 代码已成功推送到 https://github.com/fredphp/advnet.git
- 功能完整实现：
  1. 后端API：getUserDetail、getNewParentDetail、rebindParent、migrationLog
  2. 前端视图：rebind.html（重新绑定弹窗）、migrationlog.html（迁移记录列表）
  3. 前端JS：showRebindModal、loadUserDetail、loadNewParentDetail、doRebind
  4. 数据库表：fa_invite_relation_migration_log
  5. 迁移文件：sql/migrations/20260311_create_invite_relation_migration_log.sql
