<?php
// 模拟 TurntableSettlement 计算，打印分润行（不写库）
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

$record_id = isset($argv[1]) ? intval($argv[1]) : 0;
if ($record_id <= 0) { fwrite(STDERR, "请提供 record_id\n"); exit(1); }

$record = Db::name('lottery_record')->where('record_id', $record_id)->find();
if (!$record) { fwrite(STDERR, "record 不存在\n"); exit(1); }

$site_id  = intval($record['site_id'] ?? 0);
$goods_id = intval($record['goods_id'] ?? 0);
$tier_id  = intval($record['tier_id'] ?? 0);
$amount   = floatval($record['amount'] ?? 0);
$device_id= intval($record['device_id'] ?? 0);

$tier = $tier_id ? Db::name('lottery_price_tier')->where([['tier_id','=',$tier_id]])->find() : [];
if (empty($tier) && $device_id > 0) {
    $bind = Db::name('device_price_bind')->where([ ['device_id','=',$device_id], ['status','=',1] ])->order('start_time desc')->find();
    $fallback_tid = intval($bind['tier_id'] ?? 0);
    if ($fallback_tid > 0) { $tier = Db::name('lottery_price_tier')->where('tier_id',$fallback_tid)->find() ?: []; }
}
$profit_cfg = [];
if (!empty($tier)) { $profit_cfg = json_decode($tier['profit_json'] ?? '{}', true) ?: []; }
if ($amount <= 0 && !empty($tier)) { $tier_price = isset($tier['price']) ? floatval($tier['price']) : 0.0; if ($tier_price > 0) { $amount = $tier_price; } }

$supply_price = 0.0; $supplier_id = 0;
if ($goods_id) {
    $goods = Db::name('goods')->where([['goods_id','=',$goods_id]])->field('supply_price,cost_price,supplier_id')->find();
    if (!empty($goods)) { $supplier_id = intval($goods['supplier_id'] ?? 0); $sp = floatval($goods['supply_price'] ?? 0); $cp = floatval($goods['cost_price'] ?? 0); $supply_price = $sp > 0 ? $sp : $cp; }
}
$gross_profit = max(round($amount - $supply_price, 2), 0);

$values = array_values($profit_cfg ?: []);
$sumRates = 0; $maxVal = 0; $allNumeric = true;
foreach ($values as $v) { if (!is_numeric($v)) { $allNumeric = false; } $val = floatval($v); $sumRates += $val; if ($val > $maxVal) $maxVal = $val; }
$treatAsRate = ($allNumeric && $maxVal <= 1.000001 && $sumRates <= 1.000001);

$platform_money = 0; $supplier_money = 0; $promoter_money = 0; $installer_money = 0; $owner_money = 0; $merchant_money = 0; $agent_money = 0; $member_money = 0; $city_money = 0;
$map = ['platform'=>'platform_money','supplier'=>'supplier_money','promoter'=>'promoter_money','installer'=>'installer_money','owner'=>'owner_money','merchant'=>'merchant_money','shop'=>'merchant_money','agent'=>'agent_money','member'=>'member_money','city'=>'city_money'];
foreach ($map as $k=>$col) { if (isset($profit_cfg[$k])) { $num = floatval($profit_cfg[$k]); ${$col} = $treatAsRate ? round($gross_profit * $num, 2) : round($num, 2); } }

$sumShare = $platform_money + $supplier_money + $promoter_money + $installer_money + $owner_money + $merchant_money + $agent_money + $member_money + $city_money;
if ($sumShare > $gross_profit) { $scale = $gross_profit > 0 ? ($gross_profit / $sumShare) : 0; foreach (['platform_money','supplier_money','promoter_money','installer_money','owner_money','merchant_money','agent_money','member_money','city_money'] as $col) { ${$col} = round(${$col} * $scale, 2); } }
$sumShare = $platform_money + $supplier_money + $promoter_money + $installer_money + $owner_money + $merchant_money + $agent_money + $member_money + $city_money;
if ($sumShare < $gross_profit) { $platform_money = round($platform_money + ($gross_profit - $sumShare), 2); }

echo json_encode([
  'record_id'=>$record_id,
  'amount'=>$amount,
  'supply_price'=>$supply_price,
  'gross_profit'=>$gross_profit,
  'profit_cfg'=>$profit_cfg,
  'calc'=>compact('platform_money','supplier_money','promoter_money','installer_money','owner_money','merchant_money','agent_money','member_money','city_money')
], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), "\n";