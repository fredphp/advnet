# 微信授权登录接口文档

## 概述

本文档描述了微信授权登录相关的API接口，支持微信App、小程序、公众号三种平台的授权登录。

## 数据库变更

### 1. 系统配置表新增微信配置项 (advn_system_config)

执行 `sql/wechat_config.sql` 中的SQL语句，添加以下配置：

| 配置项 | 说明 |
|--------|------|
| wechat_app_enabled | 微信App登录开关 |
| wechat_app_appid | 微信App AppID |
| wechat_app_secret | 微信App Secret |
| wechat_mini_enabled | 微信小程序登录开关 |
| wechat_mini_appid | 小程序AppID |
| wechat_mini_secret | 小程序Secret |
| wechat_official_enabled | 微信公众号登录开关 |
| wechat_official_appid | 公众号AppID |
| wechat_official_secret | 公众号Secret |
| wechat_pay_enabled | 微信支付开关 |
| wechat_pay_mchid | 微信支付商户号 |
| wechat_pay_key | 微信支付API密钥 |
| wechat_transfer_enabled | 企业付款开关 |
| wechat_transfer_mchid | 企业付款商户号 |
| wechat_transfer_key | 企业付款API密钥 |
| wechat_auto_register | 自动注册开关 |
| wechat_bind_mobile | 强制绑定手机开关 |

### 2. 用户表新增微信字段 (advn_user)

执行 `sql/wechat_config.sql` 中的ALTER语句，添加以下字段：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| wechat_openid | varchar(64) | 微信App OpenID |
| wechat_unionid | varchar(64) | 微信UnionID |
| wechat_mini_openid | varchar(64) | 微信小程序OpenID |
| wechat_official_openid | varchar(64) | 微信公众号OpenID |
| wechat_nickname | varchar(100) | 微信昵称 |
| wechat_avatar | varchar(500) | 微信头像 |
| wechat_gender | tinyint | 微信性别 |
| wechat_city | varchar(50) | 微信城市 |
| wechat_province | varchar(50) | 微信省份 |
| wechat_country | varchar(50) | 微信国家 |
| wechat_bindtime | bigint | 微信绑定时间 |

---

## API接口

### 1. 微信App授权登录

**接口地址**: `POST /api/wechat/appLogin`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 微信授权返回的code |
| invite_code | string | 否 | 邀请码 |
| device_id | string | 否 | 设备ID |

**返回示例**:

```json
{
    "code": 1,
    "msg": "登录成功",
    "data": {
        "user_id": 1,
        "token": "xxxx-xxxx-xxxx-xxxx",
        "userinfo": {
            "id": 1,
            "username": "wx_abc12345",
            "nickname": "微信用户",
            "avatar": "https://...",
            "mobile": ""
        },
        "is_new": false
    }
}
```

### 2. 微信小程序登录

**接口地址**: `POST /api/wechat/miniLogin`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 小程序登录code (wx.login获取) |
| nickname | string | 否 | 用户昵称 |
| avatar | string | 否 | 用户头像 |
| gender | int | 否 | 性别: 0未知, 1男, 2女 |
| invite_code | string | 否 | 邀请码 |
| device_id | string | 否 | 设备ID |

**返回示例**: 同App登录

### 3. 微信公众号授权登录

**接口地址**: `POST /api/wechat/officialLogin`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 公众号授权code |
| invite_code | string | 否 | 邀请码 |

**返回示例**: 同App登录

### 4. 获取小程序手机号

**接口地址**: `POST /api/wechat/getMiniPhone`

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 获取手机号的code (button open-type="getPhoneNumber") |

**返回示例**:

```json
{
    "code": 1,
    "msg": "获取成功",
    "data": {
        "phoneNumber": "+8613800138000",
        "purePhoneNumber": "13800138000",
        "countryCode": "+86"
    }
}
```

### 5. 绑定微信

**接口地址**: `POST /api/wechat/bindWechat`

**需要登录**: 是

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | string | 是 | 微信授权code |
| platform | string | 是 | 平台: app/mini/official |

**返回示例**:

```json
{
    "code": 1,
    "msg": "绑定成功"
}
```

### 6. 解绑微信

**接口地址**: `POST /api/wechat/unbindWechat`

**需要登录**: 是

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| platform | string | 是 | 平台: app/mini/official |

**返回示例**:

```json
{
    "code": 1,
    "msg": "解绑成功"
}
```

### 7. 获取微信登录状态

**接口地址**: `GET /api/wechat/loginStatus`

**返回示例**:

```json
{
    "code": 1,
    "msg": "",
    "data": {
        "app_enabled": true,
        "mini_enabled": true,
        "official_enabled": false,
        "auto_register": true,
        "bind_mobile": false
    }
}
```

---

## 后台管理

### 访问路径

后台配置页面: `/admin/general/wechat_config`

### 配置分组

1. **微信App配置** - 配置微信开放平台移动应用
2. **小程序配置** - 配置微信小程序
3. **公众号配置** - 配置微信公众号
4. **微信支付配置** - 配置微信支付
5. **企业付款配置** - 配置企业付款（用于提现）
6. **登录配置** - 配置自动注册、强制绑定手机等

---

## 集成指南

### Android/iOS App集成

1. 在微信开放平台创建移动应用
2. 获取AppID和AppSecret
3. 在后台配置微信App参数
4. App端集成微信SDK，调用微信授权获取code
5. 调用 `/api/wechat/appLogin` 接口完成登录

### 小程序集成

1. 在微信公众平台创建小程序
2. 获取AppID和AppSecret
3. 在后台配置小程序参数
4. 小程序端调用 `wx.login()` 获取code
5. 调用 `/api/wechat/miniLogin` 接口完成登录

### 公众号集成

1. 在微信公众平台配置网页授权域名
2. 获取AppID和AppSecret
3. 在后台配置公众号参数
4. 网页端跳转到微信授权页面
5. 回调后调用 `/api/wechat/officialLogin` 接口完成登录

---

## 文件清单

### 新增文件

| 文件路径 | 说明 |
|----------|------|
| `sql/wechat_config.sql` | 数据库更新脚本 |
| `application/common/library/WechatService.php` | 微信服务类 |
| `application/api/controller/Wechat.php` | 微信登录API控制器 |
| `application/admin/controller/general/WechatConfig.php` | 后台配置控制器 |
| `application/admin/view/general/wechat_config/index.html` | 后台配置视图 |

### 修改文件

| 文件路径 | 修改内容 |
|----------|----------|
| `application/common/library/SystemConfigService.php` | 添加微信配置分组和方法 |
| `application/common/model/User.php` | 添加微信相关方法 |

---

## 注意事项

1. **UnionID机制**: 如果用户同时在多个平台授权，建议使用UnionID进行用户关联
2. **安全性**: AppSecret和API密钥请妥善保管，不要泄露
3. **证书配置**: 微信支付和企业付款需要配置证书
4. **测试环境**: 正式上线前请先在测试环境验证配置正确性
