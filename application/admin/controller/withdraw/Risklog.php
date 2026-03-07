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

    public function _initialize()
    {
        parent::_initialize();
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
                $user = Db::name('user')->where('id', $row['user_id'])->field('username,nickname')->find();
                $row['username'] = $user ? $user['username'] : '';
                $row['nickname'] = $user ? $user['nickname'] : '';
                
                // 时间格式化
                $row['createtime_text'] = date('Y-m-d H:i:s', $row['createtime']);
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
