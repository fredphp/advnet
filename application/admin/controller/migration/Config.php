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
        $prefix = config('database.prefix');
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (!$params) {
                $this->error('参数不能为空');
            }
            
            $now = time();
            
            foreach ($params as $key => $value) {
                // 检查是否存在
                $exists = Db::query("SELECT id FROM {$prefix}migration_config WHERE config_key = ? LIMIT 1", [$key]);
                
                if ($exists) {
                    Db::execute("UPDATE {$prefix}migration_config SET config_value = ?, updated_at = ? WHERE config_key = ?", [$value, $now, $key]);
                } else {
                    Db::execute("INSERT INTO {$prefix}migration_config (config_key, config_value, created_at, updated_at) VALUES (?, ?, ?, ?)", [$key, $value, $now, $now]);
                }
            }
            
            $this->success('保存成功');
        }

        // 获取配置数据
        $configs = [];
        try {
            $rows = Db::query("SELECT config_key, config_value, description FROM {$prefix}migration_config");
            foreach ($rows as $row) {
                $configs[$row['config_key']] = [
                    'value' => $row['config_value'],
                    'description' => $row['description']
                ];
            }
        } catch (\Exception $e) {
            // 表不存在时忽略
        }
        
        // 定义配置项
        $configItems = [
            'migration_path' => [
                'title' => '迁移文件目录',
                'type' => 'text',
                'default' => 'sql/migrations',
                'description' => 'SQL迁移文件存放的目录路径'
            ],
            'last_batch_no' => [
                'title' => '最后执行批次号',
                'type' => 'number',
                'default' => '0',
                'description' => '记录最后执行的迁移批次号（只读）',
                'readonly' => true
            ],
            'archive_days' => [
                'title' => '默认归档天数',
                'type' => 'number',
                'default' => '90',
                'description' => '数据归档的默认天数，超过此天数的数据将被归档'
            ],
            'batch_size' => [
                'title' => '批量处理数量',
                'type' => 'number',
                'default' => '1000',
                'description' => '每次批量处理的数据条数，建议1000-5000'
            ],
            'auto_archive' => [
                'title' => '自动归档',
                'type' => 'switch',
                'default' => '0',
                'description' => '是否开启自动归档功能（需配合定时任务）'
            ],
            'delete_after_archive' => [
                'title' => '归档后删除',
                'type' => 'switch',
                'default' => '0',
                'description' => '归档后是否删除原数据（谨慎开启）'
            ],
        ];
        
        // 合并配置值
        foreach ($configItems as $key => &$item) {
            if (isset($configs[$key])) {
                $item['value'] = $configs[$key]['value'];
            } else {
                $item['value'] = $item['default'];
            }
        }
        unset($item);
        
        $this->view->assign('configItems', $configItems);
        return $this->view->fetch();
    }
}
