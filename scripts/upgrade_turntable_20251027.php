<?php
// 轻量升级脚本：为 turntable 模块补充列
// - addon_turntable.data.install.sql：lottery_price_tier 增加 min_price、max_price
// - addon_turntable.data.install.sql：lottery_goods_tier 增加 tier_ids
// 使用项目 database.php 获取连接配置（兼容 prefix），并做容错创建

error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// 提供 app() shim 以便 config/database.php 可被 require
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() {
                return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;
            }
        };
    }
}

// 载入数据库配置
$config_file = __DIR__ . '/../config/database.php';
if (!file_exists($config_file)) {
    fwrite(STDERR, "database.php not found\n");
    exit(1);
}
$db_cfg = require $config_file;
$mysql = $db_cfg['connections']['mysql'];

$host     = $mysql['hostname'] ?? '127.0.0.1';
$port     = $mysql['hostport'] ?? '3306';
$dbname   = $mysql['database'] ?? '';
$user     = $mysql['username'] ?? 'root';
$pass     = $mysql['password'] ?? '';
$prefix   = $mysql['prefix'] ?? '';
$charset  = $mysql['charset'] ?? 'utf8mb4';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Connect DB failed: " . $e->getMessage() . "\n");
    exit(2);
}

function col_exists(PDO $pdo, $db, $table, $col) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$db, $table, $col]);
    $row = $stmt->fetch();
    return ($row && intval($row['c']) > 0);
}

function table_exists(PDO $pdo, $db, $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    $stmt->execute([$db, $table]);
    $row = $stmt->fetch();
    return ($row && intval($row['c']) > 0);
}

// 1) lottery_price_tier 增加 min_price、max_price
$tier_table = $prefix . 'lottery_price_tier';
if (!table_exists($pdo, $dbname, $tier_table)) {
    fwrite(STDOUT, "Table {$tier_table} not found, skip adding min/max.\n");
} else {
    if (!col_exists($pdo, $dbname, $tier_table, 'min_price')) {
        $pdo->exec("ALTER TABLE `{$tier_table}` ADD COLUMN `min_price` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `price`");
        fwrite(STDOUT, "Added {$tier_table}.min_price\n");
    }
    if (!col_exists($pdo, $dbname, $tier_table, 'max_price')) {
        $pdo->exec("ALTER TABLE `{$tier_table}` ADD COLUMN `max_price` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `min_price`");
        fwrite(STDOUT, "Added {$tier_table}.max_price\n");
    }
}

// 2) lottery_goods_tier 增加 tier_ids（用于多选）
$goods_tier_table = $prefix . 'lottery_goods_tier';
if (!table_exists($pdo, $dbname, $goods_tier_table)) {
    fwrite(STDOUT, "Table {$goods_tier_table} not found, skip adding tier_ids.\n");
} else {
    if (!col_exists($pdo, $dbname, $goods_tier_table, 'tier_ids')) {
        $pdo->exec("ALTER TABLE `{$goods_tier_table}` ADD COLUMN `tier_ids` TEXT NULL AFTER `tier_id`");
        fwrite(STDOUT, "Added {$goods_tier_table}.tier_ids\n");
    }
}

// 3) lottery_slot 增加 source_type（自营/平台/供应商；默认自营）
$slot_table = $prefix . 'lottery_slot';
if (!table_exists($pdo, $dbname, $slot_table)) {
    fwrite(STDOUT, "Table {$slot_table} not found, skip adding source_type.\n");
} else {
    if (!col_exists($pdo, $dbname, $slot_table, 'source_type')) {
        $pdo->exec("ALTER TABLE `{$slot_table}` ADD COLUMN `source_type` VARCHAR(20) NOT NULL DEFAULT 'self' AFTER `prize_type`");
        fwrite(STDOUT, "Added {$slot_table}.source_type\n");
    }
}

// 4) 新建 lottery_settlement（抽奖结算占位表）
$settle_table = $prefix . 'lottery_settlement';
if (!table_exists($pdo, $dbname, $settle_table)) {
    $sql = "CREATE TABLE `{$settle_table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` BIGINT UNSIGNED NOT NULL,
  `source_type` VARCHAR(20) NOT NULL DEFAULT 'self',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `payload` TEXT NULL,
  `create_time` INT NOT NULL DEFAULT 0,
  `update_time` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($sql);
        fwrite(STDOUT, "Created table {$settle_table}\n");
    } catch (Throwable $e) {
        fwrite(STDOUT, "Create table {$settle_table} failed: ".$e->getMessage()."\n");
    }
}

