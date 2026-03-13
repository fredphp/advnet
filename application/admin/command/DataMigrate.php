<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 数据迁移命令
 * 
 * 用法:
 * php think data:migrate --action:file --file:add_user_risk_score_fields
 * php think data:migrate --action:execute
 */
class DataMigrate extends Command
{
    protected function configure()
    {
        $this->setName('data:migrate')
            ->setDescription('数据库迁移工具')
            ->addOption('action', 'a', 'short name', 'required', false, ' '操作类型: file(生成SQL文件) 或 execute(执行迁移)')
            ->addOption('file', 'f', 'Short name', 'required', false
            '设置文件名(不含扩展名)');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getOption('action');
        $file = $input->getOption('file');
        
        $prefix = config('database.prefix');
        
        $migrations = $this->getMigrations();
        
        if ($action === 'file') {
            if (!$file) {
                $output->error('请指定文件名: --file=filename');
                return;
            }
            
            $filename = ROOT_PATH . '/sql/migrations/' . date('YmdHis') . '_' . $file . '.sql';
            
            $content = $this->generateMigrationSql($migrations);
            
            if (!is_dir(dirname($filename))) {
                mkdir(dirname($filename), 0755, true);
            }
            
            file_put_contents($filename, $content);
            $output->info("SQL文件已生成: {$filename}");
            
        } elseif ($action === 'execute') {
            foreach ($migrations as $migration) {
                $this->executeMigration($migration, $output, $prefix);
            }
            $output->info('迁移完成');
        } else {
            $output->error('未知操作: ' . $action);
            $output->info('可用操作: file, execute');
        }
    }
    
    /**
     * 获取迁移配置
     */
    protected function getMigrations()
    {
        return [
            [
                'table' => 'user_risk_score',
                'checks' => [
                    'status' => "检查status字段是否存在",
                    'ban_expire_time' => "检查ban_expire_time字段是否存在",
                    'freeze_expire_time' => "检查freeze_expire_time字段是否存在",
                    'last_violation_time' => "检查last_violation_time字段是否存在",
                    'score_history' => "检查score_history字段是否存在",
                    'global_score' => "检查global_score字段是否存在",
                    'invite_score' => "检查invite_score字段是否存在",
                ],
                'fields' => [
                    [
                        'name' => 'status',
                        'after' => 'risk_level',
                        'sql' => "ADD COLUMN `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态' AFTER `risk_level`",
                    ],
                    [
                        'name' => 'ban_expire_time',
                        'after' => 'status',
                        'sql' => "ADD COLUMN `ban_expire_time` int unsigned DEFAULT NULL COMMENT '封禁到期时间' AFTER `status`",
                    ],
                    [
                        'name' => 'freeze_expire_time',
                        'after' => 'ban_expire_time',
                        'sql' => "ADD COLUMN `freeze_expire_time` int unsigned DEFAULT NULL COMMENT '冻结到期时间' AFTER `ban_expire_time`",
                    ],
                    [
                        'name' => 'last_violation_time',
                        'after' => 'violation_count',
                        'sql' => "ADD COLUMN `last_violation_time` int unsigned DEFAULT NULL COMMENT '最后违规时间' AFTER `violation_count`",
                    ],
                    [
                        'name' => 'score_history',
                        'after' => 'last_violation_time',
                        'sql' => "ADD COLUMN `score_history` text COMMENT '评分历史JSON' AFTER `last_violation_time`",
                    ],
                    [
                        'name' => 'global_score',
                        'after' => 'redpacket_score',
                        'sql' => "ADD COLUMN `global_score` int NOT NULL DEFAULT 0 COMMENT '全局风险分' AFTER `redpacket_score`",
                    ],
                    [
                        'name' => 'invite_score',
                        'after' => 'global_score',
                        'sql' => "ADD COLUMN `invite_score` int NOT NULL DEFAULT 0 COMMENT '邀请相关风险分' AFTER `global_score`",
                    ],
                ],
            ],
        ];
    }
    
    /**
     * 生成迁移SQL文件
     */
    protected function generateMigrationSql($migrations)
    {
        $sql = "-- 数据库迁移文件\n";
        $sql .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($migrations as $migration) {
            $sql .= "\n-- 表: {$migration['table']}\n";
            $sql .= "-- 说明: 检查并添加缺失字段\n\n";
            
            foreach ($migration['checks'] as $field => $desc) {
                $sql .= "-- {$desc}\n";
            }
            
            $sql .= "\n";
            
            foreach ($migration['fields'] as $field) {
                $sql .= "ALTER TABLE `{$migration['table']}` ADD COLUMN IF NOT EXISTS `{$field['name']}` {$field['sql']};\n";
            }
        }
        
        return $sql;
    }
    
    /**
     * 执行迁移
     */
    protected function executeMigration($migration, $output, $prefix)
    {
        $table = $migration['table'];
        
        foreach ($migration['fields'] as $field) {
            $exists = Db::query("SHOW COLUMNS FROM {$prefix}{$table} LIKE '{$field['name']}'");
            
            if (empty($exists)) {
                Db::query("ALTER TABLE {$prefix}{$table} ADD COLUMN `{$field['name']}` {$field['sql']}");
                $output->info("添加字段 {$field['name']} 成功");
            } else {
                $output->info("字段 {$field['name']} 已存在，            }
        }
    }
}
