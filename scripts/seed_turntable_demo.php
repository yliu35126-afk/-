<?php
// 为转盘插件创建演示数据：1个盘、16个格子、1个价档、1个设备及绑定
// 多次运行具备幂等：按特定标识查重后才插入

if (!function_exists('app')) {
    function app() { return new class { public function getRuntimePath(){ return __DIR__.'/../runtime/'; } }; }
}

$root = realpath(__DIR__ . '/..');
$cfg  = include $root . '/config/database.php';
$dbc  = $cfg['connections'][$cfg['default']] ?? null;
if (!$dbc) { fwrite(STDERR, "无法读取数据库配置\n"); exit(1); }

$prefix = $dbc['prefix'] ?? '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $dbc['hostname'], $dbc['hostport'], $dbc['database'], $dbc['charset'] ?? 'utf8');

try { $pdo = new PDO($dsn, $dbc['username'], $dbc['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); }
catch (Throwable $e) { fwrite(STDERR, '数据库连接失败: '.$e->getMessage()."\n"); exit(1); }

$now = time();
$siteId = 0; // 演示站点ID，系统多站点可按需调整

// 1) 抽奖盘（按唯一名称查重）
$boardName = '演示抽奖盘';
$boardId = null;
$stmt = $pdo->prepare("SELECT board_id FROM {$prefix}lottery_board WHERE name = ? LIMIT 1");
$stmt->execute([$boardName]);
$boardId = $stmt->fetchColumn();
if (!$boardId) {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}lottery_board (site_id, name, status, round_control, create_time, update_time) VALUES (?, ?, 1, '', ?, ?)");
    $stmt->execute([$siteId, $boardName, $now, $now]);
    $boardId = (int)$pdo->lastInsertId();
}

// 2) 价档（按名称查重）
$tierTitle = '默认价档（演示）';
$tierId = null;
$stmt = $pdo->prepare("SELECT tier_id FROM {$prefix}lottery_price_tier WHERE title = ? LIMIT 1");
$stmt->execute([$tierTitle]);
$tierId = $stmt->fetchColumn();
if (!$tierId) {
    $profitJson = json_encode([
        'platform' => 0.1,
        'supplier' => 0.2,
        'promoter' => 0.2,
        'installer' => 0.1,
        'owner'    => 0.4,
    ], JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO {$prefix}lottery_price_tier (site_id, title, price, status, profit_json, create_time, update_time) VALUES (?, ?, 1.00, 1, ?, ?, ?)");
    $stmt->execute([$siteId, $tierTitle, $profitJson, $now, $now]);
    $tierId = (int)$pdo->lastInsertId();
}

// 3) 设备（演示设备，按SN查重）
$deviceSn = 'DEMO-0001';
$deviceId = null;
$stmt = $pdo->prepare("SELECT device_id FROM {$prefix}device_info WHERE device_sn = ? LIMIT 1");
$stmt->execute([$deviceSn]);
$deviceId = $stmt->fetchColumn();
if (!$deviceId) {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}device_info (device_sn, site_id, owner_id, installer_id, supplier_id, promoter_id, agent_code, board_id, status, create_time, update_time) VALUES (?, ?, 0, 0, 0, 0, '', ?, 1, ?, ?)");
    $stmt->execute([$deviceSn, $siteId, $boardId, $now, $now]);
    $deviceId = (int)$pdo->lastInsertId();
}

// 4) 设备价档绑定（查重：device_id + tier_id + start_time）
$start = $now - 60; $end = 0; // 长期有效
$stmt = $pdo->prepare("SELECT id FROM {$prefix}device_price_bind WHERE device_id=? AND tier_id=? AND start_time=? LIMIT 1");
$stmt->execute([$deviceId, $tierId, $start]);
$bindId = $stmt->fetchColumn();
if (!$bindId) {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}device_price_bind (device_id, tier_id, start_time, end_time, status, create_time) VALUES (?, ?, ?, ?, 1, ?)");
    $stmt->execute([$deviceId, $tierId, $start, $end, $now]);
}

// 5) 抽奖格子：0-15，若不存在则批量插入
$stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}lottery_slot WHERE board_id = ?");
$stmt->execute([$boardId]);
$slotCount = (int)$stmt->fetchColumn();
if ($slotCount < 16) {
    // 先清理残缺数据，确保16格
    $pdo->prepare("DELETE FROM {$prefix}lottery_slot WHERE board_id = ?")->execute([$boardId]);
    $insert = $pdo->prepare("INSERT INTO {$prefix}lottery_slot (board_id, position, prize_type, title, img, goods_id, sku_id, inventory, weight, value, create_time, update_time) VALUES (?, ?, ?, ?, '', 0, 0, 0, ?, ?, ?, ?)");
    // 设计一些基础奖项（不依赖商品/优惠券数据，便于快速验证）
    // 0:谢谢参与, 1:积分10, 2:余额0.5, 3:谢谢参与 ...
    $defs = [
        ['thanks','谢谢参与',0,  0],
        ['point','积分10',   10, 10],
        ['balance','余额0.5',0.5,8],
        ['thanks','谢谢参与',0,  0],
        ['point','积分20',   20, 6],
        ['thanks','谢谢参与',0,  0],
        ['balance','余额1.0',1.0,4],
        ['thanks','谢谢参与',0,  0],
        ['point','积分50',   50, 2],
        ['thanks','谢谢参与',0,  0],
        ['balance','余额2.0',2.0,2],
        ['thanks','谢谢参与',0,  0],
        ['point','积分100',  100,1],
        ['thanks','谢谢参与',0,  0],
        ['balance','余额5.0',5.0,1],
        ['thanks','谢谢参与',0,  0],
    ];
    for ($pos=0; $pos<16; $pos++) {
        [$ptype,$title,$val,$w] = $defs[$pos];
        $insert->execute([$boardId, $pos, $ptype, $title, $w, $val, $now, $now]);
    }
}

// 汇总输出
$out = [
    'database' => $dbc['database'],
    'prefix'   => $prefix,
    'board_id' => (int)$boardId,
    'tier_id'  => (int)$tierId,
    'device_id'=> (int)$deviceId,
    'slots'    => 16,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";