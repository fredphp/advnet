<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移统计
 */
class Stats extends Backend
{
    /**
     * 统计页面
     */
    public function index()
    {
        // 统计数据
        $stats = [
            'total_migrations' => Db::name('migration_log')->count(),
            'success_count' => Db::name('migration_log')->where('status', 'completed')->count(),
            'failed_count' => Db::name('migration_log')->where('status', 'failed')->count(),
            'running_count' => Db::name('migration_log')->where('status', 'running')->count(),
        ];

        // 按类型统计
        $byAction = Db::name('migration_log')
            ->field('action, COUNT(*) as count, 
                     SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as success,
                     SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->group('action')
            ->select();

        // 最近执行记录
        $recentLogs = Db::name('migration_log')
            ->order('createtime', 'desc')
            ->limit(10)
            ->select();

        $this->view->assign('stats', $stats);
        $this->view->assign('by_action', $byAction);
        $this->view->assign('recent_logs', $recentLogs);
        return $this->view->fetch();
    }
}
