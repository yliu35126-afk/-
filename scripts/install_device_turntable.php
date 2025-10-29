<?php
// 轻量安装 device_turntable 到 addon 表（不刷新菜单），用于修复路由 404
// 读取 config/database.php 获取连接配置
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

// 兼容在 CLI 下直接 include 配置文件时 app() 助手缺失的问题
if (!function_exists('app')) {
    function app() {
        return new class {
            public function getRootPath() {
                return realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR;
            }
            public function getRuntimePath() {
                return realpath(__DIR__ . '/../runtime') . DIRECTORY_SEPARATOR;
            }
        };
    }
}

function fail($msg) { fwrite(STDERR, $msg."\n"); exit(1); }

// 读取数据库配置
$cfgPath = __DIR__ . '/../config/database.php';
if (!file_exists($cfgPath)) fail('数据库配置文件不存在: ' . $cfgPath);
$cfg = require $cfgPath;
$default = $cfg['default'] ?? 'mysql';
$dbc = $cfg['connections'][$default] ?? null;
if (!$dbc) fail('未找到默认数据库连接配置');

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
    fail('数据库连接失败: ' . $e->getMessage());
}

// 读取插件 info
$infoPath = __DIR__ . '/../addon/device_turntable/config/info.php';
if (!file_exists($infoPath)) fail('插件 info 文件不存在: ' . $infoPath);
$info = require $infoPath;
if (!is_array($info) || empty($info['name'])) fail('插件 info 格式不正确');

// 修正 icon 路径
$info['icon'] = 'addon/device_turntable/icon.png';
// 写入创建时间
$info['create_time'] = time();

$prefix = $dbc['prefix'] ?? '';
$table = $prefix . 'addon';

// 已存在检查
$stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE name = ?");
$stmt->execute([$info['name']]);
$exists = (int)$stmt->fetchColumn() > 0;
if ($exists) {
    echo "已存在，无需重复写入\n";
    exit(0);
}

// 组装写入
$cols = array_keys($info);
$params = array_fill(0, count($cols), '?');
$sql = 'INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $params) . ')';
$ok = $pdo->prepare($sql)->execute(array_values($info));
if (!$ok) fail('写入 addon 记录失败');

echo "OK\n";