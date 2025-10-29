<?php
// 升级脚本：创建 ns_lottery_profit 明细表（若不存在），与事件写入字段对齐
// 用法：php scripts/upgrade_turntable_profit_table.php

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

require_once __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();

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

function table_exists(PDO $pdo, $dbname, $table)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $stmt->execute([$dbname, $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function col_exists(PDO $pdo, $dbname, $table, $column)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?");
    $stmt->execute([$dbname, $table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    list($pdo, $prefix, $dbname) = db_pdo();
    $table = $prefix . 'ns_lottery_profit';
    if (!table_exists($pdo, $dbname, $table)) {
        $sql = "CREATE TABLE `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `role` VARCHAR(20) NOT NULL DEFAULT '',
  `target_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `site_id` INT NOT NULL DEFAULT 0,
  `device_id` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `create_time` INT NOT NULL DEFAULT 0,
  `settle_time` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`),
  KEY `idx_role` (`role`),
  KEY `idx_target` (`target_id`),
  KEY `idx_site_time` (`site_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        fwrite(STDOUT, "[OK] Created table {$table}.\n");
    } else {
        fwrite(STDOUT, "[INFO] Table {$table} exists. Checking columns...\n");
    }

    // 校验并补齐必要列（与事件写入保持一致）
    $checks = [
        ['order_id',   "ALTER TABLE `{$table}` ADD COLUMN `order_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `record_id`"],
        ['role',       "ALTER TABLE `{$table}` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT '' AFTER `order_id`"],
        ['target_id',  "ALTER TABLE `{$table}` ADD COLUMN `target_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `role`"],
        ['amount',     "ALTER TABLE `{$table}` ADD COLUMN `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `target_id`"],
        ['site_id',    "ALTER TABLE `{$table}` ADD COLUMN `site_id` INT NOT NULL DEFAULT 0 AFTER `amount`"],
        ['device_id',  "ALTER TABLE `{$table}` ADD COLUMN `device_id` INT NOT NULL DEFAULT 0 AFTER `site_id`"],
        ['status',     "ALTER TABLE `{$table}` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `device_id`"],
        ['create_time',"ALTER TABLE `{$table}` ADD COLUMN `create_time` INT NOT NULL DEFAULT 0 AFTER `status`"],
        ['settle_time',"ALTER TABLE `{$table}` ADD COLUMN `settle_time` INT NOT NULL DEFAULT 0 AFTER `create_time`"],
    ];
    foreach ($checks as [$col, $ddl]) {
        if (!col_exists($pdo, $dbname, $table, $col)) {
            try { $pdo->exec($ddl); fwrite(STDOUT, "[OK] Added column {$col}.\n"); } catch (Throwable $e) { fwrite(STDOUT, "[WARN] Add {$col} failed: ".$e->getMessage()."\n"); }
        }
    }

    // 索引兜底
    try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_record ON `{$table}`(`record_id`)"); } catch (Throwable $e) {}
    try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_role ON `{$table}`(`role`)"); } catch (Throwable $e) {}
    try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_target ON `{$table}`(`target_id`)"); } catch (Throwable $e) {}
    try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_site_time ON `{$table}`(`site_id`,`create_time`)"); } catch (Throwable $e) {}

    fwrite(STDOUT, "[DONE] ns_lottery_profit schema ready.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] upgrade failed: " . $e->getMessage() . "\n");
    exit(1);
}