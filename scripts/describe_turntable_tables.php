<?php
// 输出 turntable 相关表的字段信息，便于对齐控制器代码

if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() { return __DIR__ . '/../runtime/'; }
        };
    }
}

$cfg = include __DIR__ . '/../config/database.php';
$dbc = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) { echo "DB_CONFIG_NOT_FOUND\n"; exit(1); }

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbc['hostname'], $dbc['hostport'], $dbc['database'], $dbc['charset'] ?? 'utf8');
$pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$prefix = $dbc['prefix'] ?? '';

$tables = [ 'device_info', 'lottery_slot', 'lottery_board', 'device_price_bind', 'lottery_price_tier' ];

foreach ($tables as $t) {
    $name = $prefix . $t;
    echo "===== $name =====\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$name}`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            printf("%-20s %-20s null:%s default:%s\n", $c['Field'], $c['Type'], $c['Null'], var_export($c['Default'], true));
        }
    } catch (Throwable $e) {
        echo "ERROR: ".$e->getMessage()."\n";
    }
}