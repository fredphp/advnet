<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use think\Db;

/**
 * 黑名单管理
 */
class Blacklist extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RiskBlacklist();
    }
    
    /**
     * 黑名单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'createtime');
            $order = $this->request->get('order', 'desc');
            
            // 筛选条件
            $type = $this->request->get('filter.type');
            $source = $this->request->get('filter.source');
            $enabled = $this->request->get('filter.enabled');
            $search = $this->request->get('search');
            
            $prefix = config('database.prefix');
            
            // 安全处理参数
            $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);
            $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $offset = intval($offset);
            $limit = intval($limit);
            
            $whereSql = "1=1";
            if ($type) $whereSql .= " AND type = '" . addslashes($type) . "'";
            if ($source) $whereSql .= " AND source = '" . addslashes($source) . "'";
            if ($enabled !== null && $enabled !== '') $whereSql .= " AND enabled = " . intval($enabled);
            if ($search) $whereSql .= " AND (value LIKE '%" . addslashes($search) . "%' OR reason LIKE '%" . addslashes($search) . "%')";
            
            // 使用原生SQL查询
            $list = Db::query("
                SELECT * FROM {$prefix}risk_blacklist 
                WHERE {$whereSql}
                ORDER BY {$sort} {$order}
                LIMIT {$offset}, {$limit}
            ");
            
            $countResult = Db::query("
                SELECT COUNT(*) as total FROM {$prefix}risk_blacklist WHERE {$whereSql}
            ");
            $total = $countResult[0]['total'] ?? 0;
            
            // 格式化数据
            foreach ($list as &$item) {
                $item['type_text'] = $this->getTypeText($item['type']);
                $item['source_text'] = $this->getSourceText($item['source']);
                
                // 如果是用户类型，获取用户信息
                if ($item['type'] == 'user') {
                    $user = Db::name('user')->where('id', $item['value'])->field('username, nickname, mobile')->find();
                    $item['user_info'] = $user;
                }
            }
            
            return json(['total' => $total, 'rows' => $list]);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 获取类型文本
     */
    private function getTypeText($type)
    {
        $types = [
            'user' => '用户',
            'ip' => 'IP地址',
            'device' => '设备ID',
            'phone' => '手机号',
        ];
        return $types[$type] ?? $type;
    }
    
    /**
     * 获取来源文本
     */
    private function getSourceText($source)
    {
        $sources = [
            'auto' => '系统自动',
            'manual' => '手动添加',
            'import' => '批量导入',
        ];
        return $sources[$source] ?? $source;
    }
    
    /**
     * 添加黑名单
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (!$params) {
                $this->error('参数不能为空');
            }
            
            $prefix = config('database.prefix');
            
            // 检查是否已存在
            $exists = Db::query("
                SELECT id FROM {$prefix}risk_blacklist 
                WHERE type = ? AND value = ? LIMIT 1
            ", [$params['type'], $params['value']]);
            
            if ($exists) {
                $this->error('该记录已存在');
            }
            
            $now = time();
            $expireTime = !empty($params['expire_time']) ? strtotime($params['expire_time']) : null;
            
            Db::execute("
                INSERT INTO {$prefix}risk_blacklist 
                (type, value, reason, source, risk_score, expire_time, admin_id, admin_name, enabled, createtime, updatetime)
                VALUES (?, ?, ?, 'manual', ?, ?, ?, ?, 1, ?, ?)
            ", [
                $params['type'],
                $params['value'],
                $params['reason'] ?? '',
                $params['risk_score'] ?? 0,
                $expireTime,
                $this->auth->id,
                $this->auth->username,
                $now,
                $now
            ]);
            
            $this->success();
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 编辑黑名单
     */
    public function edit($ids = null)
    {
        $prefix = config('database.prefix');
        $ids = intval($ids);
        
        // 使用原生SQL获取记录
        $row = Db::query("SELECT * FROM {$prefix}risk_blacklist WHERE id = ? LIMIT 1", [$ids]);
        
        if (empty($row)) {
            $this->error('记录不存在');
        }
        $row = $row[0];
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (!$params) {
                $this->error('参数不能为空');
            }
            
            $now = time();
            $expireTime = !empty($params['expire_time']) ? strtotime($params['expire_time']) : null;
            
            Db::execute("
                UPDATE {$prefix}risk_blacklist 
                SET type = ?, value = ?, reason = ?, risk_score = ?, expire_time = ?, enabled = ?, updatetime = ?
                WHERE id = ?
            ", [
                $params['type'],
                $params['value'],
                $params['reason'] ?? '',
                $params['risk_score'] ?? 0,
                $expireTime,
                isset($params['enabled']) ? intval($params['enabled']) : 1,
                $now,
                $ids
            ]);
            
            $this->success();
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 移除黑名单
     */
    public function remove()
    {
        $ids = $this->request->post('ids');
        
        if (!$ids) {
            $this->error('请选择要移除的记录');
        }
        
        $prefix = config('database.prefix');
        $ids = array_map('intval', explode(',', $ids));
        $idList = implode(',', $ids);
        
        Db::execute("DELETE FROM {$prefix}risk_blacklist WHERE id IN ({$idList})");
        
        $this->success();
    }
    
    /**
     * 删除（重写父类方法）
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $prefix = config('database.prefix');
            $ids = array_map('intval', is_array($ids) ? $ids : explode(',', $ids));
            $idList = implode(',', $ids);
            Db::execute("DELETE FROM {$prefix}risk_blacklist WHERE id IN ({$idList})");
            $this->success();
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    
    /**
     * 批量更新状态
     */
    public function multi($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        
        $ids = $ids ? $ids : $this->request->post("ids");
        $action = $this->request->post("action");
        
        if (!$ids || !$action) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        
        $prefix = config('database.prefix');
        $ids = array_map('intval', is_array($ids) ? $ids : explode(',', $ids));
        $idList = implode(',', $ids);
        
        if ($action == 'enable') {
            Db::execute("UPDATE {$prefix}risk_blacklist SET enabled = 1, updatetime = ? WHERE id IN ({$idList})", [time()]);
        } elseif ($action == 'disable') {
            Db::execute("UPDATE {$prefix}risk_blacklist SET enabled = 0, updatetime = ? WHERE id IN ({$idList})", [time()]);
        }
        
        $this->success();
    }
    
    /**
     * 批量添加IP黑名单
     */
    public function batchAddIp()
    {
        $ips = $this->request->post('ips');
        $reason = $this->request->post('reason', '管理员批量添加');
        $expireDays = $this->request->post('expire_days', 0);
        
        if (!$ips) {
            $this->error('请输入IP地址');
        }
        
        $ipList = array_filter(array_map('trim', explode("\n", $ips)));
        $expireTime = $expireDays > 0 ? time() + $expireDays * 86400 : null;
        $prefix = config('database.prefix');
        
        $added = 0;
        $skipped = 0;
        
        foreach ($ipList as $ip) {
            // 验证IP格式
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped++;
                continue;
            }
            
            // 检查是否已存在
            $exists = Db::query("SELECT id FROM {$prefix}risk_blacklist WHERE type = 'ip' AND value = ? LIMIT 1", [$ip]);
            if ($exists) {
                $skipped++;
                continue;
            }
            
            Db::execute("
                INSERT INTO {$prefix}risk_blacklist 
                (type, value, reason, source, expire_time, admin_id, admin_name, enabled, createtime, updatetime)
                VALUES ('ip', ?, ?, 'manual', ?, ?, ?, 1, ?, ?)
            ", [$ip, $reason, $expireTime, $this->auth->id, $this->auth->username, time(), time()]);
            
            $added++;
        }
        
        $this->success("成功添加{$added}条，跳过{$skipped}条");
    }
    
    /**
     * IP查询
     */
    public function queryIp()
    {
        $ip = $this->request->get('ip');
        
        if (!$ip) {
            $this->error('请输入IP地址');
        }
        
        $prefix = config('database.prefix');
        
        // 检查是否在黑名单
        $inBlacklist = Db::query("
            SELECT * FROM {$prefix}risk_blacklist 
            WHERE type = 'ip' AND value = ? AND enabled = 1 
            AND (expire_time IS NULL OR expire_time > ?)
            LIMIT 1
        ", [$ip, time()]);
        
        // 获取IP风险信息
        $ipRisk = Db::name('ip_risk')->where('ip', $ip)->find();
        
        // 获取关联账户
        $relatedAccounts = [];
        if ($ipRisk && $ipRisk['account_ids']) {
            $accountIds = json_decode($ipRisk['account_ids'], true);
            $relatedAccounts = Db::name('user')
                ->whereIn('id', $accountIds)
                ->field('id, username, nickname, mobile, status')
                ->select();
        }
        
        $this->success('', null, [
            'ip' => $ip,
            'in_blacklist' => !empty($inBlacklist),
            'blacklist_info' => !empty($inBlacklist) ? $inBlacklist[0] : null,
            'ip_risk' => $ipRisk,
            'related_accounts' => $relatedAccounts,
        ]);
    }
}
