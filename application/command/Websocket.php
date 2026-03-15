<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\input\Argument;
use think\console\Output;
use app\common\library\WebSocketService;

/**
 * WebSocket 服务管理命令 (基于 Swoole)
 * 
 * 使用方法：
 * php think websocket:start              # 启动 WebSocket 服务
 * php think websocket:start --port=3002  # 指定 WebSocket 端口
 * php think websocket:start --api=3003   # 指定 API 端口
 * php think websocket:start -d           # 以守护进程方式运行
 * 
 * php think websocket stop               # 停止服务
 * php think websocket restart            # 重启服务
 * php think websocket status             # 查看状态
 */
class Websocket extends Command
{
    protected function configure()
    {
        $this->setName('websocket')
            ->setDescription('WebSocket 服务管理')
            ->addArgument('action', Argument::OPTIONAL, '操作: start/stop/restart/status', 'start')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'WebSocket 端口', 3002)
            ->addOption('api', 'a', Option::VALUE_OPTIONAL, 'API 端口', 3003)
            ->addOption('daemon', 'd', Option::VALUE_NONE, '以守护进程方式运行');
    }
    
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        $port = $input->getOption('port');
        $apiPort = $input->getOption('api');
        $daemon = $input->getOption('daemon');
        
        // 检查 Swoole 扩展
        if (!extension_loaded('swoole')) {
            $output->error("请先安装 Swoole 扩展:");
            $output->info("安装方法: pecl install swoole");
            $output->info("或查看文档: https://wiki.swoole.com/#/environment");
            return;
        }
        
        switch ($action) {
            case 'start':
                $this->start($port, $apiPort, $daemon, $output);
                break;
                
            case 'stop':
                $this->stop($output);
                break;
                
            case 'restart':
                $this->restart($port, $apiPort, $daemon, $output);
                break;
                
            case 'status':
                $this->status($output);
                break;
                
            default:
                $output->error("未知操作: {$action}");
                $output->info("可用操作: start, stop, restart, status");
                $output->info("示例: php think websocket start");
        }
    }
    
    /**
     * 启动服务
     */
    protected function start($port, $apiPort, $daemon, $output)
    {
        $output->writeln("<info>========================================</info>");
        $output->writeln("<info>   广告网络管理系统 - WebSocket 服务</info>");
        $output->writeln("<info>========================================</info>");
        $output->writeln("");
        $output->writeln("<comment>WebSocket 端口:</comment> <info>{$port}</info>");
        $output->writeln("<comment>API 端口:</comment> <info>{$apiPort}</info>");
        $output->writeln("<comment>守护进程:</comment> <info>" . ($daemon ? '是' : '否') . "</info>");
        $output->writeln("<comment>启动时间:</comment> <info>" . date('Y-m-d H:i:s') . "</info>");
        $output->writeln("");
        
        WebSocketService::start($port, $apiPort, $daemon);
    }
    
    /**
     * 停止服务
     */
    protected function stop($output)
    {
        $pidFile = RUNTIME_PATH . 'websocket.pid';
        
        if (!file_exists($pidFile)) {
            $output->error("服务未运行或 PID 文件不存在");
            $output->info("PID 文件路径: {$pidFile}");
            return;
        }
        
        $pid = intval(file_get_contents($pidFile));
        
        if ($pid <= 0) {
            $output->error("无效的 PID");
            return;
        }
        
        // 检查进程是否存在
        if (!\Swoole\Process::kill($pid, 0)) {
            $output->error("进程不存在 (PID: {$pid})");
            unlink($pidFile);
            return;
        }
        
        // 发送终止信号
        \Swoole\Process::kill($pid, SIGTERM);
        
        // 等待进程结束
        $waitTime = 0;
        while ($waitTime < 10) {
            if (!\Swoole\Process::kill($pid, 0)) {
                break;
            }
            usleep(500000); // 0.5秒
            $waitTime += 0.5;
        }
        
        if (file_exists($pidFile)) {
            unlink($pidFile);
        }
        
        $output->writeln("<info>服务已停止 (PID: {$pid})</info>");
    }
    
    /**
     * 重启服务
     */
    protected function restart($port, $apiPort, $daemon, $output)
    {
        $output->writeln("<comment>正在重启服务...</comment>");
        
        // 先停止
        $this->stop($output);
        
        // 等待一秒
        sleep(1);
        
        // 再启动
        $output->writeln("");
        $this->start($port, $apiPort, $daemon, $output);
    }
    
    /**
     * 查看状态
     */
    protected function status($output)
    {
        $pidFile = RUNTIME_PATH . 'websocket.pid';
        
        $output->writeln("<info>========================================</info>");
        $output->writeln("<info>   WebSocket 服务状态</info>");
        $output->writeln("<info>========================================</info>");
        $output->writeln("");
        
        if (!file_exists($pidFile)) {
            $output->writeln("<comment>状态:</comment> <error>未运行</error>");
            $output->writeln("<comment>PID 文件:</comment> 不存在");
            return;
        }
        
        $pid = intval(file_get_contents($pidFile));
        
        $output->writeln("<comment>PID:</comment> <info>{$pid}</info>");
        
        if ($pid > 0 && extension_loaded('swoole') && \Swoole\Process::kill($pid, 0)) {
            $output->writeln("<comment>状态:</comment> <info>运行中</info>");
            
            // 获取进程信息
            $processInfo = posix_getpid() === $pid ? '当前进程' : "进程 #{$pid}";
            $output->writeln("<comment>进程:</comment> <info>{$processInfo}</info>");
        } else {
            $output->writeln("<comment>状态:</comment> <error>已停止</error>");
            $output->writeln("<comment>注意:</comment> PID 文件存在但进程不存在");
            unlink($pidFile);
        }
        
        $output->writeln("");
        $output->writeln("<comment>配置信息:</comment>");
        $output->writeln("  WebSocket 端口: 3002");
        $output->writeln("  API 端口: 3003");
        $output->writeln("  PID 文件: " . RUNTIME_PATH . 'websocket.pid');
        $output->writeln("  日志文件: " . RUNTIME_PATH . 'log' . DS . 'websocket.log');
    }
}
