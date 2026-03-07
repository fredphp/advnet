<?php

namespace app\admin\controller\withdraw;

use app\common\controller\Backend;
use think\Db;

/**
 * 提现风控记录管理
 */
class Risklog extends Backend
{
    protected $model = null;

    // 表名
    protected $tableName = 'withdraw_risk_log';

    // 风险类型映射
    protected $riskTypeMap = [
        'ip_check' => 'IP检测',
        'device_check' => '设备检测',
        'frequency_check' => '频率检测',
        'amount_check' => '金额检测',
        'account_check' => '账号检测',
        'risk_check' => '风险检测',
        'score_check' => '评分检测',
        'video_watch_speed' => '视频观看速度',
        'video_watch_repeat' => '视频重复观看',
        'video_daily_limit' => '视频每日限额',
        'video_reward_speed' => '视频奖励速度',
        'video_skip_ratio' => '视频跳过比例',
        'task_complete_speed' => '任务完成速度',
        'task_daily_limit' => '任务每日限额',
        'task_repeat_submit' => '任务重复提交',
        'task_fake_behavior' => '任务虚假行为',
        'withdraw_frequency' => '提现频率',
        'withdraw_amount_anomaly' => '提现金额异常',
        'withdraw_new_account' => '新账号提现',
        'redpacket_grab_speed' => '红包抢夺速度',
        'redpacket_daily_limit' => '红包每日限额',
        'invite_speed' => '邀请速度',
        'invite_fake_account' => '邀请虚假账号',
        'ip_multi_account' => 'IP多账号',
        'device_multi_account' => '设备多账号',
        'behavior_pattern' => '行为模式',
        // 旧类型兼容
        'video' => '视频',
        'task' => '任务',
        'withdraw' => '提现',
        'redpacket' => '红包',
        'invite' => '邀请',
        'global' => '全局',
    ];

    // 风险等级映射
    protected $riskLevelMap = [
        0 => '普通',
        1 => '低风险',
        2 => '中风险',
        3 => '高风险'
    ];

    // 处理动作映射
    protected $handleActionMap = [
        'pass' => '通过',
        'review' => '人工审核',
        'reject' => '拒绝',
        'freeze' => '冻结'
    ];

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
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $prefix = \think\Config::get('database.prefix');

            $list = Db::name($this->tableName)
                ->alias('rl')
                ->join($prefix . 'user u', 'u.id = rl.user_id', 'LEFT')
                ->field('rl.*, u.username, u.nickname')
                ->where($where)
                ->order("rl.{$sort}", $order)
                ->limit($offset, $limit)
                ->select();

            $total = Db::name($this->tableName)
                ->alias('rl')
                ->join($prefix . 'user u', 'u.id = rl.user_id', 'LEFT')
                ->where($where)
                ->count();

            // 格式化数据
            foreach ($list as &$row) {
                $row['risk_type_text'] = $this->riskTypeMap[$row['risk_type']] ?? $row['risk_type'];
                $row['risk_level_text'] = $this->riskLevelMap[$row['risk_level']] ?? '未知';
                $row['handle_action_text'] = $this->handleActionMap[$row['handle_action']] ?? $row['handle_action'];
            }

