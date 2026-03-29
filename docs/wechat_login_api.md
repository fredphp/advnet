# 微信授权登录接口文档

## 概述

本文档描述了微信授权登录相关的API接口，支持微信App、小程序、公众号三种平台的授权登录。

## 配置说明

### 后台配置入口

微信配置已集成到后台 **系统配置 -> 微信配置** 菜单中，直接访问：
```
http://你的域名/BgYmdTvqpf.php/general/config
```

在配置页面中找到 **"微信配置"** 标签页，即可配置：

- 微信App登录设置
- 微信小程序登录设置  
- 微信公众号登录设置
- 微信支付设置
- 企业付款设置（用于提现）
- 登录配置（自动注册、强制绑定手机）

### 配置项说明

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
| wechat_auto_register | 自动注册开关 |
| wechat_bind_mobile | 强制绑定手机开关 |

---

## 数据库变更

执行 `sql/wechat_config.sql` 中的SQL语句：

1. 更新 `advn_config` 表添加微信配置分组和配置项
2. 更新 `advn_user` 表添加微信相关字段

**用户表新增字段：**

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

### 6. 解绑微信

**接口地址**: `POST /api/wechat/unbindWechat`

**需要登录**: 是

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| platform | string | 是 | 平台: app/mini/official |

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

## 集成指南

### Android/iOS App集成

1. 在微信开放平台创建移动应用
2. 获取AppID和AppSecret
3. 在后台 **系统配置 -> 微信配置** 中配置微信App参数
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

### 修改文件

| 文件路径 | 修改内容 |
|----------|----------|
| `application/common/library/SystemConfigService.php` | 添加微信配置方法 |
| `application/common/model/User.php` | 添加微信相关方法 |
| `api.html` | 添加微信登录API文档 |

---

## 注意事项

1. **UnionID机制**: 如果用户同时在多个平台授权，建议使用UnionID进行用户关联
2. **安全性**: AppSecret和API密钥请妥善保管，不要泄露
3. **证书配置**: 微信支付和企业付款需要配置证书
4. **测试环境**: 正式上线前请先在测试环境验证配置正确性
5. **配置刷新**: 修改配置后，FastAdmin会自动刷新配置文件
