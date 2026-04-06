<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;

/**
 * 修复 red_packet_resource 表缺少的字段
 * 
 * 代码中引用但数据库中不存在的列：
 * chat_duration, chat_requirement, miniapp_duration,
 * adv_id, adv_platform, adv_duration, images, display_description
 * 
 * 用法: php think resource:migrate
 */
class ResourceMigrate extends Command
{
    protected function configure()
    {
        $this->setName('resource:migrate')
            ->setDescription('修复 red_packet_resource 表缺少的字段');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>正在检查 red_packet_resource 表结构...</info>');

        $tableName = 'red_packet_resource';
        $prefix = Db::getConfig('prefix');
        $fullTable = $prefix . $tableName;

        try {
            // 获取现有列
            $columns = Db::query("SHOW COLUMNS FROM `{$fullTable}`");
            $existingCols = array_map(function ($col) {
                return $col['Field'];
            }, $columns);

            $output->writeln('  现有字段: ' . implode(', ', $existingCols));
            $output->writeln('');

            // 需要添加的列定义
            $addColumnSqls = [
                'chat_duration'    => "ADD COLUMN `chat_duration` int unsigned NOT NULL DEFAULT 30 COMMENT '聊天时长(秒)' AFTER `video_duration`",
                'chat_requirement' => "ADD COLUMN `chat_requirement` varchar(500) NOT NULL DEFAULT '' COMMENT '聊天要求内容' AFTER `chat_duration`",
                'miniapp_duration' => "ADD COLUMN `miniapp_duration` int unsigned NOT NULL DEFAULT 0 COMMENT '小程序游戏时长(秒)' AFTER `chat_requirement`",
                'adv_id'           => "ADD COLUMN `adv_id` varchar(100) NOT NULL DEFAULT '' COMMENT '广告位ID' AFTER `miniapp_duration`",
                'adv_platform'     => "ADD COLUMN `adv_platform` varchar(50) NOT NULL DEFAULT '' COMMENT '广告平台(gromore/gdt等)' AFTER `adv_id`",
                'adv_duration'     => "ADD COLUMN `adv_duration` int unsigned NOT NULL DEFAULT 30 COMMENT '广告时长(秒)' AFTER `adv_platform`",
                'images'           => "ADD COLUMN `images` text COMMENT '图片JSON数组' AFTER `adv_duration`",
            ];

            $added = 0;
            foreach ($addColumnSqls as $colName => $sql) {
                if (in_array($colName, $existingCols)) {
                    $output->writeln("  <comment>跳过（已存在）: {$colName}</comment>");
                    continue;
                }

                try {
                    Db::execute("ALTER TABLE `{$fullTable}` {$sql}");
                    $output->writeln("  <info>已添加: {$colName}</info>");
                    $added++;
                } catch (\Exception $e) {
                    $output->writeln("  <error>失败: {$colName} - {$e->getMessage()}</error>");
                }
            }

            if ($added > 0) {
                $output->writeln('');
                $output->writeln("<info>成功添加 {$added} 个字段</info>");
            } else {
                $output->writeln('');
                $output->writeln('<info>所有字段已存在，无需修改</info>');
            }

            // 验证结果
            $newColumns = Db::query("SHOW COLUMNS FROM `{$fullTable}`");
            $newColNames = array_map(function ($col) {
                return $col['Field'];
            }, $newColumns);
            $output->writeln('');
            $output->writeln('当前字段: ' . implode(', ', $newColNames));

        } catch (\Exception $e) {
            $output->writeln('<error>迁移失败: ' . $e->getMessage() . '</error>');
            Log::error('resource:migrate 失败: ' . $e->getMessage());
        }
    }
}
