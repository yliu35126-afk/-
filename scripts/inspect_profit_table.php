<?php
// 检查 ns_lottery_profit 表结构与现有行数
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
require_once __DIR__ . '/../vendor/autoload.php';
if (!function_exists('app')) {
    function app() { return new class { public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; }
}

function db_pdo() {
    $cfg = include __DIR__ . '/../config/database.php';
    $type = $cfg['default'];
    $conf = $cfg['connections'][$type];
    $host = $conf['hostname'];
    $port = $conf['hostport'];
    $user = $conf['username'];
    $pass = $conf['password'];
    $dbname = $conf['database'];
    $prefix = $conf['prefix'];
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return [$pdo, $prefix, $dbname];
}

list($pdo, $prefix, $dbname) = db_pdo();
$table = $prefix.'ns_lottery_profit';

$stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.columns WHERE table_schema=? AND table_name=? ORDER BY ORDINAL_POSITION");
$stmt->execute([$dbname, $table]);
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cnt = 0;
try {
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
} catch (Throwable $e) {}

echo json_encode(['table'=>$table,'columns'=>$cols,'count'=>$cnt], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";