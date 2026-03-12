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
            
            $type = $this->request->get('type');
            $source = $this->request->get('source');
            $search = $this->request->get('search');
            
            $query = $this->model;
            
            if ($type) {
                $query->where('type', $type);
            }
            
            if ($source) {
                $query->where('source', $source);
            }
            
            if ($search) {
                $query->whereLike('value', "%{$search}%");
            }
            
            $total = $query->count();
            $list = $this->model->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            // 补充关联信息
            foreach ($list as &$item) {
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
     * 添加黑名单
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (!$params) {
                $this->error('参数不能为空');
            }
            
            // 检查是否已存在
            $exists = $this->model->where('type', $params['type'])
                ->where('value', $params['value'])
                ->find();
            
            if ($exists) {
                $this->error('该记录已存在');
            }
            
            $params['source'] = 'manual';
            $params['admin_id'] = $this->auth->id;
            $params['admin_name'] = $this->auth->username;
            $params['createtime'] = time();
            
            $result = $this->model->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        
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
        
        $this->model->destroy($ids);
        
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
        
        $added = 0;
        $skipped = 0;
        
        foreach ($ipList as $ip) {
            // 验证IP格式
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped++;
                continue;
            }
            
            // 检查是否已存在
            $exists = $this->model->where('type', 'ip')->where('value', $ip)->find();
            if ($exists) {
                $skipped++;
                continue;
            }
            
            $this->model->save([
                'type' => 'ip',
                'value' => $ip,
                'reason' => $reason,
                'source' => 'manual',
                'expire_time' => $expireTime,
                'admin_id' => $this->auth->id,
                'admin_name' => $this->auth->username,
                'createtime' => time(),
            ]);
            
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
        
        // 检查是否在黑名单
        $inBlacklist = $this->model->where('type', 'ip')
            ->where('value', $ip)
            ->where('enabled', 1)
            ->where(function ($query) {
                $query->whereNull('expire_time')
                    ->whereOr('expire_time', '>', time());
            })
            ->find();
        
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
            'in_blacklist' => $inBlacklist ? true : false,
            'blacklist_info' => $inBlacklist,
            'ip_risk' => $ipRisk,
            'related_accounts' => $relatedAccounts,
        ]);
    }
}
