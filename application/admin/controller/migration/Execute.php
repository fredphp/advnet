<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移执行
 */
class Execute extends Backend
{
    /**
     * 执行页面
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $action = $this->request->post('action');
            $params = $this->request->post('params/a', []);
            
            try {
                $result = $this->executeMigration($action, $params);
                $this->success('执行成功', null, $result);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        // 获取可执行的迁移任务
        $tasks = [
            ['name' => 'user_data', 'title' => '用户数据迁移', 'description' => '迁移用户基础数据'],
            ['name' => 'coin_data', 'title' => '金币数据迁移', 'description' => '迁移金币账户和流水数据'],
            ['name' => 'video_data', 'title' => '视频数据迁移', 'description' => '迁移视频和观看记录数据'],
            ['name' => 'withdraw_data', 'title' => '提现数据迁移', 'description' => '迁移提现订单数据'],
            ['name' => 'invite_data', 'title' => '邀请数据迁移', 'description' => '迁移邀请关系和分佣数据'],
        ];

        $this->view->assign('tasks', $tasks);
        return $this->view->fetch();
    }

    /**
     * 执行迁移
     */
    protected function executeMigration($action, $params)
    {
        // 记录日志
        $logId = Db::name('migration_log')->insertGetId([
            'action' => $action,
            'params' => json_encode($params),
            'status' => 'running',
            'createtime' => time(),
        ]);

        try {
            // 这里执行实际的迁移逻辑
            $result = ['affected_rows' => 0];

            Db::name('migration_log')->where('id', $logId)->update([
                'status' => 'completed',
                'result' => json_encode($result),
                'updatetime' => time(),
            ]);

            return $result;
        } catch (\Exception $e) {
            Db::name('migration_log')->where('id', $logId)->update([
                'status' => 'failed',
                'result' => json_encode(['error' => $e->getMessage()]),
                'updatetime' => time(),
            ]);
            throw $e;
        }
    }
}
