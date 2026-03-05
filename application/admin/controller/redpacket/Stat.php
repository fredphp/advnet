<?php

namespace app\admin\controller\redpacket;

use app\common\controller\Backend;
use think\Db;
use think\Exception;

/**
 * 数据统计
 */
class Stat extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 统计概览
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $this->request->get('end_date', date('Y-m-d'));

            // 初始化默认值
            $taskStats = [
                'total' => 0,
                'total_amount' => 0,
                'receive_amount' => 0,
                'complete_count' => 0
            ];
            $participationStats = [
                'total' => 0,
                'pending' => 0,
                'wait_audit' => 0,
                'pass' => 0,
                'rewarded' => 0,
                'rejected' => 0
            ];
            $dailyStats = [];
            $typeStats = [];

            try {
                // 任务统计 - 检查表是否存在及字段
                if ($this->tableExists('red_packet_task')) {
                    $fields = $this->getTableFields('red_packet_task');
                    $taskQuery = Db::name('red_packet_task');

                    // 构建查询字段
                    $selectFields = ['COUNT(*) as total'];
                    $selectFields[] = 'SUM(total_amount) as total_amount';

                    // 检查 receive_amount 字段是否存在
                    if (in_array('receive_amount', $fields)) {
                        $selectFields[] = 'SUM(receive_amount) as receive_amount';
                    } else {
                        $selectFields[] = '0 as receive_amount';
                    }

                    // 检查 complete_count 字段是否存在
                    if (in_array('complete_count', $fields)) {
                        $selectFields[] = 'SUM(complete_count) as complete_count';
                    } else {
                        $selectFields[] = '0 as complete_count';
                    }

                    $result = $taskQuery->field(implode(', ', $selectFields))->find();
                    if ($result) {
                        $taskStats = array_merge($taskStats, $result);
                    }
                }

                // 参与统计
                if ($this->tableExists('task_participation')) {
                    $result = Db::name('task_participation')
                        ->field('COUNT(*) as total,
                            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as wait_audit,
                            SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as pass,
                            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as rewarded,
                            SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as rejected')
                        ->find();
                    if ($result) {
                        $participationStats = array_merge($participationStats, $result);
                    }
                }

                // 每日统计
                if ($this->tableExists('task_participation')) {
                    $dailyStats = Db::name('task_participation')
                        ->whereTime('createtime', 'between', [strtotime($startDate), strtotime($endDate . ' 23:59:59')])
                        ->field('FROM_UNIXTIME(createtime, "%Y-%m-%d") as date,
                            COUNT(*) as count,
                            SUM(reward_coin) as reward_coin')
                        ->group('date')
                        ->order('date', 'asc')
                        ->select();
                    $dailyStats = $dailyStats ?: [];
                }

                // 任务类型分布
                if ($this->tableExists('red_packet_task')) {
                    $fields = $this->getTableFields('red_packet_task');
                    $typeQuery = Db::name('red_packet_task');

                    // 检查 type 字段是否存在
                    if (in_array('type', $fields)) {
                        $typeSelectFields = ['type', 'COUNT(*) as count'];

                        // 检查 receive_amount 字段是否存在
                        if (in_array('receive_amount', $fields)) {
                            $typeSelectFields[] = 'SUM(receive_amount) as amount';
                        } else {
                            $typeSelectFields[] = '0 as amount';
                        }

                        $typeStats = $typeQuery->field(implode(', ', $typeSelectFields))
                            ->group('type')
                            ->select();
                        $typeStats = $typeStats ?: [];
                    }
                }

            } catch (Exception $e) {
                // 记录错误日志
                \think\Log::error('统计查询错误: ' . $e->getMessage());
            }

            $this->success('', null, [
                'task' => $taskStats,
                'participation' => $participationStats,
                'daily' => $dailyStats,
                'type' => $typeStats
            ]);
        }

        return $this->view->fetch();
    }

    /**
     * 检查表是否存在
     */
    protected function tableExists($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTable = $prefix . $tableName;
        $exists = Db::query("SHOW TABLES LIKE '{$fullTable}'");
        return !empty($exists);
    }

    /**
     * 获取表字段列表
     */
    protected function getTableFields($tableName)
    {
        $prefix = \think\Config::get('database.prefix');
        $fullTable = $prefix . $tableName;
        $fields = Db::query("SHOW COLUMNS FROM `{$fullTable}`");
        return array_column($fields, 'Field');
    }
}
