# 提现系统 - 完整设计文档

## 1. 系统概述

### 1.1 核心规则

| 规则 | 说明 |
|------|------|
| 金币换算 | 10000金币 = 1元人民币 |
| 提现流程 | 申请 → 冻结金币 → 审核 → 打款 → 完成 |
| 审核拒绝 | 自动退还金币到用户账户 |
| 打款失败 | 支持重试，达到次数后退还金币 |

### 1.2 提现方式

| 方式 | 说明 |
|------|------|
| wechat | 微信零钱（企业付款） |
| alipay | 支付宝转账 |
| bank | 银行卡转账 |

---

## 2. 金币冻结机制

### 2.1 冻结流程

```
┌──────────────────────────────────────────────────────────────┐
│                      金币冻结流程                             │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  1. 用户申请提现                                             │
│         │                                                    │
│         ▼                                                    │
│  2. 检查余额是否充足                                         │
│         │                                                    │
│     [余额充足?]──否──► 返回错误                              │
│         │是                                                  │
│         ▼                                                    │
│  3. 冻结金币                                                 │
│     balance = balance - coin_amount                          │
│     frozen = frozen + coin_amount                            │
│         │                                                    │
│         ▼                                                    │
│  4. 创建提现订单                                             │
│     status = 0 (待审核)                                      │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 冻结逻辑

```php
// 冻结金币
protected function freezeCoin($userId, $amount, $account = null)
{
    // 使用乐观锁防止并发
    $affected = Db::name('coin_account')
        ->where('user_id', $userId)
        ->where('version', $account['version'])
        ->update([
            'balance' => $account['balance'] - $amount,
            'frozen' => $account['frozen'] + $amount,
            'version' => $account['version'] + 1,
            'updatetime' => time(),
        ]);
    
    if ($affected === 0) {
        throw new \Exception('操作失败，请重试');
    }
}
```

### 2.3 账户状态

| 字段 | 说明 |
|------|------|
| balance | 可用余额（可提现） |
| frozen | 冻结金额（提现中） |
| total_earn | 累计获得 |
| total_withdraw | 累计提现 |

---

## 3. 事务处理逻辑

### 3.1 申请提现事务

```php
Db::startTrans();
try {
    // 1. 获取账户（加行锁）
    $account = Db::name('coin_account')
        ->where('user_id', $userId)
        ->lock(true)
        ->find();
    
    // 2. 检查余额
    if ($account['balance'] < $coinAmount) {
        throw new \Exception('金币余额不足');
    }
    
    // 3. 检查每日限制
    $this->checkDailyLimit($userId, $coinAmount, $config);
    
    // 4. 风控检测
    $riskResult = $this->riskCheck($userId, $coinAmount, $options);
    
    // 5. 冻结金币（乐观锁）
    $this->freezeCoin($userId, $coinAmount, $account);
    
    // 6. 创建订单
    $order = new WithdrawOrder();
    $order->order_no = $orderNo;
    // ... 其他字段
    $order->save();
    
    // 7. 记录流水
    $this->addCoinLog($userId, [...]);
    
    Db::commit();
    
} catch (\Exception $e) {
    Db::rollback();
    throw $e;
}
```

### 3.2 审核通过事务

```php
Db::startTrans();
try {
    // 1. 更新订单状态
    $order->status = self::STATUS_APPROVED;
    $order->audit_time = time();
    $order->save();
    
    Db::commit();
    
} catch (\Exception $e) {
    Db::rollback();
}
```

### 3.3 审核拒绝事务（退还金币）

```php
Db::startTrans();
try {
    // 1. 解冻金币
    $this->unfreezeCoin($order->user_id, $order->coin_amount);
    
    // 2. 记录流水
    $this->addCoinLog($order->user_id, [
        'type' => 'withdraw_refund',
        'amount' => $order->coin_amount,
        'description' => '提现拒绝退还',
    ]);
    
    // 3. 更新订单状态
    $order->status = self::STATUS_REJECTED;
    $order->reject_reason = $reason;
    $order->save();
    
    Db::commit();
    
} catch (\Exception $e) {
    Db::rollback();
}
```

### 3.4 打款成功事务

```php
Db::startTrans();
try {
    // 1. 扣减冻结金币
    $this->deductFrozenCoin($order->user_id, $order->coin_amount);
    
    // 2. 记录流水
    $this->addCoinLog($order->user_id, [
        'type' => 'withdraw',
        'amount' => -$order->coin_amount,
        'description' => '提现成功',
    ]);
    
    // 3. 更新订单状态
    $order->status = self::STATUS_SUCCESS;
    $order->transfer_no = $transferNo;
    $order->complete_time = time();
    $order->save();
    
    // 4. 更新统计
    $this->updateUserWithdrawStat($order);
    
    // 5. 触发分佣
    $this->triggerInviteCommission($order);
    
    Db::commit();
    
} catch (\Exception $e) {
    Db::rollback();
}
```

---

## 4. 审核流程

### 4.1 审核流程图

```
┌──────────────────────────────────────────────────────────────┐
│                      审核流程                                 │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  用户申请提现                                                │
│         │                                                    │
│         ▼                                                    │
│  ┌─────────────────┐                                         │
│  │ 风控评分检测     │                                         │
│  └─────────────────┘                                         │
│         │                                                    │
│         ├─ score >= 80 ──► 自动拒绝                          │
│         │                                                    │
│         ├─ score >= 50 ──► 人工审核                          │
│         │                                                    │
│         ▼                                                    │
│  ┌─────────────────┐                                         │
│  │ 金额检测         │                                         │
│  └─────────────────┘                                         │
│         │                                                    │
│         ├─ amount > 50元 ──► 人工审核                        │
│         │                                                    │
│         ├─ amount <= 10元 ──► 自动审核                       │
│         │                                                    │
│         ▼                                                    │
│  ┌─────────────────┐                                         │
│  │ 自动审核         │                                         │
│  └─────────────────┘                                         │
│         │                                                    │
│         ├─ 通过 ──► 进入打款队列                              │
│         │                                                    │
│         └─ 拒绝 ──► 退还金币                                 │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 4.2 审核类型

