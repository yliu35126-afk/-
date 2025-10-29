<?php
// 清理 ns_lottery_profit 重复 (record_id, role) 并添加唯一索引
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

require_once __DIR__ . '/../vendor/autoload.php';
if (!function_exists('app')) { function app(){ return new class{ public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; } }

function db_pdo() {
    $cfg = include __DIR__ . '/../config/database.php';
    $type = $cfg['default'];
    $conf = $cfg['connections'][$type];
    $dsn = "mysql:host={$conf['hostname']};port={$conf['hostport']};dbname={$conf['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['username'], $conf['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return [$pdo, $conf['prefix'], $conf['database']];
}

list($pdo, $prefix, $dbname) = db_pdo();
$table = $prefix . 'ns_lottery_profit';

// 1) 统计重复项
$sqlDup = "SELECT record_id, role, COUNT(*) AS cnt FROM `{$table}` GROUP BY record_id, role HAVING cnt>1";
$dups = $pdo->query($sqlDup)->fetchAll(PDO::FETCH_ASSOC);
$removed = 0;
foreach ($dups as $d) {
    $rid = intval($d['record_id']); $role = strval($d['role']);
    // 保留最新一条（最大 id），删除其余
    $ids = $pdo->prepare("SELECT id FROM `{$table}` WHERE record_id=? AND role=? ORDER BY id DESC");
    $ids->execute([$rid, $role]);
    $rows = $ids->fetchAll(PDO::FETCH_COLUMN);
    if (count($rows) > 1) {
        $keep = array_shift($rows); // 最大 id 保留
        if (!empty($rows)) {
            $in = implode(',', array_map('intval', $rows));
            $pdo->exec("DELETE FROM `{$table}` WHERE id IN ({$in})");
            $removed += count($rows);
        }
    }
}

// 2) 添加唯一索引（若不存在）
try {
    // 检查是否已存在索引
    $check = $pdo->prepare("SELECT COUNT(1) FROM information_schema.STATISTICS WHERE table_schema=? AND table_name=? AND index_name='uniq_record_role'");
    $check->execute([$dbname, $table]);
    $exists = intval($check->fetchColumn());
    if ($exists === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD UNIQUE KEY `uniq_record_role` (`record_id`,`role`)");
        $added = true;
    } else { $added = false; }
} catch (Throwable $e) {
    $added = false;
    fwrite(STDERR, "Add unique index failed: " . $e->getMessage() . "\n");
}

echo json_encode([
    'removed_duplicates' => $removed,
    'unique_index_added' => $added,
    'table' => $table
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";