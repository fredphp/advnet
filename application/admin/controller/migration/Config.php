<?php

namespace app\admin\controller\migration;

use app\common\controller\Backend;
use think\Db;

/**
 * 数据迁移配置
 */
class Config extends Backend
{
    /**
     * 配置页面
     */
    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            $prefix = config('database.prefix');
            $now = time();
            
            foreach ($params as $key => $value) {
                // 使用原生SQL，检查是否存在
                $exists = Db::query("SELECT id FROM {$prefix}migration_config WHERE config_key = ? LIMIT 1", [$key]);
                
                if ($exists) {
                    Db::execute("UPDATE {$prefix}migration_config SET config_value = ?, updated_at = ? WHERE config_key = ?", [$value, $now, $key]);
                } else {
                    Db::execute("INSERT INTO {$prefix}migration_config (config_key, config_value, created_at, updated_at) VALUES (?, ?, ?, ?)", [$key, $value, $now, $now]);
                }
            }
            
            $this->success();
        }

        $prefix = config('database.prefix');
        $configs = [];
        
        $rows = Db::query("SELECT config_key, config_value FROM {$prefix}migration_config");
        foreach ($rows as $row) {
            $configs[$row['config_key']] = $row['config_value'];
        }
        
        $this->view->assign('configs', $configs);
        return $this->view->fetch();
    }
}
