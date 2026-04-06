#!/usr/bin/env php
<?php
/**
 * 系统会员数据初始化脚本
 * 生成100个系统会员，member_type=1
 * 密码统一为 qwe123，使用 md5(md5(password) . salt) 加密
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

    $prefix = $config['prefix'];
    $table = $prefix . 'user';

    echo "数据库连接成功\n";
    echo "表前缀: {$prefix}\n";
    echo "用户表: {$table}\n\n";

    // 检查 member_type 字段是否存在，不存在则添加
    echo "检查 member_type 字段...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'member_type'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN `member_type` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '会员类型: 0=真实会员, 1=系统会员' AFTER `group_id`");
        echo "已添加 member_type 字段 (0=真实会员, 1=系统会员)\n";
    } else {
        echo "member_type 字段已存在\n";
    }

    // 检查现有系统会员数量
    $existingCount = $pdo->query("SELECT COUNT(*) FROM {$table} WHERE member_type = 1")->fetchColumn();
    if ($existingCount >= 100) {
        echo "系统会员已存在 {$existingCount} 个，无需重复生成\n";
        exit(0);
    }
    echo "现有系统会员: {$existingCount} 个\n";

    // 100个网络化昵称（中文、英文、混合风格）
    $nicknames = [
        // ===== 中文网络风昵称 (50个) =====
        '夏天的风', '阳光少年', '暴走萝莉', '奶茶续命', '佛系青年',
        '烟火人间', '暮色四合', '逍遥游', '追风少年', '樱花落尽',
        '蓝色海岸', '星辰大海', '浅笑安然', '温柔以待', '月光如水',
        '青春纪念册', '繁花似锦', '清风明月', '踏雪寻梅', '南风知我意',
        '一叶知秋', '浮生若梦', '云淡风轻', '时光旅人', '静水深流',
        '紫霞仙子', '闲云野鹤', '雨后初晴', '桃花源记', '诗和远方',
        '寻梦环游', '岁月静好', '山河故人', '晚风轻拂', '林间小路',
        '晨曦微露', '花间一壶酒', '竹林听雨', '秋水共长天', '江上清风',
        '红尘客栈', '归来少年', '海上生明月', '雪落无声', '春风十里',
        '半夏微凉', '柠檬不酸', '薄荷微凉', '南城旧事', '拾光者',

        // ===== 英文/国际化昵称 (30个) =====
        'Alex Chen', 'Emma Wilson', 'Jake Miller', 'Sophia Lee', 'Noah Zhang',
        'Olivia Wang', 'Liam Liu', 'Ava Chen', 'Ethan Huang', 'Isabella Wu',
        'Mason Xu', 'Mia Zhao', 'Logan Yang', 'Charlotte Sun', 'Aiden Zhou',
        'Amelia Zheng', 'Lucas Feng', 'Harper Lin', 'Henry Wu', 'Evelyn He',
        'Daniel Kim', 'Abigail Park', 'Michael Chang', 'Emily Song', 'James Yoon',
        'Elizabeth Cho', 'Benjamin Tang', 'Chloe Guan', 'William Hao', 'Avery Jiang',

        // ===== 混合/趣味昵称 (20个) =====
        '大卫Walker', '小李飞刀', '安娜贝尔', '马克思K', '海阔天空',
        'Jack王同学', 'Rose李小姐', '大胡子Bob', 'Vicky张', '小土豆Tom',
        '花花世界', 'Kevin刘总', '一杯咖啡', 'Cici陈', 'Leo王大锤',
        '自由飞翔', 'Amy赵小花', '大白兔Miki', 'Tony孙大圣', '猫和鱼Fish',
    ];

    // 生成用户名的随机后缀
    function generateUsername($index) {
        $prefixes = ['sys_member', 'sys_user', 'system', 'auto_user'];
        $prefix = $prefixes[$index % count($prefixes)];
        $suffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . '_' . $suffix;
    }

    // 生成随机盐
    function generateSalt() {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $salt = '';
        for ($i = 0; $i < 6; $i++) {
            $salt .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $salt;
    }

    // FastAdmin 密码加密: md5(md5(password) . salt)
    function encryptPassword($password, $salt) {
        return md5(md5($password) . $salt);
    }

    // 生成邀请码
    function generateInviteCode($index) {
        $code = 'SYS' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
        return $code;
    }

    // 随机手机号
    function generateMobile($index) {
        $prefixes = ['130', '131', '132', '133', '135', '136', '137', '138', '139', '150', '151', '152', '155', '156', '157', '158', '159', '170', '176', '177', '178', '180', '181', '182', '183', '185', '186', '187', '188', '189'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        return $prefix . $suffix;
    }

    // 随机邮箱
    function generateEmail($username) {
        $domains = ['qq.com', '163.com', 'gmail.com', 'outlook.com', 'foxmail.com', 'hotmail.com', 'yahoo.com', 'sina.com'];
        return $username . '@' . $domains[array_rand($domains)];
    }

    // 随机生日
    function generateBirthday() {
        $year = rand(1985, 2002);
        $month = rand(1, 12);
        $day = rand(1, 28);
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // 随机IP
    function generateIP() {
        return rand(1, 254) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    // 随机性别
    function generateGender() {
        return rand(0, 2); // 0=未知, 1=男, 2=女
    }

    $password = 'qwe123';
    $now = time();
    $success = 0;
    $failed = 0;

    echo "\n开始生成100个系统会员...\n";
    echo "默认密码: {$password}\n";
    echo "会员类型: 1 (系统会员)\n\n";

    foreach ($nicknames as $index => $nickname) {
        $username = generateUsername($index);
        $salt = generateSalt();
        $encryptedPassword = encryptPassword($password, $salt);
        $inviteCode = generateInviteCode($index);
        $mobile = generateMobile($index);
        $email = generateEmail($username);
        $birthday = generateBirthday();
        $gender = generateGender();
        $ip = generateIP();

        // 随机注册时间（过去30~365天内）
        $createtime = $now - rand(30 * 86400, 365 * 86400);
        $jointime = $createtime;
        $updatetime = $now;

        try {
            $sql = "INSERT INTO {$table} (
                `group_id`, `member_type`, `username`, `nickname`, `password`, `salt`,
                `invite_code`, `parent_id`, `grandparent_id`, `email`, `mobile`,
                `avatar`, `level`, `gender`, `birthday`, `bio`, `money`, `score`,
                `successions`, `maxsuccessions`, `joinip`, `jointime`,
                `createtime`, `updatetime`, `status`, `source`, `verification`
            ) VALUES (
                0, 1, :username, :nickname, :password, :salt,
                :invite_code, 0, 0, :email, :mobile,
                '', 0, :gender, :birthday, '', 0.00, 0,
                1, 1, :ip, :jointime,
                :createtime, :updatetime, 'normal', 'system', ''
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':nickname' => $nickname,
                ':password' => $encryptedPassword,
                ':salt' => $salt,
                ':invite_code' => $inviteCode,
                ':email' => $email,
                ':mobile' => $mobile,
                ':gender' => $gender,
                ':birthday' => $birthday,
                ':ip' => $ip,
                ':jointime' => $jointime,
                ':createtime' => $createtime,
                ':updatetime' => $updatetime,
            ]);

            $success++;
            echo sprintf("[%03d] ✅ 用户名: %-20s 昵称: %-20s 邀请码: %s\n",
                $index + 1, $username, $nickname, $inviteCode);
        } catch (PDOException $e) {
            $failed++;
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo sprintf("[%03d] ⚠️  用户名重复: %-20s 跳过\n", $index + 1, $username);
            } else {
                echo sprintf("[%03d] ❌ 错误: %s\n", $index + 1, $e->getMessage());
            }
        }
    }

    echo "\n" . str_repeat('=', 60) . "\n";
    echo "安装完成!\n";
    echo "成功: {$success} 个\n";
    echo "失败: {$failed} 个\n";

    // 验证数据
    echo "\n数据验证:\n";
    $totalSys = $pdo->query("SELECT COUNT(*) FROM {$table} WHERE member_type = 1")->fetchColumn();
    $totalAll = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $totalReal = $pdo->query("SELECT COUNT(*) FROM {$table} WHERE member_type = 0")->fetchColumn();

    echo "系统会员总数: {$totalSys}\n";
    echo "真实会员总数: {$totalReal}\n";
    echo "会员总数: {$totalAll}\n";

    // 显示前5个系统会员的登录信息
    echo "\n系统会员登录示例（密码统一为 qwe123）:\n";
    $sampleUsers = $pdo->query("SELECT username, nickname FROM {$table} WHERE member_type = 1 ORDER BY id ASC LIMIT 5")->fetchAll();
    foreach ($sampleUsers as $user) {
        echo "  用户名: {$user['username']}  昵称: {$user['nickname']}  密码: qwe123\n";
    }

} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("错误: " . $e->getMessage() . "\n");
}
