<?php
// 查询 ns_lottery_profit 某条 record 的分润明细
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

$rid = isset($argv[1]) ? intval($argv[1]) : 0;
$roleFilter = isset($argv[2]) ? strval($argv[2]) : '';
list($pdo, $prefix, $dbname) = db_pdo();
$table = $prefix.'ns_lottery_profit';

$sql = "SELECT id, role, target_id, amount, site_id, device_id, status, create_time FROM `{$table}` WHERE record_id=?";
if ($roleFilter !== '') { $sql .= " AND role=?"; }
$sql .= " ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
if ($roleFilter !== '') { $stmt->execute([$rid, $roleFilter]); } else { $stmt->execute([$rid]); }
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['record_id'=>$rid,'rows'=>$rows], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";