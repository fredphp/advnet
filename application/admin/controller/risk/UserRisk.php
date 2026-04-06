<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\RiskControlService;
use app\common\library\AutoBanService;
use app\common\library\DeviceFingerprintService;
use think\Db;

/**
 * 用户风险管理
 */
class UserRisk extends Backend
{
    /**
     * 用户风险列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'total_score');
            $order = $this->request->get('order', 'desc');
            
            // 筛选条件
            $riskLevel = $this->request->get('risk_level');
            $status = $this->request->get('status');
            $minScore = $this->request->get('min_score');
            $search = $this->request->get('search');
            
            // 构建查询条件
            $where = [];
            if ($riskLevel) {
                $where['rs.risk_level'] = $riskLevel;
            }
            
            if ($status) {
                $where['rs.status'] = $status;
            }
            
            // 总数查询
            $totalQuery = Db::name('user_risk_score rs')
                ->join('user u', 'u.id = rs.user_id', 'LEFT')
                ->where($where);
            
            if ($minScore !== null && $minScore !== '') {
                $totalQuery->where('rs.total_score', '>=', $minScore);
            }
            
            if ($search) {
                $totalQuery->where(function ($q) use ($search) {
                    $q->whereLike('u.username', "%{$search}%")
                      ->whereOr('u.nickname', 'like', "%{$search}%")
                      ->whereOr('u.mobile', 'like', "%{$search}%");
                });
            }
            
            $total = $totalQuery->count();
            
            // 列表查询
            $listQuery = Db::name('user_risk_score rs')
                ->join('user u', 'u.id = rs.user_id', 'LEFT')
                ->field('rs.*, u.username, u.nickname, u.mobile, u.avatar')
                ->where($where);
            
            if ($minScore !== null && $minScore !== '') {
                $listQuery->where('rs.total_score', '>=', $minScore);
            }
            
            if ($search) {
                $listQuery->where(function ($q) use ($search) {
                    $q->whereLike('u.username', "%{$search}%")
                      ->whereOr('u.nickname', 'like', "%{$search}%")
                      ->whereOr('u.mobile', 'like', "%{$search}%");
                });
            }
            
            $list = $listQuery->order("rs.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();
            
            // 确保返回数组格式
            $rows = is_array($list) ? $list : ($list ? $list->toArray() : []);
            
            // 检查每个用户是否有最近的撤销记录（最近7天内的撤销操作）
            $userIds = array_column($rows, 'user_id');
            if (!empty($userIds)) {
                $revokeRecords = Db::name('risk_log')
                    ->where('user_id', 'in', $userIds)
                    ->where('rule_code', 'like', 'REVOKE_%')
                    ->where('createtime', '>', time() - 7 * 86400)
                    ->field('user_id, MAX(createtime) as last_revoke_time')
                    ->group('user_id')
                    ->select();
                
                $revokeMap = [];
                if ($revokeRecords) {
                    foreach ($revokeRecords as $record) {
                        $revokeMap[$record['user_id']] = $record['last_revoke_time'];
                    }
                }
                
                // 为每行添加是否已撤销标记
                foreach ($rows as &$row) {
                    $row['is_revoked'] = isset($revokeMap[$row['user_id']]) ? 1 : 0;
                    $row['last_revoke_time'] = $revokeMap[$row['user_id']] ?? null;
                }
            }
            
            return json(['total' => $total, 'rows' => $rows]);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 用户风险详情
     */
    public function detail($ids = null)
    {
        $userId = $ids ?: $this->request->get('user_id');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 用户基本信息
        $user = Db::name('user')->where('id', $userId)->find();
        
        // 风险评分详情
        $riskScore = Db::name('user_risk_score')->where('user_id', $userId)->find();
        
        // 封禁历史
        $banHistory = Db::name('ban_record')
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();
        
        // 违规记录
        $violations = Db::name('risk_log')
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->limit(20)
            ->select();
        
        // 设备信息
        $devices = Db::name('device_fingerprint')
            ->where('user_id', $userId)
            ->select();
        
        // 行为统计(最近7天)
        $behaviorStats = Db::name('user_behavior_stat')
            ->where('user_id', $userId)
            ->where('stat_date', '>=', date('Y-m-d', strtotime('-7 days')))
            ->order('stat_date', 'desc')
            ->select();
        
        // 关联账户(同设备)
        $relatedAccounts = [];
        if ($devices) {
            foreach ($devices as $device) {
                $accountIds = json_decode($device['account_ids'] ?? '[]', true);
                if (count($accountIds) > 1) {
                    $relatedAccounts = array_merge($relatedAccounts, $accountIds);
                }
            }
            $relatedAccounts = array_unique(array_diff($relatedAccounts, [$userId]));
        }
        
        // 如果是AJAX请求，返回JSON数据
        if ($this->request->isAjax()) {
            $this->success('', null, [
                'user' => $user,
                'risk_score' => $riskScore,
                'ban_history' => $banHistory,
                'violations' => $violations,
                'devices' => $devices,
                'behavior_stats' => $behaviorStats,
                'related_accounts' => $relatedAccounts,
            ]);
        }
        
        // 非AJAX请求，返回视图
        $this->view->assign('user', $user);
        $this->view->assign('risk_score', $riskScore ?: ['total_score' => 0, 'risk_level' => 'safe', 'violation_count' => 0]);
        $this->view->assign('ban_history', $banHistory);
        $this->view->assign('violations', $violations);
        $this->view->assign('devices', $devices);
        $this->view->assign('behavior_stats', $behaviorStats);
        $this->view->assign('related_accounts', $relatedAccounts);
        
        return $this->view->fetch();
    }
    
