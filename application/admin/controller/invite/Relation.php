<?php

namespace app\admin\controller\invite;

use app\common\controller\Backend;
use app\common\model\CoinLog;
use think\Db;

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

            $total = Db::name('invite_relation')->where($where)->count();
            $list = Db::name('invite_relation')
                ->alias('ir')
                ->join('user u1', 'u1.id = ir.parent_id', 'LEFT')
                ->join('user u2', 'u2.id = ir.user_id', 'LEFT')
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

    /**
     * 详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('invite_relation')
            ->alias('ir')
            ->join('user u1', 'u1.id = ir.parent_id', 'LEFT')
            ->join('user u2', 'u2.id = ir.user_id', 'LEFT')
            ->field('ir.*, u1.username as inviter_name, u1.nickname as inviter_nickname, u1.mobile as inviter_mobile,
                     u2.username as invitee_name, u2.nickname as invitee_nickname, u2.mobile as invitee_mobile')
            ->where('ir.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        // 获取邀请人的累计邀请人数和佣金
        $inviterStats = Db::name('user_invite_stat')
            ->where('user_id', $row['parent_id'])
            ->find();

        // 获取被邀请人的消费统计（从最近6个月的分表查询）
        $startTime = strtotime('-6 months');
        $endTime = time();
        $tables = CoinLog::getTablesByRange($startTime, $endTime);
        $prefix = \think\Config::get('database.prefix');
        
        $inviteeStats = [
            'total_spend' => 0,
            'spend_count' => 0
        ];
        
        foreach ($tables as $table) {
            if (CoinLog::tableExists($table)) {
                $result = Db::name($table)
                    ->where('user_id', $row['user_id'])
                    ->where('type', 'spend')
                    ->field('SUM(amount) as total_spend, COUNT(*) as spend_count')
                    ->find();
                if ($result) {
                    $inviteeStats['total_spend'] += abs($result['total_spend'] ?? 0);
                    $inviteeStats['spend_count'] += $result['spend_count'] ?? 0;
                }
            }
        }

        $this->view->assign('row', $row);
        $this->view->assign('inviterStats', $inviterStats);
        $this->view->assign('inviteeStats', $inviteeStats);
        return $this->view->fetch();
    }
}
