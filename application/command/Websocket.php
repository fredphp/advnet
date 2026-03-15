<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use app\common\library\WebSocketService;

/**
 * WebSocket 服务启动命令 (基于 Swoole)
 * 
 * 使用方法：
 * php think websocket:start              # 启动 WebSocket 服务
 * php think websocket:start --port=3002  # 指定 WebSocket 端口
 * php think websocket:start --api=3003   # 指定 API 端口
 * php think websocket:start -d           # 以守护进程方式运行
 * php think websocket:stop               # 声止服务
 * php think websocket:restart             # 重启服务
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
        $port = $input->getOption('port');
        $apiPort = $input->getOption('api');
        $daemon = $input->getOption('daemon');
        
        // 检查 Swoole 扩展
        if (!extension_loaded('swoole')) {
            $output->error("<error>请先安装 Swoole 扩展:</error>");
            $output->info("安装方法: pecl install swoole");
            $output->info("或查看文档: https://wiki.swoole.com/#/environment");
            return;
        }
        
        WebSocketService::start($port, $apiPort, $daemon);
    }
}
