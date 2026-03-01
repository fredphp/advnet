<?php

namespace app\admin\controller\coin;

use app\common\controller\Backend;
use think\Db;

/**
 * 金币流水管理
 */
class Log extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\CoinLog();
    }

    /**
     * 金币流水列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model->where($where)->count();
            $list = $this->model->alias('cl')
                ->join('user u', 'u.id = cl.user_id', 'LEFT')
                ->field('cl.*, u.username, u.nickname')
                ->where($where)
                ->order("cl.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 金币统计
     */
    public function statistics()
    {
        $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->request->get('end_date', date('Y-m-d'));

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // 总体统计
        $totalStats = $this->model->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->field('SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
                     SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_expense,
                     COUNT(*) as total_count')
            ->find();

        // 按类型统计
        $typeStats = $this->model->field('type, 
                     SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                     SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense,
                     COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('type')
            ->select();

        // 每日趋势
        $dailyStats = $this->model
            ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                     SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                     SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expense,
                     COUNT(*) as count')
            ->where('createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('date')
            ->order('date', 'asc')
            ->select();

        // 用户收支排行
        $topIncomeUsers = $this->model->alias('cl')
            ->join('user u', 'u.id = cl.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, SUM(CASE WHEN cl.amount > 0 THEN cl.amount ELSE 0 END) as total_income')
            ->where('cl.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('cl.user_id')
            ->order('total_income', 'desc')
            ->limit(20)
            ->select();

        $topExpenseUsers = $this->model->alias('cl')
            ->join('user u', 'u.id = cl.user_id', 'LEFT')
            ->field('u.id, u.username, u.nickname, SUM(CASE WHEN cl.amount < 0 THEN ABS(cl.amount) ELSE 0 END) as total_expense')
            ->where('cl.createtime', 'between', [$startTimestamp, $endTimestamp])
            ->group('cl.user_id')
            ->order('total_expense', 'desc')
            ->limit(20)
            ->select();

        $this->success('', [
            'total_stats' => $totalStats,
            'type_stats' => $typeStats,
            'daily_stats' => $dailyStats,
            'top_income_users' => $topIncomeUsers,
            'top_expense_users' => $topExpenseUsers,
        ]);
    }

    /**
     * 用户金币流水
     */
    public function userLog($ids = null)
    {
        $userId = $ids ?: $this->request->get('user_id');

        if (!$userId) {
            $this->error('请指定用户ID');
        }

        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 20);
            $type = $this->request->get('type');

            $query = $this->model->where('user_id', $userId);

            if ($type) {
                $query->where('type', $type);
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
     * 导出流水
     */
    public function export()
    {
        $type = $this->request->get('type');
        $userId = $this->request->get('user_id');
        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');

        $query = $this->model->alias('cl')
            ->join('user u', 'u.id = cl.user_id', 'LEFT')
            ->field('cl.*, u.username, u.nickname');

        if ($type) {
            $query->where('cl.type', $type);
        }

        if ($userId) {
            $query->where('cl.user_id', $userId);
        }

        if ($startDate) {
            $query->where('cl.createtime', '>=', strtotime($startDate));
        }

        if ($endDate) {
            $query->where('cl.createtime', '<=', strtotime($endDate . ' 23:59:59'));
        }

        $list = $query->order('cl.createtime', 'desc')->limit(10000)->select();

        $filename = 'coin_logs_' . date('YmdHis') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['流水ID', '用户ID', '用户名', '类型', '金额', '余额', '描述', '时间']);

        foreach ($list as $row) {
            fputcsv($output, [
                $row['id'],
                $row['user_id'],
                $row['username'],
                $row['type'],
                $row['amount'],
                $row['after_balance'],
                $row['description'],
                date('Y-m-d H:i:s', $row['createtime']),
            ]);
        }

        fclose($output);
        exit;
    }
}

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
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'balance');
            $order = $this->request->get('order', 'desc');

            $total = Db::name('coin_account')->count();
            $list = Db::name('coin_account ca')
                ->join('user u', 'u.id = ca.user_id', 'LEFT')
                ->field('ca.*, u.username, u.nickname, u.mobile, u.avatar')
                ->order("ca.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            return json(['total' => $total, 'rows' => $list]);
        }
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
}
