# Bug Fix Documentation

## 错误修复说明

### 错误1: strpos() expects parameter 1 to be string, array given

**错误路径**: `/BgYmdTvqpf.php/member/user/statistics`

**错误原因**: 
- 代码仓库中缺少 `member` 模块
- 某处代码调用 `$this->error()` 或 `$this->success()` 时传入了数组类型的URL参数

**修复方法**:

1. 确保 `member` 模块已正确安装（该模块可能是通过FastAdmin插件安装的）

2. 检查 `application/member/controller/User.php` 中的 `statistics` 方法，确保所有跳转方法的URL参数是字符串类型：

```php
// 错误示例 ❌
$this->error('错误信息', ['module', 'controller', 'action']);
$this->success('成功', ['index', 'index']);
$this->redirect(['member/user/profile']);

// 正确示例 ✅
$this->error('错误信息', 'member/user/index');
$this->success('成功', 'index/index');
$this->redirect('member/user/profile');
```

### 错误2: Unknown column 'advn_video.deletetime' in 'where clause'

**错误路径**: `/BgYmdTvqpf.php/video/video/index`

**错误原因**: 
- 数据库表 `advn_video` 缺少 `deletetime` 字段
- video 模型使用了 SoftDelete trait（软删除功能）

**修复方法**:

执行以下SQL语句添加缺失的字段：

```sql
-- 为 advn_video 表添加 deletetime 字段
ALTER TABLE `advn_video` ADD COLUMN `deletetime` bigint(30) DEFAULT NULL COMMENT '删除时间' AFTER `updatetime`;
ALTER TABLE `advn_video` ADD INDEX `idx_deletetime` (`deletetime`);
```

或运行迁移文件：
```bash
mysql -u username -p database_name < sql/migrations/20260301_add_deletetime_fields.sql
```

## 快速修复命令

```bash
# 进入项目目录
cd /path/to/your/project

# 执行数据库迁移
mysql -u root -p advnet < sql/migrations/20260301_add_deletetime_fields.sql
```

## 注意事项

1. 如果 `member` 和 `video` 模块是通过 FastAdmin 插件安装的，请确保插件已正确安装和启用
2. 执行SQL迁移前请先备份数据库
3. 如果问题持续存在，请检查服务器上的完整代码是否与Git仓库同步
