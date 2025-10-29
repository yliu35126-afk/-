<?php
// Reset a verify record to pending and its linked lottery_settlement to pending
// Usage: php scripts/reset_verify.php <VERIFY_CODE>

if ($argc < 2) { fwrite(STDERR, "Usage: php scripts/reset_verify.php <VERIFY_CODE>\n"); exit(1); }
$code = strtoupper(trim($argv[1] ?? ''));
if ($code === '') { fwrite(STDERR, "Empty verify code\n"); exit(1); }

$root = realpath(__DIR__ . '/..');
// Provide fallback app() to satisfy config/database.php references in CLI
if (!function_exists('app')) {
    function app() { return new class { public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; }
}
$cfg  = include $root . '/config/database.php';
$dbc  = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) { fwrite(STDERR, "无法读取数据库配置\n"); exit(1); }

$prefix = $dbc['prefix'] ?? '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbc['hostname'], $dbc['hostport'], $dbc['database'], $dbc['charset'] ?? 'utf8');

try { $pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
catch (Throwable $e) { fwrite(STDERR, '数据库连接失败: '.$e->getMessage()."\n"); exit(1); }

// Locate verify row
$stmt = $pdo->prepare("SELECT id, relate_id, is_verify FROM {$prefix}verify WHERE verify_code = ? LIMIT 1");
$stmt->execute([$code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { fwrite(STDERR, "核销码不存在\n"); exit(1); }

$verifyId = intval($row['id']);
$recordId = intval($row['relate_id']);

// Reset verify status
$pdo->prepare("UPDATE {$prefix}verify SET is_verify=0, verify_time=0 WHERE id = ?")->execute([$verifyId]);

// Ensure settlement pending
if ($recordId > 0) {
    // Insert pending if not exists; else set pending
    $stmt2 = $pdo->prepare("SELECT id FROM {$prefix}lottery_settlement WHERE record_id=? LIMIT 1");
    $stmt2->execute([$recordId]);
    $exists = $stmt2->fetchColumn();
    if ($exists) {
        $pdo->prepare("UPDATE {$prefix}lottery_settlement SET status='pending', update_time=? WHERE record_id=?")
            ->execute([time(), $recordId]);
    } else {
        $pdo->prepare("INSERT INTO {$prefix}lottery_settlement (record_id, source_type, status, payload, create_time, update_time) VALUES (?, 'self', 'pending', ?, ?, ?) ")
            ->execute([$recordId, json_encode(['from'=>'reset_script'], JSON_UNESCAPED_UNICODE), time(), time()]);
    }
}

echo json_encode(['verify_id'=>$verifyId, 'record_id'=>$recordId, 'status'=>'reset'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";