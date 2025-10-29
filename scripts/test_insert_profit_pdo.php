<?php
// 使用 PDO 直插 ns_lottery_profit，输出具体错误信息
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

$record_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($record_id <= 0) { fwrite(STDERR, "请提供 record_id\n"); exit(1); }

list($pdo, $prefix, $dbname) = db_pdo();
$table = $prefix.'ns_lottery_profit';

try {
    $sql = "INSERT INTO `{$table}`(record_id,order_id,role,target_id,amount,site_id,device_id,status,create_time) VALUES(?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([$record_id, 0, 'merchant', 1, 1.23, 0, 1, 'pending', time()]);
    fwrite(STDOUT, $ok ? "[OK] insert success\n" : "[WARN] insert returned false\n");
} catch (Throwable $e) {
    fwrite(STDOUT, "[ERROR] ".$e->getMessage()."\n");
}