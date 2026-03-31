<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Log;

/**
 * 安装自动推送配置项
 * 
 * 在 advn_config 表中插入 AutoPush 相关配置
 * 用法: php think autopush:install
 */
class InstallAutoPush extends Command
{
    protected function configure()
    {
        $this->setName('autopush:install')
            ->setDescription('安装自动推送服务配置项');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>正在安装 AutoPush 配置...</info>');

        $configs = [
            [
                'name'    => 'auto_push_enabled',
                'group'   => 'redpacket',
                'title'   => '自动推送普通消息',
                'tip'     => '开启后自动推送普通聊天/下载App/广告时长消息到前端页面',
                'type'    => 'switch',
                'value'   => '1',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
            [
                'name'    => 'auto_redpacket_enabled',
                'group'   => 'redpacket',
                'title'   => '自动推送红包任务',
                'tip'     => '开启后每隔4-10秒自动推送一个红包(小程序游戏)任务，并记录在红包任务数据库中',
                'type'    => 'switch',
                'value'   => '0',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
            [
                'name'    => 'auto_push_chat_min',
                'group'   => 'redpacket',
                'title'   => '聊天消息最小间隔(秒)',
                'tip'     => '每条普通消息之间的最小间隔秒数',
                'type'    => 'number',
                'value'   => '2',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
            [
                'name'    => 'auto_push_chat_max',
                'group'   => 'redpacket',
                'title'   => '聊天消息最大间隔(秒)',
                'tip'     => '每条普通消息之间的最大间隔秒数',
                'type'    => 'number',
                'value'   => '5',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
            [
                'name'    => 'auto_redpacket_min',
                'group'   => 'redpacket',
                'title'   => '红包最小间隔(秒)',
                'tip'     => '每个红包之间的最小间隔秒数',
                'type'    => 'number',
                'value'   => '4',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
            [
                'name'    => 'auto_redpacket_max',
                'group'   => 'redpacket',
                'title'   => '红包最大间隔(秒)',
                'tip'     => '每个红包之间的最大间隔秒数',
                'type'    => 'number',
                'value'   => '10',
                'content' => '',
                'rule'    => '',
                'extend'  => '',
            ],
        ];

        foreach ($configs as $config) {
            try {
                // 检查是否已存在
                $exists = Db::name('config')
                    ->where('name', $config['name'])
                    ->count();

                if ($exists > 0) {
                    $output->writeln('  <comment>跳过（已存在）: ' . $config['name'] . '</comment>');
                    continue;
                }

                Db::name('config')->insert(array_merge($config, [
                    'createtime' => time(),
                    'updatetime' => time(),
                ]));

                $output->writeln('  <info>已创建: ' . $config['name'] . ' (' . $config['title'] . ')</info>');
            } catch (\Exception $e) {
                $output->writeln('  <error>失败: ' . $config['name'] . ' - ' . $e->getMessage() . '</error>');
            }
        }

        // 刷新配置缓存（删除运行时缓存文件）
        try {
            $cacheFile = RUNTIME_PATH . 'cache' . DS . 'config' . DS . 'site.php';
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
            $output->writeln('');
            $output->writeln('<info>配置缓存已刷新</info>');
        } catch (\Exception $e) {
            // 忽略
        }

        $output->writeln('');
        $output->writeln('<info>安装完成！</info>');
        $output->writeln('<comment>管理配置: 后台 → 配置 → 红包配置</comment>');
        $output->writeln('<comment>启动服务: php think autopush start [--daemon]</comment>');
        $output->writeln('<comment>停止服务: php think autopush stop</comment>');
        $output->writeln('<comment>查看状态: php think autopush status</comment>');
    }
}
