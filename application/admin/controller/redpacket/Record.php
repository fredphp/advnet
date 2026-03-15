<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;

/**
 * 红包领取记录
 */
class Record extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\RedPacketRecord();
    }

    /**
     * 红包记录列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('red_packet_record')->alias('rpr')->where($where)->count();
            $list = Db::name('red_packet_record')
                ->alias('rpr')
                ->join('user u', 'u.id = rpr.user_id', 'LEFT')
                ->join('red_packet_task task', 'task.id = rpr.task_id', 'LEFT')
                ->field('rpr.*, u.username, u.nickname, task.title as task_title')
                ->where($where)
                ->order("rpr.{$sort}", $order)
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
        $row = Db::name('red_packet_record')
            ->alias('rpr')
            ->join('user u', 'u.id = rpr.user_id', 'LEFT')
            ->join('red_packet_task task', 'task.id = rpr.task_id', 'LEFT')
            ->field('rpr.*, u.username, u.nickname, u.mobile, task.title as task_title, task.resource_id')
            ->where('rpr.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
