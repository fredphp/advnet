<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 数据库迁移：为user_risk_score表添加缺失字段
 */
class MigrateRiskScore extends Command
{
    protected function configure()
    {
        $this->setName('migrate:risk_score')
            ->setDescription('为user_risk_score表添加缺失的字段');
    }

    protected function execute(Input $input, Output $output)
    {
        $prefix = config('database.prefix');
        
        // 检查并添加status字段
        $statusExists = Db::query("SHOW COLUMNS FROM {$prefix}user_risk_score LIKE 'status'");
        
        if (empty($statusExists)) {
            Db::query("ALTER TABLE {$prefix}user_risk_score ADD COLUMN `status` enum('normal','frozen','banned') NOT NULL DEFAULT 'normal' COMMENT '状态' AFTER `risk_level`");
            $output->writeln('添加status字段成功');
        } else {
            $output->writeln('status字段已存在');
        }
        
        // 检查并添加ban_expire_time字段
        $banExpireExists = Db::query("SHOW COLUMNS FROM {$prefix}user_risk_score LIKE 'ban_expire_time'");
        
        if (empty($banExpireExists)) {
            Db::query("ALTER TABLE {$prefix}user_risk_score ADD COLUMN `ban_expire_time` int unsigned DEFAULT NULL COMMENT '封禁到期时间' AFTER `status`");
            $output->writeln('添加ban_expire_time字段成功');
        } else {
            $output->writeln('ban_expire_time字段已存在');
        }
        
        // 检查并添加freeze_expire_time字段
        $freezeExpireExists = Db::query("SHOW COLUMNS FROM {$prefix}user_risk_score LIKE 'freeze_expire_time'");
        
        if (empty($freezeExpireExists)) {
            Db::query("ALTER TABLE {$prefix}user_risk_score ADD COLUMN `freeze_expire_time` int unsigned DEFAULT null COMMENT '冻结到期时间' AFTER `ban_expire_time`");
            $output->writeln('添加freeze_expire_time字段成功');
        } else {
            $output->writeln('freeze_expire_time字段已存在');
        }
        
        // 检查并添加last_violation_time字段
        $lastViolationExists = Db::query("SHOW COLUMNS FROM {$prefix}user_risk_score LIKE 'last_violation_time'");
        
        if (empty($lastViolationExists)) {
            Db::query("ALTER TABLE {$prefix}user_risk_score ADD COLUMN `last_violation_time` int unsigned DEFAULT null COMMENT '最后违规时间' AFTER `violation_count`");
            $output->writeln('添加last_violation_time字段成功');
        } else {
            $output->writeln('last_violation_time字段已存在');
        }
        
        // 检查并添加score_history字段
        $scoreHistoryExists = Db::query("SHOW COLUMNS FROM {$prefix}user_risk_score LIKE 'score_history'");
        
        if (empty($scoreHistoryExists)) {
            Db::query("ALTER TABLE {$prefix}user_risk_score ADD COLUMN `score_history` text COMMENT '评分历史JSON' AFTER `last_violation_time`");
            $output->writeln('添加score_history字段成功');
        } else {
            $output->writeln('score_history字段已存在');
        }
        
        $output->writeln('迁移完成！');
    }
}
