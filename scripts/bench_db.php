<?php
// Simple DB latency benchmark for local dev (no ThinkPHP dependency)
$root = dirname(__DIR__);
$file = $root . '/config/database.php';
$txt  = file_get_contents($file);
if ($txt === false) { fwrite(STDERR, "无法读取配置文件\n"); exit(1); }

function pick($txt, $key, $def='') {
    $re = "#'".$key."'\s*=>\s*'([^']*)'#";
    if (preg_match($re, $txt, $m)) return $m[1];
    return $def;
}

$host = pick($txt, 'hostname', '127.0.0.1');
$db   = pick($txt, 'database', 'test');
$user = pick($txt, 'username', 'root');
$pass = pick($txt, 'password', '');
$port = pick($txt, 'hostport', '3306');
$charset = pick($txt, 'charset', 'utf8mb4');

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $db, $charset);

$start = microtime(true);
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, '连接失败: '.$e->getMessage()."\n");
    exit(2);
}
$connectMs = (microtime(true) - $start) * 1000;

// Run a simple query and measure time
$qStart = microtime(true);
try {
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetchAll();
} catch (Throwable $e) {
    fwrite(STDERR, '查询失败: '.$e->getMessage()."\n");
    exit(3);
}
$queryMs = (microtime(true) - $qStart) * 1000;

// Print result
printf("DB连接耗时: %.2f ms\n", $connectMs);
printf("简单查询耗时: %.2f ms\n", $queryMs);

// Optional: repeated queries
$loopMs = 0.0;
for ($i = 0; $i < 5; $i++) {
    $t0 = microtime(true);
    $pdo->query('SELECT NOW()')->fetchColumn();
    $loopMs += (microtime(true) - $t0) * 1000;
}
printf("5次轻量查询平均耗时: %.2f ms\n", $loopMs / 5);