<?php
/**
 * 批量为盘ID=1填充16格“实物商品”奖品
 * 使用方法：
 * - 通过CLI执行：php public/tools/seed_board1_goods_slots.php
 * - 或浏览器访问：/public/tools/seed_board1_goods_slots.php
 *
 * 安全说明：本脚本为一次性运维脚本，不做权限校验。执行完可删除。
 */

// 数据库配置（取自 config/database.php）
$db = [
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'root',
    'pass' => '123456',
    'name' => '10-27',
    'charset' => 'utf8',
];

// 目标盘ID
$BOARD_ID = 1;

function respond($code, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => $code, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// 连接数据库
$mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
if ($mysqli->connect_errno) {
    respond(-1, '数据库连接失败: ' . $mysqli->connect_error);
}
$mysqli->set_charset($db['charset']);

// 检查盘是否存在
$resBoard = $mysqli->query('SELECT board_id, name FROM lottery_board WHERE board_id = ' . intval($BOARD_ID) . ' LIMIT 1');
$board = $resBoard ? $resBoard->fetch_assoc() : null;
if (!$board) {
    // 尝试创建盘ID=1（最小字段集）
    $now = time();
    $nameEsc = $mysqli->real_escape_string('盘ID1');
    $sqlCreate = sprintf("INSERT INTO lottery_board (board_id, site_id, name, status, round_control, create_time, update_time) VALUES (%d, %d, '%s', %d, '%s', %d, %d)", $BOARD_ID, 0, $nameEsc, 1, '', $now, $now);
    if (!$mysqli->query($sqlCreate)) {
        respond(-1, '创建盘ID=1失败：' . $mysqli->error);
    }
    // 重新读取
    $resBoard = $mysqli->query('SELECT board_id, name FROM lottery_board WHERE board_id = ' . intval($BOARD_ID) . ' LIMIT 1');
    $board = $resBoard ? $resBoard->fetch_assoc() : null;
}

// 取最多16个可用商品（如不足则循环使用）
$goods = [];
$res = $mysqli->query("SELECT goods_id, goods_name, goods_image FROM goods WHERE is_delete = 0 ORDER BY goods_id DESC LIMIT 16");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // 取第一张图
        $img = '';
        if (!empty($row['goods_image'])) {
            $parts = explode(',', $row['goods_image']);
            $img = $parts[0];
        }
        $goods[] = [
            'goods_id' => intval($row['goods_id']),
            'name'     => $row['goods_name'] ?: ('实物奖品' . $row['goods_id']),
            'img'      => $img,
        ];
    }
}
// 若无商品，仍可创建占位的实物奖（goods_id=0）
if (empty($goods)) {
    for ($i=1; $i<=16; $i++) {
        $goods[] = [ 'goods_id' => 0, 'name' => '实物奖品'.$i, 'img' => '' ];
    }
}

// 读取已存在的格子，按位置索引
$exists = [];
$res = $mysqli->query("SELECT slot_id, position FROM lottery_slot WHERE board_id = " . intval($BOARD_ID));
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $exists[intval($row['position'])] = intval($row['slot_id']);
    }
}

// 统一库存与本轮次数（round_qty）
$DEFAULT_INV = 10; // 每格默认库存/次数
$DEFAULT_WEIGHT = 1;
$now = time();

// 采用直接SQL以避免某些环境下prepare不可用问题

$summary = ['inserted' => [], 'updated' => []];

for ($pos = 0; $pos < 16; $pos++) {
    $g = $goods[$pos % count($goods)];
    $title = $g['name'] ?: ('实物奖品' . ($pos+1));
    $img   = $g['img'];
    $gid   = intval($g['goods_id']);
    $sku   = 0; // 可选：如需绑定SKU可改
    $val   = 0.0; // 面值：实物不使用

    $titleEsc = $mysqli->real_escape_string($title);
    $imgEsc   = $mysqli->real_escape_string($img);
    if (!isset($exists[$pos])) {
        // 插入
        $sql = sprintf(
            "INSERT INTO lottery_slot (board_id, position, prize_type, title, img, goods_id, sku_id, inventory, weight, value, create_time, update_time) VALUES (%d, %d, 'goods', '%s', '%s', %d, %d, %d, %d, %.2f, %d, %d)",
            $BOARD_ID, $pos, $titleEsc, $imgEsc, $gid, $sku, $DEFAULT_INV, $DEFAULT_WEIGHT, $val, $now, $now
        );
        if (!$mysqli->query($sql)) {
            respond(-1, '插入失败@pos=' . $pos . ': ' . $mysqli->error);
        }
        $summary['inserted'][] = ['position' => $pos, 'slot_id' => intval($mysqli->insert_id), 'goods_id' => $gid, 'title' => $title];
    } else {
        // 更新
        $slot_id = $exists[$pos];
        $sql = sprintf(
            "UPDATE lottery_slot SET prize_type='goods', title='%s', img='%s', goods_id=%d, sku_id=%d, inventory=%d, weight=%d, value=%.2f, update_time=%d WHERE slot_id=%d",
            $titleEsc, $imgEsc, $gid, $sku, $DEFAULT_INV, $DEFAULT_WEIGHT, $val, $now, $slot_id
        );
        if (!$mysqli->query($sql)) {
            respond(-1, '更新失败@pos=' . $pos . ': ' . $mysqli->error);
        }
        $summary['updated'][] = ['position' => $pos, 'slot_id' => $slot_id, 'goods_id' => $gid, 'title' => $title];
    }
}

$mysqli->close();
$mysqli->close();

respond(0, '盘ID=1 实物奖品16格配置完成', [
    'board' => [ 'board_id' => $BOARD_ID, 'name' => $board['name'] ?? '' ],
    'inserted' => $summary['inserted'],
    'updated'  => $summary['updated'],
]);

?>