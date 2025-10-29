<?php
// Simple SQL importer for addon/turntable/data/install.sql using config/database.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

// Load DB config
$configFile = $root . 'config' . DIRECTORY_SEPARATOR . 'database.php';
// Provide a minimal shim for app()->getRuntimePath() used in config/database.php
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() {
                return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;
            }
        };
    }
}
if (!file_exists($configFile)) {
    fwrite(STDERR, "Database config not found: {$configFile}\n");
    exit(1);
}
$dbConfig = require $configFile;
$mysql = $dbConfig['connections']['mysql'] ?? null;
if (!$mysql) {
    fwrite(STDERR, "MySQL connection config missing in config/database.php\n");
    exit(1);
}

$host = $mysql['hostname'] ?? '127.0.0.1';
$port = $mysql['hostport'] ?? '3306';
$dbname = $mysql['database'] ?? '';
$user = $mysql['username'] ?? '';
$pass = $mysql['password'] ?? '';
$charset = $mysql['charset'] ?? 'utf8';
$prefix = $mysql['prefix'] ?? '';

if ($dbname === '') {
    fwrite(STDERR, "Database name is empty in config/database.php\n");
    exit(1);
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to connect database: " . $e->getMessage() . "\n");
    exit(1);
}

// Load SQL file
$sqlFile = $root . 'addon' . DIRECTORY_SEPARATOR . 'turntable' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'install.sql';
if (!file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read SQL file: {$sqlFile}\n");
    exit(1);
}

// Replace prefix placeholder
$sql = str_replace('{{prefix}}', $prefix, $sql);

// Normalize line endings
$sql = str_replace(["\r\n", "\r"], "\n", $sql);

// Remove BOM if present
if (substr($sql, 0, 3) === "\xEF\xBB\xBF") {
    $sql = substr($sql, 3);
}

// Split statements by semicolon at line end
$statements = [];
$buffer = '';
foreach (explode("\n", $sql) as $line) {
    $trim = trim($line);
    // Skip single-line comments
    if ($trim === '' || strpos($trim, '-- ') === 0) {
        continue;
    }
    $buffer .= $line . "\n";
    if (preg_match('/;\s*$/', $trim)) {
        $statements[] = trim($buffer);
        $buffer = '';
    }
}
if (trim($buffer) !== '') {
    $statements[] = trim($buffer);
}

// Execute
try {
    $pdo->exec("SET NAMES {$charset}");
} catch (Throwable $e) {
    // ignore
}

$ok = 0; $fail = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $ok++;
    } catch (Throwable $e) {
        $fail++;
        fwrite(STDERR, "Error executing statement: " . $e->getMessage() . "\nStatement:\n{$stmt}\n\n");
    }
}

echo "Executed statements: {$ok}, Failed: {$fail}\n";
exit($fail > 0 ? 2 : 0);