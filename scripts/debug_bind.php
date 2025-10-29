<?php
// 输出设备的价档绑定与当前 tier 信息
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

$device_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($device_id <= 0) { fwrite(STDERR, "请提供 device_id\n"); exit(1); }

$bind = Db::name('device_price_bind')->where([ ['device_id','=',$device_id], ['status','=',1] ])->order('start_time desc')->select()->toArray();
$tiers = [];
foreach ($bind as $b) {
    $t = Db::name('lottery_price_tier')->where('tier_id', intval($b['tier_id']))->find();
    if ($t) $tiers[] = $t;
}
echo json_encode(['device_id'=>$device_id,'bind'=>$bind,'tiers'=>$tiers], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";