| 类型 | code | 触发条件 |
|------|------|----------|
| 自动审核 | 0 | 金额≤10元 且 风控评分<50 |
| 人工审核 | 1 | 金额>50元 或 风控评分≥50 |

### 4.3 订单状态流转

```
状态流转图:

    ┌─────────┐
    │ 待审核  │ (0)
    │         │
    └────┬────┘
         │
    ┌────┴────┬─────────────┐
    │         │             │
    ▼         ▼             ▼
┌───────┐ ┌───────┐   ┌───────┐
│审核通过│ │审核拒绝│   │已取消 │
│  (1)  │ │  (4)  │   │  (6)  │
└───┬───┘ └───────┘   └───────┘
    │
    ▼
┌───────┐
│ 打款中 │ (2)
└───┬───┘
    │
┌───┴───┬─────────┐
│       │         │
▼       ▼         ▼
┌───────┐ ┌───────┐ ┌───────┐
│提现成功│ │打款失败│ │退还金币│
│  (3)  │ │  (5)  │ │ (退款) │
└───────┘ └───────┘ └───────┘
```

---

## 5. 微信打款接口设计

### 5.1 企业付款接口

```php
// 微信企业付款API
protected function callWechatTransferApi($params, $config)
{
    // 请求参数
    $params = [
        'mch_appid' => $config['appid'],          // 公众号APPID
        'mchid' => $config['mch_id'],             // 商户号
        'nonce_str' => $this->generateNonceStr(), // 随机字符串
        'partner_trade_no' => $orderNo,           // 商户订单号
        'openid' => $openid,                       // 收款用户openid
        'check_name' => 'FORCE_CHECK',            // 强制校验实名
        're_user_name' => $realName,              // 收款用户姓名
        'amount' => $amount,                       // 金额(分)
        'desc' => '金币提现',                      // 描述
        'spbill_create_ip' => $ip,                // IP
    ];
    
    // 签名
    $params['sign'] = $this->generateSign($params, $config['api_key']);
    
    // 发送请求
    $xml = $this->arrayToXml($params);
    $response = $this->postXmlCurl('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers', $xml);
    $result = $this->xmlToArray($response);
    
    return $result;
}
```

### 5.2 响应处理

```php
if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
    // 打款成功
    return [
        'success' => true,
        'data' => [
            'transfer_no' => $result['payment_no'],
            'payment_time' => $result['payment_time'],
        ]
    ];
} else {
    // 打款失败
    return [
        'success' => false,
        'message' => $result['err_code_des'],
        'error_code' => $result['err_code'],
    ];
}
```

### 5.3 错误码处理

| 错误码 | 说明 | 处理方式 |
|--------|------|----------|
| NOTENOUGH | 余额不足 | 联系管理员充值 |
| ORDERPAID | 订单已支付 | 查询订单状态 |
| SYSTEMERROR | 系统错误 | 重试 |
| NAME_MISMATCH | 姓名不一致 | 拒绝并通知用户 |
| OPENID_ERROR | Openid错误 | 拒绝并通知用户 |

---

## 6. 风控策略

### 6.1 风控评分规则

| 风险项 | 评分 | 说明 |
|--------|------|------|
| 同IP频繁提现 | +30 | 同IP日提现≥5次 |
| 同设备频繁提现 | +30 | 同设备日提现≥3次 |
| 大额提现 | +20 | 单次≥50元 |
| 新用户提现 | +15 | 注册<7天 |
| 提现频率过高 | +25 | 1小时内≥3次 |
| 历史拒绝多 | +35 | 30天内拒绝≥3次 |

### 6.2 风控阈值

