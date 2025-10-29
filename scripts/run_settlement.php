<?php
// 手动触发 TurntableSettlement 事件处理，便于在未注册事件或插件未启用时联调
// 用法：php scripts/run_settlement.php 5

use addon\turntable\event\TurntableSettlement;

// 引导 ThinkPHP 应用，确保 Db 门面与事件系统可用
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();
// 直接引入事件类文件，确保类存在（即便事件系统可动态加载）
require_once __DIR__ . '/../addon/turntable/event/TurntableSettlement.php';

// 简易加载框架环境（若不可用，事件内部使用 Db 静态门面仍有效）
if (!function_exists('app')) {
    function app() { return new class { public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; }
}

$recordId = isset($argv[1]) ? intval($argv[1]) : 0;
if ($recordId <= 0) { fwrite(STDERR, "请提供 record_id\n"); exit(1); }

try {
    // 通过事件系统触发，若事件未注册则直接调用处理器兜底
    $ok = false;
    try {
        if (function_exists('event')) {
            event('turntable_settlement', ['record_id' => $recordId]);
            $ok = true;
        }
    } catch (Throwable $e) {}
    if (!$ok) {
        $handler = new TurntableSettlement();
        $ok = $handler->handle(['record_id' => $recordId]);
    }
    echo json_encode(['ok' => $ok, 'record_id' => $recordId], JSON_UNESCAPED_UNICODE), "\n";
} catch (Throwable $e) {
    fwrite(STDERR, '执行失败: '.$e->getMessage()."\n");
    exit(1);
}