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

            $list = [];
            $total = 0;

            try {
                // 构建查询 - 使用原生SQL确保字段正确
                $prefix = config('database.prefix');
                
                // 安全处理参数
                $banType = $banType ? addslashes($banType) : '';
                $status = $status ? addslashes($status) : '';
                $banSource = $banSource ? addslashes($banSource) : '';
                $search = $search ? addslashes($search) : '';
                $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);
                $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
                $offset = intval($offset);
                $limit = intval($limit);
                
                $whereSql = "1=1";
                if ($banType) $whereSql .= " AND br.ban_type = '{$banType}'";
                if ($status) $whereSql .= " AND br.status = '{$status}'";
                if ($banSource) $whereSql .= " AND br.ban_source = '{$banSource}'";
                if ($search) $whereSql .= " AND (u.username LIKE '%{$search}%' OR u.nickname LIKE '%{$search}%' OR u.mobile LIKE '%{$search}%')";
                
                $list = Db::query("
                    SELECT br.*, 
                           u.username, 
                           u.nickname, 
                           u.mobile,
                           a.username as admin_name
                    FROM {$prefix}ban_record br
                    LEFT JOIN {$prefix}user u ON u.id = br.user_id
                    LEFT JOIN {$prefix}admin a ON a.id = br.admin_id
                    WHERE {$whereSql}
                    ORDER BY br.{$sort} {$order}
                    LIMIT {$offset}, {$limit}
                ");

                $countResult = Db::query("
                    SELECT COUNT(*) as total
                    FROM {$prefix}ban_record br
                    LEFT JOIN {$prefix}user u ON u.id = br.user_id
                    WHERE {$whereSql}
                ");

                $total = $countResult[0]['total'] ?? 0;
            } catch (\Exception $e) {
                \think\Log::error('BanRecord index error: ' . $e->getMessage());
            }

            // 格式化数据
            foreach ($list as &$row) {
                $row['ban_type_text'] = $this->getBanTypeText($row['ban_type']);
                $row['ban_source_text'] = $this->getBanSourceText($row['ban_source']);
                $row['status_text'] = $this->getStatusText($row['status']);
                // 处理可能的null值
                $row['username'] = $row['username'] ?? '';
                $row['nickname'] = $row['nickname'] ?? '';
                $row['mobile'] = $row['mobile'] ?? '';
                $row['admin_name'] = $row['admin_name'] ?? '';
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

        // GET请求返回解封表单 - 使用原生SQL确保字段正确
        $prefix = config('database.prefix');
        $ids = intval($ids);
        $record = Db::query("
            SELECT br.*, u.username, u.nickname, u.mobile
            FROM {$prefix}ban_record br
            LEFT JOIN {$prefix}user u ON u.id = br.user_id
            WHERE br.id = {$ids}
        ");

        if (empty($record)) {
            $this->error('记录不存在');
        }

        $record = $record[0];

        $this->view->assign('record', $record);
        return $this->view->fetch();
    }

    /**
     * 查看封禁详情弹窗
     */
    public function viewDetail($ids = null)
    {
        // 使用原生SQL确保字段正确获取
        $prefix = config('database.prefix');
        $ids = intval($ids);
        
        $record = Db::query("
            SELECT br.*, u.username, u.nickname, u.mobile, u.avatar, a.username as admin_name
            FROM {$prefix}ban_record br
            LEFT JOIN {$prefix}user u ON u.id = br.user_id
            LEFT JOIN {$prefix}admin a ON a.id = br.admin_id
            WHERE br.id = {$ids}
        ");

        if (empty($record)) {
            $this->error('记录不存在');
        }

        $record = $record[0];

        // 获取用户风险评分
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $record['user_id'])
            ->find();

        // 获取该用户所有封禁历史
        $banHistory = Db::query("
            SELECT * FROM {$prefix}ban_record 
            WHERE user_id = {$record['user_id']} 
            ORDER BY createtime DESC
        ");

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