    /**
     * 手动调整风险分
     */
    public function adjustScore()
    {
        $userId = $this->request->post('user_id');
        $score = $this->request->post('score');
        $reason = $this->request->post('reason', '管理员手动调整');
        
        if (!$userId || !is_numeric($score)) {
            $this->error('参数无效');
        }
        
        $riskService = new RiskControlService();
        $riskService->init($userId);
        
        if ($score >= 0) {
            $riskService->addRiskScore(abs($score), 'global', 'MANUAL_ADJUST');
        } else {
            // 减分
            Db::name('user_risk_score')
                ->where('user_id', $userId)
                ->dec('total_score', abs($score))
                ->update();
        }
        
        // 记录操作日志
        Db::name('risk_log')->insert([
            'user_id' => $userId,
            'rule_code' => 'MANUAL_ADJUST',
            'rule_name' => '管理员手动调整',
            'rule_type' => 'global',
            'risk_level' => 1,
            'trigger_value' => 0,
            'threshold' => 0,
            'score_add' => $score,
            'action' => 'warn',
            'action_duration' => 0,
            'ip' => request()->ip(),
            'user_agent' => request()->header('user-agent'),
            'request_data' => json_encode(['reason' => $reason, 'admin_id' => $this->auth->id]),
            'createtime' => time(),
        ]);
        
        $this->success();
    }
    
    /**
     * 重置风险分
     */
    public function resetScore()
    {
        $userId = $this->request->post('user_id');
        $reason = $this->request->post('reason', '管理员重置');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        Db::name('user_risk_score')->where('user_id', $userId)->update([
            'total_score' => 0,
            'video_score' => 0,
            'task_score' => 0,
            'withdraw_score' => 0,
            'redpacket_score' => 0,
            'invite_score' => 0,
            'global_score' => 0,
            'violation_count' => 0,
            'risk_level' => 'safe',
            'status' => 'normal',
            'ban_expire_time' => null,
            'freeze_expire_time' => null,
            'score_history' => '[]',
            'updatetime' => time(),
        ]);
        
        $this->success();
    }
    
    /**
     * 手动封禁
     */
    public function ban()
    {
        $userIds = $this->request->post('user_ids/a');
        $reason = $this->request->post('reason');
        $duration = $this->request->post('duration', 0);
        
        if (!$userIds || !is_array($userIds)) {
            $this->error('请选择要封禁的用户');
        }
        
        if (!$reason) {
            $this->error('请填写封禁原因');
        }
        
        $autoBanService = new AutoBanService();
        $result = $autoBanService->batchBan($userIds, $reason, $duration, $this->auth->id);
        
        $this->success('', $result);
    }
    
