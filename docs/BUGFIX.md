# Bug Fix Documentation

## 错误修复说明

### 错误1: strpos() expects parameter 1 to be string, array given

**错误路径**: `/BgYmdTvqpf.php/member/user/statistics`

**错误原因**: 
- 代码仓库中缺少 `member` 模块
- 访问不存在的模块导致框架无法找到控制器

**修复方法**:

已创建完整的 `member` 模块，包含：
- `application/member/controller/User.php` - 会员控制器
- `application/member/model/User.php` - 会员模型
- `application/member/view/user/index.html` - 列表视图
- `application/member/view/user/statistics.html` - 统计视图
- `application/member/lang/zh-cn.php` - 语言包

### 错误2: Unknown column 'advn_video.deletetime' in 'where clause'

**错误路径**: `/BgYmdTvqpf.php/video/video/index`

**错误原因**: 
- 数据库表 `advn_video` 缺少 `deletetime` 字段
- video 模型使用了 SoftDelete trait（软删除功能）

**修复方法**:

1. 已创建完整的 `video` 模块，包含：
   - `application/video/controller/Video.php` - 视频控制器
   - `application/video/model/Video.php` - 视频模型
   - `application/video/view/video/index.html` - 列表视图
   - `application/video/lang/zh-cn.php` - 语言包

2. 执行数据库迁移文件添加缺失的字段和表：

```bash
mysql -u root -p advnet < sql/migrations/20260301_add_deletetime_fields.sql
```

## 文件清单

### 新增文件

```
application/
├── member/
│   ├── controller/
│   │   └── User.php          # 会员控制器
│   ├── model/
│   │   └── User.php          # 会员模型
│   ├── view/
│   │   └── user/
│   │       ├── index.html    # 列表视图
│   │       └── statistics.html # 统计视图
│   └── lang/
│       └── zh-cn.php         # 语言包
└── video/
    ├── controller/
    │   └── Video.php         # 视频控制器
    ├── model/
    │   └── Video.php         # 视频模型
    ├── view/
    │   └── video/
    │       └── index.html    # 列表视图
    └── lang/
        └── zh-cn.php         # 语言包

sql/migrations/
└── 20260301_add_deletetime_fields.sql  # 数据库迁移文件（已更新）
```

## 快速修复命令

```bash
# 进入项目目录
cd /path/to/your/project

# 拉取最新代码
git pull origin master

# 执行数据库迁移
mysql -u root -p advnet < sql/migrations/20260301_add_deletetime_fields.sql
```

## 注意事项

1. 执行SQL迁移前请先备份数据库
2. 确保服务器上的代码与Git仓库同步
3. 如果问题持续存在，请检查服务器上的完整代码是否正确部署

## 版本历史

- 2026-03-02: 创建 member 和 video 模块，修复两个 500 错误
- 2026-03-01: 初始错误文档创建
