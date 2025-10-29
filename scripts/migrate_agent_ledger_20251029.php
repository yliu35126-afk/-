<?php
// 一次性迁移脚本：将 ns_lottery_profit 中 agent/city 角色历史分润写入 ns_agent_ledger
// 使用 config/database.php 连接数据库，避免硬编码

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// 复用 upgrade 脚本中的 app() shim 以便载入配置
if (!function_exists('app')) {
    function app() { return new class { public function config() { return include __DIR__.'/../config/database.php'; } }; }
}

function db_pdo()
{
    $cfg = include __DIR__ . '/../config/database.php';
    $type = $cfg['connections']['mysql']['type'] ?? 'mysql';
    $host = $cfg['connections']['mysql']['hostname'] ?? '127.0.0.1';
    $port = (int)($cfg['connections']['mysql']['hostport'] ?? 3306);
    $user = $cfg['connections']['mysql']['username'] ?? 'root';
    $pass = $cfg['connections']['mysql']['password'] ?? '';
    $dbname = $cfg['connections']['mysql']['database'] ?? '';
    $charset = $cfg['connections']['mysql']['charset'] ?? 'utf8mb4';
    $prefix = $cfg['connections']['mysql']['prefix'] ?? '';
    $dsn = "$type:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]);
    return [$pdo, $prefix, $dbname];
}

function table_exists(PDO $pdo, $dbname, $table)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $stmt->execute([$dbname, $table]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    list($pdo, $prefix, $dbname) = db_pdo();
    $profit_table = $prefix.'ns_lottery_profit';
    $ledger_table = $prefix.'ns_agent_ledger';

    if (!table_exists($pdo, $dbname, $profit_table)) {
        fwrite(STDERR, "[ERROR] table $profit_table not exists\n");
        exit(1);
    }
    if (!table_exists($pdo, $dbname, $ledger_table)) {
        fwrite(STDERR, "[ERROR] table $ledger_table not exists. Please run upgrade_turntable_20251027.php first.\n");
        exit(1);
    }

    fwrite(STDOUT, "[INFO] start migrating agent/city profits to ledger...\n");
    $roles = ['agent','city'];
    // 逐批迁移，避免一次性加载过多
    $batch = 1000; $offset = 0; $migrated = 0; $skipped = 0;
    while (true) {
        $stmt = $pdo->prepare("SELECT id, record_id, role, target_id, amount, site_id, device_id, create_time FROM {$profit_table} WHERE role IN ('agent','city') ORDER BY id ASC LIMIT {$batch} OFFSET {$offset}");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) break;
        foreach ($rows as $r) {
            $agent_id = (int)($r['target_id'] ?? 0);
            $role = strval($r['role'] ?? '');
            $record_id = (int)($r['record_id'] ?? 0);
            $amount = (float)($r['amount'] ?? 0);
            $site_id = (int)($r['site_id'] ?? 0);
            $device_id = (int)($r['device_id'] ?? 0);
            $create_time = (int)($r['create_time'] ?? time());
            if ($agent_id <= 0 || $role === '' || $record_id <= 0) { $skipped++; continue; }
            // 查重：同一记录（agent_id+role+record_id）已存在则跳过
            $check = $pdo->prepare("SELECT COUNT(*) FROM {$ledger_table} WHERE agent_id=? AND role=? AND record_id=?");
            $check->execute([$agent_id, $role, $record_id]);
            if ((int)$check->fetchColumn() > 0) { $skipped++; continue; }
            $ins = $pdo->prepare("INSERT INTO {$ledger_table} (agent_id, role, record_id, amount, site_id, device_id, type, remark, create_time) VALUES (?,?,?,?,?,?,?,?,?)");
            $ins->execute([$agent_id, $role, $record_id, $amount, $site_id, $device_id, 'profit', '', $create_time]);
            $migrated++;
        }
        $offset += $batch;
        fwrite(STDOUT, "[INFO] processed {$offset}, migrated={$migrated}, skipped={$skipped}\n");
    }
    fwrite(STDOUT, "[DONE] migrated={$migrated}, skipped={$skipped}\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] ".$e->getMessage()."\n");
    exit(1);
}