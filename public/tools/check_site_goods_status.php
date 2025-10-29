<?php
// 简查站点与商品数量，帮助定位 site_id 与是否有商品
header('Content-Type: application/json; charset=utf-8');

$db = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '123456',
    'name' => '10-27',
    'charset' => 'utf8',
];

$mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
if ($mysqli->connect_errno) {
    echo json_encode(['ok'=>false,'error'=>$mysqli->connect_error], JSON_UNESCAPED_UNICODE); exit;
}
$mysqli->set_charset($db['charset']);

$sites = [];
$res = $mysqli->query('SELECT site_id, site_type FROM site ORDER BY site_id ASC');
if ($res) { while ($row = $res->fetch_assoc()) { $sites[] = $row; } }

$counts = [];
foreach ($sites as $s) {
    $sid = intval($s['site_id']);
    $c1 = $mysqli->query('SELECT COUNT(*) c FROM goods WHERE site_id='.$sid)->fetch_assoc()['c'] ?? 0;
    $c2 = $mysqli->query('SELECT COUNT(*) c FROM goods_sku WHERE site_id='.$sid)->fetch_assoc()['c'] ?? 0;
    $counts[$sid] = ['goods'=>intval($c1),'sku'=>intval($c2)];
}

echo json_encode(['ok'=>true,'sites'=>$sites,'counts'=>$counts], JSON_UNESCAPED_UNICODE);
?>