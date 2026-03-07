<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use think\Db;

/**
 * 提现风控记录
 */
class Risklog extends Backend
{
    protected $model = null;
    protected $searchFields = 'id,user_id,order_no,ip';
    protected $relationSearch = false;

    // 风险类型映射
    protected $riskTypeList = [
        'video' => '视频',
        'task' => '任务',
        'withdraw' => '提现',
        'redpacket' => '红包',
        'invite' => '邀请',
        'global' => '全局',
    ];

    // 处理动作映射
    protected $actionList = [
        '' => '待处理',
        'pass' => '通过',
        'review' => '人工审核',
        'reject' => '拒绝',
        'freeze' => '冻结',
    ];

    // 风险等级映射
    protected $riskLevelList = [
        0 => '普通',
        1 => '低风险',
        2 => '中风险',
        3 => '高风险',
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->view->assign('riskTypeList', $this->riskTypeList);
        $this->view->assign('actionList', $this->actionList);
        $this->view->assign('riskLevelList', $this->riskLevelList);
    }

    /**
     * 风控记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('withdraw_risk_log')->where($where)->count();
            
            $list = Db::name('withdraw_risk_log')
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 格式化数据
            foreach ($list as &$row) {
                // 获取用户信息
                $userId = isset($row['user_id']) ? $row['user_id'] : 0;
                $user = $userId ? Db::name('user')->where('id', $userId)->field('username,nickname')->find() : null;
                $row['username'] = $user ? $user['username'] : '-';
                $row['nickname'] = $user ? $user['nickname'] : '-';
            }

            return json(['total' => $total, 'rows' => $list]);
        }

        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('withdraw_risk_log')->where('id', $ids)->find();
        if (!$row) {
            $this->error(__('记录不存在'));
        }

        // 获取用户信息
        $user = null;
        if (!empty($row['user_id'])) {
            $user = Db::name('user')->where('id', $row['user_id'])->find();
        }
        
        // 获取订单信息（如果有）
        $order = null;
        if (!empty($row['order_no'])) {
            $order = Db::name('withdraw_order')->where('order_no', $row['order_no'])->find();
        }

        $this->view->assign('row', $row);
        $this->view->assign('user', $user);
        $this->view->assign('order', $order);
        
        return $this->view->fetch();
    }

    /**
     * 通过（标记为已通过）
     */
    public function pass($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            if (empty($ids)) {
                $this->error(__('参数错误'));
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->update([
                    'handle_action' => 'pass',
                    'handle_remark' => '管理员通过',
                ]);
            
            $this->success(__('成功处理%d条记录', $count));
        }
        
        $this->error(__('请求方式错误'));
    }

    /**
     * 人工审核
     */
    public function review($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            if (empty($ids)) {
                $this->error(__('参数错误'));
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->update([
                    'handle_action' => 'review',
                    'handle_remark' => '标记为人工审核',
                ]);
            
            $this->success(__('成功标记%d条记录为人工审核', $count));
        }
        
        $this->error(__('请求方式错误'));
    }

    /**
     * 拒绝
     */
    public function reject($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            $reason = $this->request->post('reason', $this->request->post('handle_remark', ''));
            if (empty($ids)) {
                $this->error(__('参数错误'));
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->update([
                    'handle_action' => 'reject',
                    'handle_remark' => $reason ?: '管理员拒绝',
                ]);
            
            $this->success(__('成功拒绝%d条记录', $count));
        }
        
        $this->error(__('请求方式错误'));
    }

    /**
     * 冻结用户
     */
    public function freeze($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            $reason = $this->request->post('reason', $this->request->post('handle_remark', ''));
            if (empty($ids)) {
                $this->error(__('参数错误'));
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            // 获取记录中的用户ID
            $userIds = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->column('user_id');
            
            $userIds = array_unique(array_filter($userIds));
            
            Db::startTrans();
            try {
                // 更新风控记录状态
                Db::name('withdraw_risk_log')
                    ->where('id', 'in', $ids)
                    ->update([
                        'handle_action' => 'freeze',
                        'handle_remark' => $reason ?: '风控冻结',
                    ]);
                
                // 冻结用户
                if (!empty($userIds)) {
                    Db::name('user')->where('id', 'in', $userIds)->update([
                        'status' => 'freeze',
                        'updatetime' => time(),
                    ]);
                }
                
                Db::commit();
                $this->success(__('成功冻结%d个用户', count($userIds)));
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        $this->error(__('请求方式错误'));
    }

    /**
     * 删除
     */
    public function del($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            if (empty($ids)) {
                $this->error(__('参数错误'));
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')->where('id', 'in', $ids)->delete();
            
            $this->success(__('成功删除%d条记录', $count));
        }
        
        $this->error(__('请求方式错误'));
    }
}
