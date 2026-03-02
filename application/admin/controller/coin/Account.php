<?php

namespace app\admin\controller\coin;

use app\common\controller\Backend;
use think\Db;

/**
 * 金币账户管理
 */
class Account extends Backend
{
    /**
     * 账户列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = Db::name('coin_account')->where($where)->count();
            $list = Db::name('coin_account')
                ->alias('ca')
                ->join('user u', 'u.id = ca.user_id', 'LEFT')
                ->field('ca.*, u.username, u.nickname, u.mobile, u.avatar')
                ->where($where)
                ->order("ca.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 账户详情
     */
    public function detail($ids = null)
    {
        $row = Db::name('coin_account')
            ->alias('ca')
            ->join('user u', 'u.id = ca.user_id', 'LEFT')
            ->field('ca.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('ca.id', $ids)
            ->find();

        if (!$row) {
            $this->error(__('未找到记录'));
        }

        // 获取最近流水
        $recentLogs = Db::name('coin_log')
            ->where('user_id', $row['user_id'])
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();

        $this->view->assign('row', $row);
        $this->view->assign('recent_logs', $recentLogs);
        return $this->view->fetch();
    }

    /**
     * 账户统计
     */
    public function summary()
    {
        $stats = Db::name('coin_account')
            ->field('COUNT(*) as total_accounts,
                     SUM(balance) as total_balance,
                     SUM(frozen) as total_frozen,
                     AVG(balance) as avg_balance,
                     MAX(balance) as max_balance')
            ->find();

        // 余额分布
        $distribution = [
            '0' => Db::name('coin_account')->where('balance', 0)->count(),
            '1-1000' => Db::name('coin_account')->where('balance', 'between', [1, 1000])->count(),
            '1001-5000' => Db::name('coin_account')->where('balance', 'between', [1001, 5000])->count(),
            '5001-10000' => Db::name('coin_account')->where('balance', 'between', [5001, 10000])->count(),
            '10000+' => Db::name('coin_account')->where('balance', '>', 10000)->count(),
        ];

        $this->success('', [
            'stats' => $stats,
            'distribution' => $distribution,
        ]);
    }

    /**
     * 调整余额
     */
    public function adjust($ids = null)
    {
        $row = Db::name('coin_account')->where('id', $ids)->find();
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        if ($this->request->isPost()) {
            $amount = $this->request->post('amount', 0);
            $type = $this->request->post('type', 'add');
            $remark = $this->request->post('remark', '');

            if ($amount <= 0) {
                $this->error('金额必须大于0');
            }

            $adjustAmount = $type == 'add' ? $amount : -$amount;

            if ($type == 'deduct' && $row['balance'] < $amount) {
                $this->error('余额不足');
            }

            Db::startTrans();
            try {
                // 更新账户余额
                Db::name('coin_account')
                    ->where('id', $ids)
                    ->update([
                        'balance' => Db::raw('balance + ' . $adjustAmount),
                        'updatetime' => time(),
                    ]);

                // 记录流水
                Db::name('coin_log')->insert([
                    'user_id' => $row['user_id'],
                    'type' => 'admin_adjust',
                    'amount' => $adjustAmount,
                    'after_balance' => $row['balance'] + $adjustAmount,
                    'description' => '管理员调整: ' . $remark,
                    'admin_id' => $this->auth->id,
                    'createtime' => time(),
                ]);

                Db::commit();
                $this->success();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
