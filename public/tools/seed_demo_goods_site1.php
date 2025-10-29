<?php
/**
 * 批量为 site_id=1 创建 16 个演示“实物商品”（含 1 个SKU），便于后台商品列表展示与抽奖格子绑定。
 * 使用：php public/tools/seed_demo_goods_site1.php
 */

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
if ($mysqli->connect_errno) { echo json_encode(['code'=>-1,'msg'=>$mysqli->connect_error]); exit; }
$mysqli->set_charset($db['charset']);

// 确认站点 1 存在
$res = $mysqli->query("SELECT site_id, site_type FROM site WHERE site_id=1 LIMIT 1");
if (!$res || !$res->fetch_assoc()) { echo json_encode(['code'=>-1,'msg'=>'site_id=1 不存在']); exit; }

$now = time();

$created = [];
for ($i=1; $i<=16; $i++) {
    $name = '演示实物商品 ' . $i;
    $nameEsc = $mysqli->real_escape_string($name);
    // 插入 goods（最小字段集）
    $sqlGoods = sprintf(
        "INSERT INTO goods (goods_name, goods_class, goods_attr_class, site_id, goods_state, verify_state, price, market_price, cost_price, goods_stock, is_virtual, virtual_indate, is_free_shipping, create_time, modify_time) VALUES ('%s', 1, 1, 1, 1, 1, 9.90, 9.90, 5.00, 100, 0, 1, 0, %d, %d)",
        $nameEsc, $now, $now
    );
    if (!$mysqli->query($sqlGoods)) { echo json_encode(['code'=>-1,'msg'=>'goods插入失败: '.$mysqli->error]); exit; }
    $goodsId = intval($mysqli->insert_id);

    // 插入 goods_sku（最小字段集）
    $sqlSku = sprintf(
        "INSERT INTO goods_sku (site_id, goods_id, sku_name, price, market_price, cost_price, discount_price, stock, goods_name, goods_state, verify_state, create_time, modify_time) VALUES (1, %d, '%s', 9.90, 9.90, 5.00, 9.90, 100, '%s', 1, 1, %d, %d)",
        $goodsId, $nameEsc, $nameEsc, $now, $now
    );
    if (!$mysqli->query($sqlSku)) { echo json_encode(['code'=>-1,'msg'=>'sku插入失败: '.$mysqli->error]); exit; }
    $skuId = intval($mysqli->insert_id);

    // 回写 goods.sku_id 与汇总库存价格（简单同步）
    $mysqli->query(sprintf("UPDATE goods SET sku_id=%d, price=9.90, market_price=9.90, cost_price=5.00, goods_stock=100, modify_time=%d WHERE goods_id=%d", $skuId, $now, $goodsId));

    $created[] = ['goods_id'=>$goodsId,'sku_id'=>$skuId,'name'=>$name];
}

echo json_encode(['code'=>0,'msg'=>'已创建16个演示实物商品（site_id=1）','created'=>$created], JSON_UNESCAPED_UNICODE);
?>