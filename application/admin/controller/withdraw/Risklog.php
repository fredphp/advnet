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

    // 原始处理动作映射
    protected $originalActionList = [
        'pass' => '通过',
        'review' => '人工审核',
        'reject' => '拒绝',
        'freeze' => '冻结',
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
            // 排序参数
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            
            // 筛选参数
            $filter = json_decode($this->request->get('filter', '{}'), true);
            $op = json_decode($this->request->get('op', '{}'), true);

            // 构建查询
            $query = Db::name('withdraw_risk_log');

            // 处理筛选条件
            foreach ($filter as $field => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }
                
                $fieldOp = $op[$field] ?? '=';
                
                switch ($field) {
                    case 'username':
                        // 关联用户表查询
                        $userIds = Db::name('user')->where('username', 'like', "%{$value}%")->column('id');
                        if ($userIds) {
                            $query->where('user_id', 'in', $userIds);
                        } else {
                            $query->where('user_id', 0); // 无结果
                        }
                        break;
                    case 'createtime':
                        if ($fieldOp == 'RANGE' && strpos($value, ' - ') !== false) {
                            list($start, $end) = explode(' - ', $value);
                            $query->where('createtime', 'between', [strtotime($start), strtotime($end . ' 23:59:59')]);
                        }
                        break;
                    case 'order_no':
                    case 'ip':
                        $query->where($field, 'like', "%{$value}%");
                        break;
                    default:
                        $query->where($field, '=', $value);
                        break;
                }
            }

            // 查询总数
            $total = $query->count();

            // 查询列表
            $list = $query->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 格式化数据
            foreach ($list as &$row) {
                // 获取用户信息
                $userId = isset($row['user_id']) ? $row['user_id'] : 0;
                $user = $userId ? Db::name('user')->where('id', $userId)->field('username,nickname')->find() : null;
                $row['username'] = $user ? $user['username'] : '';
                $row['nickname'] = $user ? $user['nickname'] : '';
                
                // 时间格式化
                $row['createtime_text'] = isset($row['createtime']) ? date('Y-m-d H:i:s', $row['createtime']) : '';
                
                // 风险类型中文
                $riskType = isset($row['risk_type']) ? $row['risk_type'] : '';
                $row['risk_type_text'] = isset($this->riskTypeList[$riskType]) ? $this->riskTypeList[$riskType] : $riskType;
                
                // 处理动作中文 (使用 handle_action 字段)
                $handleAction = isset($row['handle_action']) ? $row['handle_action'] : '';
                $row['handle_action_text'] = isset($this->originalActionList[$handleAction]) ? $this->originalActionList[$handleAction] : ($handleAction ?: '待处理');
                
                // 风险等级中文
                $riskLevel = isset($row['risk_level']) ? $row['risk_level'] : 0;
                $row['risk_level_text'] = isset($this->riskLevelList[$riskLevel]) ? $this->riskLevelList[$riskLevel] : '普通';
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
            $this->error('记录不存在');
        }

        // 获取用户信息
        $user = Db::name('user')->where('id', $row['user_id'])->find();
        
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
                $this->error('参数错误');
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->where('handle_action', '')
                ->update([
                    'handle_action' => 'pass',
                    'handle_remark' => '管理员通过',
                ]);
            
            $this->success("成功处理{$count}条记录");
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 人工审核（标记为需要人工审核）
     */
    public function review($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            if (empty($ids)) {
                $this->error('参数错误');
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->where('handle_action', '')
                ->update([
                    'handle_action' => 'review',
                    'handle_remark' => '标记为人工审核',
                ]);
            
            $this->success("成功标记{$count}条记录为人工审核");
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 拒绝（标记为已拒绝）
     */
    public function reject($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            $reason = $this->request->post('reason', '');
            if (empty($ids)) {
                $this->error('参数错误');
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->where('handle_action', '')
                ->update([
                    'handle_action' => 'reject',
                    'handle_remark' => $reason,
                ]);
            
            $this->success("成功拒绝{$count}条记录");
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 冻结用户
     */
    public function freeze($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            $reason = $this->request->post('reason', '');
            if (empty($ids)) {
                $this->error('参数错误');
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            // 获取记录中的用户ID
            $records = Db::name('withdraw_risk_log')
                ->where('id', 'in', $ids)
                ->column('user_id', 'id');
            
            $userIds = array_unique(array_values($records));
            
            Db::startTrans();
            try {
                // 更新风控记录状态
                Db::name('withdraw_risk_log')
                    ->where('id', 'in', $ids)
                    ->update([
                        'handle_action' => 'freeze',
                        'handle_remark' => $reason,
                    ]);
                
                // 冻结用户
                if (!empty($userIds)) {
                    Db::name('user')->where('id', 'in', $userIds)->update([
                        'status' => 'freeze',
                        'updatetime' => time(),
                    ]);
                }
                
                Db::commit();
                $this->success("成功冻结" . count($userIds) . "个用户");
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 批量处理
     */
    public function multi($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            $action = $this->request->post('action');
            
            if (empty($ids) || empty($action)) {
                $this->error('参数错误');
            }

            switch ($action) {
                case 'pass':
                    return $this->pass($ids);
                case 'review':
                    return $this->review($ids);
                case 'reject':
                    return $this->reject($ids);
                case 'freeze':
                    return $this->freeze($ids);
                case 'del':
                    return $this->del($ids);
                default:
                    $this->error('未知操作');
            }
        }
        
        $this->error('请求方式错误');
    }

    /**
     * 删除
     */
    public function del($ids = null)
    {
        if ($this->request->isPost()) {
            $ids = $ids ? $ids : $this->request->post('ids');
            if (empty($ids)) {
                $this->error('参数错误');
            }

            $ids = is_array($ids) ? $ids : explode(',', $ids);
            
            $count = Db::name('withdraw_risk_log')->where('id', 'in', $ids)->delete();
            
            $this->success("成功删除{$count}条记录");
        }
        
        $this->error('请求方式错误');
    }
}
