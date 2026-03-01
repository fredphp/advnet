<?php

namespace app\common\library;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\CoinAccount;
use app\common\model\CoinLog;

/**
 * 金币服务类
 */
class CoinService
{
    // Redis键前缀
    const CACHE_PREFIX = 'coin:';
    const LOCK_PREFIX = 'lock:coin:';
    
    /**
     * 增加金币
     * @param int $userId 用户ID
     * @param float $amount 金币数量
     * @param string $type 类型
     * @param array $options 额外选项
     * @return array
     */
    public function addCoin($userId, $amount, $type, $options = [])
    {
        $result = [
            'success' => false,
            'balance' => 0,
            'message' => '',
        ];
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        // 分布式锁
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取账户(加锁)
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$account) {
                // 创建账户
                $account = $this->createAccount($userId);
            }
            
            $oldBalance = $account['balance'];
            $newBalance = $oldBalance + $amount;
            
            // 更新账户
            $updateData = [
                'balance' => $newBalance,
                'total_earn' => $account['total_earn'] + $amount,
                'updatetime' => time(),
            ];
            
            // 更新今日获得
            $today = date('Y-m-d');
            if ($account['today_earn_date'] != $today) {
                $updateData['today_earn'] = $amount;
                $updateData['today_earn_date'] = $today;
            } else {
                $updateData['today_earn'] = $account['today_earn'] + $amount;
            }
            
            // 乐观锁
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update(array_merge($updateData, ['version' => $account['version'] + 1]));
            
            if ($affected === 0) {
                throw new \Exception('操作失败，请重试');
            }
            
            // 记录流水
            $this->addCoinLog($userId, [
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'relation_type' => $options['relation_type'] ?? null,
                'relation_id' => $options['relation_id'] ?? null,
                'description' => $options['description'] ?? '',
                'ip' => $options['ip'] ?? null,
            ]);
            
            Db::commit();
            
            $result['success'] = true;
            $result['balance'] = $newBalance;
            $result['message'] = '金币发放成功';
            
            // 清除缓存
            $this->clearAccountCache($userId);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('金币增加失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 扣减金币
     * @param int $userId 用户ID
     * @param float $amount 金币数量
     * @param string $type 类型
     * @param array $options 额外选项
     * @return array
     */
    public function reduceCoin($userId, $amount, $type, $options = [])
    {
        $result = [
            'success' => false,
            'balance' => 0,
            'message' => '',
        ];
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$account) {
                throw new \Exception('账户不存在');
            }
            
            if ($account['balance'] < $amount) {
                throw new \Exception('金币余额不足');
            }
            
            $oldBalance = $account['balance'];
            $newBalance = $oldBalance - $amount;
            
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'balance' => $newBalance,
                    'total_spend' => $account['total_spend'] + $amount,
                    'version' => $account['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            if ($affected === 0) {
                throw new \Exception('操作失败，请重试');
            }
            
            // 记录流水
            $this->addCoinLog($userId, [
                'type' => $type,
                'amount' => -$amount,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance,
                'relation_type' => $options['relation_type'] ?? null,
                'relation_id' => $options['relation_id'] ?? null,
                'description' => $options['description'] ?? '',
                'ip' => $options['ip'] ?? null,
            ]);
            
            Db::commit();
            
            $result['success'] = true;
            $result['balance'] = $newBalance;
            $result['message'] = '金币扣减成功';
            
            $this->clearAccountCache($userId);
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('金币扣减失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 冻结金币
     */
    public function freezeCoin($userId, $amount)
    {
        $result = [
            'success' => false,
            'message' => '',
        ];
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$account || $account['balance'] < $amount) {
                throw new \Exception('金币余额不足');
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
                throw new \Exception('操作失败，请重试');
            }
            
            Db::commit();
            
            $result['success'] = true;
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 解冻金币
     */
    public function unfreezeCoin($userId, $amount)
    {
        $result = [
            'success' => false,
            'message' => '',
        ];
        
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $account = Db::name('coin_account')
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$account || $account['frozen'] < $amount) {
                throw new \Exception('冻结金币不足');
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
                throw new \Exception('操作失败，请重试');
            }
            
            Db::commit();
            
            $result['success'] = true;
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 获取账户余额
     */
    public function getBalance($userId)
    {
        $cacheKey = self::CACHE_PREFIX . "balance:{$userId}";
        $balance = Cache::get($cacheKey);
        
        if ($balance !== null) {
            return $balance;
        }
        
        $account = Db::name('coin_account')->where('user_id', $userId)->find();
        
        if (!$account) {
            return 0;
        }
        
        // 缓存5秒
        Cache::set($cacheKey, $account['balance'], 5);
        
        return $account['balance'];
    }
    
    /**
     * 获取账户详情
     */
    public function getAccountInfo($userId)
    {
        $account = Db::name('coin_account')->where('user_id', $userId)->find();
        
        if (!$account) {
            return $this->createAccount($userId);
        }
        
        return $account;
    }
    
    /**
     * 创建账户
     */
    protected function createAccount($userId)
    {
        Db::name('coin_account')->insert([
            'user_id' => $userId,
            'balance' => 0,
            'frozen' => 0,
            'total_earn' => 0,
            'total_spend' => 0,
            'total_withdraw' => 0,
            'today_earn' => 0,
            'today_earn_date' => date('Y-m-d'),
            'version' => 0,
            'createtime' => time(),
            'updatetime' => time(),
        ]);
        
        return [
            'user_id' => $userId,
            'balance' => 0,
            'frozen' => 0,
            'total_earn' => 0,
            'total_spend' => 0,
            'total_withdraw' => 0,
            'version' => 0,
        ];
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
            'balance_before' => $data['balance_before'],
            'balance_after' => $data['balance_after'],
            'relation_type' => $data['relation_type'],
            'relation_id' => $data['relation_id'],
            'description' => $data['description'],
            'ip' => $data['ip'],
            'createtime' => time(),
            'create_date' => date('Y-m-d'),
        ]);
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
        $cacheKey = self::CACHE_PREFIX . "balance:{$userId}";
        Cache::delete($cacheKey);
    }
}
