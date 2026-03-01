<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use think\Db;

/**
 * 邀请统计管理
 */
class Statistic extends Backend
{
    /**
     * 邀请统计概览
     */
    public function index()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 总体统计
        $totalStats = Db::name('invite_relation')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_invites, COUNT(DISTINCT inviter_id) as unique_inviters')
            ->find();

        // 每日邀请趋势
        $dailyStats = Db::name('invite_relation')
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 邀请排行榜
        $topInviters = Db::name('user_invite_stat uis')
            ->join('user u', 'u.id = uis.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, uis.total_invite_count, uis.total_commission')
            ->order('uis.total_invite_count', 'desc')
            ->limit(20)
            ->select();

        // 分佣统计
        $commissionStats = Db::name('invite_commission_log')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('SUM(commission_amount) as total_commission, COUNT(*) as total_count')
            ->find();

        // 分佣来源分布
        $commissionBySource = Db::name('invite_commission_log')
            ->field('source, COUNT(*) as count, SUM(commission_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('source')
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'daily_stats' => $dailyStats,
            'top_inviters' => $topInviters,
            'commission_stats' => $commissionStats,
            'commission_by_source' => $commissionBySource,
        ]);
    }
}

/**
 * 邀请关系管理
 */
class Relation extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InviteRelation();
    }

    /**
     * 邀请关系列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('ir')
                ->join('user u1', 'u1.id = ir.inviter_id', 'LEFT')
                ->join('user u2', 'u2.id = ir.invitee_id', 'LEFT')
                ->field('ir.*, u1.username as inviter_name, u1.nickname as inviter_nickname,
                         u2.username as invitee_name, u2.nickname as invitee_nickname')
                ->where($where)
                ->order("ir.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}

/**
 * 分佣记录
 */
class Commission extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InviteCommissionLog();
    }

    /**
     * 分佣记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('icl')
                ->join('user u', 'u.id = icl.user_id', 'LEFT')
                ->field('icl.*, u.username, u.nickname')
                ->where($where)
                ->order("icl.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 分佣统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 总体统计
        $totalStats = $this->model->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('COUNT(*) as total_count, SUM(commission_amount) as total_amount,
                     AVG(commission_amount) as avg_amount')
            ->find();

        // 按来源统计
        $sourceStats = $this->model->field('source, COUNT(*) as count, SUM(commission_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('source')
            ->select();

        // 每日趋势
        $dailyStats = $this->model
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     COUNT(*) as count, SUM(commission_amount) as amount')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 用户分佣排行
        $topUsers = $this->model->alias('icl')
            ->join('user u', 'u.id = icl.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, COUNT(*) as commission_count, SUM(icl.commission_amount) as total_amount')
            ->where('icl.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('icl.user_id')
            ->order('total_amount', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'source_stats' => $sourceStats,
            'daily_stats' => $dailyStats,
            'top_users' => $topUsers,
        ]);
    }
}

/**
 * 分佣配置
 */
class Config extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\InviteCommissionConfig();
    }

    /**
     * 配置列表
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
     * 添加配置
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

            $result = $this->model->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($this->model->getError());
            }
        }
        return $this->view->fetch();
    }

    /**
     * 编辑配置
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
            $result = $row->allowField(true)->save($params);
            if ($result !== false) {
                $this->success();
            } else {
                $this->error($row->getError());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 启用/禁用
     */
    public function toggle($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $row->enabled = $row->enabled == 1 ? 0 : 1;
        $row->updatetime = time();
        $row->save();

        $this->success();
    }
}
