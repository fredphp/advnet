<?php

namespace app\common\library;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\RedPacketTask;
use app\common\model\TaskParticipation;
use app\common\model\UserTaskStat;
use app\common\model\AnticheatLog;

/**
 * 红包任务服务类
 */
class RedPacketService
{
    // Redis键前缀
    const CACHE_PREFIX = 'red_packet:';
    const LOCK_PREFIX = 'lock:red_packet:';
    
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
        $categoryId = $filters['category_id'] ?? 0;
        
        $query = RedPacketTask::where('status', 1)
            ->where(function ($q) {
                $q->whereNull('start_time')->whereOr('start_time', '<=', time());
            })
            ->where(function ($q) {
                $q->whereNull('end_time')->whereOr('end_time', '>=', time());
            })
            ->where('remain_count', '>', 0);
        
        if ($taskType) {
            $query->where('type', $taskType);
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $total = $query->count();
        $list = $query->order('is_recommend', 'desc')
            ->order('is_hot', 'desc')
            ->order('sort', 'asc')
            ->page($page, $limit)
            ->select();
        
        // 标记用户领取状态
        foreach ($list as $task) {
            $task->user_receive_count = TaskParticipation::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->whereIn('status', [0, 1, 2, 3])
                ->count();
            $task->can_receive = $task->user_receive_count < $task->user_limit;
            $task->progress_text = $this->getProgressText($task);
        }
        
        return [
            'total' => $total,
            'list' => $list
        ];
    }
    
    /**
     * 获取任务详情
     */
    public function getTaskDetail($taskId, $userId = null)
    {
        $task = RedPacketTask::find($taskId);
        if (!$task) {
            return null;
        }
        
        if ($userId) {
            $task->user_receive_count = TaskParticipation::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->whereIn('status', [0, 1, 2, 3])
                ->count();
            $task->can_receive = $task->user_receive_count < $task->user_limit;
            
            // 获取用户当前参与记录
            $task->current_participation = TaskParticipation::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->where('status', TaskParticipation::STATUS_RECEIVED)
                ->order('id', 'desc')
                ->find();
        }
        
        return $task;
    }
    
    /**
     * 领取任务
     * @param int $userId 用户ID
     * @param int $taskId 任务ID
     * @param array $options 额外选项
     * @return array
     */
    public function receiveTask($userId, $taskId, $options = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
        
        // 分布式锁
        $lockKey = self::LOCK_PREFIX . "receive:{$userId}:{$taskId}";
        $lock = $this->getLock($lockKey, 5);
        
        if (!$lock) {
            $result['message'] = '操作过于频繁，请稍后重试';
            return $result;
        }
        
        try {
            Db::startTrans();
            
            // 获取任务(加锁)
            $task = RedPacketTask::where('id', $taskId)->lock(true)->find();
            if (!$task) {
                $result['message'] = '任务不存在';
                Db::rollback();
                return $result;
            }
            
            // 检查是否可以领取
            $checkResult = $task->canUserReceive($userId);
            if (!$checkResult['can']) {
                $result['message'] = $checkResult['message'];
                Db::rollback();
                return $result;
            }
            
            // 防刷检测
            $antiCheatResult = $this->antiCheatCheck($userId, $taskId, $options);
            if (!$antiCheatResult['pass']) {
                $result['message'] = $antiCheatResult['message'];
                Db::rollback();
                return $result;
            }
            
            // 创建参与记录
            $participation = TaskParticipation::createParticipation($userId, $taskId, [
                'ip' => $options['ip'] ?? null,
                'device_id' => $options['device_id'] ?? null,
                'device_info' => $options['device_info'] ?? [],
                'platform' => $options['platform'] ?? null,
                'app_version' => $options['app_version'] ?? null,
            ]);
            
            // 记录设备日志
            $this->logDevice($taskId, $userId, $options);
            
            // 更新任务统计
            $task->view_count = $task->view_count + 1;
            $task->save();
            
            Db::commit();
            
            $result['success'] = true;
            $result['message'] = '领取成功';
            $result['data'] = [
                'participation_id' => $participation->id,
                'order_no' => $participation->order_no,
                'task_url' => $task->task_url,
                'task_params' => json_decode($task->task_params, true),
                'required_duration' => $task->required_duration,
                'expire_time' => time() + ($task->expire_hours * 3600),
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('领取任务失败: ' . $e->getMessage());
        } finally {
            $this->releaseLock($lockKey);
        }
        
        return $result;
    }
    
    /**
     * 提交任务完成
     * @param int $userId 用户ID
     * @param string $orderNo 订单号
     * @param array $data 提交数据
     * @return array
     */
    public function submitTask($userId, $orderNo, $data = [])
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];
        
