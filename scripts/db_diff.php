<?php
// 数据库差异报告生成脚本
// 用法：php scripts/db_diff.php [sql_path] [--apply]
// 默认对比 addon/turntable/data/install.sql 中定义的表结构

// 兼容未引导 ThinkPHP 的 app() 函数，仅用于读取配置文件
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() { return __DIR__ . '/../runtime/'; }
        };
    }
}

function readDatabaseConfig() {
    $cfgPath = __DIR__ . '/../config/database.php';
    if (!file_exists($cfgPath)) {
        throw new RuntimeException('数据库配置文件不存在: ' . $cfgPath);
    }
    $cfg = require $cfgPath;
    $default = $cfg['default'] ?? 'mysql';
    $db = $cfg['connections'][$default] ?? [];
    if (!$db) throw new RuntimeException('未找到默认数据库连接配置');
    return $db;
}

function connectPdo(array $db): PDO {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['hostname'] ?? '127.0.0.1',
        $db['hostport'] ?? '3306',
        $db['database'] ?? '',
        $db['charset'] ?? 'utf8'
    );
    $pdo = new PDO($dsn, $db['username'] ?? 'root', $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function readSql(string $path): string {
    if (!file_exists($path)) throw new RuntimeException('SQL文件不存在: ' . $path);
    return file_get_contents($path);
}

function parseCreateTables(string $sql, string $prefix = ''): array {
    // 提取每个 CREATE TABLE 语句块
    $tables = [];
    $pattern = '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?\{\{prefix\}\}([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*ENGINE/si';
    if (preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $rawTable = $m[1];
            $tableName = ($prefix ? $prefix : '') . $rawTable;
            $body = $m[2];
            // 提取列名，忽略 KEY/PRIMARY/UNIQUE
            $cols = [];
            $lines = preg_split('/\r?\n/', $body);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || stripos($line, 'KEY ') === 0 || stripos($line, 'PRIMARY KEY') === 0 || stripos($line, 'UNIQUE KEY') === 0) {
                    continue;
                }
                if (preg_match('/^`([a-zA-Z0-9_]+)`\s+/i', $line, $cm)) {
                    $cols[] = $cm[1];
                }
            }
            $tables[$tableName] = [
                'raw' => $m[0],
                'columns' => $cols,
            ];
        }
    }
    return $tables;
}

function tableExists(PDO $pdo, string $dbName, string $table): bool {
    $stmt = $pdo->prepare('SELECT COUNT(1) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $stmt->execute([$dbName, $table]);
    $row = $stmt->fetch();
    return ($row && intval($row['c']) > 0);
}

function getColumns(PDO $pdo, string $dbName, string $table): array {
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
    $stmt->execute([$dbName, $table]);
    return array_map(function($r){ return $r['COLUMN_NAME']; }, $stmt->fetchAll());
}

function applyMissingTables(PDO $pdo, string $dbName, array $missingTables, string $prefix, string $logPath) {
    if (!$missingTables) return;
    $log = fopen($logPath, 'a');
    fwrite($log, "\n==== 执行DDL开始: " . date('Y-m-d H:i:s') . "\n");
    foreach ($missingTables as $t => $raw) {
      $ddl = str_replace('{{prefix}}', $prefix, $raw) . ';';
      fwrite($log, "\n-- 执行: {$t}\n{$ddl}\n");
      try {
        $pdo->exec($ddl);
        fwrite($log, "结果: OK\n");
        echo "[执行] {$t} -> OK" . PHP_EOL;
      } catch (Throwable $e) {
        fwrite($log, "结果: FAIL - " . $e->getMessage() . "\n");
        fwrite(STDERR, "[执行失败] {$t} -> " . $e->getMessage() . PHP_EOL);
      }
    }
    fwrite($log, "\n==== 执行DDL结束\n");
    fclose($log);
}

function main(array $argv) {
    $db = readDatabaseConfig();
    $pdo = connectPdo($db);
    $prefix = $db['prefix'] ?? '';
    $dbName = $db['database'] ?? '';
    $sqlPath = isset($argv[1]) && substr($argv[1],0,2) !== '--' ? $argv[1] : (__DIR__ . '/../addon/turntable/data/install.sql');
    $doApply = in_array('--apply', $argv, true);
    $sql = readSql($sqlPath);
    $tables = parseCreateTables($sql, $prefix);

    $missingTables = [];
    $columnDiffs = [];

    foreach ($tables as $table => $info) {
        if (!tableExists($pdo, $dbName, $table)) {
            $missingTables[$table] = $info['raw'];
            continue;
        }
        $existing = getColumns($pdo, $dbName, $table);
        $need = $info['columns'];
        $missingCols = array_values(array_diff($need, $existing));
        $extraCols = array_values(array_diff($existing, $need));
        if ($missingCols || $extraCols) {
            $columnDiffs[$table] = [
                'missing' => $missingCols,
                'extra' => $extraCols,
            ];
        }
    }

    echo "\n==== 数据库差异报告 ====" . PHP_EOL;
    echo "数据库: {$dbName}\n前缀: '{$prefix}'\n基准SQL: {$sqlPath}\n";

    if ($missingTables) {
        echo "\n【缺失数据表】" . PHP_EOL;
        foreach ($missingTables as $t => $raw) {
            echo "- {$t}" . PHP_EOL;
        }
    } else {
        echo "\n【缺失数据表】无" . PHP_EOL;
    }

    if ($columnDiffs) {
        echo "\n【字段差异】" . PHP_EOL;
        foreach ($columnDiffs as $t => $diff) {
            echo "- {$t}\n  缺失字段: " . (implode(',', $diff['missing']) ?: '无') . "\n  冗余字段: " . (implode(',', $diff['extra']) ?: '无') . "\n";
        }
    } else {
        echo "\n【字段差异】无" . PHP_EOL;
    }

    $logPath = __DIR__ . '/db_diff.log';
    if ($doApply && $missingTables) {
        echo "\n【执行缺失表DDL】写入日志: {$logPath}\n";
        applyMissingTables($pdo, $dbName, $missingTables, $prefix, $logPath);
    } else if ($doApply) {
        echo "\n【执行缺失表DDL】无缺失项，跳过。\n";
    }

    if ($missingTables) {
        echo "\n【建议DDL补齐（预览）】" . PHP_EOL;
        foreach ($missingTables as $t => $raw) {
            $ddl = str_replace('{{prefix}}', $prefix, $raw) . ";";
            echo "\n-- {$t}\n{$ddl}\n";
        }
    }

    echo "\n报告生成完成。" . PHP_EOL;
}

try {
    main($argv);
} catch (Throwable $e) {
    fwrite(STDERR, '[错误] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}