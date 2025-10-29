<?php
// 调试：打印指定抽奖记录的关键字段与相关表数据
// 用法：php scripts/debug_record.php 5

if (!function_exists('app')) {
    function app() { return new class { public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; }
}

$root = realpath(__DIR__ . '/..');
$cfg  = include $root . '/config/database.php';
$dbc  = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) { fwrite(STDERR, "无法读取数据库配置\n"); exit(1); }

$prefix = $dbc['prefix'] ?? '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbc['hostname'], $dbc['hostport'], $dbc['database'], $dbc['charset'] ?? 'utf8');

try { $pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
catch (Throwable $e) { fwrite(STDERR, '数据库连接失败: '.$e->getMessage()."\n"); exit(1); }

$recordId = isset($argv[1]) ? intval($argv[1]) : 0;
if ($recordId <= 0) { fwrite(STDERR, "请提供 record_id\n"); exit(1); }

function row($pdo, $sql, $args = []) {
    $stmt = $pdo->prepare($sql); $stmt->execute($args); return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
function rows($pdo, $sql, $args = []) {
    $stmt = $pdo->prepare($sql); $stmt->execute($args); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$rec = row($pdo, "SELECT * FROM {$prefix}lottery_record WHERE record_id = ?", [$recordId]);
if (!$rec) { fwrite(STDERR, "记录不存在\n"); exit(1); }

$tier = [];
if (intval($rec['tier_id'] ?? 0) > 0) {
    $tier = row($pdo, "SELECT * FROM {$prefix}lottery_price_tier WHERE tier_id = ?", [intval($rec['tier_id'])]);
} else {
    // 设备当前绑定的生效价档
    $bind = row($pdo, "SELECT * FROM {$prefix}device_price_bind WHERE device_id = ? AND status = 1 ORDER BY start_time DESC LIMIT 1", [intval($rec['device_id'] ?? 0)]);
    if ($bind) { $tier = row($pdo, "SELECT * FROM {$prefix}lottery_price_tier WHERE tier_id = ?", [intval($bind['tier_id'])]); }
}

$dev  = row($pdo, "SELECT * FROM {$prefix}device_info WHERE device_id = ?", [intval($rec['device_id'] ?? 0)]);
$goods= [];
if (intval($rec['goods_id'] ?? 0) > 0) {
    $goods = row($pdo, "SELECT goods_id,goods_name,supply_price,cost_price,supplier_id FROM {$prefix}goods WHERE goods_id = ?", [intval($rec['goods_id'])]);
}

$settle = row($pdo, "SELECT * FROM {$prefix}lottery_settlement WHERE record_id = ?", [$recordId]);
$lp     = row($pdo, "SELECT * FROM {$prefix}lottery_profit WHERE record_id = ?", [$recordId]);
$nslp   = rows($pdo, "SELECT * FROM {$prefix}ns_lottery_profit WHERE record_id = ?", [$recordId]);

$out = [
    'record' => [
        'record_id' => intval($rec['record_id']),
        'order_id' => intval($rec['order_id'] ?? 0),
        'device_id' => intval($rec['device_id'] ?? 0),
        'goods_id' => intval($rec['goods_id'] ?? 0),
        'tier_id' => intval($rec['tier_id'] ?? 0),
        'amount' => floatval($rec['amount'] ?? 0),
        'ext' => $rec['ext'] ?? '',
    ],
    'tier' => $tier,
    'device' => $dev,
    'goods' => $goods,
    'settlement' => $settle,
    'lottery_profit' => $lp,
    'ns_lottery_profit' => $nslp,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";