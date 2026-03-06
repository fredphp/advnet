<?php
/**
 * 诊断脚本 - 查找 think\facade 引用
 *
 * 使用方法: php diagnose_facade.php
 */

echo "=== 查找 think\\facade 引用 ===\n\n";

$searchDirs = [
    __DIR__ . '/application',
    __DIR__ . '/extend',
    __DIR__ . '/addons',
    __DIR__ . '/vendor',
];

$found = false;

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) {
        echo "目录不存在: $dir\n";
        continue;
    }

    echo "扫描目录: $dir\n";

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());

        // 检查 think\facade 引用
        if (preg_match('/think\\\\facade\\\\(Db|Cache|Log|Config|Session|Request|Response|Route|Url|View|Env|Cookie|Middleware|Validate)/i', $content)) {
            echo "\n找到问题文件: " . $file->getPathname() . "\n";

            // 显示匹配的行
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (preg_match('/think\\\\facade\\\\\w+/i', $line)) {
                    echo "  行 " . ($num + 1) . ": " . trim($line) . "\n";
                }
            }
            $found = true;
        }
    }
}

if (!$found) {
    echo "\n没有找到 think\\facade 引用。\n";
}

echo "\n=== 检查 thinkphp 核心框架版本 ===\n";
$thinkFile = __DIR__ . '/thinkphp/library/think/App.php';
if (file_exists($thinkFile)) {
    $content = file_get_contents($thinkFile);
    if (preg_match("/THINK_VERSION\s*=\s*'([^']+)'/", $content, $matches)) {
        echo "ThinkPHP 版本: " . $matches[1] . "\n";
    } elseif (preg_match("/define\('THINK_VERSION',\s*'([^']+)'\)/", $content, $matches)) {
        echo "ThinkPHP 版本: " . $matches[1] . "\n";
    } else {
        echo "无法确定 ThinkPHP 版本\n";
    }
} else {
    $composerJson = __DIR__ . '/vendor/topthink/framework/composer.json';
    if (file_exists($composerJson)) {
        $content = json_decode(file_get_contents($composerJson), true);
        echo "ThinkPHP 版本 (vendor): " . ($content['version'] ?? 'unknown') . "\n";
    } else {
        echo "ThinkPHP 核心文件未找到\n";
    }
}

echo "\n=== 完成 ===\n";
