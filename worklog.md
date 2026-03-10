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
