<?php
// 将 addon/turntable/data/install.sql 导入到当前数据库（自动替换 {{prefix}}）

// 兼容直接 include 配置文件时的 app() 辅助函数缺失问题
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRuntimePath() { return __DIR__ . '/../runtime/'; }
        };
    }
}

$root = realpath(__DIR__ . '/..');
$sqlFile = $root . '/addon/turntable/data/install.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "SQL 文件不存在: $sqlFile\n");
    exit(1);
}

$cfg = include $root . '/config/database.php';
$dbc = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) {
    fwrite(STDERR, "无法读取数据库配置\n");
    exit(1);
}

$prefix = $dbc['prefix'] ?? '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbc['hostname'], $dbc['hostport'], $dbc['database'], $dbc['charset'] ?? 'utf8');

try {
    $pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, '数据库连接失败: ' . $e->getMessage() . "\n");
    exit(1);
}

$raw = file_get_contents($sqlFile);
// 去除 BOM
$raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
// 替换表前缀模板
$raw = str_replace('{{prefix}}', $prefix, $raw);

// 移除注释：--、#、/* ... */
$noBlock = preg_replace('#/\*.*?\*/#s', '', $raw);
$lines = preg_split('/\r?\n/', $noBlock);
$clean = [];
foreach ($lines as $line) {
    $trim = ltrim($line);
    if ($trim === '' || strpos($trim, '--') === 0 || strpos($trim, '#') === 0) {
        continue;
    }
    $clean[] = $line;
}
$cleanSql = trim(implode("\n", $clean));

// 按分号切分语句（简单场景足够，当前文件均为建表与 SET NAMES）
$stmts = array_filter(array_map('trim', explode(';', $cleanSql)), function($s){ return $s !== ''; });

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$succ = 0; $fail = 0; $errors = [];
foreach ($stmts as $i => $sql) {
    try {
        $pdo->exec($sql);
        $succ++;
    } catch (Throwable $e) {
        $fail++;
        $errors[] = [
            'index' => $i,
            'snippet' => mb_substr(preg_replace('/\s+/', ' ', $sql), 0, 120),
            'error' => $e->getMessage(),
        ];
    }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$result = [
    'ok' => $fail === 0,
    'database' => $dbc['database'],
    'prefix' => $prefix,
    'executed' => $succ + $fail,
    'success' => $succ,
    'failed' => $fail,
    'errors' => $errors,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";