// 5) goods 增加供货/参与/来源/价池缓存字段
$goods_table = $prefix . 'goods';
if (!table_exists($pdo, $dbname, $goods_table)) {
    fwrite(STDOUT, "Table {$goods_table} not found, skip goods fields.\n");
} else {
    // supply_price
    if (!col_exists($pdo, $dbname, $goods_table, 'supply_price')) {
        try {
            $pdo->exec("ALTER TABLE `{$goods_table}` ADD COLUMN `supply_price` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `cost_price`");
            fwrite(STDOUT, "Added {$goods_table}.supply_price\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$goods_table}.supply_price failed: ".$e->getMessage()."\n");
        }
    }
    // lottery_participation
    if (!col_exists($pdo, $dbname, $goods_table, 'lottery_participation')) {
        try {
            $pdo->exec("ALTER TABLE `{$goods_table}` ADD COLUMN `lottery_participation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否参与抽奖' AFTER `promotion_addon`");
            fwrite(STDOUT, "Added {$goods_table}.lottery_participation\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$goods_table}.lottery_participation failed: ".$e->getMessage()."\n");
        }
    }
    // lottery_tier_ids (JSON字符串缓存)
    if (!col_exists($pdo, $dbname, $goods_table, 'lottery_tier_ids')) {
        try {
            $pdo->exec("ALTER TABLE `{$goods_table}` ADD COLUMN `lottery_tier_ids` TEXT NULL COMMENT '参与的价池ID集合(JSON数组字符串)' AFTER `lottery_participation`");
            fwrite(STDOUT, "Added {$goods_table}.lottery_tier_ids\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$goods_table}.lottery_tier_ids failed: ".$e->getMessage()."\n");
        }
    }
    // source_type
    if (!col_exists($pdo, $dbname, $goods_table, 'source_type')) {
        try {
            $pdo->exec("ALTER TABLE `{$goods_table}` ADD COLUMN `source_type` VARCHAR(20) NOT NULL DEFAULT 'merchant' COMMENT '商品来源: merchant|supplier' AFTER `supplier_id`");
            fwrite(STDOUT, "Added {$goods_table}.source_type\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$goods_table}.source_type failed: ".$e->getMessage()."\n");
        }
    }
}

// 6) 新建 settlement_amount（供应商结算明细表）
$settle_amount_table = $prefix . 'settlement_amount';
if (!table_exists($pdo, $dbname, $settle_amount_table)) {
    $sql = "CREATE TABLE `{$settle_amount_table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` BIGINT UNSIGNED NOT NULL,
  `site_id` INT NOT NULL DEFAULT 0,
  `supplier_id` INT NOT NULL DEFAULT 0,
  `goods_id` INT NOT NULL DEFAULT 0,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '抽奖金额/价档价',
  `supply_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `gross_profit` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `create_time` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_record` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    try {
        $pdo->exec($sql);
        fwrite(STDOUT, "Created table {$settle_amount_table}\n");
    } catch (Throwable $e) {
        fwrite(STDOUT, "Create table {$settle_amount_table} failed: ".$e->getMessage()."\n");
    }
}

// 7) lottery_board 增加 round_no 与 auto_reset_round（轮次与自动重置开关）
$board_table = $prefix . 'lottery_board';
if (!table_exists($pdo, $dbname, $board_table)) {
    fwrite(STDOUT, "Table {$board_table} not found, skip board round fields.\n");
} else {
    if (!col_exists($pdo, $dbname, $board_table, 'round_no')) {
        try {
            $pdo->exec("ALTER TABLE `{$board_table}` ADD COLUMN `round_no` INT NOT NULL DEFAULT 0 AFTER `update_time`");
            fwrite(STDOUT, "Added {$board_table}.round_no\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$board_table}.round_no failed: ".$e->getMessage()."\n");
        }
    }
    if (!col_exists($pdo, $dbname, $board_table, 'auto_reset_round')) {
        try {
            $pdo->exec("ALTER TABLE `{$board_table}` ADD COLUMN `auto_reset_round` TINYINT(1) NOT NULL DEFAULT 1 AFTER `round_no`");
            fwrite(STDOUT, "Added {$board_table}.auto_reset_round\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$board_table}.auto_reset_round failed: ".$e->getMessage()."\n");
        }
    }
}

// 8) lottery_slot 增加 round_qty（本轮可中次数）
if (!table_exists($pdo, $dbname, $slot_table)) {
    fwrite(STDOUT, "Table {$slot_table} not found, skip slot round_qty.\n");
} else {
    if (!col_exists($pdo, $dbname, $slot_table, 'round_qty')) {
        try {
            $pdo->exec("ALTER TABLE `{$slot_table}` ADD COLUMN `round_qty` INT NOT NULL DEFAULT 0 AFTER `inventory`");
            fwrite(STDOUT, "Added {$slot_table}.round_qty\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "Add {$slot_table}.round_qty failed: ".$e->getMessage()."\n");
        }
    }
}

// 9) 新增代理流水表 ns_agent_ledger（记录 agent/city 分润到账与筛选导出）
$ledger_table = $prefix . 'ns_agent_ledger';
try {
    if (!table_exists($pdo, $dbname, $ledger_table)) {
        $sql = "CREATE TABLE `{$ledger_table}` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `agent_id` INT NOT NULL DEFAULT 0 COMMENT '代理ID',
            `role` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '角色：agent|city',
            `record_id` INT NOT NULL DEFAULT 0 COMMENT '抽奖记录ID',
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '到账金额',
            `site_id` INT NOT NULL DEFAULT 0 COMMENT '站点ID',
            `device_id` INT NOT NULL DEFAULT 0 COMMENT '设备ID',
            `type` VARCHAR(20) NOT NULL DEFAULT 'profit' COMMENT '类型：profit等',
            `remark` TEXT NULL COMMENT '备注',
            `create_time` INT NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`),
            KEY `idx_agent` (`agent_id`),
            KEY `idx_role` (`role`),
            KEY `idx_record` (`record_id`),
            KEY `idx_site_time` (`site_id`,`create_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
        fwrite(STDOUT, "Created table {$ledger_table}\n");
    } else {
        fwrite(STDOUT, "Table {$ledger_table} already exists, skip creation.\n");
    }
} catch (Throwable $e) {
    fwrite(STDOUT, "Create {$ledger_table} failed: ".$e->getMessage()."\n");
}

fwrite(STDOUT, "Turntable upgrade done.\n");
exit(0);