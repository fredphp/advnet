<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * WebSocket 服务管理命令
 * 
 * 使用方法：
 * php think websocket:stop      # 停止服务
 * php think websocket:restart   # 重启服务
 * php think websocket:status    # 查看状态
 */
class WebsocketManager extends Command
{
    protected function configure()
    {
        $this->setName('websocket:manage')
            ->setDescription('管理 WebSocket 服务')
            ->addArgument('action', null, '操作: stop/restart/status', 'status');
    }
    
    protected function execute(Input $input, Output $output)
    {
        // 检查是否安装 Workerman
        if (!class_exists('Workerman\Worker')) {
            $output->error("请先安装 Workerman:");
            $output->info("运行命令: composer require workerman/workerman");
            return;
        }
        
        $action = $input->getArgument('action');
        
        // 修改全局 argv 来控制 Workerman
        global $argv;
        $argv[0] = 'think';
        
        switch ($action) {
            case 'stop':
                $output->writeln("<info>正在停止 WebSocket 服务...</info>");
                $argv[1] = 'stop';
                break;
                
            case 'restart':
                $output->writeln("<info>正在重启 WebSocket 服务...</info>");
                $argv[1] = 'restart';
                break;
                
            case 'status':
                $output->writeln("<info>查看 WebSocket 服务状态...</info>");
                $argv[1] = 'status';
                break;
                
            default:
                $output->error("未知操作: {$action}");
                $output->info("可用操作: stop, restart, status");
                return;
        }
        
        // 创建一个临时 Worker 来执行命令
        try {
            $worker = new \Workerman\Worker();
            \Workerman\Worker::runAll();
        } catch (\Exception $e) {
            $output->error("操作失败: " . $e->getMessage());
        }
    }
}
