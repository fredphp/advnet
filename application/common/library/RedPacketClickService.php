<?php

namespace app\common\library;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\RedPacketTask;
use app\common\model\RedPacketAmountConfig;
use app\common\model\UserRedPacketAccumulate;

/**
 * 红包点击服务类
 * 实现新的红包金额计算逻辑
 */
class RedPacketClickService
{
    // Redis键前缀
    const CACHE_PREFIX = 'red_packet_click:';
    const LOCK_PREFIX = 'lock:red_packet_click:';

    /**
     * 获取任务列表
     * @param int $userId 用户ID
     * @param array $filters 筛选条件
     * @return array
     */
    public function getTaskList($userId, $filters = [])
    {
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 20;
        $taskType = $filters['type'] ?? '';

        $query = RedPacketTask::where('status', 'normal')
            ->where('push_status', 1)
            ->where(function ($q) {
                $q->whereNull('start_time')->whereOr('start_time', '<=', time());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->whereOr('end_time', '>=', time());
            });

        if ($taskType) {
            $query->where('type', $taskType);
        }

        $total = $query->count();
        $list = $query->order('weigh', 'desc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        // 获取今日已领取金额
        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $userId)
            ->where('type', 'red_packet_click')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        // 判断是否为新用户
        $isNewUser = $this->isNewUser($userId);

        // 补充每个任务的展示数据
        foreach ($list as $task) {
            $task->display_data = $task->getDisplayData();
            $task->show_red_packet = $task->shouldShowRedPacket();

            // 获取用户对当前任务的累计状态
            $accumulate = UserRedPacketAccumulate::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->find();

            if ($accumulate) {
                $task->user_accumulate = [
                    'total_amount' => $accumulate->total_amount,
                    'click_count' => $accumulate->click_count,
                    'is_collected' => $accumulate->is_collected,
                ];
            } else {
                $task->user_accumulate = null;
            }
        }

        return [
            'total' => $total,
            'list' => $list,
            'today_amount' => intval($todayAmount),
            'is_new_user' => $isNewUser,
        ];
    }

    /**
     * 获取任务详情
     */
    public function getTaskDetail($taskId, $userId = null)
    {
        $task = RedPacketTask::with(['resource'])->find($taskId);
        if (!$task) {
            return null;
        }

        $task->display_data = $task->getDisplayData();
        $task->show_red_packet = $task->shouldShowRedPacket();

        if ($userId) {
            // 获取用户累计状态
            $accumulate = UserRedPacketAccumulate::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->find();

            if ($accumulate) {
                $task->user_accumulate = [
                    'base_amount' => $accumulate->base_amount,
                    'accumulate_amount' => $accumulate->accumulate_amount,
                    'total_amount' => $accumulate->total_amount,
                    'click_count' => $accumulate->click_count,
                    'is_collected' => $accumulate->is_collected,
                ];
            } else {
                $task->user_accumulate = null;
            }

            // 今日已领取金额
            $todayStart = strtotime(date('Y-m-d'));
            $task->today_amount = Db::name('coin_log')
                ->where('user_id', $userId)
                ->where('type', 'red_packet_click')
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');

            // 是否新用户
            $task->is_new_user = $this->isNewUser($userId);
        }

        return $task;
    }

    /**
     * 点击红包
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param array $options 额外选项
     * @return array
     */
    public function clickRedPacket($userId, $taskId, $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        // 分布式锁
        $lockKey = self::LOCK_PREFIX . "{$userId}:{$taskId}";
        $lock = $this->getLock($lockKey, 5);

        if (!$lock) {
            $result['message'] = '操作过于频繁，请稍后重试';
            return $result;
        }

        try {
            // 获取任务
            $task = RedPacketTask::find($taskId);
            if (!$task) {
                $result['message'] = '任务不存在';
                $this->releaseLock($lockKey);
                return $result;
            }

            // 检查任务状态
            if ($task->status !== 'normal' || $task->push_status != 1) {
                $result['message'] = '任务状态异常';
                $this->releaseLock($lockKey);
                return $result;
            }

            // 检查是否显示红包
            if (!$task->shouldShowRedPacket()) {
                $result['message'] = '该任务不显示红包';
                $this->releaseLock($lockKey);
                return $result;
            }

            // 检查每日点击次数限制
            $todayStart = strtotime(date('Y-m-d'));
            $todayClickCount = UserRedPacketAccumulate::where('user_id', $userId)
                ->where('task_id', $taskId)
                ->where('updatetime', '>=', $todayStart)
                ->sum('click_count');

            $maxClickPerDay = $task->max_click_per_day ?: 10;
            if ($todayClickCount >= $maxClickPerDay) {
                $result['message'] = '今日点击次数已达上限';
                $this->releaseLock($lockKey);
                return $result;
            }

            // 判断是否为新用户
            $isNewUser = $this->isNewUser($userId);

            // 获取今日已领取金额
            $todayAmount = Db::name('coin_log')
                ->where('user_id', $userId)
                ->where('type', 'red_packet_click')
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');

            // 点击红包，计算金额
            $clickResult = UserRedPacketAccumulate::clickRedPacket(
                $userId,
                $taskId,
                $todayAmount,
                $isNewUser
            );

            $this->releaseLock($lockKey);

            if ($clickResult['success']) {
                $result['success'] = true;
                $result['message'] = '点击成功';
                $result['data'] = $clickResult['data'];
            } else {
                $result['message'] = $clickResult['message'];
            }

        } catch (\Exception $e) {
            $this->releaseLock($lockKey);
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('点击红包失败: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 领取红包
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @return array
     */
    public function collectRedPacket($userId, $taskId)
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        try {
            $collectResult = UserRedPacketAccumulate::collectRedPacket($userId, $taskId);

            if ($collectResult['success']) {
                $result['success'] = true;
                $result['message'] = '领取成功';
                $result['data'] = $collectResult['data'];
            } else {
                $result['message'] = $collectResult['message'];
            }

        } catch (\Exception $e) {
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('领取红包失败: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 获取用户红包状态
     */
    public function getUserRedPacketStatus($userId, $taskId)
    {
        $task = RedPacketTask::find($taskId);
        if (!$task) {
            return null;
        }

        $accumulate = UserRedPacketAccumulate::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->find();

        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $userId)
            ->where('type', 'red_packet_click')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        return [
            'task_id' => $taskId,
            'show_red_packet' => $task->shouldShowRedPacket(),
            'max_click_per_day' => $task->max_click_per_day ?: 10,
            'today_amount' => intval($todayAmount),
            'is_new_user' => $this->isNewUser($userId),
            'accumulate' => $accumulate ? [
                'base_amount' => $accumulate->base_amount,
                'accumulate_amount' => $accumulate->accumulate_amount,
                'total_amount' => $accumulate->total_amount,
                'click_count' => $accumulate->click_count,
                'is_collected' => $accumulate->is_collected,
            ] : null,
        ];
    }

    /**
     * 获取用户记录
     */
    public function getUserRecords($userId, $page = 1, $limit = 20)
    {
        $query = UserRedPacketAccumulate::with(['task'])
            ->where('user_id', $userId)
            ->where('total_amount', '>', 0);

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        return [
            'total' => $total,
            'list' => $list
        ];
    }

    /**
     * 判断是否为新用户
     * 新用户定义：从未领取过红包的用户
     */
    protected function isNewUser($userId)
    {
        $count = Db::name('coin_log')
            ->where('user_id', $userId)
            ->where('type', 'red_packet_click')
            ->count();

        return $count == 0;
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
}
