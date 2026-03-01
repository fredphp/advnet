<?php

namespace app\admin\controller\member;

use app\common\controller\Backend;
use app\common\library\CoinService;
use think\Db;
use think\Exception;

/**
 * 用户管理
 */
class User extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\User();
    }

    /**
     * 用户列表
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

            foreach ($list as &$item) {
                $account = Db::name('coin_account')
                    ->where('user_id', $item['id'])
                    ->find();
                $item['coin_balance'] = $account ? $account['balance'] : 0;
                $item['frozen_coin'] = $account ? $account['frozen'] : 0;
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 用户详情
     */
    public function detail($ids = null)
    {
        $user = $this->model->get($ids);
        if (!$user) {
            $this->error('用户不存在');
        }

        $coinAccount = Db::name('coin_account')
            ->where('user_id', $ids)
            ->find();

        $inviteStats = Db::name('user_invite_stat')
            ->where('user_id', $ids)
            ->find();

        $todayStats = Db::name('user_behavior_stat')
            ->where('user_id', $ids)
            ->where('stat_date', date('Y-m-d'))
            ->find();

        $withdrawStats = Db::name('withdraw_order')
            ->where('user_id', $ids)
            ->field('COUNT(*) as total_count, SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount')
            ->find();

        $riskInfo = Db::name('user_risk_score')
            ->where('user_id', $ids)
            ->find();

        $recentLogins = Db::name('user_behavior')
            ->where('user_id', $ids)
            ->where('behavior_type', 'login')
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();

        $devices = Db::name('device_fingerprint')
            ->where('user_id', $ids)
            ->select();

        $this->success('', [
            'user' => $user,
            'coin_account' => $coinAccount,
            'invite_stats' => $inviteStats,
            'today_stats' => $todayStats,
            'withdraw_stats' => $withdrawStats,
            'risk_info' => $riskInfo,
            'recent_logins' => $recentLogins,
            'devices' => $devices,
        ]);
    }

    /**
     * 编辑用户
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
     * 修改用户状态
     */
    public function status($ids = '')
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('未找到记录'));
        }

        $status = $this->request->post('status', 'normal');

        if (!in_array($status, ['normal', 'frozen', 'banned'])) {
            $this->error('状态值无效');
        }

        $row->status = $status;
        $row->updatetime = time();
        $row->save();

        $this->success();
    }

    /**
     * 手动充值金币
     */
    public function recharge()
    {
        $userId = $this->request->post('user_id');
        $amount = $this->request->post('amount');
        $remark = $this->request->post('remark', '后台充值');

        if (!$userId || !$amount || $amount <= 0) {
            $this->error('参数错误');
        }

        Db::startTrans();
        try {
            $coinService = new CoinService();
            $result = $coinService->addCoin($userId, $amount, 'admin_recharge', 0, $remark);

            if (!$result) {
                throw new Exception('充值失败');
            }

            Db::name('admin_coin_log')->insert([
                'admin_id' => $this->auth->id,
                'admin_name' => $this->auth->username,
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'recharge',
                'remark' => $remark,
                'createtime' => time(),
            ]);

            Db::commit();
            $this->success('充值成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 扣除金币
     */
    public function deduct()
    {
        $userId = $this->request->post('user_id');
        $amount = $this->request->post('amount');
        $remark = $this->request->post('remark', '后台扣除');

        if (!$userId || !$amount || $amount <= 0) {
            $this->error('参数错误');
        }

        Db::startTrans();
        try {
            $coinService = new CoinService();
            $result = $coinService->deductCoin($userId, $amount, 'admin_deduct', 0, $remark);

            if (!$result) {
                throw new Exception('扣除失败');
            }

            Db::name('admin_coin_log')->insert([
                'admin_id' => $this->auth->id,
                'admin_name' => $this->auth->username,
                'user_id' => $userId,
                'amount' => -$amount,
                'type' => 'deduct',
                'remark' => $remark,
                'createtime' => time(),
            ]);

            Db::commit();
            $this->success('扣除成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 用户统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        $totalStats = $this->model->field('COUNT(*) as total, 
                     SUM(CASE WHEN status = "normal" THEN 1 ELSE 0 END) as normal_count,
                     SUM(CASE WHEN status = "frozen" THEN 1 ELSE 0 END) as frozen_count,
                     SUM(CASE WHEN status = "banned" THEN 1 ELSE 0 END) as banned_count')
            ->find();

        $dailyNew = $this->model
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date, COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 尝试获取每日活跃用户，如果表不存在则返回空数组
        $dailyActive = [];
        try {
            $dailyActive = Db::name('user_behavior_stat')
                ->field('stat_date as date, COUNT(DISTINCT user_id) as count')
                ->where('stat_date', 'between', [$startDate, $endDate])
                ->group('stat_date')
                ->order('stat_date', 'asc')
                ->select();
        } catch (\Exception $e) {
            $dailyActive = [];
        }

        // 尝试获取用户来源分布，如果字段不存在则返回空数组
        $sourceDistribution = [];
        try {
            $sourceDistribution = $this->model
                ->field('source, COUNT(*) as count')
                ->group('source')
                ->select();
        } catch (\Exception $e) {
            $sourceDistribution = [];
        }

        // 尝试获取设备分布，如果表不存在则返回空数组
        $deviceDistribution = [];
        try {
            $deviceDistribution = Db::name('device_fingerprint')
                ->field('device_type, COUNT(DISTINCT user_id) as count')
                ->group('device_type')
                ->select();
        } catch (\Exception $e) {
            $deviceDistribution = [];
        }

        if ($this->request->isAjax()) {
            $this->success('', [
                'total_stats' => $totalStats,
                'daily_new' => $dailyNew,
                'daily_active' => $dailyActive,
                'source_distribution' => $sourceDistribution,
                'device_distribution' => $deviceDistribution,
            ]);
        }

        $this->view->assign('start_date', $startDate);
        $this->view->assign('end_date', $endDate);
        $this->view->assign('total_stats', $totalStats);
        $this->view->assign('daily_new', $dailyNew);
        $this->view->assign('daily_active', $dailyActive);
        $this->view->assign('source_distribution', $sourceDistribution);
        $this->view->assign('device_distribution', $deviceDistribution);
        return $this->view->fetch();
    }

    /**
     * 用户行为记录
     */
    public function behaviors($ids = null)
    {
        $userId = $ids ?: $this->request->get('user_id');

        if (!$userId) {
            $this->error('请指定用户ID');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 20);
            $type = $this->request->get('type');

            $query = Db::name('user_behavior')->where('user_id', $userId);

            if ($type) {
                $query->where('behavior_type', $type);
            }

            $total = $query->count();
            $list = $query->order('createtime', 'desc')
                ->limit($offset, $limit)
                ->select();

            return json(['total' => $total, 'rows' => $list]);
        }

        $this->view->assign('user_id', $userId);
        return $this->view->fetch();
    }

    /**
     * 导出用户
     */
    public function export()
    {
        $status = $this->request->get('status');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');

        $query = $this->model;

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->where('createtime', '>=', strtotime($startDate));
        }

        if ($endDate) {
            $query->where('createtime', '<=', strtotime($endDate . ' 23:59:59'));
        }

        $list = $query->order('createtime', 'desc')->select();

        $filename = 'users_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['用户ID', '用户名', '昵称', '手机号', '状态', '注册时间', '最后登录']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['id'],
                $row['username'],
                $row['nickname'],
                $row['mobile'],
                $row['status'],
                date('Y-m-d H:i:s', $row['createtime']),
                $row['logintime'] ? date('Y-m-d H:i:s', $row['logintime']) : '',
            ]);
        }

        fclose($output);
        exit;
    }
}
