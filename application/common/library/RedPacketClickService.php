<?php

namespace app\common\library;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\RedPacketTask;
use app\common\model\UserRedPacketAccumulate;

/**
 * 红包点击服务类
 * 实现新的红包金额计算逻辑
 * 每个红包任务只能被一个用户抢到
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
            ->where('type', 'red_packet_grab')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        // 判断是否为新用户
        $isNewUser = $this->isNewUser($userId);

        // 补充每个任务的展示数据
        foreach ($list as $task) {
            $task->display_data = $task->getDisplayData();
            $task->show_red_packet = $task->shouldShowRedPacket();

            // 获取红包抢夺状态
            $accumulate = UserRedPacketAccumulate::where('task_id', $task->id)->find();

            if ($accumulate) {
                $isOwner = $accumulate->user_id == $userId;
                $task->red_packet_status = [
                    'is_grabbed' => true,
                    'is_owner' => $isOwner,
                    'is_collected' => $accumulate->is_collected,
                    'total_amount' => $isOwner ? $accumulate->total_amount : 0,
                    'click_count' => $isOwner ? $accumulate->click_count : 0,
                ];
            } else {
                $task->red_packet_status = [
                    'is_grabbed' => false,
                    'is_owner' => false,
                    'is_collected' => false,
                    'total_amount' => 0,
                    'click_count' => 0,
                ];
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
            // 获取红包抢夺状态
            $accumulate = UserRedPacketAccumulate::where('task_id', $task->id)->find();

            if ($accumulate) {
                $isOwner = $accumulate->user_id == $userId;
                $task->red_packet_status = [
                    'is_grabbed' => true,
                    'is_owner' => $isOwner,
                    'is_collected' => $accumulate->is_collected,
                    'base_amount' => $isOwner ? $accumulate->base_amount : 0,
                    'accumulate_amount' => $isOwner ? $accumulate->accumulate_amount : 0,
                    'total_amount' => $isOwner ? $accumulate->total_amount : 0,
                    'click_count' => $isOwner ? $accumulate->click_count : 0,
                ];
            } else {
                $task->red_packet_status = [
                    'is_grabbed' => false,
                    'is_owner' => false,
                    'is_collected' => false,
                    'total_amount' => 0,
                    'click_count' => 0,
                ];
            }

            // 今日已领取金额
            $todayStart = strtotime(date('Y-m-d'));
            $task->today_amount = Db::name('coin_log')
                ->where('user_id', $userId)
                ->where('type', 'red_packet_grab')
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');

            // 是否新用户
            $task->is_new_user = $this->isNewUser($userId);
        }

        return $task;
    }

    /**
     * 点击红包（抢红包或累加金额）
     * 第一次点击：抢红包
     * 后续点击：累加金额（只有抢到红包的用户才能累加）
     *
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

        try {
            // 获取任务
            $task = RedPacketTask::find($taskId);
            if (!$task) {
                $result['message'] = '任务不存在';
                return $result;
            }

            // 检查任务状态
            if ($task->status !== 'normal' || $task->push_status != 1) {
                $result['message'] = '任务状态异常';
                return $result;
            }

            // 检查是否显示红包
            if (!$task->shouldShowRedPacket()) {
                $result['message'] = '该任务不显示红包';
                return $result;
            }

            // 判断是否为新用户
            $isNewUser = $this->isNewUser($userId);

            // 获取今日已领取金额
            $todayStart = strtotime(date('Y-m-d'));
            $todayAmount = Db::name('coin_log')
                ->where('user_id', $userId)
                ->where('type', 'red_packet_grab')
                ->where('createtime', '>=', $todayStart)
                ->sum('amount');

            // 检查红包是否已被抢走
            $existingClaim = UserRedPacketAccumulate::where('task_id', $taskId)->find();

            if ($existingClaim) {
                // 红包已被抢
                if ($existingClaim->user_id == $userId && $existingClaim->is_collected == 0) {
                    // 当前用户抢到的，执行累加
                    $clickResult = UserRedPacketAccumulate::accumulateRedPacket(
                        $userId,
                        $taskId,
                        $todayAmount
                    );
                } else {
                    // 被其他人抢走了
                    $result['success'] = false;
                    $result['message'] = '手慢了，红包已被抢走';
                    $result['data'] = ['is_owner' => false];
                    return $result;
                }
            } else {
                // 红包还没被抢，执行抢红包
                $clickResult = UserRedPacketAccumulate::grabRedPacket(
                    $userId,
                    $taskId,
                    $todayAmount,
                    $isNewUser
                );
            }

            if ($clickResult['success']) {
                $result['success'] = true;
                $result['message'] = $clickResult['message'];
                $result['data'] = $clickResult['data'];
            } else {
                $result['message'] = $clickResult['message'];
            }

        } catch (\Exception $e) {
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

        $accumulate = UserRedPacketAccumulate::where('task_id', $taskId)->find();

        $todayStart = strtotime(date('Y-m-d'));
        $todayAmount = Db::name('coin_log')
            ->where('user_id', $userId)
            ->where('type', 'red_packet_grab')
            ->where('createtime', '>=', $todayStart)
            ->sum('amount');

        $status = [
            'task_id' => $taskId,
            'show_red_packet' => $task->shouldShowRedPacket(),
            'today_amount' => intval($todayAmount),
            'is_new_user' => $this->isNewUser($userId),
        ];

        if ($accumulate) {
            $isOwner = $accumulate->user_id == $userId;
            $status['is_grabbed'] = true;
            $status['is_owner'] = $isOwner;
            $status['is_collected'] = $accumulate->is_collected;
            $status['base_amount'] = $isOwner ? $accumulate->base_amount : 0;
            $status['accumulate_amount'] = $isOwner ? $accumulate->accumulate_amount : 0;
            $status['total_amount'] = $isOwner ? $accumulate->total_amount : 0;
            $status['click_count'] = $isOwner ? $accumulate->click_count : 0;
        } else {
            $status['is_grabbed'] = false;
            $status['is_owner'] = false;
            $status['is_collected'] = false;
            $status['total_amount'] = 0;
            $status['click_count'] = 0;
        }

        return $status;
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
            ->where('type', 'red_packet_grab')
            ->count();

        return $count == 0;
    }
}
