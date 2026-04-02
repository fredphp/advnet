<?php

namespace app\admin\controller\signin;

use app\common\controller\Backend;
use think\Db;

/**
 * 签到配置管理
 */
class Config extends Backend
{
    /**
     * 不启用数据限制
     */
    protected $dataLimit = false;

    /**
     * 无需鉴权的方法（getRuleList是AJAX内部接口）
     */
    protected $noNeedRight = ['getRuleList'];
    
    /**
     * 签到配置首页 - 显示规则列表和配置
     */
    public function index()
    {
        // 获取签到配置
        $config = Db::name('signin_config')->find(1);
        $this->view->assign('config', $config);
        return $this->view->fetch();
    }

    /**
     * 获取奖励规则列表（AJAX接口，返回JSON）
     */
    public function getRuleList()
    {
        $list = Db::name('signin_rule')
            ->order('day', 'asc')
            ->select();

        $this->success('', null, [
            'rows'  => $list,
            'total' => count($list)
        ]);
    }
    
    /**
     * 保存签到配置
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (empty($params)) {
                $this->error('参数不能为空');
            }
            
            $data = [
                'enabled'     => isset($params['enabled']) ? intval($params['enabled']) : 1,
                'fillup_days' => isset($params['fillup_days']) ? intval($params['fillup_days']) : 3,
                'fillup_cost' => isset($params['fillup_cost']) ? intval($params['fillup_cost']) : 50,
                'updatetime'  => time(),
            ];
            
            $result = Db::name('signin_config')->where('id', 1)->update($data);
            
            if ($result !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
        $this->error('非法请求');
    }
    
    /**
     * 添加奖励规则
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (empty($params)) {
                $this->error('参数不能为空');
            }
            
            $day = intval($params['day'] ?? 0);
            $coins = intval($params['coins'] ?? 0);
            $description = trim($params['description'] ?? '');
            
            if ($day <= 0) {
                $this->error('天数必须大于0');
            }
            
            if ($coins < 0) {
                $this->error('金币不能为负数');
            }
            
            // 检查天数是否已存在
            $exists = Db::name('signin_rule')->where('day', $day)->find();
            if ($exists) {
                $this->error('第' . $day . '天的规则已存在');
            }
            
            Db::name('signin_rule')->insert([
                'day'         => $day,
                'coins'       => $coins,
                'description' => $description ?: '第' . $day . '天签到奖励',
                'createtime'  => time(),
                'updatetime'  => time(),
            ]);
            
            $this->success('添加成功');
        }
        return $this->view->fetch();
    }
    
    /**
     * 编辑奖励规则
     */
    public function edit($ids = null)
    {
        $row = Db::name('signin_rule')->where('id', $ids)->find();
        if (!$row) {
            $this->error('未找到记录');
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (empty($params)) {
                $this->error('参数不能为空');
            }
            
            $coins = intval($params['coins'] ?? 0);
            $description = trim($params['description'] ?? '');
            
            if ($coins < 0) {
                $this->error('金币不能为负数');
            }
            
            Db::name('signin_rule')->where('id', $ids)->update([
                'coins'       => $coins,
                'description' => $description ?: $row['description'],
                'updatetime'  => time(),
            ]);
            
            $this->success('修改成功');
        }
        
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
    
    /**
     * 删除奖励规则
     */
    public function del($ids = null)
    {
        if (!$ids) {
            $this->error('缺少参数');
        }
        
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        
        Db::name('signin_rule')->where('id', 'in', $ids)->delete();
        
        $this->success('删除成功');
    }
}
