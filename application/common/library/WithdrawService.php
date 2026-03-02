<?php

namespace app\common\library;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\WithdrawOrder;
use app\common\model\User;

/**
 * 提现服务类
 * 
 * 核心功能：
 * 1. 申请提现（冻结金币）
 * 2. 审核流程（自动审核/人工审核）
 * 3. 微信打款
 * 4. 风控检测
 */
class WithdrawService
{
    // Redis键前缀
    const CACHE_PREFIX = 'withdraw:';
    const LOCK_PREFIX = 'lock:withdraw:';
    
    // 金币换算比例：10000金币 = 1元
    const COIN_RATE = 10000;
    
    // 订单状态
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_APPROVED = 1;     // 审核通过
    const STATUS_TRANSFERING = 2;  // 打款中
    const STATUS_SUCCESS = 3;      // 提现成功
    const STATUS_REJECTED = 4;     // 审核拒绝
    const STATUS_FAILED = 5;       // 打款失败
    const STATUS_CANCELED = 6;     // 已取消
    
    /**
     * 申请提现
     * @param int $userId 用户ID
     * @param float $coinAmount 提现金币数量
     * @param array $options 提现选项
     * @return array
     */
    public function apply($userId, $coinAmount, $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null,
        ];
        
        // 参数校验
        if ($coinAmount <= 0) {
            $result['message'] = '提现金额必须大于0';
            return $result;
        }
        
        // 获取配置
        $config = $this->getConfig();
        
        // 检查最低提现
        if ($coinAmount < $config['min_withdraw']) {
            $result['message'] = "最低提现{$config['min_withdraw']}金币";
            return $result;
        }
        
        // 检查最高提现
        if ($coinAmount > $config['max_withdraw']) {
            $result['message'] = "单次最高提现{$config['max_withdraw']}金币";
            return $result;
        }
        
