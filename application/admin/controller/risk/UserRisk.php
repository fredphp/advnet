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
            
            $query = Db::name('user_risk_score rs')
                ->join('user u', 'u.id = rs.user_id', 'LEFT')
                ->field('rs.*, u.username, u.nickname, u.mobile, u.avatar');
            
            if ($riskLevel) {
                $query->where('rs.risk_level', $riskLevel);
            }
            
            if ($status) {
                $query->where('rs.status', $status);
            }
            
            if ($minScore !== null && $minScore !== '') {
                $query->where('rs.total_score', '>=', $minScore);
            }
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('u.username', "%{$search}%")
                      ->whereOr('u.nickname', 'like', "%{$search}%")
                      ->whereOr('u.mobile', 'like', "%{$search}%");
                });
            }
            
            $total = $query->count();
            $list = $query->order("rs.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();
            
            return json(['total' => $total, 'rows' => $list]);
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
        
        $this->success('', [
            'user' => $user,
            'risk_score' => $riskScore,
            'ban_history' => $banHistory,
            'violations' => $violations,
            'devices' => $devices,
            'behavior_stats' => $behaviorStats,
            'related_accounts' => $relatedAccounts,
        ]);
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
}