            return json(['total' => $total, 'rows' => $list]);
        }
        return $this->view->fetch();
    }

    /**
     * 风控记录详情
     */
    public function detail($ids = null)
    {
        $prefix = \think\Config::get('database.prefix');

        $row = Db::name($this->tableName)
            ->alias('rl')
            ->join($prefix . 'user u', 'u.id = rl.user_id', 'LEFT')
            ->field('rl.*, u.username, u.nickname, u.mobile, u.avatar')
            ->where('rl.id', $ids)
            ->find();

        if (!$row) {
            $this->error('记录不存在');
        }

        // 获取用户风险评分
        $riskScore = Db::name('user_risk_score')
            ->where('user_id', $row['user_id'])
            ->find();

        // 获取用户最近的风控记录
        $recentLogs = Db::name($this->tableName)
            ->alias('rl')
            ->join($prefix . 'user u', 'u.id = rl.user_id', 'LEFT')
            ->field('rl.id, rl.user_id, rl.order_no, rl.risk_type, rl.risk_level, rl.risk_score, rl.handle_action, rl.createtime, u.username')
            ->where('rl.user_id', $row['user_id'])
            ->where('rl.id', '<>', $ids)
            ->order('rl.createtime', 'desc')
            ->limit(10)
            ->select();

        // 格式化数据
        $row['risk_type_text'] = $this->riskTypeMap[$row['risk_type']] ?? $row['risk_type'];
        $row['risk_level_text'] = $this->riskLevelMap[$row['risk_level']] ?? '未知';
        $row['handle_action_text'] = $this->handleActionMap[$row['handle_action']] ?? $row['handle_action'];

        foreach ($recentLogs as &$log) {
            $log['risk_type_text'] = $this->riskTypeMap[$log['risk_type']] ?? $log['risk_type'];
            $log['risk_level_text'] = $this->riskLevelMap[$log['risk_level']] ?? '未知';
            $log['handle_action_text'] = $this->handleActionMap[$log['handle_action']] ?? $log['handle_action'];
        }

        if ($this->request->isAjax()) {
            $this->success('', [
                'row' => $row,
                'risk_score' => $riskScore,
                'recent_logs' => $recentLogs
            ]);
        }

        $this->view->assign('row', $row);
        $this->view->assign('risk_score', $riskScore);
        $this->view->assign('recent_logs', $recentLogs);
        return $this->view->fetch();
    }

    /**
     * 通过
     */
    public function pass($ids = null)
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $row = Db::name($this->tableName)->where('id', $ids)->find();
        if (!$row) {
            $this->error('记录不存在');
        }

        $result = Db::name($this->tableName)->where('id', $ids)->update([
            'handle_action' => 'pass',
            'handle_remark' => '管理员通过',
            'handle_time' => time()
        ]);

        if ($result !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 人工审核
     */
    public function review($ids = null)
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $row = Db::name($this->tableName)->where('id', $ids)->find();
        if (!$row) {
            $this->error('记录不存在');
        }

        $result = Db::name($this->tableName)->where('id', $ids)->update([
            'handle_action' => 'review',
            'handle_remark' => '需人工审核',
            'handle_time' => time()
        ]);

        if ($result !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 拒绝
     */
    public function reject($ids = null)
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $row = Db::name($this->tableName)->where('id', $ids)->find();
        if (!$row) {
            $this->error('记录不存在');
        }

        $result = Db::name($this->tableName)->where('id', $ids)->update([
            'handle_action' => 'reject',
            'handle_remark' => '管理员拒绝',
            'handle_time' => time()
        ]);

        if ($result !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 冻结用户
     */
    public function freeze($ids = null)
    {
        $ids = $ids ? $ids : $this->request->param('ids');
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }

        $row = Db::name($this->tableName)->where('id', $ids)->find();
        if (!$row) {
            $this->error('记录不存在');
        }

        Db::startTrans();
        try {
            // 更新风控记录状态
            Db::name($this->tableName)->where('id', $ids)->update([
                'handle_action' => 'freeze',
                'handle_remark' => '冻结用户',
                'handle_time' => time()
            ]);

            // 冻结用户风险评分
            $riskScore = Db::name('user_risk_score')->where('user_id', $row['user_id'])->find();
            if ($riskScore) {
                Db::name('user_risk_score')
                    ->where('user_id', $row['user_id'])
                    ->update(['status' => 'frozen', 'updatetime' => time()]);
            } else {
                Db::name('user_risk_score')->insert([
                    'user_id' => $row['user_id'],
                    'total_score' => 0,
                    'risk_level' => 'high',
                    'status' => 'frozen',
                    'createtime' => time(),
                    'updatetime' => time()
                ]);
            }

            Db::commit();
            $this->success('操作成功，用户已冻结');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 批量操作
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }
        $ids = is_array($ids) ? $ids : explode(',', $ids);

        $action = $this->request->post('action');
        if (!in_array($action, ['pass', 'review', 'reject', 'freeze', 'del'])) {
            $this->error('无效的操作');
        }

        $actionText = [
            'pass' => '通过',
            'review' => '人工审核',
            'reject' => '拒绝',
            'freeze' => '冻结',
            'del' => '删除'
        ];

        $count = 0;
        foreach ($ids as $id) {
            if ($action == 'del') {
                $result = Db::name($this->tableName)->where('id', $id)->delete();
            } else {
                $result = Db::name($this->tableName)->where('id', $id)->update([
                    'handle_action' => $action,
                    'handle_remark' => '批量' . $actionText[$action],
                    'handle_time' => time()
                ]);
            }
            if ($result !== false) {
                $count++;
            }
        }

        $this->success("成功{$actionText[$action]}{$count}条记录");
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if (empty($ids)) {
            $this->error(__('参数错误'));
        }
        $ids = is_array($ids) ? $ids : explode(',', $ids);

        $count = Db::name($this->tableName)->whereIn('id', $ids)->delete();
        $this->success("成功删除{$count}条记录");
    }
}