    /**
     * 解封
     */
    public function release()
    {
        $userId = $this->request->post('user_id');
        $reason = $this->request->post('reason', '管理员解封');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        $autoBanService = new AutoBanService();
        $result = $autoBanService->releaseBan($userId, $reason, $this->auth->id);
        
        if ($result['success']) {
            $this->success();
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 冻结用户
     */
    public function freeze()
    {
        $userId = $this->request->post('user_id');
        $duration = $this->request->post('duration', 7);
        $reason = $this->request->post('reason', '管理员冻结');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 检查用户是否存在
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        
        $freezeExpireTime = time() + $duration * 86400;
        
        // 更新或创建风险评分记录
        $riskScore = Db::name('user_risk_score')->where('user_id', $userId)->find();
        if ($riskScore) {
            Db::name('user_risk_score')->where('user_id', $userId)->update([
                'status' => 'frozen',
                'freeze_expire_time' => $freezeExpireTime,
                'updatetime' => time(),
            ]);
        } else {
            Db::name('user_risk_score')->insert([
                'user_id' => $userId,
                'total_score' => 0,
                'risk_level' => 'low',
                'status' => 'frozen',
                'freeze_expire_time' => $freezeExpireTime,
                'violation_count' => 0,
                'createtime' => time(),
                'updatetime' => time(),
            ]);
        }
        
        // 记录日志
        Db::name('risk_log')->insert([
            'user_id' => $userId,
            'rule_code' => 'MANUAL_FREEZE',
            'rule_name' => '管理员冻结',
            'rule_type' => 'global',
            'risk_level' => 2,
            'trigger_value' => 0,
            'threshold' => 0,
            'score_add' => 0,
            'action' => 'freeze',
            'action_duration' => $duration * 86400,
            'ip' => request()->ip(),
            'user_agent' => request()->header('user-agent'),
            'request_data' => json_encode([
                'reason' => $reason,
                'duration' => $duration,
                'admin_id' => $this->auth->id
            ]),
            'createtime' => time(),
        ]);
        
        $this->success('冻结成功');
    }
    
    /**
     * 解冻用户
     */
    public function unfreeze()
    {
        $userId = $this->request->post('user_id');
        $reason = $this->request->post('reason', '管理员解冻');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 更新风险评分状态
        Db::name('user_risk_score')
            ->where('user_id', $userId)
            ->update([
                'status' => 'normal',
                'freeze_expire_time' => null,
                'updatetime' => time(),
            ]);
        
        // 记录日志
        Db::name('risk_log')->insert([
            'user_id' => $userId,
            'rule_code' => 'MANUAL_UNFREEZE',
            'rule_name' => '管理员解冻',
            'rule_type' => 'global',
            'risk_level' => 1,
            'trigger_value' => 0,
            'threshold' => 0,
            'score_add' => 0,
            'action' => 'unfreeze',
            'action_duration' => 0,
            'ip' => request()->ip(),
            'user_agent' => request()->header('user-agent'),
            'request_data' => json_encode([
                'reason' => $reason,
                'admin_id' => $this->auth->id
            ]),
            'createtime' => time(),
        ]);
        
        $this->success('解冻成功');
    }
    
    /**
     * 加入白名单
     */
    public function addWhitelist()
    {
        $userId = $this->request->post('user_id');
        $reason = $this->request->post('reason', '');
        $expireDays = $this->request->post('expire_days', 0);
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        $expireTime = $expireDays > 0 ? time() + $expireDays * 86400 : null;
        
        Db::name('risk_whitelist')->insert([
            'type' => 'user',
            'value' => $userId,
            'reason' => $reason,
            'expire_time' => $expireTime,
            'admin_id' => $this->auth->id,
            'admin_name' => $this->auth->username,
            'createtime' => time(),
        ]);
        
        $this->success();
    }
    
    /**
     * 移出白名单
     */
    public function removeWhitelist()
    {
        $userId = $this->request->post('user_id');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        Db::name('risk_whitelist')
            ->where('type', 'user')
            ->where('value', $userId)
            ->delete();
        
        $this->success();
    }
    
    /**
     * 批量导出
     */
    public function export()
    {
        $riskLevel = $this->request->get('risk_level');
        $minScore = $this->request->get('min_score');
        
        $query = Db::name('user_risk_score rs')
            ->join('user u', 'u.id = rs.user_id', 'LEFT')
            ->field('rs.*, u.username, u.nickname, u.mobile');
        
        if ($riskLevel) {
            $query->where('rs.risk_level', $riskLevel);
        }
        
        if ($minScore !== null && $minScore !== '') {
            $query->where('rs.total_score', '>=', $minScore);
        }
        
        $list = $query->order('rs.total_score', 'desc')->select();
        
        // 导出为CSV
        $filename = 'risk_users_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        
        fputcsv($output, ['用户ID', '用户名', '手机号', '风险分', '风险等级', '状态', '违规次数', '最后违规时间']);
        
        foreach ($list as $row) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['mobile'],
                $row['total_score'],
                $row['risk_level'],
                $row['status'],
                $row['violation_count'],
                $row['last_violation_time'] ? date('Y-m-d H:i:s', $row['last_violation_time']) : '',
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * 获取撤销风控所需信息
     */
    public function revokeInfo()
    {
        $userId = $this->request->get('user_id');
        
        // 尝试多种方式获取user_id
        if (!$userId) {
            $userId = $this->request->param('user_id');
        }
        if (!$userId) {
            $userId = $this->request->request('user_id');
        }
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 用户基本信息
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        
        // 风险评分详情
        $riskScore = Db::name('user_risk_score')->where('user_id', $userId)->find();
        if (!$riskScore) {
            $riskScore = [
                'total_score' => 0,
                'risk_level' => 'safe',
                'violation_count' => 0,
            ];
        }
        
        // 最近风控记录（排除撤销类型的记录）
        $riskLogs = Db::name('risk_log')
            ->where('user_id', $userId)
            ->where('rule_code', 'not like', 'REVOKE_%')  // 排除撤销记录
            ->order('createtime', 'desc')
            ->limit(20)
            ->select();
        
        // 处理时间格式
        $logsData = [];
        if ($riskLogs) {
            foreach ($riskLogs as $log) {
                $log['createtime_text'] = date('Y-m-d H:i:s', $log['createtime']);
                $logsData[] = $log;
            }
        }
        
        // 检查用户是否已在白名单中
        $inWhitelist = Db::name('risk_whitelist')
            ->where('type', 'user')
            ->where('value', $userId)
            ->where(function($query) {
                $query->whereNull('expire_time')
                      ->whereOr('expire_time', '>', time());
            })
            ->find();
        
        $this->success('', null, [
            'user' => $user,
            'risk_score' => $riskScore,
            'risk_logs' => $logsData,
            'in_whitelist' => $inWhitelist ? 1 : 0,
        ]);
    }
    
    /**
     * 撤销风控
     */
    public function revoke()
    {
        $userId = $this->request->post('user_id');
        
        // 尝试多种方式获取user_id
        if (!$userId) {
            $userId = $this->request->param('user_id');
        }
        if (!$userId) {
            $userId = $this->request->request('user_id');
        }
        
        $revokeType = $this->request->post('revoke_type', 'reset');
        if (!$revokeType) {
            $revokeType = $this->request->param('revoke_type', 'reset');
        }
        
        $reduceScore = $this->request->post('reduce_score', 50);
        $whitelistDays = $this->request->post('whitelist_days', 30);
        $reason = $this->request->post('reason', '管理员撤销风控');
        
        if (!$userId) {
            $this->error('请指定用户ID');
        }
        
        // 检查用户是否存在
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        
        switch ($revokeType) {
            case 'reset':
                // 重置风险分
                Db::name('user_risk_score')->where('user_id', $userId)->update([
                    'total_score' => 0,
                    'video_score' => 0,
                    'task_score' => 0,
                    'withdraw_score' => 0,
                    'redpacket_score' => 0,
                    'invite_score' => 0,
                    'global_score' => 0,
                    'violation_count' => 0,
                    'risk_level' => 'safe',
                    'status' => 'normal',
                    'ban_expire_time' => null,
                    'freeze_expire_time' => null,
                    'score_history' => '[]',
                    'updatetime' => time(),
                ]);
                
                // 记录操作日志
                Db::name('risk_log')->insert([
                    'user_id' => $userId,
                    'rule_code' => 'REVOKE_RESET',
                    'rule_name' => '撤销风控-重置',
                    'rule_type' => 'global',
                    'risk_level' => 1,
                    'trigger_value' => 0,
                    'threshold' => 0,
                    'score_add' => 0,
                    'action' => 'revoke',
                    'action_duration' => 0,
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'request_data' => json_encode(['reason' => $reason, 'admin_id' => $this->auth->id]),
                    'createtime' => time(),
                ]);
                break;
                
            case 'reduce':
                // 降低风险分 + 同步解封
                Db::name('user_risk_score')
                    ->where('user_id', $userId)
                    ->dec('total_score', $reduceScore)
                    ->update();
                
                // 重新计算风险等级，并解除封禁/冻结
                $newScore = Db::name('user_risk_score')
                    ->where('user_id', $userId)
                    ->value('total_score');
                
                $newScore = max(0, $newScore); // 确保不为负数
                
                $newLevel = 'safe';
                if ($newScore >= 200) $newLevel = 'dangerous';
                elseif ($newScore >= 100) $newLevel = 'high';
                elseif ($newScore >= 50) $newLevel = 'medium';
                elseif ($newScore >= 20) $newLevel = 'low';
                
                Db::name('user_risk_score')
                    ->where('user_id', $userId)
                    ->update([
                        'risk_level' => $newLevel, 
                        'status' => 'normal',
                        'ban_expire_time' => null,
                        'freeze_expire_time' => null,
                        'updatetime' => time()
                    ]);
                
                // 记录操作日志
                Db::name('risk_log')->insert([
                    'user_id' => $userId,
                    'rule_code' => 'REVOKE_REDUCE',
                    'rule_name' => '撤销风控-降分',
                    'rule_type' => 'global',
                    'risk_level' => 1,
                    'trigger_value' => 0,
                    'threshold' => 0,
                    'score_add' => -$reduceScore,
                    'action' => 'revoke',
                    'action_duration' => 0,
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'request_data' => json_encode(['reason' => $reason, 'reduce_score' => $reduceScore, 'admin_id' => $this->auth->id]),
                    'createtime' => time(),
                ]);
                break;
                
            case 'whitelist':
                // 加入白名单 + 同步解封
                $expireTime = $whitelistDays > 0 ? time() + $whitelistDays * 86400 : null;
                
                // 检查是否已在白名单
                $exists = Db::name('risk_whitelist')
                    ->where('type', 'user')
                    ->where('value', $userId)
                    ->find();
                
                if ($exists) {
                    // 更新
                    Db::name('risk_whitelist')
                        ->where('id', $exists['id'])
                        ->update([
                            'reason' => $reason,
                            'expire_time' => $expireTime,
                            'admin_id' => $this->auth->id,
                            'admin_name' => $this->auth->username,
                            'updatetime' => time(),
                        ]);
                } else {
                    // 新增
                    Db::name('risk_whitelist')->insert([
                        'type' => 'user',
                        'value' => $userId,
                        'reason' => $reason,
                        'expire_time' => $expireTime,
                        'admin_id' => $this->auth->id,
                        'admin_name' => $this->auth->username,
                        'createtime' => time(),
                    ]);
                }
                
                // 同步解除封禁/冻结状态
                Db::name('user_risk_score')
                    ->where('user_id', $userId)
                    ->update([
                        'status' => 'normal',
                        'ban_expire_time' => null,
                        'freeze_expire_time' => null,
                        'updatetime' => time()
                    ]);
                
                // 记录操作日志
                Db::name('risk_log')->insert([
                    'user_id' => $userId,
                    'rule_code' => 'REVOKE_WHITELIST',
                    'rule_name' => '撤销风控-白名单',
                    'rule_type' => 'global',
                    'risk_level' => 1,
                    'trigger_value' => 0,
                    'threshold' => 0,
                    'score_add' => 0,
                    'action' => 'whitelist',
                    'action_duration' => 0,
                    'ip' => request()->ip(),
                    'user_agent' => request()->header('user-agent'),
                    'request_data' => json_encode(['reason' => $reason, 'whitelist_days' => $whitelistDays, 'admin_id' => $this->auth->id]),
                    'createtime' => time(),
                ]);
                break;
        }
        
        $this->success('撤销成功');
    }
}
