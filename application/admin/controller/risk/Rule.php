<?php

namespace app\admin\controller\risk;

use app\common\controller\Backend;
use app\common\library\RiskControlService;
use think\Db;

/**
 * 风控规则管理
 */
class Rule extends Backend
{
    protected $model = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RiskRule();
    }
    
    /**
     * 规则列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 添加规则
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }
            
            $params['createtime'] = time();
            $params['updatetime'] = time();
            
            $result = $this->model->validate('RiskRule')->save($params);
            if ($result !== false) {
                // 清除规则缓存
                RiskControlService::clearRulesCache();
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        
        return $this->view->fetch();
    }
    
    /**
     * 编辑规则
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if (!$params) {
                $this->error(__('参数不能为空'));
            }
            
            $params['updatetime'] = time();
            
            $result = $row->validate('RiskRule')->save($params);
            if ($result !== false) {
                // 清除规则缓存
                RiskControlService::clearRulesCache();
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 启用/禁用规则
     */
    public function toggle($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }
        
        $row->enabled = $row->enabled ? 0 : 1;
        $row->updatetime = time();
        $row->save();
        
        // 清除规则缓存
        RiskControlService::clearRulesCache();
        
        $this->success();
    }
    
    /**
     * 批量更新阈值
     */
    public function batchUpdate()
    {
        $updates = $this->request->post('updates/a');
        
        if (!$updates || !is_array($updates)) {
            $this->error(__('参数无效'));
        }
        
        foreach ($updates as $id => $params) {
            $this->model->where('id', $id)->update([
                'threshold' => $params['threshold'] ?? 0,
                'score_weight' => $params['score_weight'] ?? 10,
                'action' => $params['action'] ?? 'warn',
                'action_duration' => $params['action_duration'] ?? 0,
                'enabled' => $params['enabled'] ?? 1,
                'updatetime' => time(),
            ]);
        }
        
        // 清除规则缓存
        RiskControlService::clearRulesCache();
        
        $this->success();
    }
    
    /**
     * 规则测试
     */
    public function test($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }
        
        $userId = $this->request->get('user_id', 0);
        $testValue = $this->request->get('test_value', 0);
        
        if (!$userId) {
            $this->error('请指定测试用户');
        }
        
        $riskService = new RiskControlService();
        $riskService->init($userId);
        
        // 构建测试上下文
        $context = [];
        switch ($row['rule_code']) {
            case 'VIDEO_WATCH_SPEED':
            case 'VIDEO_WATCH_REPEAT':
            case 'VIDEO_REWARD_SPEED':
                $context['video_id'] = 1;
                $context['watch_duration'] = $testValue;
                $context['video_duration'] = 30;
                break;
            case 'TASK_COMPLETE_SPEED':
                $context['task_id'] = 1;
                $context['task_duration'] = $testValue;
                break;
            case 'WITHDRAW_FREQUENCY':
            case 'WITHDRAW_AMOUNT_ANOMALY':
                $context['amount'] = $testValue;
                break;
            default:
                $context['test_value'] = $testValue;
        }
        
        // 执行规则检查
        $result = $riskService->check($row['rule_type'], 'test', $context);
        
        $this->success('', null, [
            'rule' => $row,
            'test_value' => $testValue,
            'threshold' => $row['threshold'],
            'passed' => $result['passed'],
            'risk_score' => $result['risk_score'],
            'violations' => $result['violations'],
        ]);
    }
}