| 阈值 | 默认值 | 处理动作 |
|------|--------|----------|
| 拦截阈值 | 80分 | 自动拒绝 |
| 人工阈值 | 50分 | 转人工审核 |
| 通过阈值 | <50分 | 自动审核 |

### 6.3 风控检测代码

```php
protected function riskCheck($userId, $coinAmount, $options)
{
    $score = 0;
    $tags = [];
    $config = $this->getConfig();
    
    // 1. 同IP检测
    if ($options['ip']) {
        $ipCount = WithdrawOrder::where('ip', $options['ip'])
            ->where('createtime', '>=', strtotime('today'))
            ->count();
        if ($ipCount >= $config['same_ip_limit']) {
            $score += 30;
            $tags[] = '同IP频繁提现';
        }
    }
    
    // 2. 同设备检测
    if ($options['device_id']) {
        $deviceCount = WithdrawOrder::where('device_id', $options['device_id'])
            ->where('createtime', '>=', strtotime('today'))
            ->count();
        if ($deviceCount >= $config['same_device_limit']) {
            $score += 30;
            $tags[] = '同设备频繁提现';
        }
    }
    
    // 3. 大额提现
    $cashAmount = $this->coinToCash($coinAmount);
    if ($cashAmount >= $config['manual_audit_amount']) {
        $score += 20;
        $tags[] = '大额提现';
    }
    
    // 4. 新用户检测
    $user = User::find($userId);
    $registerDays = (time() - $user->createtime) / 86400;
    if ($registerDays < 7) {
        $score += 15;
        $tags[] = '新用户提现';
    }
    
    // 5. 提现频率
    $recentCount = WithdrawOrder::where('user_id', $userId)
        ->where('createtime', '>=', time() - 3600)
        ->count();
    if ($recentCount >= 3) {
        $score += 25;
        $tags[] = '提现频率过高';
    }
    
    // 6. 历史拒绝记录
    $rejectCount = WithdrawOrder::where('user_id', $userId)
        ->where('status', self::STATUS_REJECTED)
        ->where('createtime', '>=', time() - 86400 * 30)
        ->count();
    if ($rejectCount >= 3) {
        $score += 35;
        $tags[] = '历史拒绝记录多';
    }
    
    return [
        'score' => $score,
        'tags' => $tags,
        'pass' => $score < $config['risk_reject_threshold'],
    ];
}
```

---

## 7. 数据库设计

### 7.1 表结构

| 表名 | 说明 |
|------|------|
| advn_withdraw_config | 提现配置表 |
| advn_withdraw_order | 提现订单表 |
| advn_withdraw_risk_log | 风控记录表 |
| advn_withdraw_stat | 提现统计表 |
| advn_wechat_transfer_log | 微信打款日志表 |

### 7.2 订单状态

| 状态码 | 说明 | 可转换状态 |
|--------|------|----------|
| 0 | 待审核 | 1, 4, 6 |
| 1 | 审核通过 | 2, 4 |
| 2 | 打款中 | 3, 5 |
| 3 | 提现成功 | - |
| 4 | 审核拒绝 | - |
| 5 | 打款失败 | 1, 4 |
| 6 | 已取消 | - |

---

## 8. API 接口

### 8.1 客户端接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /api/withdraw/config | GET | 获取提现配置 |
| /api/withdraw/preview | POST | 计算提现金额 |
| /api/withdraw/apply | POST | 申请提现 |
| /api/withdraw/list | GET | 提现记录列表 |
| /api/withdraw/detail | GET | 提现详情 |
| /api/withdraw/cancel | POST | 取消提现 |
| /api/withdraw/stat | GET | 提现统计 |

### 8.2 后台接口

| 接口 | 方法 | 说明 |
|------|------|------|
| /admin/withdraw/order | GET | 订单列表 |
| /admin/withdraw/order/detail | GET | 订单详情 |
| /admin/withdraw/order/approve | POST | 审核通过 |
| /admin/withdraw/order/reject | POST | 审核拒绝 |
| /admin/withdraw/order/transfer | POST | 发起打款 |
| /admin/withdraw/order/retry | POST | 重试打款 |
| /admin/withdraw/order/stat | GET | 统计数据 |

---

## 9. 文件清单

```
/sql/withdraw_system.sql                    # 数据库SQL
/application/common/library/WithdrawService.php  # 核心服务
/application/common/model/WithdrawOrder.php      # 订单模型
/application/api/controller/Withdraw.php         # 客户端API
/application/admin/controller/withdraw/Order.php # 后台管理
/web/api/withdraw.js                         # UniApp API
```

---

## 10. 注意事项

1. **并发控制**: 使用分布式锁 + 行锁 + 乐观锁三重保护
2. **事务完整性**: 所有关键操作都在事务中执行
3. **金币流水**: 每次金币变动都记录流水，便于对账
4. **风控日志**: 记录所有风控检测结果
5. **打款日志**: 记录微信打款的请求和响应
6. **重试机制**: 打款失败支持自动/手动重试
7. **分佣触发**: 打款成功后自动触发邀请分佣
