<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\AutoBanService;
use think\Db;

/**
 * 封禁记录管理
 */
class BanRecord extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\BanRecord();
    }
    
    /**
     * 封禁记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');

            // 筛选条件
            $banType = $this->request->get('filter.ban_type');
            $status = $this->request->get('filter.status');
            $banSource = $this->request->get('filter.ban_source');
            $search = $this->request->get('search');
            $startDate = $this->request->get('filter.createtime');
            $endDate = $this->request->get('filter.createtime');

            $query = Db::name('ban_record br')
                ->join('user u', 'u.id = br.user_id', 'LEFT')
                ->join('admin a', 'a.id = br.admin_id', 'LEFT')
                ->field('br.*, u.username, u.nickname, u.mobile, a.username as admin_name');

            if ($banType) {
                $query->where('br.ban_type', $banType);
            }

            if ($status) {
                $query->where('br.status', $status);
            }

            if ($banSource) {
                $query->where('br.ban_source', $banSource);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('u.username', "%{$search}%")
                      ->whereOr('u.nickname', 'like', "%{$search}%")
                      ->whereOr('u.mobile', 'like', "%{$search}%");
                });
            }

            // 处理日期范围筛选
            $createtimeRange = $this->request->get('filter.createtime');
            if ($createtimeRange && strpos($createtimeRange, ' - ') !== false) {
                list($startDt, $endDt) = explode(' - ', $createtimeRange);
                $query->where('br.createtime', '>=', strtotime($startDt));
                $query->where('br.createtime', '<=', strtotime($endDt . ' 23:59:59'));
            }

            $total = $query->count();
            $list = $query->order("br.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            // 格式化数据
            foreach ($list as &$row) {
                $row['ban_type_text'] = $this->getBanTypeText($row['ban_type']);
                $row['ban_source_text'] = $this->getBanSourceText($row['ban_source']);
                $row['status_text'] = $this->getStatusText($row['status']);
                // 为前端兼容性添加reason字段
                $row['reason'] = $row['ban_reason'];
                $row['expire_time'] = $row['end_time'];
            }

            return json(['total' => $total, 'rows' => $list]);
        }

        return $this->view->fetch();
    }

    /**
     * 获取封禁类型文本
     */
    private function getBanTypeText($type)
    {
        $types = [
            'temporary' => '临时封禁',
            'permanent' => '永久封禁',
        ];
        return $types[$type] ?? $type;
    }

    /**
     * 获取封禁来源文本
     */
    private function getBanSourceText($source)
    {
        $sources = [
            'auto' => '系统自动',
            'manual' => '手动封禁',
        ];
        return $sources[$source] ?? $source;
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status)
    {
        $statuses = [
            'active' => '封禁中',
            'released' => '已解封',
            'expired' => '已过期',
        ];
        return $statuses[$status] ?? $status;
    }
    
    /**
     * 封禁详情
     */
    public function detail($ids = null)
    {
        $record = Db::name('ban_record br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->field('br.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('br.id', $ids)
            ->find();
        
        if (!$record) {
            $this->error('记录不存在');
        }
        
        // 获取用户当前风险状态
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $record['user_id'])
            ->find();
        
        // 获取该用户所有封禁记录
        $allRecords = Db::name('ban_record')
            ->where('user_id', $record['user_id'])
            ->order('createtime', 'desc')
            ->select();
        
        $this->success('', null, [
            'record' => $record,
            'risk_score' => $riskScore,
            'all_records' => $allRecords,
        ]);
    }
    
    /**
     * 手动解封
     */
    public function release($ids = null)
    {
        $record = $this->model->get($ids);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record->status != 'active') {
            $this->error('该记录已处理');
        }
        
        $reason = $this->request->post('reason', '管理员手动解封');
        
        $autoBanService = new AutoBanService();
        $result = $autoBanService->releaseBan($record->user_id, $reason, $this->auth->id);
        
        if ($result['success']) {
            $this->success();
        } else {
            $this->error($result['message']);
        }
    }
    
    /**
     * 批量解封
     */
    public function batchRelease()
    {
        $ids = $this->request->post('ids/a');
        $reason = $this->request->post('reason', '管理员批量解封');
        
        if (!$ids || !is_array($ids)) {
            $this->error('请选择要解封的记录');
        }
        
        $autoBanService = new AutoBanService();
        $success = 0;
        $failed = 0;
        
        foreach ($ids as $id) {
            $record = $this->model->get($id);
            if ($record && $record->status == 'active') {
                $result = $autoBanService->releaseBan($record->user_id, $reason, $this->auth->id);
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }
        
        $this->success("成功解封{$success}个，失败{$failed}个");
    }
    
    /**
     * 获取用户详情（用于弹窗显示）
     */
    public function userInfo()
    {
        $userId = $this->request->get('user_id');
        if (!$userId) {
            $this->error('缺少用户ID');
        }

        // 获取用户基本信息
        $user = Db::name('user')
            ->where('id', $userId)
            ->field('id, username, nickname, mobile, avatar, email, status, createtime, logintime')
            ->find();

        if (!$user) {
            $this->error('用户不存在');
        }

        // 获取用户风险评分
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $userId)
            ->find();

        // 获取近期违规记录（最近10条）
        $recentViolations = Db::name('risk_log rl')
            ->join('risk_rule rr', 'rr.id = rl.rule_id', 'LEFT')
            ->where('rl.user_id', $userId)
            ->field('rl.*, rr.name as rule_name')
            ->order('rl.createtime', 'desc')
            ->limit(10)
            ->select();

        // 获取封禁记录
        $banRecords = Db::name('ban_record')
            ->where('user_id', $userId)
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();

        // 格式化数据
        $user['status_text'] = $this->getUserStatusText($user['status']);
        $user['createtime_text'] = date('Y-m-d H:i:s', $user['createtime']);
        $user['logintime_text'] = $user['logintime'] ? date('Y-m-d H:i:s', $user['logintime']) : '从未登录';

        if ($riskScore) {
            $riskScore['risk_level_text'] = $this->getRiskLevelText($riskScore['risk_level']);
            $riskScore['status_text'] = $this->getRiskStatusText($riskScore['status']);
        }

        foreach ($banRecords as &$record) {
            $record['ban_type_text'] = $this->getBanTypeText($record['ban_type']);
            $record['ban_source_text'] = $this->getBanSourceText($record['ban_source']);
            $record['status_text'] = $this->getStatusText($record['status']);
            $record['createtime_text'] = date('Y-m-d H:i:s', $record['createtime']);
        }

        $this->success('', null, [
            'user' => $user,
            'risk_score' => $riskScore,
            'recent_violations' => $recentViolations,
            'ban_records' => $banRecords,
        ]);
    }

    /**
     * 解封操作（带弹窗确认）
     */
    public function releaseDialog($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ?: $this->request->post('ids');
            $reason = $this->request->post('reason', '管理员手动解封');

            $record = $this->model->get($ids);
            if (!$record) {
                $this->error('记录不存在');
            }

            if ($record->status != 'active') {
                $this->error('该记录已处理，无法解封');
            }

            $autoBanService = new AutoBanService();
            $result = $autoBanService->releaseBan($record->user_id, $reason, $this->auth->id);

            if ($result['success']) {
                $this->success('解封成功');
            } else {
                $this->error($result['message']);
            }
        }

        // GET请求返回解封表单
        $record = Db::name('ban_record br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->field('br.*, u.username, u.nickname, u.mobile')
            ->where('br.id', $ids)
            ->find();

        if (!$record) {
            $this->error('记录不存在');
        }

        $this->view->assign('record', $record);
        return $this->view->fetch();
    }

    /**
     * 查看封禁详情弹窗
     */
    public function viewDetail($ids = null)
    {
        $record = Db::name('ban_record br')
            ->join('user u', 'u.id = br.user_id', 'LEFT')
            ->join('admin a', 'a.id = br.admin_id', 'LEFT')
            ->field('br.*, u.username, u.nickname, u.mobile, u.avatar, a.username as admin_name')
            ->where('br.id', $ids)
            ->find();

        if (!$record) {
            $this->error('记录不存在');
        }

        // 获取用户风险评分
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $record['user_id'])
            ->find();

        // 获取该用户所有封禁历史
        $banHistory = Db::name('ban_record')
            ->where('user_id', $record['user_id'])
            ->order('createtime', 'desc')
            ->select();

        $this->view->assign([
            'record' => $record,
            'risk_score' => $riskScore,
            'ban_history' => $banHistory,
        ]);

        return $this->view->fetch();
    }

    /**
     * 获取用户状态文本
     */
    private function getUserStatusText($status)
    {
        $statuses = [
            'normal' => '正常',
            'frozen' => '已冻结',
            'banned' => '已封禁',
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * 获取风险等级文本
     */
    private function getRiskLevelText($level)
    {
        $levels = [
            'low' => '低风险',
            'medium' => '中风险',
            'high' => '高风险',
            'dangerous' => '危险',
        ];
        return $levels[$level] ?? $level;
    }

    /**
     * 获取风控状态文本
     */
    private function getRiskStatusText($status)
    {
        $statuses = [
            'normal' => '正常',
            'frozen' => '冻结中',
            'banned' => '已封禁',
        ];
        return $statuses[$status] ?? $status;
    }

    /**
     * 封禁统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));
        
        $autoBanService = new AutoBanService();
        $stats = $autoBanService->getBanStatistics($startDate, $endDate);
        
        // 按日期统计
        $dailyStats = Db::name('ban_record')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, 
                     COUNT(*) as total,
                     SUM(CASE WHEN ban_type = "temporary" THEN 1 ELSE 0 END) as temporary,
                     SUM(CASE WHEN ban_type = "permanent" THEN 1 ELSE 0 END) as permanent,
                     SUM(CASE WHEN ban_source = "auto" THEN 1 ELSE 0 END) as auto_ban,
                     SUM(CASE WHEN ban_source = "manual" THEN 1 ELSE 0 END) as manual_ban')
            ->where('createtime', '>=', strtotime($startDate))
            ->where('createtime', '<=', strtotime($endDate . ' 23:59:59'))
            ->group('date')
            ->order('date', 'asc')
            ->select();
        
        // 当前封禁状态分布
        $statusDistribution = Db::name('ban_record')
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select();
        
        $this->success('', null, [
            'stats' => $stats,
            'daily_stats' => $dailyStats,
            'status_distribution' => $statusDistribution,
        ]);
    }
}
