<?php

namespace app\common\library;

use think\Db;
use think\Log;
use think\Exception;
use think\Cache;
use app\common\model\CoinAccount;
use app\common\model\CoinLog;

/**
 * 金币服务类
 * 
 * 优化版本：
 * 1. 使用统一配置服务
 * 2. 添加分布式锁
 * 3. 添加乐观锁
 */
class CoinService
{
    // 缓存前缀
    const CACHE_PREFIX = 'coin:';
    const LOCK_PREFIX = 'lock:coin:';
    
    /**
     * @var array 配置缓存
     */
    protected $config = null;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->loadConfig();
    }
    
    /**
     * 加载配置
     */
    protected function loadConfig()
    {
        $this->config = SystemConfigService::getCoinConfig();
    }
    
    /**
     * 获取配置
     */
    protected function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * 获取用户账户
     * 
     * @param int $userId 用户ID
     * @param bool $lock 是否加锁
     * @return array|null
     */
    public function getAccount($userId, $lock = false)
    {
        $userId = (int)$userId;
        
        $query = Db::name('coin_account')->where('user_id', $userId);
        
        if ($lock) {
            $query->lock(true);
        }
        
        return $query->find();
    }
    
    /**
     * 获取或创建账户
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function getOrCreateAccount($userId)
    {
        $userId = (int)$userId;
        
        $account = $this->getAccount($userId);
        
        if (!$account) {
            $account = $this->createAccount($userId);
        }
        
        return $account;
    }
    
    /**
     * 创建账户
     * 
     * @param int $userId 用户ID
     * @return array
     */
    protected function createAccount($userId)
    {
        $userId = (int)$userId;
        
        Db::startTrans();
        try {
            // 再次检查是否已存在
            $exists = $this->getAccount($userId, true);
            if ($exists) {
                Db::commit();
                return $exists;
            }
            
            $account = [
                'user_id' => $userId,
                'balance' => 0,
                'frozen' => 0,
                'total_income' => 0,
                'total_expense' => 0,
                'version' => 1,
                'createtime' => time(),
                'updatetime' => time(),
            ];
            
            Db::name('coin_account')->insert($account);
            Db::commit();
            
            return $account;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 获取余额
     * 
     * @param int $userId 用户ID
     * @return int
     */
    public function getBalance($userId)
    {
        $account = $this->getOrCreateAccount($userId);
        return (int)max(0, $account['balance']);
    }
    
    /**
     * 获取可用余额（扣除冻结）
     * 
     * @param int $userId 用户ID
     * @return int
     */
    public function getAvailableBalance($userId)
    {
        $account = $this->getOrCreateAccount($userId);
        $balance = (int)$account['balance'];
        $frozen = (int)$account['frozen'];
        return max(0, $balance - $frozen);
    }
    
    /**
     * 增加金币
     * 
     * @param int $userId 用户ID
     * @param int $amount 金币数量
     * @param string $type 类型
     * @param string $relationType 关联类型
     * @param int $relationId 关联ID
     * @param string $description 描述
     * @return array
     */
    public function addCoin($userId, $amount, $type = 'system', $relationType = '', $relationId = 0, $description = '')
    {
        $result = [
            'success' => false,
            'message' => '',
            'balance' => 0,
        ];
        
        $userId = (int)$userId;
        $amount = (int)$amount;
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        // 获取分布式锁
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取账户（加锁）
            $account = $this->getAccount($userId, true);
            
            if (!$account) {
                $account = $this->createAccount($userId);
            }
            
            $balanceBefore = (int)$account['balance'];
            $balanceAfter = $balanceBefore + $amount;
            
            // 使用乐观锁更新
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'balance' => $balanceAfter,
                    'total_income' => (int)$account['total_income'] + $amount,
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            if ($affected === 0) {
                throw new Exception('账户更新失败，请重试');
            }
            
            // 记录流水
            $this->addLog($userId, [
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'relation_type' => $relationType,
                'relation_id' => $relationId,
                'description' => $description,
            ]);
            
            Db::commit();
            
            // 清除缓存
            $this->clearAccountCache($userId);
            
            $result['success'] = true;
            $result['balance'] = $balanceAfter;
            
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('AddCoin error: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 扣除金币
     * 
     * @param int $userId 用户ID
     * @param int $amount 金币数量
     * @param string $type 类型
     * @param string $relationType 关联类型
     * @param int $relationId 关联ID
     * @param string $description 描述
     * @return array
     */
    public function deductCoin($userId, $amount, $type = 'system', $relationType = '', $relationId = 0, $description = '')
    {
        $result = [
            'success' => false,
            'message' => '',
            'balance' => 0,
        ];
        
        $userId = (int)$userId;
        $amount = (int)$amount;
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        // 获取分布式锁
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取账户（加锁）
            $account = $this->getAccount($userId, true);
            
            if (!$account) {
                throw new Exception('账户不存在');
            }
            
            $balanceBefore = (int)$account['balance'];
            
            if ($balanceBefore < $amount) {
                throw new Exception('金币余额不足');
            }
            
            $balanceAfter = $balanceBefore - $amount;
            
            // 使用乐观锁更新
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'balance' => $balanceAfter,
                    'total_expense' => (int)$account['total_expense'] + $amount,
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            if ($affected === 0) {
                throw new Exception('账户更新失败，请重试');
            }
            
            // 记录流水
            $this->addLog($userId, [
                'type' => $type,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'relation_type' => $relationType,
                'relation_id' => $relationId,
                'description' => $description,
            ]);
            
            Db::commit();
            
            // 清除缓存
            $this->clearAccountCache($userId);
            
            $result['success'] = true;
            $result['balance'] = $balanceAfter;
            
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('DeductCoin error: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 冻结金币
     * 
     * @param int $userId 用户ID
     * @param int $amount 金币数量
     * @return array
     */
    public function freeze($userId, $amount)
    {
        $result = ['success' => false, 'message' => ''];
        
        $userId = (int)$userId;
        $amount = (int)$amount;
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $account = $this->getAccount($userId, true);
            
            if (!$account) {
                throw new Exception('账户不存在');
            }
            
            $balance = (int)$account['balance'];
            $frozen = (int)$account['frozen'];
            $available = $balance - $frozen;
            
            if ($available < $amount) {
                throw new Exception('可用金币不足');
            }
            
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'frozen' => $frozen + $amount,
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            if ($affected === 0) {
                throw new Exception('操作失败，请重试');
            }
            
            Db::commit();
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 解冻金币
     * 
     * @param int $userId 用户ID
     * @param int $amount 金币数量
     * @return array
     */
    public function unfreeze($userId, $amount)
    {
        $result = ['success' => false, 'message' => ''];
        
        $userId = (int)$userId;
        $amount = (int)$amount;
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = $this->getLock($lockKey, 10);
        
        if (!$lock) {
            $result['message'] = '操作频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            $account = $this->getAccount($userId, true);
            
            if (!$account) {
                throw new Exception('账户不存在');
            }
            
            $frozen = (int)$account['frozen'];
            
            if ($frozen < $amount) {
                throw new Exception('冻结金币不足');
            }
            
            $affected = Db::name('coin_account')
                ->where('user_id', $userId)
                ->where('version', $account['version'])
                ->update([
                    'frozen' => $frozen - $amount,
                    'version' => (int)$account['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            if ($affected === 0) {
                throw new Exception('操作失败，请重试');
            }
            
            Db::commit();
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 转账
     * 
     * @param int $fromUserId 转出用户ID
     * @param int $toUserId 转入用户ID
     * @param int $amount 金币数量
     * @param string $description 描述
     * @return array
     */
    public function transfer($fromUserId, $toUserId, $amount, $description = '')
    {
        $result = [
            'success' => false,
            'message' => '',
        ];
        
        $fromUserId = (int)$fromUserId;
        $toUserId = (int)$toUserId;
        $amount = (int)$amount;
        
        if ($amount <= 0) {
            $result['message'] = '金币数量必须大于0';
            return $result;
        }
        
        if ($fromUserId == $toUserId) {
            $result['message'] = '不能转给自己';
            return $result;
        }
        
        // 获取锁（按用户ID排序避免死锁）
        $lockIds = [$fromUserId, $toUserId];
        sort($lockIds);
        
        $locks = [];
        foreach ($lockIds as $uid) {
            $lockKey = self::LOCK_PREFIX . $uid;
            $lock = $this->getLock($lockKey, 10);
            if (!$lock) {
                // 释放已获取的锁
                foreach ($locks as $lk) {
                    $this->releaseLock($lk);
                }
                $result['message'] = '操作频繁，请稍后重试';
                return $result;
            }
            $locks[] = $lockKey;
        }
        
        try {
            Db::startTrans();
            
            // 获取转出账户
            $fromAccount = $this->getAccount($fromUserId, true);
            if (!$fromAccount) {
                throw new Exception('转出账户不存在');
            }
            
            $fromBalance = (int)$fromAccount['balance'];
            if ($fromBalance < $amount) {
                throw new Exception('余额不足');
            }
            
            // 获取或创建转入账户
            $toAccount = $this->getAccount($toUserId, true);
            if (!$toAccount) {
                $toAccount = $this->createAccount($toUserId);
            }
            
            $toBalance = (int)$toAccount['balance'];
            
            // 更新转出账户
            Db::name('coin_account')
                ->where('user_id', $fromUserId)
                ->where('version', $fromAccount['version'])
                ->update([
                    'balance' => $fromBalance - $amount,
                    'total_expense' => (int)$fromAccount['total_expense'] + $amount,
                    'version' => (int)$fromAccount['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            // 更新转入账户
            Db::name('coin_account')
                ->where('user_id', $toUserId)
                ->where('version', $toAccount['version'])
                ->update([
                    'balance' => $toBalance + $amount,
                    'total_income' => (int)$toAccount['total_income'] + $amount,
                    'version' => (int)$toAccount['version'] + 1,
                    'updatetime' => time(),
                ]);
            
            // 记录流水
            $this->addLog($fromUserId, [
                'type' => 'transfer_out',
                'amount' => -$amount,
                'balance_before' => $fromBalance,
                'balance_after' => $fromBalance - $amount,
                'relation_type' => 'user',
                'relation_id' => $toUserId,
                'description' => $description ?: "转账给用户{$toUserId}",
            ]);
            
            $this->addLog($toUserId, [
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $toBalance,
                'balance_after' => $toBalance + $amount,
                'relation_type' => 'user',
                'relation_id' => $fromUserId,
                'description' => $description ?: "收到用户{$fromUserId}转账",
            ]);
            
            Db::commit();
            
            // 清除缓存
            $this->clearAccountCache($fromUserId);
            $this->clearAccountCache($toUserId);
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            Db::rollback();
            $result['message'] = $e->getMessage();
            Log::error('Transfer error: ' . $e->getMessage());
        } finally {
            foreach ($locks as $lockKey) {
                $this->releaseLock($lockKey);
            }
        }
        
        return $result;
    }
    
    /**
     * 新用户注册奖励
     * 
     * @param int $userId 用户ID
     * @return array
     */
    public function newUserReward($userId)
    {
        $amount = (int)$this->getConfig('new_user_coin', 1000);
        
        if ($amount <= 0) {
            return ['success' => true, 'amount' => 0];
        }
        
        return $this->addCoin(
            $userId,
            $amount,
            'new_user',
            '',
            0,
            '新用户注册奖励'
        );
    }
    
    /**
     * 视频观看奖励
     * 
     * @param int $userId 用户ID
     * @param int $videoId 视频ID
     * @param int $watchDuration 观看时长
     * @return array
     */
    public function videoReward($userId, $videoId, $watchDuration = 0)
    {
        $amount = (int)$this->getConfig('video_coin_reward', 100);
        $requiredDuration = (int)$this->getConfig('video_watch_duration', 30);
        
        if ($watchDuration < $requiredDuration) {
            return ['success' => false, 'message' => '观看时长不足', 'amount' => 0];
        }
        
        if ($amount <= 0) {
            return ['success' => true, 'amount' => 0];
        }
        
        return $this->addCoin(
            $userId,
            $amount,
            'video',
            'video',
            $videoId,
            '观看视频奖励'
        );
    }
    
    /**
     * 检查每日限制
     * 
     * @param int $userId 用户ID
     * @return bool
     */
    public function checkDailyLimit($userId)
    {
        $dailyLimit = (int)$this->getConfig('daily_coin_limit', 50000);
        
        if ($dailyLimit <= 0) {
            return true;
        }
        
        $today = date('Y-m-d');
        
        // 使用当月分表查询
        $tableName = CoinLog::getTableByMonth();
        
        if (!CoinLog::tableExists($tableName)) {
            return true; // 表不存在说明没有记录，不限制
        }
        
        $earned = Db::name($tableName)
            ->where('user_id', $userId)
            ->where('amount', '>', 0)
            ->where('create_date', $today)
            ->sum('amount');
        
        return (int)$earned < $dailyLimit;
    }
    
    /**
     * 添加金币流水
     * 
     * @param int $userId 用户ID
     * @param array $data 数据
     * @param string $relationTable 关联数据所在的分表名（可选，用于解决分表后ID不唯一问题）
     */
    protected function addLog($userId, array $data, $relationTable = '')
    {
        // 获取当月分表名并确保表存在
        $tableName = CoinLog::getOrCreateTable();
        
        Db::name($tableName)->insert([
            'user_id' => (int)$userId,
            'type' => (string)($data['type'] ?? ''),
            'amount' => (int)($data['amount'] ?? 0),
            'balance_before' => (int)($data['balance_before'] ?? 0),
            'balance_after' => (int)($data['balance_after'] ?? 0),
            'relation_type' => (string)($data['relation_type'] ?? ''),
            'relation_id' => (int)($data['relation_id'] ?? 0),
            'relation_table' => (string)($data['relation_table'] ?? $relationTable),
            'description' => (string)($data['description'] ?? ''),
            'createtime' => time(),
            'create_date' => date('Y-m-d'),
        ]);
    }
    
    /**
     * 获取分布式锁
     * 优先使用 Redis，如果不可用则使用文件锁
     */
    protected function getLock($key, $expire = 5)
    {
        try {
            // 尝试使用 Redis 锁
            $cacheConfig = config('cache');
            if (isset($cacheConfig['type']) && strtolower($cacheConfig['type']) === 'redis') {
                $redis = Cache::store('redis')->handler();
                if ($redis) {
                    return $redis->set($key, 1, ['NX', 'EX' => $expire]);
                }
            }
            
            // Redis 不可用时，使用文件锁作为备用方案
            return $this->getFileLock($key, $expire);
        } catch (\Exception $e) {
            // 发生异常时，使用文件锁
            return $this->getFileLock($key, $expire);
        }
    }
    
    /**
     * 获取文件锁（Redis 不可用时的备用方案）
     */
    protected function getFileLock($key, $expire = 5)
    {
        $lockDir = RUNTIME_PATH . 'lock' . DS;
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        
        $lockFile = $lockDir . md5($key) . '.lock';
        $lockHandle = fopen($lockFile, 'w+');
        
        if (!$lockHandle) {
            return false;
        }
        
        // 尝试获取非阻塞锁
        if (flock($lockHandle, LOCK_EX | LOCK_NB)) {
            // 写入过期时间
            fwrite($lockHandle, time() + $expire);
            
            // 保存句柄到静态变量，以便后续释放
            self::$lockHandles[$key] = $lockHandle;
            
            return true;
        }
        
        // 检查锁是否已过期
        $expireTime = (int)fread($lockHandle, 20);
        if (time() > $expireTime) {
            // 锁已过期，强制获取
            flock($lockHandle, LOCK_EX);
            ftruncate($lockHandle, 0);
            fwrite($lockHandle, time() + $expire);
            self::$lockHandles[$key] = $lockHandle;
            return true;
        }
        
        fclose($lockHandle);
        return false;
    }
    
    /**
     * 释放锁
     */
    protected function releaseLock($key)
    {
        try {
            // 尝试释放 Redis 锁
            $cacheConfig = config('cache');
            if (isset($cacheConfig['type']) && strtolower($cacheConfig['type']) === 'redis') {
                $redis = Cache::store('redis')->handler();
                if ($redis) {
                    $redis->del($key);
                    return;
                }
            }
            
            // 释放文件锁
            $this->releaseFileLock($key);
        } catch (\Exception $e) {
            // 发生异常时，释放文件锁
            $this->releaseFileLock($key);
        }
    }
    
    /**
     * 释放文件锁
     */
    protected function releaseFileLock($key)
    {
        if (isset(self::$lockHandles[$key])) {
            $lockHandle = self::$lockHandles[$key];
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            unset(self::$lockHandles[$key]);
        }
    }
    
    /**
     * @var array 文件锁句柄存储
     */
    private static $lockHandles = [];
    
    /**
     * 清除账户缓存
     */
    protected function clearAccountCache($userId)
    {
        // ThinkPHP 5.0 使用 Cache::rm() 删除缓存
        Cache::rm(self::CACHE_PREFIX . 'balance:' . $userId);
    }
    
    /**
     * 金币转人民币
     */
    public static function coinToCash($coin)
    {
        $rate = SystemConfigService::getCoinRate();
        return round($coin / $rate, 4);
    }
    
    /**
     * 人民币转金币
     */
    public static function cashToCoin($cash)
    {
        $rate = SystemConfigService::getCoinRate();
        return intval($cash * $rate);
    }
}
