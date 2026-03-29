<?php

namespace app\common\model;

use think\Model;
use think\Db;
use think\Cache;
use think\Log;

/**
 * 用户红包累计记录模型
 * 每个红包任务只能被一个用户领取
 */
class UserRedPacketAccumulate extends Model
{
    // 表名
    protected $name = 'user_red_packet_accumulate';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    // Redis锁前缀
    const LOCK_PREFIX = 'lock:red_packet_grab:';

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo('RedPacketTask', 'task_id');
    }

    /**
     * 抢红包 - 带并发控制
     * 每个红包任务只能被一个用户抢到
     *
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param int $todayAmount 今日已领取金额
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function grabRedPacket($userId, $taskId, $todayAmount, $isNewUser = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        // 任务级别的分布式锁，确保同一时间只有一个用户能抢
        $lockKey = self::LOCK_PREFIX . $taskId;

        try {
            // 尝试获取锁（3秒超时）
            $locked = self::getLock($lockKey, 3);

            if (!$locked) {
                $result['message'] = '手慢了，红包正在被其他人抢';
                return $result;
            }

            Db::startTrans();

            try {
                // 再次检查任务是否已被抢走（双重检查）
                $existingClaim = self::where('task_id', $taskId)
                    ->where('total_amount', '>', 0)
                    ->lock(true)
                    ->find();

                if ($existingClaim) {
                    Db::rollback();
                    self::releaseLock($lockKey);

                    if ($existingClaim->user_id == $userId && $existingClaim->is_collected == 0) {
                        // 当前用户已经抢到了，返回累计信息
                        $result['success'] = true;
                        $result['message'] = '您已抢到红包，请点击领取';
                        $result['data'] = [
                            'base_amount' => $existingClaim->base_amount,
                            'accumulate_amount' => $existingClaim->accumulate_amount,
                            'total_amount' => $existingClaim->total_amount,
                            'click_count' => $existingClaim->click_count,
                            'is_collected' => $existingClaim->is_collected,
                            'is_owner' => true,
                        ];
                        return $result;
                    }

                    $result['message'] = '手慢了，红包已被抢走';
                    $result['data'] = ['is_owner' => false];
                    return $result;
                }

                // 获取当前小时
                $currentHour = intval(date('H'));
                
                // 使用统一配置模型获取基础奖励区间
                // 第一步：先判断今日金额限制，第二步：判断时间段限制
                $range = RedPacketRewardConfig::getBaseRewardRange($todayAmount, $currentHour, $isNewUser);

                // 随机生成基础金额
                $baseAmount = RedPacketRewardConfig::randomAmount($range);
                
                // 确保不超过最高限制
                $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
                $baseAmount = min($baseAmount, $maxLimit);

                // 创建抢红包记录
                $record = new self();
                $record->user_id = $userId;
                $record->task_id = $taskId;
                $record->is_new_user = $isNewUser ? 1 : 0;
                $record->click_count = 1;
                $record->base_amount = $baseAmount;
                $record->accumulate_amount = 0;
                $record->total_amount = $baseAmount;
                $record->is_collected = 0;
                $record->save();

                Db::commit();
                self::releaseLock($lockKey);

                $result['success'] = true;
                $result['message'] = '恭喜！抢到红包';
                $result['data'] = [
                    'base_amount' => $baseAmount,
                    'accumulate_amount' => 0,
                    'total_amount' => $baseAmount,
                    'click_count' => 1,
                    'is_collected' => 0,
                    'is_owner' => true,
                    'max_limit' => $maxLimit,
                ];

            } catch (\Exception $e) {
                Db::rollback();
                self::releaseLock($lockKey);
                throw $e;
            }

        } catch (\Exception $e) {
            self::releaseLock($lockKey);
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('抢红包失败: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 累加红包金额 - 带并发控制
     * 只有抢到红包的用户才能累加
     *
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param int $todayAmount 今日已领取金额
     * @return array
     */
    public static function accumulateRedPacket($userId, $taskId, $todayAmount)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        $lockKey = self::LOCK_PREFIX . $taskId;

        try {
            $locked = self::getLock($lockKey, 3);

            if (!$locked) {
                $result['message'] = '操作过于频繁，请稍后重试';
                return $result;
            }

            Db::startTrans();

            try {
                // 查找当前用户的抢红包记录
                $record = self::where('task_id', $taskId)
                    ->where('user_id', $userId)
                    ->where('is_collected', 0)
                    ->lock(true)
                    ->find();

                if (!$record) {
                    Db::rollback();
                    self::releaseLock($lockKey);
                    $result['message'] = '您没有抢到这个红包';
                    return $result;
                }

                if ($record->is_collected == 1) {
                    Db::rollback();
                    self::releaseLock($lockKey);
                    $result['message'] = '红包已领取';
                    return $result;
                }

                // 获取最高金额限制
                $maxLimit = RedPacketRewardConfig::getMaxRewardLimit();
                
                // 检查是否已达到最高限制
                if ($record->total_amount >= $maxLimit) {
                    Db::rollback();
                    self::releaseLock($lockKey);
                    $result['success'] = true;
                    $result['message'] = '红包已达到最高金额，请领取';
                    $result['data'] = [
                        'added_amount' => 0,
                        'base_amount' => $record->base_amount,
                        'accumulate_amount' => $record->accumulate_amount,
                        'total_amount' => $record->total_amount,
                        'click_count' => $record->click_count,
                        'is_collected' => 0,
                        'is_owner' => true,
                        'max_limit' => $maxLimit,
                        'reached_limit' => true,
                    ];
                    return $result;
                }

                // 计算累加金额 - 使用统一配置
                $currentHour = intval(date('H'));
                $isNewUser = $record->is_new_user == 1;
                
                $range = RedPacketRewardConfig::getAccumulateRewardRange($todayAmount, $currentHour, $isNewUser);
                $accumulateAmount = RedPacketRewardConfig::randomAmount($range);

                // 计算实际可累加金额（不超过最高限制）
                $maxAddable = $maxLimit - $record->total_amount;
                $accumulateAmount = min($accumulateAmount, $maxAddable);

                $record->accumulate_amount += $accumulateAmount;
                $record->total_amount = $record->base_amount + $record->accumulate_amount;
                $record->click_count += 1;
                $record->save();

                Db::commit();
                self::releaseLock($lockKey);

                $result['success'] = true;
                $result['message'] = "累加成功，+{$accumulateAmount}金币";
                $result['data'] = [
                    'added_amount' => $accumulateAmount,
                    'base_amount' => $record->base_amount,
                    'accumulate_amount' => $record->accumulate_amount,
                    'total_amount' => $record->total_amount,
                    'click_count' => $record->click_count,
                    'is_collected' => 0,
                    'is_owner' => true,
                    'max_limit' => $maxLimit,
                    'reached_limit' => $record->total_amount >= $maxLimit,
                ];

            } catch (\Exception $e) {
                Db::rollback();
                self::releaseLock($lockKey);
                throw $e;
            }

        } catch (\Exception $e) {
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('累加红包失败: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 领取红包
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @return array
     */
    public static function collectRedPacket($userId, $taskId)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        $lockKey = self::LOCK_PREFIX . $taskId;

        try {
            $locked = self::getLock($lockKey, 5);

            if (!$locked) {
                $result['message'] = '操作过于频繁，请稍后重试';
                return $result;
            }

            Db::startTrans();

            try {
                $record = self::where('user_id', $userId)
                    ->where('task_id', $taskId)
                    ->lock(true)
                    ->find();

                if (!$record) {
                    $result['message'] = '您没有抢到这个红包';
                    Db::rollback();
                    self::releaseLock($lockKey);
                    return $result;
                }

                if ($record->is_collected == 1) {
                    $result['message'] = '红包已领取';
                    Db::rollback();
                    self::releaseLock($lockKey);
                    return $result;
                }

                if ($record->total_amount <= 0) {
                    $result['message'] = '红包金额为0';
                    Db::rollback();
                    self::releaseLock($lockKey);
                    return $result;
                }

                // 发放金币
                $coinService = new \app\common\library\CoinService();
                $coinResult = $coinService->addCoin(
                    $userId,
                    $record->total_amount,
                    'red_packet_grab',
                    [
                        'relation_type' => 'red_packet_task',
                        'relation_id' => $taskId,
                        'description' => '抢红包奖励'
                    ]
                );

                if (!$coinResult['success']) {
                    $result['message'] = '金币发放失败';
                    Db::rollback();
                    self::releaseLock($lockKey);
                    return $result;
                }

                // 更新领取状态
                $record->is_collected = 1;
                $record->collect_time = time();
                $record->save();

                // 更新任务状态为已抢完
                $task = RedPacketTask::get($taskId);
                if ($task) {
                    $task->status = 'finished';
                    $task->save();
                }

                Db::commit();
                self::releaseLock($lockKey);

                $result['success'] = true;
                $result['message'] = '领取成功';
                $result['data'] = [
                    'amount' => $record->total_amount,
                    'balance' => $coinResult['balance'] ?? 0
                ];

            } catch (\Exception $e) {
                Db::rollback();
                self::releaseLock($lockKey);
                throw $e;
            }

        } catch (\Exception $e) {
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('领取红包失败: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 获取分布式锁
     */
    protected static function getLock($key, $expire = 3)
    {
        try {
            $redis = Cache::store('redis')->handler();
            return $redis->set($key, 1, ['NX', 'EX' => $expire]);
        } catch (\Exception $e) {
            Log::error('获取Redis锁失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 释放锁
     */
    protected static function releaseLock($key)
    {
        try {
            Cache::store('redis')->handler()->del($key);
        } catch (\Exception $e) {
            Log::error('释放Redis锁失败: ' . $e->getMessage());
        }
    }
}