        try {
            Db::startTrans();
            
            // 获取参与记录
            $participation = TaskParticipation::where('order_no', $orderNo)
                ->where('user_id', $userId)
                ->lock(true)
                ->find();
            
            if (!$participation) {
                $result['message'] = '参与记录不存在';
                Db::rollback();
                return $result;
            }
            
            if ($participation->status != TaskParticipation::STATUS_RECEIVED) {
                $result['message'] = '任务状态异常';
                Db::rollback();
                return $result;
            }
            
            // 检查是否过期
            $task = RedPacketTask::find($participation->task_id);
            if ($participation->isExpired($task->expire_hours)) {
                $participation->status = TaskParticipation::STATUS_EXPIRED;
                $participation->save();
                $result['message'] = '任务已过期';
                Db::rollback();
                return $result;
            }
            
            // 验证任务完成
            $verifyResult = $this->verifyTaskComplete($task, $participation, $data);
            if (!$verifyResult['pass']) {
                $result['message'] = $verifyResult['message'];
                Db::rollback();
                return $result;
            }
            
            // 完成任务
            $participation->complete([
                'progress' => $data['progress'] ?? 100,
                'screenshots' => $data['screenshots'] ?? [],
                'proof_data' => $data['proof_data'] ?? [],
                'extra_data' => $data['extra_data'] ?? []
            ]);
            
            // 更新统计
            $stat = UserTaskStat::getToday($userId);
            $stat->increment('complete_count');
            $this->incrementTypeStat($stat, $task->type);
            
            // 自动审核或等待人工审核
            if ($task->audit_type == 'auto') {
                $rewardResult = $this->autoAudit($participation, $task);
            } else {
                // 人工审核，更新待审核计数
                $task->audit_pending_count = $task->audit_pending_count + 1;
                $task->save();
                $result['message'] = '已提交，等待审核';
            }
            
            Db::commit();
            
            $result['success'] = true;
            if ($task->audit_type == 'auto') {
                $result['message'] = '任务完成，奖励已发放';
                $result['data'] = [
                    'reward_coin' => $rewardResult['reward_coin'] ?? 0,
                    'balance' => $rewardResult['balance'] ?? 0
                ];
            }
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('提交任务失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 自动审核
     */
    protected function autoAudit($participation, $task)
    {
        $result = ['success' => false, 'reward_coin' => 0, 'balance' => 0];
        
        try {
            // 计算奖励金额
            $rewardCoin = $task->calculateReward();
            
            // 检查库存
            if (!$task->decreaseStock($rewardCoin)) {
                $participation->auditReject('红包已抢完', null, 'system');
                return $result;
            }
            
            // 审核通过
            $participation->auditPass(null, 'system');
            
            // 发放奖励
            $coinService = new CoinService();
            $coinResult = $coinService->addCoin(
                $participation->user_id,
                $rewardCoin,
                'red_packet',
                [
                    'relation_type' => 'task',
                    'relation_id' => $participation->id,
                    'description' => "完成红包任务[{$task->name}]"
                ]
            );
            
            if ($coinResult['success']) {
                $participation->reward($rewardCoin);
                
                // 更新统计
                $stat = UserTaskStat::getToday($participation->user_id);
                $stat->increment('reward_count', 1, $rewardCoin);
                
                $result['success'] = true;
                $result['reward_coin'] = $rewardCoin;
                $result['balance'] = $coinResult['balance'];
            }
            
        } catch (\Exception $e) {
            Log::error('自动审核失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 人工审核
     */
    public function manualAudit($participationId, $pass, $adminId, $adminName, $remark = '')
    {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        try {
            Db::startTrans();
            
            $participation = TaskParticipation::where('id', $participationId)
                ->lock(true)
                ->find();
            
            if (!$participation) {
                $result['message'] = '参与记录不存在';
                Db::rollback();
                return $result;
            }
            
            if ($participation->status != TaskParticipation::STATUS_COMPLETED) {
                $result['message'] = '任务状态异常';
                Db::rollback();
                return $result;
            }
            
            $task = RedPacketTask::find($participation->task_id);
            
            if ($pass) {
                // 计算奖励
                $rewardCoin = $task->calculateReward();
                
                // 检查库存
                if (!$task->decreaseStock($rewardCoin)) {
                    $result['message'] = '红包库存不足';
                    Db::rollback();
                    return $result;
                }
                
                // 审核通过
                $participation->auditPass($adminId, $adminName, $remark);
                
                // 发放奖励
                $coinService = new CoinService();
                $coinResult = $coinService->addCoin(
                    $participation->user_id,
                    $rewardCoin,
                    'red_packet',
                    [
                        'relation_type' => 'task',
                        'relation_id' => $participation->id,
                        'description' => "完成红包任务[{$task->name}]"
                    ]
                );
                
                if ($coinResult['success']) {
                    $participation->reward($rewardCoin);
                    
                    // 更新统计
                    $stat = UserTaskStat::getToday($participation->user_id);
                    $stat->increment('reward_count', 1, $rewardCoin);
                }
                
                // 更新任务待审核计数
                $task->audit_pending_count = max(0, $task->audit_pending_count - 1);
                $task->save();
                
                $result['success'] = true;
                $result['message'] = '审核通过，奖励已发放';
                
            } else {
                // 审核拒绝
                $participation->auditReject($remark, $adminId, $adminName);
                
                // 更新任务统计
                $task->audit_pending_count = max(0, $task->audit_pending_count - 1);
                $task->audit_reject_count = $task->audit_reject_count + 1;
                $task->save();
                
                // 更新用户统计
                $stat = UserTaskStat::getToday($participation->user_id);
                $stat->increment('reject_count');
                
                $result['success'] = true;
                $result['message'] = '已拒绝';
            }
            
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollback();
            $result['message'] = '系统错误: ' . $e->getMessage();
            Log::error('人工审核失败: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * 获取用户参与记录
     */
    public function getUserParticipations($userId, $status = null, $page = 1, $limit = 20)
    {
        $query = TaskParticipation::with(['task'])
            ->where('user_id', $userId);
        
        if ($status !== null) {
            $query->where('status', $status);
        }
        
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
     * 获取进度文本
     */
    protected function getProgressText($task)
    {
        switch ($task->type) {
            case 'download_app':
                return "下载安装后运行{$task->required_duration}秒";
            case 'mini_program':
                return "跳转小程序浏览{$task->required_duration}秒";
            case 'play_game':
                return "玩游戏{$task->required_duration}秒";
            case 'watch_video':
                return "观看视频{$task->required_duration}秒";
            case 'share_link':
                return "分享{$task->required_count}次";
            default:
                return '';
        }
    }
    
    /**
     * 验证任务完成
     */
    protected function verifyTaskComplete($task, $participation, $data)
    {
        $result = ['pass' => true, 'message' => ''];
        
        // 验证时长要求
        if ($task->required_duration > 0) {
            $actualDuration = $data['duration'] ?? ($participation->duration);
            if ($actualDuration < $task->required_duration) {
                $result['pass'] = false;
                $result['message'] = '任务时长不足';
                return $result;
            }
        }
        
        // 验证进度要求
        if ($task->required_progress > 0) {
            $actualProgress = $data['progress'] ?? 0;
            if ($actualProgress < $task->required_progress) {
                $result['pass'] = false;
                $result['message'] = '任务进度不足';
                return $result;
            }
        }
        
        // 验证截图
        if ($task->need_screenshot) {
            $screenshots = $data['screenshots'] ?? [];
            if (empty($screenshots)) {
                $result['pass'] = false;
                $result['message'] = '请上传任务完成截图';
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * 防刷检测
     */
    protected function antiCheatCheck($userId, $taskId, $options)
    {
        $result = ['pass' => true, 'message' => ''];
        
        $ip = $options['ip'] ?? '';
        $deviceId = $options['device_id'] ?? '';
        
        // 同IP限制
        if ($ip) {
            $cacheKey = self::CACHE_PREFIX . "ip_receive:" . date('Ymd') . ":{$ip}";
            $ipCount = Cache::get($cacheKey) ?: 0;
            if ($ipCount >= 50) {
                $result['pass'] = false;
                $result['message'] = '该网络今日领取次数已达上限';
                AnticheatLog::log($userId, 'ip_limit', ['ip' => $ip, 'count' => $ipCount], $ip);
                return $result;
            }
            Cache::set($cacheKey, $ipCount + 1, 86400);
        }
        
        // 同设备限制
        if ($deviceId) {
            $cacheKey = self::CACHE_PREFIX . "device_receive:" . date('Ymd') . ":{$deviceId}";
            $deviceCount = Cache::get($cacheKey) ?: 0;
            if ($deviceCount >= 20) {
                $result['pass'] = false;
                $result['message'] = '该设备今日领取次数已达上限';
                AnticheatLog::log($userId, 'device_limit', ['device_id' => $deviceId, 'count' => $deviceCount], $ip);
                return $result;
            }
            Cache::set($cacheKey, $deviceCount + 1, 86400);
        }
        
        return $result;
    }
    
    /**
     * 记录设备日志
     */
    protected function logDevice($taskId, $userId, $options)
    {
        try {
            Db::name('task_device_log')->insert([
                'task_id' => $taskId,
                'user_id' => $userId,
                'device_id' => $options['device_id'] ?? null,
                'device_name' => $options['device_info']['device_name'] ?? null,
                'device_brand' => $options['device_info']['brand'] ?? null,
                'device_model' => $options['device_info']['model'] ?? null,
                'os_version' => $options['device_info']['os_version'] ?? null,
                'ip' => $options['ip'] ?? null,
                'network_type' => $options['device_info']['network_type'] ?? null,
                'action' => 'receive',
                'createtime' => time(),
            ]);
        } catch (\Exception $e) {
            Log::error('记录设备日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 增加类型统计
     */
    protected function incrementTypeStat($stat, $taskType)
    {
        switch ($taskType) {
            case 'download_app':
                $stat->increment('download_count');
                break;
            case 'mini_program':
                $stat->increment('mini_program_count');
                break;
            case 'play_game':
                $stat->increment('game_count');
                break;
            case 'watch_video':
                $stat->increment('video_count');
                break;
            case 'share_link':
                $stat->increment('share_count');
                break;
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
}
