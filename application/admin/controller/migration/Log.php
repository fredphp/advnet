<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移日志
 */
class Log extends Backend
{
    /**
     * 日志列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $offset = $this->request->get('offset', 0);
            $limit = $this->request->get('limit', 10);
            $sort = $this->request->get('sort', 'id');
            $order = $this->request->get('order', 'desc');
            
            $prefix = config('database.prefix');
            
            // 安全处理参数
            $sort = preg_replace('/[^a-zA-Z0-9_]/', '', $sort);
            $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $offset = intval($offset);
            $limit = intval($limit);
            
            // 获取总数
            $countResult = Db::query("SELECT COUNT(*) as total FROM {$prefix}data_migration_log");
            $total = $countResult[0]['total'] ?? 0;
            
            // 获取列表
            $list = Db::query("
                SELECT * FROM {$prefix}data_migration_log 
                ORDER BY {$sort} {$order}
                LIMIT {$offset}, {$limit}
            ");
            
            // 格式化数据
            foreach ($list as &$row) {
                $row['action_text'] = $this->getActionText($row['action']);
                $row['status_text'] = isset($row['error']) && $row['error'] ? '失败' : '成功';
                $row['createtime_text'] = $row['createtime'] ? date('Y-m-d H:i:s', $row['createtime']) : '-';
            }

            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 获取操作类型文本
     */
    private function getActionText($action)
    {
        $actions = [
            'migrate' => '数据迁移',
            'clean' => '数据清理',
            'stats' => '统计计算',
            'coin_log' => '金币流水迁移',
            'watch_record' => '观看记录迁移',
            'watch_session' => '观看会话迁移',
            'risk_log' => '风控日志迁移',
            'user_behavior' => '用户行为迁移',
            'anticheat' => '防刷日志迁移',
            'red_packet' => '红包记录迁移',
            'commission' => '分佣日志迁移',
            'transfer' => '打款日志迁移',
            'inactive' => '未活跃用户标记',
        ];
        return $actions[$action] ?? $action;
    }

    /**
     * 日志详情
     */
    public function detail($ids = null)
    {
        $prefix = config('database.prefix');
        $ids = intval($ids);
        
        $row = Db::query("SELECT * FROM {$prefix}data_migration_log WHERE id = ? LIMIT 1", [$ids]);
        
        if (empty($row)) {
            $this->error(__('未找到记录'));
        }
        
        $row = $row[0];
        $row['params'] = json_decode($row['params'] ?? '', true);
        $row['result'] = json_decode($row['result'] ?? '', true);
        $row['action_text'] = $this->getActionText($row['action']);
        $row['createtime_text'] = $row['createtime'] ? date('Y-m-d H:i:s', $row['createtime']) : '-';

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
