<?php

namespace app\common\model;

use think\Model;
use think\Db;

/**
 * 用户红包累计记录模型
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
     * 获取或创建用户对某个任务的累计记录
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param bool $isNewUser 是否新用户
     * @return UserRedPacketAccumulate|null
     */
    public static function getOrCreate($userId, $taskId, $isNewUser = false)
    {
        $record = self::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->find();

        if (!$record) {
            // 创建新记录
            $record = new self();
            $record->user_id = $userId;
            $record->task_id = $taskId;
            $record->is_new_user = $isNewUser ? 1 : 0;
            $record->click_count = 0;
            $record->base_amount = 0;
            $record->accumulate_amount = 0;
            $record->total_amount = 0;
            $record->is_collected = 0;
            $record->save();
        }

        return $record;
    }

    /**
     * 点击红包，累加金额
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param int $todayAmount 今日已领取金额
     * @param bool $isNewUser 是否新用户
     * @return array
     */
    public static function clickRedPacket($userId, $taskId, $todayAmount, $isNewUser = false)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        try {
            Db::startTrans();

            // 获取或创建记录（加锁）
            $record = self::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->lock(true)
                ->find();

            if (!$record) {
                $record = new self();
                $record->user_id = $userId;
                $record->task_id = $taskId;
                $record->is_new_user = $isNewUser ? 1 : 0;
                $record->click_count = 0;
                $record->base_amount = 0;
                $record->accumulate_amount = 0;
                $record->total_amount = 0;
                $record->is_collected = 0;
            }

            // 如果已领取，不能继续累加
            if ($record->is_collected == 1) {
                $result['message'] = '红包已领取';
                Db::rollback();
                return $result;
            }

            // 第一次点击：生成基础金额
            if ($record->click_count == 0) {
                if ($isNewUser) {
                    // 新用户使用新用户红包金额
                    $range = RedPacketAmountConfig::getNewUserAmountRange();
                } else {
                    // 老用户根据今日领取金额获取基础额度
                    $range = RedPacketAmountConfig::getBaseAmountRange($todayAmount);
                }
                $record->base_amount = RedPacketAmountConfig::randomAmount($range);
            } else {
                // 非第一次点击：累加金额
                $range = RedPacketAmountConfig::getAccumulateAmountRange($todayAmount);
                $accumulateAmount = RedPacketAmountConfig::randomAmount($range);
                $record->accumulate_amount += $accumulateAmount;
            }

            $record->click_count += 1;
            $record->total_amount = $record->base_amount + $record->accumulate_amount;
            $record->save();

            Db::commit();

            $result['success'] = true;
            $result['message'] = '点击成功';
            $result['data'] = [
                'base_amount' => $record->base_amount,
                'accumulate_amount' => $record->accumulate_amount,
                'total_amount' => $record->total_amount,
                'click_count' => $record->click_count,
                'is_collected' => $record->is_collected
            ];

        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
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

        try {
            Db::startTrans();

            $record = self::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->lock(true)
                ->find();

            if (!$record) {
                $result['message'] = '红包记录不存在';
                Db::rollback();
                return $result;
            }

            if ($record->is_collected == 1) {
                $result['message'] = '红包已领取';
                Db::rollback();
                return $result;
            }

            if ($record->total_amount <= 0) {
                $result['message'] = '红包金额为0';
                Db::rollback();
                return $result;
            }

            // 发放金币
            $coinService = new \app\common\library\CoinService();
            $coinResult = $coinService->addCoin(
                $userId,
                $record->total_amount,
                'red_packet_click',
                [
                    'relation_type' => 'red_packet_task',
                    'relation_id' => $taskId,
                    'description' => '点击红包奖励'
                ]
            );

            if (!$coinResult['success']) {
                $result['message'] = '金币发放失败';
                Db::rollback();
                return $result;
            }

            // 更新领取状态
            $record->is_collected = 1;
            $record->collect_time = time();
            $record->save();

            Db::commit();

            $result['success'] = true;
            $result['message'] = '领取成功';
            $result['data'] = [
                'amount' => $record->total_amount,
                'balance' => $coinResult['balance'] ?? 0
            ];

        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
        }

        return $result;
    }
}