        // 分布式锁（防并发）
        $lockKey = self::LOCK_PREFIX . "apply:{$userId}";
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取用户账户（加锁）
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$account) {
                throw new \Exception('账户不存在');
            }
            
            // 检查余额（可用余额 = 余额 - 冻结）
            $availableBalance = $account['balance'];
            if ($availableBalance < $coinAmount) {
                throw new \Exception('金币余额不足');
            }
            
            // 检查每日提现限制
            $this->checkDailyLimit($userId, $coinAmount, $config);
            
            // 检查新用户限制
            $this->checkNewUserLimit($userId, $config);
            
            // 计算人民币金额
            $cashAmount = $this->coinToCash($coinAmount);
            
            // 计算手续费
            $feeAmount = $this->calculateFee($cashAmount, $config);
            $actualAmount = $cashAmount - $feeAmount;
            
            // 风控检测
            $riskResult = $this->riskCheck($userId, $coinAmount, $options);
            
            // 确定审核类型
            $auditType = $this->determineAuditType($cashAmount, $riskResult, $config);
            
            // 冻结金币
            $freezeResult = $this->freezeCoin($userId, $coinAmount, $account);
            if (!$freezeResult['success']) {
                throw new \Exception($freezeResult['message']);
            }
            
            // 创建提现订单
            $orderNo = WithdrawOrder::generateOrderNo();
            $order = new WithdrawOrder();
            $order->order_no = $orderNo;
            $order->user_id = $userId;
            $order->coin_amount = $coinAmount;
            $order->exchange_rate = self::COIN_RATE;
            $order->cash_amount = $cashAmount;
            $order->fee_amount = $feeAmount;
            $order->actual_amount = $actualAmount;
            $order->withdraw_type = $options['withdraw_type'] ?? 'wechat';
            $order->withdraw_account = $options['withdraw_account'] ?? '';
            $order->withdraw_name = $options['withdraw_name'] ?? '';
            $order->bank_name = $options['bank_name'] ?? null;
            $order->bank_branch = $options['bank_branch'] ?? null;
            $order->status = self::STATUS_PENDING;
            $order->audit_type = $auditType;
            $order->risk_score = $riskResult['score'];
            $order->risk_tags = json_encode($riskResult['tags']);
            $order->ip = $options['ip'] ?? null;
            $order->device_id = $options['device_id'] ?? null;
            $order->user_agent = $options['user_agent'] ?? null;
            $order->createtime = time();
            $order->save();
            
            // 记录金币流水（冻结）
            $this->addCoinLog($userId, [
                'type' => 'withdraw_freeze',
                'amount' => -$coinAmount,
                'balance_before' => $account['balance'],
                'balance_after' => $account['balance'] - $coinAmount,
                'relation_type' => 'withdraw',
                'relation_id' => $order->id,
                'description' => "提现申请冻结，订单号: {$orderNo}",
            ]);
            
            // 自动审核
            if ($auditType == 0) {
                $auditResult = $this->autoAudit($order->id);
                if ($auditResult['success']) {
                    $order->status = $auditResult['status'];
                    $order->audit_time = time();
                    $order->audit_remark = '自动审核通过';
                }
            }
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '提现申请成功，等待审核';
            $result['data'] = [
                'order_no' => $orderNo,
                'coin_amount' => $coinAmount,
                'cash_amount' => $cashAmount,
                'fee_amount' => $feeAmount,
                'actual_amount' => $actualAmount,
                'status' => $order->status,
                'audit_type' => $auditType == 0 ? '自动审核' : '人工审核',
            ];
            
            // 清除账户缓存
            $this->clearAccountCache($userId);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('提现申请失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 冻结金币
     */
    protected function freezeCoin($userId, $amount, $account = null)
    {
        $result = ['success' => false, 'message' => ''];
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        if (!$account) {
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
        }
        
        if (!$account || $account['balance'] < $amount) {
            $result['message'] = '金币余额不足';
            return $result;
        }
        
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
            $result['message'] = '操作失败，请重试';
            return $result;
        }
        
        $result['success'] = true;
        return $result;
    }
    
    /**
     * 解冻金币（退还）
     */
    protected function unfreezeCoin($userId, $amount)
    {
        $result = ['success' => false, 'message' => ''];
        
        $account = Db::name('coin_account')
            ->where('user_id', $userId)
            ->lock(true)
            ->find();
        
        if (!$account || $account['frozen'] < $amount) {
            $result['message'] = '冻结金币不足';
            return $result;
        }
        
        $affected = Db::name('coin_account')
            ->where('user_id', $userId)
            ->where('version', $account['version'])
            ->update([
                'balance' => $account['balance'] + $amount,
                'frozen' => $account['frozen'] - $amount,
                'version' => $account['version'] + 1,
                'updatetime' => time(),
            ]);
        
        if ($affected === 0) {
            $result['message'] = '操作失败，请重试';
            return $result;
        }
        
        $result['success'] = true;
        return $result;
    }
    
    /**
     * 扣减冻结金币（打款成功后）
     */
    protected function deductFrozenCoin($userId, $amount)
    {
        $result = ['success' => false, 'message' => ''];
        
        $account = Db::name('coin_account')
            ->where('user_id', $userId)
            ->lock(true)
            ->find();
        
        if (!$account || $account['frozen'] < $amount) {
            $result['message'] = '冻结金币不足';
            return $result;
        }
        
        $affected = Db::name('coin_account')
            ->where('user_id', $userId)
            ->where('version', $account['version'])
            ->update([
                'frozen' => $account['frozen'] - $amount,
                'total_withdraw' => $account['total_withdraw'] + $amount,
                'version' => $account['version'] + 1,
                'updatetime' => time(),
            ]);
        
        if ($affected === 0) {
            $result['message'] = '操作失败，请重试';
            return $result;
        }
        
        $result['success'] = true;
        return $result;
    }
    
    /**
     * 自动审核
     */
    protected function autoAudit($orderId)
    {
        $result = ['success' => false, 'status' => self::STATUS_PENDING];
        
        $order = WithdrawOrder::find($orderId);
        if (!$order) {
            return $result;
        }
        
        $config = $this->getConfig();
        
        // 检查风控评分
        if ($order->risk_score >= $config['risk_reject_threshold']) {
            // 风控评分过高，自动拒绝
            $this->rejectOrder($orderId, '系统风控拦截', 0, 'system');
            $result['status'] = self::STATUS_REJECTED;
            $result['success'] = true;
            return $result;
        }
        
        // 检查用户状态
        $user = User::find($order->user_id);
        if (!$user || $user->status != 'normal') {
            $this->rejectOrder($orderId, '用户状态异常', 0, 'system');
            $result['status'] = self::STATUS_REJECTED;
            $result['success'] = true;
            return $result;
        }
        
        // 自动审核通过
        $order->status = self::STATUS_APPROVED;
        $order->audit_type = 0;
        $order->audit_time = time();
        $order->audit_remark = '自动审核通过';
        $order->save();
        
        $result['status'] = self::STATUS_APPROVED;
        $result['success'] = true;
        
        return $result;
    }
    
    /**
     * 人工审核通过
     */
    public function approveOrder($orderId, $adminId, $adminName, $remark = '')
    {
        $result = ['success' => false, 'message' => ''];
        
        $lockKey = self::LOCK_PREFIX . "audit:{$orderId}";
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $order = WithdrawOrder::where('id', $orderId)->lock(true)->find();
            
            if (!$order) {
                throw new \Exception('订单不存在');
            }
            
            if ($order->status != self::STATUS_PENDING) {
                throw new \Exception('订单状态异常');
            }
            
            $order->status = self::STATUS_APPROVED;
            $order->audit_type = 1;
            $order->audit_admin_id = $adminId;
            $order->audit_admin_name = $adminName;
            $order->audit_time = time();
            $order->audit_remark = $remark;
            $order->save();
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '审核通过';
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('审核通过失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 审核拒绝
     */
    public function rejectOrder($orderId, $reason, $adminId = 0, $adminName = 'system')
    {
        $result = ['success' => false, 'message' => ''];
        
        $lockKey = self::LOCK_PREFIX . "audit:{$orderId}";
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $order = WithdrawOrder::where('id', $orderId)->lock(true)->find();
            
            if (!$order) {
                throw new \Exception('订单不存在');
            }
            
            if (!in_array($order->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
                throw new \Exception('订单状态异常');
            }
            
            // 解冻金币（退还给用户）
            $unfreezeResult = $this->unfreezeCoin($order->user_id, $order->coin_amount);
            if (!$unfreezeResult['success']) {
                throw new \Exception($unfreezeResult['message']);
            }
            
            // 记录金币流水（退还）
            $account = Db::name('coin_account')->where('user_id', $order->user_id)->find();
            $this->addCoinLog($order->user_id, [
                'type' => 'withdraw_refund',
                'amount' => $order->coin_amount,
                'balance_before' => $account['balance'],
                'balance_after' => $account['balance'] + $order->coin_amount,
                'relation_type' => 'withdraw',
                'relation_id' => $order->id,
                'description' => "提现拒绝退还，订单号: {$order->order_no}，原因: {$reason}",
            ]);
            
            // 更新订单状态
            $order->status = self::STATUS_REJECTED;
            $order->audit_admin_id = $adminId;
            $order->audit_admin_name = $adminName;
            $order->audit_time = time();
            $order->reject_reason = $reason;
            $order->save();
            
            // 记录风控日志
            $this->addRiskLog($order->user_id, $order->order_no, 'manual_reject', 1, 0, [
                'reason' => $reason,
                'admin' => $adminName,
            ]);
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '审核拒绝，金币已退还';
            
            $this->clearAccountCache($order->user_id);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('审核拒绝失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 发起打款
     */
    public function transfer($orderId)
    {
        $result = ['success' => false, 'message' => '', 'data' => null];
        
        $lockKey = self::LOCK_PREFIX . "transfer:{$orderId}";
        $lock = $this->getLock($lockKey, 30);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $order = WithdrawOrder::where('id', $orderId)->lock(true)->find();
            
            if (!$order) {
                throw new \Exception('订单不存在');
            }
            
            if ($order->status != self::STATUS_APPROVED) {
                throw new \Exception('订单状态异常');
            }
            
            // 更新状态为打款中
            $order->status = self::STATUS_TRANSFERING;
            $order->transfer_time = time();
            $order->save();
            
            Db::commit();
            
            // 调用微信打款接口
            $transferResult = $this->wechatTransfer($order);
            
            Db::startTrans();
            
            // 重新获取订单
            $order = WithdrawOrder::where('id', $orderId)->lock(true)->find();
            
            if ($transferResult['success']) {
                // 打款成功
                // 扣减冻结金币
                $this->deductFrozenCoin($order->user_id, $order->coin_amount);
                
                // 记录金币流水
                $account = Db::name('coin_account')->where('user_id', $order->user_id)->find();
                $this->addCoinLog($order->user_id, [
                    'type' => 'withdraw',
                    'amount' => -$order->coin_amount,
                    'balance_before' => $account['balance'],
                    'balance_after' => $account['balance'],
                    'relation_type' => 'withdraw',
                    'relation_id' => $order->id,
                    'description' => "提现成功，订单号: {$order->order_no}",
                ]);
                
                $order->status = self::STATUS_SUCCESS;
                $order->transfer_no = $transferResult['data']['transfer_no'] ?? null;
                $order->transfer_result = json_encode($transferResult['data']);
                $order->complete_time = time();
                $order->save();
                
                // 更新用户提现统计
                $this->updateUserWithdrawStat($order);
                
                // 触发邀请分佣
                $this->triggerInviteCommission($order);
                
                $result['success'] = true;
                $result['message'] = '打款成功';
                $result['data'] = $transferResult['data'];
                
            } else {
                // 打款失败
                $order->status = self::STATUS_FAILED;
                $order->fail_reason = $transferResult['message'];
                $order->transfer_result = json_encode($transferResult['data'] ?? []);
                $order->retry_count = $order->retry_count + 1;
                
                $config = $this->getConfig();
                if ($order->retry_count < $config['transfer_retry_count']) {
                    $order->next_retry_time = time() + $config['transfer_retry_interval'];
                }
                $order->save();
                
                $result['message'] = '打款失败: ' . $transferResult['message'];
            }
            
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('打款失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 微信打款接口
     */
    protected function wechatTransfer($order)
    {
        $result = ['success' => false, 'message' => '', 'data' => null];
        
        try {
            // 获取微信配置
            $wechatConfig = $this->getWechatConfig();
            
            // 生成商户订单号
            $partnerTradeNo = $order->order_no;
            
            // 金额（分）
            $amount = intval($order->actual_amount * 100);
            
            // 获取用户openid
            $openid = $this->getUserOpenid($order->user_id);
            if (empty($openid)) {
                throw new \Exception('用户未绑定微信');
            }
            
            // 调用微信企业付款接口
            // TODO: 实际调用微信API，这里模拟返回
            $transferResult = $this->callWechatTransferApi([
                'mch_appid' => $wechatConfig['appid'],
                'mchid' => $wechatConfig['mch_id'],
                'nonce_str' => $this->generateNonceStr(),
                'partner_trade_no' => $partnerTradeNo,
                'openid' => $openid,
                'check_name' => 'FORCE_CHECK', // 强制校验实名
                're_user_name' => $order->withdraw_name,
                'amount' => $amount,
                'desc' => '金币提现',
                'spbill_create_ip' => $order->ip ?? '127.0.0.1',
            ], $wechatConfig);
            
            if ($transferResult['return_code'] == 'SUCCESS' && $transferResult['result_code'] == 'SUCCESS') {
                $result['success'] = true;
                $result['data'] = [
                    'transfer_no' => $transferResult['payment_no'] ?? null,
                    'partner_trade_no' => $partnerTradeNo,
                    'payment_time' => $transferResult['payment_time'] ?? null,
                ];
            } else {
                $result['message'] = $transferResult['err_code_des'] ?? '微信打款失败';
                $result['data'] = $transferResult;
            }
            
            // 记录打款日志
            $this->addWechatTransferLog($order, $transferResult);
            
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            Log::error('微信打款异常: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 调用微信企业付款API
     */
    protected function callWechatTransferApi($params, $config)
    {
        // TODO: 实际调用微信API
        // 这里返回模拟数据，实际项目中需要实现完整的微信API调用
        
        // 1. 生成签名
        // 2. 组装XML
        // 3. 发送请求
        // 4. 解析响应
        // 5. 验证签名
        
        // 模拟成功响应
        return [
            'return_code' => 'SUCCESS',
            'result_code' => 'SUCCESS',
            'payment_no' => 'WX' . date('YmdHis') . rand(1000, 9999),
            'payment_time' => date('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * 重试打款
     */
    public function retryTransfer($orderId)
    {
        $order = WithdrawOrder::find($orderId);
        
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        
        if ($order->status != self::STATUS_FAILED) {
            return ['success' => false, 'message' => '订单状态异常'];
        }
        
        $config = $this->getConfig();
        if ($order->retry_count >= $config['transfer_retry_count']) {
            return ['success' => false, 'message' => '已达到最大重试次数'];
        }
        
        // 重置状态为审核通过
        $order->status = self::STATUS_APPROVED;
        $order->save();
        
        return $this->transfer($orderId);
    }
    
    /**
     * 取消提现（用户主动取消）
     */
    public function cancelOrder($orderId, $userId)
    {
        $result = ['success' => false, 'message' => ''];
        
        $lockKey = self::LOCK_PREFIX . "cancel:{$orderId}";
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $order = WithdrawOrder::where('id', $orderId)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$order) {
                throw new \Exception('订单不存在');
            }
            
            if (!in_array($order->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
                throw new \Exception('订单状态不允许取消');
            }
            
            // 解冻金币
            $unfreezeResult = $this->unfreezeCoin($order->user_id, $order->coin_amount);
            if (!$unfreezeResult['success']) {
                throw new \Exception($unfreezeResult['message']);
            }
            
            // 记录金币流水
            $account = Db::name('coin_account')->where('user_id', $order->user_id)->find();
            $this->addCoinLog($order->user_id, [
                'type' => 'withdraw_cancel',
                'amount' => $order->coin_amount,
                'balance_before' => $account['balance'],
                'balance_after' => $account['balance'] + $order->coin_amount,
                'relation_type' => 'withdraw',
                'relation_id' => $order->id,
                'description' => "取消提现，订单号: {$order->order_no}",
            ]);
            
            $order->status = self::STATUS_CANCELED;
            $order->complete_time = time();
            $order->save();
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '取消成功，金币已退还';
            
            $this->clearAccountCache($order->user_id);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('取消提现失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 风控检测
     */
    protected function riskCheck($userId, $coinAmount, $options)
    {
        $result = [
            'score' => 0,
            'tags' => [],
            'pass' => true,
        ];
        
        $config = $this->getConfig();
        
        // 1. 检查同一IP提现次数
        $ip = $options['ip'] ?? '';
        if ($ip) {
            $ipCount = WithdrawOrder::where('ip', $ip)
                ->where('createtime', '>=', strtotime('today'))
                ->count();
            
            if ($ipCount >= $config['same_ip_limit']) {
                $result['score'] += 30;
                $result['tags'][] = '同IP频繁提现';
            }
        }
        
        // 2. 检查同一设备提现次数
        $deviceId = $options['device_id'] ?? '';
        if ($deviceId) {
            $deviceCount = WithdrawOrder::where('device_id', $deviceId)
                ->where('createtime', '>=', strtotime('today'))
                ->count();
            
            if ($deviceCount >= $config['same_device_limit']) {
                $result['score'] += 30;
                $result['tags'][] = '同设备频繁提现';
            }
        }
        
        // 3. 检查大额提现
        $cashAmount = $this->coinToCash($coinAmount);
        if ($cashAmount >= $config['manual_audit_amount']) {
            $result['score'] += 20;
            $result['tags'][] = '大额提现';
        }
        
        // 4. 检查用户注册时间
        $user = User::find($userId);
        if ($user) {
            $registerDays = (time() - $user->createtime) / 86400;
            if ($registerDays < 7) {
                $result['score'] += 15;
                $result['tags'][] = '新用户提现';
            }
        }
        
        // 5. 检查提现频率
        $recentCount = WithdrawOrder::where('user_id', $userId)
            ->where('createtime', '>=', time() - 3600)
            ->count();
        
        if ($recentCount >= 3) {
            $result['score'] += 25;
            $result['tags'][] = '提现频率过高';
        }
        
        // 6. 检查历史拒绝记录
        $rejectCount = WithdrawOrder::where('user_id', $userId)
            ->where('status', self::STATUS_REJECTED)
            ->where('createtime', '>=', time() - 86400 * 30)
            ->count();
        
        if ($rejectCount >= 3) {
            $result['score'] += 35;
            $result['tags'][] = '历史拒绝记录多';
        }
        
        // 判断是否通过
        if ($result['score'] >= $config['risk_reject_threshold']) {
            $result['pass'] = false;
        }
        
        // 记录风控日志
        if (!empty($result['tags'])) {
            $this->addRiskLog($userId, null, 'risk_check', $result['score'] >= 50 ? 2 : 1, $result['score'], $result['tags']);
        }
        
        return $result;
    }
    
    /**
     * 确定审核类型
     */
    protected function determineAuditType($cashAmount, $riskResult, $config)
    {
        // 风控评分过高，需人工审核
        if ($riskResult['score'] >= $config['risk_manual_threshold']) {
            return 1;
        }
        
        // 金额超过自动审核阈值，需人工审核
        if ($cashAmount > $config['auto_audit_amount']) {
            return 1;
        }
        
        return 0; // 自动审核
    }
    
    /**
     * 检查每日提现限制
     */
    protected function checkDailyLimit($userId, $coinAmount, $config)
    {
        $today = strtotime('today');
        
        // 检查次数
        $todayCount = WithdrawOrder::where('user_id', $userId)
            ->where('createtime', '>=', $today)
            ->whereNotIn('status', [self::STATUS_CANCELED])
            ->count();
        
        if ($todayCount >= $config['daily_withdraw_limit']) {
            throw new \Exception('今日提现次数已达上限');
        }
        
        // 检查金额
        $todayAmount = WithdrawOrder::where('user_id', $userId)
            ->where('createtime', '>=', $today)
            ->whereNotIn('status', [self::STATUS_CANCELED])
            ->sum('cash_amount');
        
        $cashAmount = $this->coinToCash($coinAmount);
        if ($todayAmount + $cashAmount > $config['daily_withdraw_amount']) {
            throw new \Exception('今日提现金额已达上限');
        }
    }
    
    /**
     * 检查新用户限制
     */
    protected function checkNewUserLimit($userId, $config)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }
        
        $registerDays = (time() - $user->createtime) / 86400;
        if ($registerDays < $config['new_user_withdraw_days']) {
            throw new \Exception("注册{$config['new_user_withdraw_days']}天后才能提现");
        }
    }
    
    /**
     * 金币转人民币
     */
    protected function coinToCash($coinAmount)
    {
        return round($coinAmount / self::COIN_RATE, 4);
    }
    
    /**
     * 计算手续费
     */
    protected function calculateFee($cashAmount, $config)
    {
        $feeRate = floatval($config['fee_rate'] ?? 0);
        return round($cashAmount * $feeRate, 4);
    }
    
    /**
     * 获取配置
     */
    protected function getConfig()
    {
        static $config = null;
        
        if ($config !== null) {
            return $config;
        }
        
        $list = Db::name('withdraw_config')->select();
        $config = [];
        
        foreach ($list as $item) {
            $config[$item['code']] = $item['value'];
        }
        
        // 默认值
        $defaults = [
            'exchange_rate' => 10000,
            'min_withdraw' => 10000,
            'max_withdraw' => 1000000,
            'daily_withdraw_limit' => 3,
            'daily_withdraw_amount' => 100,
            'fee_rate' => 0,
            'auto_audit_amount' => 10,
            'manual_audit_amount' => 50,
            'new_user_withdraw_days' => 3,
            'risk_reject_threshold' => 80,
            'risk_manual_threshold' => 50,
            'same_ip_limit' => 5,
            'same_device_limit' => 3,
            'transfer_retry_count' => 3,
            'transfer_retry_interval' => 300,
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }
        
        return $config;
    }
    
    /**
     * 获取微信配置
     */
    protected function getWechatConfig()
    {
        // TODO: 从配置或环境变量获取
        return [
            'appid' => '',
            'mch_id' => '',
            'api_key' => '',
            'cert_path' => '',
            'key_path' => '',
        ];
    }
    
    /**
     * 获取用户OpenID
     */
    protected function getUserOpenid($userId)
    {
        // TODO: 从用户绑定信息获取
        return Db::name('user_oauth')
            ->where('user_id', $userId)
            ->where('platform', 'wechat')
            ->value('openid');
    }
    
    /**
     * 生成随机字符串
     */
    protected function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    /**
     * 添加金币流水
     */
    protected function addCoinLog($userId, $data)
    {
        $tableName = 'coin_log_' . date('Ym');
        
        Db::name($tableName)->insert([
            'user_id' => $userId,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'balance_before' => $data['balance_before'] ?? 0,
            'balance_after' => $data['balance_after'] ?? 0,
            'relation_type' => $data['relation_type'] ?? null,
            'relation_id' => $data['relation_id'] ?? null,
            'description' => $data['description'] ?? '',
            'createtime' => time(),
            'create_date' => date('Y-m-d'),
        ]);
    }
    
    /**
     * 添加风控日志
     */
    protected function addRiskLog($userId, $orderNo, $riskType, $riskLevel, $riskScore, $riskDetail)
    {
        Db::name('withdraw_risk_log')->insert([
            'user_id' => $userId,
            'order_no' => $orderNo,
            'risk_type' => $riskType,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_detail' => json_encode($riskDetail),
            'createtime' => time(),
        ]);
    }
    
    /**
     * 添加微信打款日志
     */
    protected function addWechatTransferLog($order, $transferResult)
    {
        Db::name('wechat_transfer_log')->insert([
            'order_no' => $order->order_no,
            'transfer_no' => $transferResult['payment_no'] ?? null,
            'partner_trade_no' => $order->order_no,
            'amount' => intval($order->actual_amount * 100),
            'description' => '金币提现',
            'response_data' => json_encode($transferResult),
            'status' => ($transferResult['return_code'] == 'SUCCESS' && $transferResult['result_code'] == 'SUCCESS') ? 2 : 3,
            'error_code' => $transferResult['err_code'] ?? null,
            'error_msg' => $transferResult['err_code_des'] ?? null,
            'createtime' => time(),
        ]);
    }
    
    /**
     * 更新用户提现统计
     */
    protected function updateUserWithdrawStat($order)
    {
        $stat = Db::name('withdraw_stat')->where('user_id', $order->user_id)->find();
        
        if (!$stat) {
            Db::name('withdraw_stat')->insert([
                'user_id' => $order->user_id,
                'total_withdraw_count' => 1,
                'total_withdraw_amount' => $order->cash_amount,
                'total_withdraw_coin' => $order->coin_amount,
                'total_fee_amount' => $order->fee_amount,
                'success_count' => 1,
                'today_withdraw_count' => 1,
                'today_withdraw_amount' => $order->cash_amount,
                'today_withdraw_date' => date('Y-m-d'),
                'first_withdraw_time' => time(),
                'last_withdraw_time' => time(),
                'createtime' => time(),
                'updatetime' => time(),
            ]);
        } else {
            $today = date('Y-m-d');
            $todayCount = $stat['today_withdraw_date'] == $today ? $stat['today_withdraw_count'] + 1 : 1;
            $todayAmount = $stat['today_withdraw_date'] == $today ? $stat['today_withdraw_amount'] + $order->cash_amount : $order->cash_amount;
            
            Db::name('withdraw_stat')->where('user_id', $order->user_id)->update([
                'total_withdraw_count' => $stat['total_withdraw_count'] + 1,
                'total_withdraw_amount' => $stat['total_withdraw_amount'] + $order->cash_amount,
                'total_withdraw_coin' => $stat['total_withdraw_coin'] + $order->coin_amount,
                'total_fee_amount' => $stat['total_fee_amount'] + $order->fee_amount,
                'success_count' => $stat['success_count'] + 1,
                'today_withdraw_count' => $todayCount,
                'today_withdraw_amount' => $todayAmount,
                'today_withdraw_date' => $today,
                'last_withdraw_time' => time(),
                'updatetime' => time(),
            ]);
        }
    }
    
    /**
     * 触发邀请分佣
     */
    protected function triggerInviteCommission($order)
    {
        try {
            $service = new InviteCommissionService();
            $service->triggerWithdrawCommission(
                $order->user_id,
                $order->cash_amount,
                $order->order_no,
                $order->id
            );
        } catch (\Exception $e) {
            Log::error('触发提现分佣失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取分布式锁
     */
    protected function getLock($key, $expire = 5)
    {
        try {
            $redis = Cache::store('redis')->handler();
            return $redis->set($key, 1, ['NX', 'EX' => $expire]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 释放锁
     */
    protected function releaseLock($key)
    {
        try {
            Cache::store('redis')->handler()->del($key);
        } catch (\Exception $e) {
        }
    }
    
    /**
     * 清除账户缓存
     */
    protected function clearAccountCache($userId)
    {
        Cache::delete('coin:balance:' . $userId);
    }
    
    /**
     * 获取用户提现记录
     */
    public function getUserOrders($userId, $status = null, $page = 1, $limit = 20)
    {
        $query = WithdrawOrder::where('user_id', $userId);
        
        if ($status !== null) {
            $query->where('status', $status);
        }
        
        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select();
        
        return [
            'total' => $total,
            'list' => $list,
        ];
    }
    
    /**
     * 获取用户提现统计
     */
    public function getUserStat($userId)
    {
        $stat = Db::name('withdraw_stat')->where('user_id', $userId)->find();
        
        if (!$stat) {
            return [
                'total_withdraw_count' => 0,
                'total_withdraw_amount' => 0,
                'total_withdraw_coin' => 0,
                'success_count' => 0,
                'today_withdraw_count' => 0,
                'today_withdraw_amount' => 0,
            ];
        }
        
        return $stat;
    }
}
