<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\input\Argument;
use think\console\Output;
use app\common\library\WebSocketService;

/**
 * WebSocket 服务启动命令
 * 
 * 使用方法：
 * php think websocket:start              # 启动 WebSocket 服务
 * php think websocket:start --port=3002  # 指定 WebSocket 端口
 * php think websocket:start --api=3003   # 指定 API 端口
 * php think websocket:start -d           # 以守护进程方式运行
 * php think websocket:stop               # 停止服务
 * php think websocket:restart            # 重启服务
 * php think websocket:status             # 查看状态
 */
class Websocket extends Command
{
    protected function configure()
    {
        $this->setName('websocket:start')
            ->setDescription('启动 WebSocket 服务')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'WebSocket 端口', 3002)
            ->addOption('api', 'a', Option::VALUE_OPTIONAL, 'API 端口', 3003)
            ->addOption('daemon', 'd', Option::VALUE_NONE, '以守护进程方式运行');
    }
    
    protected function execute(Input $input, Output $output)
    {
        // 检查是否安装 Workerman
        if (!class_exists('Workerman\Worker')) {
            $output->error("请先安装 Workerman:");
            $output->info("运行命令: composer require workerman/workerman");
            return;
        }
        
        $port = $input->getOption('port');
        $apiPort = $input->getOption('api');
        $daemon = $input->getOption('daemon');
        
        // 设置守护进程模式
        if ($daemon) {
            global $argv;
            $argv = ['think', 'websocket:start', '-d'];
        }
        
        $output->writeln("<info>========================================</info>");
        $output->writeln("<info>   广告网络管理系统 - WebSocket 服务</info>");
        $output->writeln("<info>========================================</info>");
        $output->writeln("");
        $output->writeln("<comment>WebSocket 端口:</comment> <info>{$port}</info>");
        $output->writeln("<comment>API 端口:</comment> <info>{$apiPort}</info>");
        $output->writeln("<comment>守护进程:</comment> <info>" . ($daemon ? '是' : '否') . "</info>");
        $output->writeln("");
        
        // 启动服务
        WebSocketService::start($port, $apiPort);
    }
}
