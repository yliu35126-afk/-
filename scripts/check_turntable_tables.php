<?php
// 检查 addon/turntable/data/install.sql 是否已导入（通过检查应有的数据表是否存在）

// 兼容直接 include 配置文件时的 app() 辅助函数缺失问题
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() {
                return __DIR__ . '/../runtime/';
            }
        };
    }
}

$cfg = include __DIR__ . '/../config/database.php';
$dbc = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) {
    echo json_encode(['ok' => false, 'error' => 'DB_CONFIG_NOT_FOUND'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbc['hostname'],
    $dbc['hostport'],
    $dbc['database'],
    $dbc['charset'] ?? 'utf8'
);

try {
    $pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => 'DB_CONNECT_FAIL',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(1);
}

$prefix = $dbc['prefix'] ?? '';
$tables = [
    'lottery_board',
    'lottery_slot',
    'lottery_price_tier',
    'device_info',
    'device_price_bind',
    'lottery_record',
    'lottery_profit',
];

$present = [];
$missing = [];
foreach ($tables as $t) {
    $name = $prefix . $t;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
    $stmt->execute([$dbc['database'], $name]);
    $exists = (int)$stmt->fetchColumn() > 0;
    if ($exists) {
        $present[] = $name;
    } else {
        $missing[] = $name;
    }
}

echo json_encode([
    'ok' => true,
    'database' => $dbc['database'],
    'prefix' => $prefix,
    'present' => $present,
    'missing' => $missing,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);