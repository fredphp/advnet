<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use app\common\library\AutoPushService;

/**
 * 自动消息推送服务命令
 * 
 * 用法:
 *   php think autopush start [--daemon]  启动服务（--daemon 后台运行）
 *   php think autopush stop              停止服务
 *   php think autopush restart           重启服务
 *   php think autopush status            查看服务状态
 */
class AutoPush extends Command
{
    protected function configure()
    {
        $this->setName('autopush')
            ->addArgument('action', Argument::REQUIRED, 'start|stop|restart|status')
            ->addOption('daemon', 'd', \think\console\input\Option::VALUE_NONE, '以守护进程方式运行')
            ->setDescription('自动消息推送服务（聊天/下载/广告/红包）');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        switch ($action) {
            case 'start':
                $this->doStart($input, $output);
                break;
            case 'stop':
                $this->doStop($output);
                break;
            case 'restart':
                $this->doStop($output);
                sleep(1);
                $this->doStart($input, $output);
                break;
            case 'status':
                $this->doStatus($output);
                break;
            default:
                $output->writeln('<error>未知操作: ' . $action . '</error>');
                $output->writeln('用法: php think autopush {start|stop|restart|status} [--daemon]');
        }
    }

    /**
     * 启动服务
     */
    private function doStart(Input $input, Output $output)
    {
        $pidFile = RUNTIME_PATH . AutoPushService::PID_FILE;

        // 检查是否已在运行
        if (file_exists($pidFile)) {
            $oldPid = intval(file_get_contents($pidFile));
            if ($oldPid > 0 && posix_kill($oldPid, 0)) {
                $output->writeln('<error>AutoPush 已在运行中 (PID: ' . $oldPid . ')</error>');
                $output->writeln('请先执行 php think autopush stop 停止');
                return;
            } else {
                // PID文件存在但进程不存在，清理残留
                @unlink($pidFile);
            }
        }

        $daemon = $input->getOption('daemon');

        if ($daemon) {
            // 守护进程模式：使用 Swoole\Process::daemon()
            if (!extension_loaded('swoole')) {
                $output->writeln('<error>Swoole 扩展未安装，无法使用 --daemon 模式</error>');
                $output->writeln('请使用: nohup php think autopush start > /dev/null 2>&1 &');
                return;
            }

            $output->writeln('<info>以守护进程模式启动 AutoPush...</info>');

            // 写入 PID（daemon化前）
            $pid = getmypid();
            file_put_contents($pidFile, $pid);

            // Swoole\Process::daemon(不更改目录, 关闭标准IO)
            \Swoole\Process::daemon(false, true);

            // 重写 PID（daemon化后PID会变）
            $newPid = getmypid();
            file_put_contents($pidFile, $newPid);

            // 启动服务（阻塞）
            $service = new AutoPushService();
            $service->run();
        } else {
            // 前台模式
            $pid = getmypid();
            file_put_contents($pidFile, $pid);

            $output->writeln('<info>前台模式启动 AutoPush (PID: ' . $pid . ')</info>');
            $output->writeln('<comment>按 Ctrl+C 停止，或使用 SIGUSR1 信号查看统计</comment>');
            $output->writeln('');

            // 启动服务（阻塞）
            $service = new AutoPushService();
            $service->run();

            // 服务退出后清理 PID
            @unlink($pidFile);
        }
    }

    /**
     * 停止服务
     */
    private function doStop(Output $output)
    {
        $pidFile = RUNTIME_PATH . AutoPushService::PID_FILE;

        if (!file_exists($pidFile)) {
            $output->writeln('<comment>AutoPush 未在运行（PID 文件不存在）</comment>');
            return;
        }

        $pid = intval(file_get_contents($pidFile));

        if ($pid <= 0) {
            @unlink($pidFile);
            $output->writeln('<comment>无效的 PID，已清理</comment>');
            return;
        }

        // 尝试优雅停止（SIGTERM）
        if (posix_kill($pid, SIGTERM)) {
            $output->writeln('<info>已发送停止信号到 PID: ' . $pid . '</info>');

            // 等待进程退出（最多10秒）
            $waited = 0;
            while ($waited < 10) {
                usleep(500000); // 0.5秒
                $waited += 0.5;
                if (!posix_kill($pid, 0)) {
                    $output->writeln('<info>AutoPush 已停止</info>');
                    @unlink($pidFile);
                    return;
                }
            }

            // 超时，强制杀死
            $output->writeln('<comment>等待超时，强制停止...</comment>');
            if (posix_kill($pid, SIGKILL)) {
                $output->writeln('<info>AutoPush 已强制停止</info>');
            }
        } else {
            $output->writeln('<error>无法停止进程 PID: ' . $pid . '（进程可能不存在）</error>');
        }

        @unlink($pidFile);
    }

    /**
     * 查看状态
     */
    private function doStatus(Output $output)
    {
        $pidFile = RUNTIME_PATH . AutoPushService::PID_FILE;

        if (!file_exists($pidFile)) {
            $output->writeln('<comment>AutoPush 未运行</comment>');
            return;
        }

        $pid = intval(file_get_contents($pidFile));

        if ($pid > 0 && posix_kill($pid, 0)) {
            // 进程存在
            $output->writeln('<info>AutoPush 运行中 (PID: ' . $pid . ')</info>');

            // 尝试获取运行时长
            $startTime = @filemtime($pidFile);
            if ($startTime) {
                $elapsed = time() - $startTime;
                $h = intval($elapsed / 3600);
                $m = intval(($elapsed % 3600) / 60);
                $output->writeln('运行时长: ' . ($h > 0 ? $h . '小时' : '') . $m . '分' . ($elapsed % 60) . '秒');
            }

            // 发送 SIGUSR1 让进程打印统计
            if (posix_kill($pid, SIGUSR1)) {
                $output->writeln('<comment>已发送统计信号（统计信息将输出到服务的控制台）</comment>');
            }
        } else {
            $output->writeln('<comment>AutoPush 已停止（PID 文件残留）</comment>');
            @unlink($pidFile);
        }
    }
}
