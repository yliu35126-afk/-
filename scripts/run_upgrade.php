<?php
// 简易升级执行脚本：读取项目根目录 upgrade.sql 并执行
// 用法：php scripts/run_upgrade.php

function parseDbConfigFromFile($configPath)
{
    if (!file_exists($configPath)) {
        throw new RuntimeException('数据库配置文件不存在');
    }
    $raw = file_get_contents($configPath);
    $cfg = [];
    $map = [
        'hostname' => '/\'hostname\'\s*=>\s*\'(.*?)\'/',
        'hostport' => '/\'hostport\'\s*=>\s*\'(.*?)\'/',
        'database' => '/\'database\'\s*=>\s*\'(.*?)\'/',
        'username' => '/\'username\'\s*=>\s*\'(.*?)\'/',
        'password' => '/\'password\'\s*=>\s*\'(.*?)\'/',
        'charset'  => '/\'charset\'\s*=>\s*\'(.*?)\'/',
    ];
    foreach ($map as $k => $re) {
        if (preg_match($re, $raw, $m)) {
            $cfg[$k] = $m[1];
        }
    }
    if (empty($cfg['hostname']) || empty($cfg['database']) || !isset($cfg['username'])) {
        throw new RuntimeException('解析数据库配置失败');
    }
    if (empty($cfg['hostport'])) $cfg['hostport'] = '3306';
    if (empty($cfg['charset'])) $cfg['charset'] = 'utf8';
    return $cfg;
}

function getPdo($db)
{
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['hostname'], $db['hostport'], $db['database'], $db['charset'] ?? 'utf8');
    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function runSqlFile(PDO $pdo, $filePath)
{
    if (!file_exists($filePath)) throw new RuntimeException('升级文件不存在: ' . $filePath);
    $sql = file_get_contents($filePath);
    // 规范换行与分隔
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $stmts = [];
    $buffer = '';
    foreach (explode("\n", $sql) as $line) {
        // 跳过注释
        if (preg_match('/^\s*--/', $line)) continue;
        $buffer .= $line . "\n";
        if (substr(trim($line), -1) === ';') {
            $stmts[] = $buffer;
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') $stmts[] = $buffer;

    $count = 0;
    foreach ($stmts as $stmt) {
        $trim = trim($stmt);
        if ($trim === '') continue;
        echo "\n>>> 执行: " . preg_replace('/\s+/', ' ', substr($trim, 0, 120)) . "...\n";
        try {
            $pdo->exec($trim);
            $count++;
        } catch (Throwable $e) {
            echo "[跳过或失败] " . $e->getMessage() . "\n";
        }
    }
    echo "\n完成执行，共处理语句: {$count}\n";
}

try {
    $root = dirname(__DIR__);
    $db = parseDbConfigFromFile($root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php');
    $pdo = getPdo($db);
    runSqlFile($pdo, $root . DIRECTORY_SEPARATOR . 'upgrade.sql');
    echo "OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, '执行失败: ' . $e->getMessage() . "\n");
    exit(1);
}