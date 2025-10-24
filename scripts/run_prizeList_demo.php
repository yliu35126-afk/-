<?php
require __DIR__ . '/../vendor/autoload.php';

use addon\turntable\model\Lottery;

$params = [
    'site_id'   => 0,
    'device_sn' => 'DEMO-0001',
    'device_id' => 1,
    'board_id'  => 1,
];

$lottery = new Lottery();
$res = $lottery->prizeList($params);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";