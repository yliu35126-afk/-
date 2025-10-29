<?php
// 尝试向 ns_lottery_profit 插入一条示例数据，打印具体报错，定位字段不匹配
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

$record_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($record_id <= 0) { fwrite(STDERR, "请提供 record_id\n"); exit(1); }

try {
    $row = [
        'record_id'   => $record_id,
        'order_id'    => 0,
        'role'        => 'merchant',
        'target_id'   => 1,
        'amount'      => 1.23,
        'site_id'     => 0,
        'device_id'   => 1,
        'status'      => 'pending',
        'create_time' => time(),
    ];
    Db::name('ns_lottery_profit')->insert($row);
    echo "[OK] insert success\n";
} catch (\Throwable $e) {
    echo "[ERROR] ".$e->getMessage()."\n";
}