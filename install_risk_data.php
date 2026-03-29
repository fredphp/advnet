#!/usr/bin/env php
<?php
/**
 * 风控系统数据初始化脚本
 */

// 定义项目路径
define('APP_PATH', __DIR__ . '/application/');
define('RUNTIME_PATH', __DIR__ . '/runtime/');

// 加载框架引导文件
require __DIR__ . '/thinkphp/base.php';

// 读取数据库配置
$config = include APP_PATH . 'database.php';

try {
    // 创建PDO连接
    $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "数据库连接成功\n";
    
    // 读取SQL文件
    $sqlFile = __DIR__ . '/sql/risk_demo_data.sql';
    if (!file_exists($sqlFile)) {
        die("SQL文件不存在: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // 分割SQL语句
    $statements = explode(";\n", $sql);
    
    $success = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || $statement === ';') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success++;
            
            // 输出CREATE TABLE语句的结果
            if (stripos($statement, 'CREATE TABLE') !== false) {
                if (preg_match('/`(\w+)`/', $statement, $matches)) {
                    echo "创建表: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                if (preg_match('/`(\w+)`/', $statement, $matches)) {
                    echo "插入数据: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            $failed++;
            // 忽略已存在的错误
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "错误: " . substr($statement, 0, 60) . "...\n";
                echo "信息: " . $e->getMessage() . "\n\n";
            }
        }
    }
    
    echo "\n执行完成! 成功: {$success}, 失败: {$failed}\n";
    
    // 验证数据
    echo "\n验证数据:\n";
    $tables = ['risk_rule', 'user_risk_score', 'ban_record', 'risk_log', 'risk_blacklist', 'risk_whitelist', 'device_fingerprint', 'user_behavior_stat'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM fa_{$table}")->fetchColumn();
        echo "fa_{$table}: {$count} 条记录\n";
    }
    
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("错误: " . $e->getMessage() . "\n");
}
