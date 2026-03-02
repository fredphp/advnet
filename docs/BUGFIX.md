# Bug Fix Documentation

## 错误修复说明

### 错误: strpos() expects parameter 1 to be string, array given

**错误路径**: `/BgYmdTvqpf.php/member/user/statistics`

**错误原因**: 

ThinkPHP 5 框架的 `Jump` trait 中定义的 `success()` 和 `error()` 方法签名是：

```php
protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
```

参数顺序是：
1. `$msg` - 提示信息
2. `$url` - 跳转URL地址
3. `$data` - 返回的数据

**问题**：FastAdmin 项目中的代码习惯写法是：
```php
$this->success('', ['total_stats' => $totalStats]);
```

这里第二个参数是数组（期望作为返回数据），但框架会将其当作 `$url` 参数处理，导致 `strpos($url, '://')` 出错。

**修复方法**：

在 `application/common/controller/Backend.php` 中重写 `success()` 和 `error()` 方法，智能处理参数：

```php
protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
{
    // 如果第二个参数是数组，则将其作为 $data 参数
    if (is_array($url)) {
        $data = $url;
        $url = null;
    }
    
    parent::success($msg, $url, $data, $wait, $header);
}
```

这样就可以继续使用 FastAdmin 的标准写法：
```php
// 以下写法都可以正常工作：
$this->success('', ['data' => $value]);           // 第二个参数是数组，作为 data
$this->success('操作成功', 'user/index');          // 第二个参数是字符串，作为 url
$this->success('成功', null, ['data' => $value]);  // 标准参数顺序
```

## 文件清单

### 修改的文件

| 文件 | 修改内容 |
|------|----------|
| `application/common/controller/Backend.php` | 重写 `success()` 和 `error()` 方法 |

## 快速修复命令

```bash
# 进入项目目录
cd /path/to/your/project

# 拉取最新代码
git pull origin master
```

## 版本历史

- 2026-03-02 v2: 重写 Backend 的 success/error 方法支持数组作为第二个参数
- 2026-03-02 v1: 初始错误文档创建
