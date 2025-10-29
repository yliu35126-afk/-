<?php
/**
 * 将 site_id=1 的16个演示商品及其SKU、盘ID=1的16格统一设置占位图片。
 * 使用：php public/tools/set_demo_images_site1.php
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

$IMAGES = [
    // 使用项目内现有真实PNG资源（非占位）
    'upload/default/diy_view/btn_style_1.png',
    'upload/default/diy_view/btn_style_2.png',
    'upload/default/diy_view/crack_figure.png',
    'upload/default/diy_view/crack_figure_small.png',
    'upload/default/diy_view/fenxiao_market_cube_1.png',
    'upload/default/diy_view/fenxiao_market_cube_2.png',
    'upload/default/diy_view/fenxiao_market_cube_3.png',
    'upload/default/diy_view/fenxiao_market_gg.png',
    'upload/default/diy_view/fenxiao_market_gg2.png',
    'upload/default/diy_view/index_bargain_gg.png',
    'upload/default/diy_view/index_bg.png',
    'upload/default/diy_view/index_gg_1.png',
    'upload/default/diy_view/index_groupbuy_gg.png',
    'upload/default/diy_view/index_pintuan_gg.png',
    'upload/default/diy_view/index_pintuan_tips.png',
    'upload/default/diy_view/index_presale.png',
];
$BOARD_ID = 1;
$SITE_ID  = 1;

function out($code, $msg, $data = []) {
    echo json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// 生成同名缩略图（_small/_mid）；若GD不可用则直接复制为同名文件
function ensure_thumbs($relative_path, $sizes = ['small','mid']) {
    $root = dirname(__DIR__, 2); // 项目根目录
    $src = $root . DIRECTORY_SEPARATOR . str_replace(['\\','/'], DIRECTORY_SEPARATOR, $relative_path);
    if (!is_file($src)) return [false, '源文件不存在: '.$relative_path];

    $ext = pathinfo($src, PATHINFO_EXTENSION);
    $basename = substr($src, 0, -(strlen($ext)+1));
    $made = [];

    foreach ($sizes as $sz) {
        $dest = $basename . '_' . $sz . '.' . $ext;
        if (is_file($dest)) { $made[] = ['size'=>$sz,'path'=>$dest,'action'=>'skip']; continue; }

        // 优先尝试等比缩放，失败则回退为复制原图
        $ok = false; $err = '';
        try {
            if (function_exists('imagecreatefrompng') && strtolower($ext) === 'png') {
                $im = @imagecreatefrompng($src);
                if ($im) {
                    $w = imagesx($im); $h = imagesy($im);
                    // 约定尺寸：mid 300x300，small 150x150（与常规配置接近，非严格）
                    $targetW = ($sz === 'mid') ? 300 : 150;
                    $targetH = ($sz === 'mid') ? 300 : 150;
                    $canvas = imagecreatetruecolor($targetW, $targetH);
                    // 保持透明
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    imagecopyresampled($canvas, $im, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
                    $ok = imagepng($canvas, $dest);
                    imagedestroy($canvas); imagedestroy($im);
                }
            }
        } catch (\Throwable $t) { $err = $t->getMessage(); }

        if (!$ok) { // 兜底：直接复制
            $ok = @copy($src, $dest);
        }
        $made[] = ['size'=>$sz,'path'=>$dest,'action'=>$ok ? 'create' : 'fail','error'=>$ok?'' : $err];
    }
    return [true, $made];
}

$mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port']);
if ($mysqli->connect_errno) out(-1, '数据库连接失败: '.$mysqli->connect_error);
$mysqli->set_charset($db['charset']);

// 拿到16个“演示实物商品”ID（按goods_id升序），若不存在则尝试1-16区间
$goods_ids = [];
$res = $mysqli->query("SELECT goods_id FROM goods WHERE site_id = ".$SITE_ID." AND goods_name LIKE '演示实物商品 %' ORDER BY goods_id ASC LIMIT 16");
if ($res) {
    while ($row = $res->fetch_assoc()) { $goods_ids[] = intval($row['goods_id']); }
}
if (empty($goods_ids)) { for ($i=1; $i<=16; $i++) $goods_ids[] = $i; }

$ids_str = implode(',', array_map('intval', $goods_ids));
if ($ids_str === '') out(-1, '未找到可更新的演示商品ID');

$now = time();

// 先为所有PNG生成对应的_small/_mid文件，避免前端 util.img(size) 请求404
$thumb_report = [];
foreach ($IMAGES as $rp) {
    list($ok, $detail) = ensure_thumbs($rp, ['small','mid']);
    $thumb_report[] = ['src'=>$rp,'ok'=>$ok,'detail'=>$detail];
}

// 为每个商品分配不同PNG并更新 goods 与 sku
$updated_goods = [];
$resg = $mysqli->query("SELECT goods_id FROM goods WHERE goods_id IN (".$ids_str.") ORDER BY goods_id ASC");
if ($resg) {
    $idx = 0;
    while ($row = $resg->fetch_assoc()) {
        $gid = intval($row['goods_id']);
        $img = $IMAGES[$idx % count($IMAGES)];
        $imgEsc = $mysqli->real_escape_string($img);
        $ok1 = $mysqli->query("UPDATE goods SET goods_image='".$imgEsc."', modify_time=".$now." WHERE goods_id=".$gid);
        $ok2 = $mysqli->query("UPDATE goods_sku SET sku_image='".$imgEsc."', modify_time=".$now." WHERE goods_id=".$gid);
        if ($ok1 && $ok2) { $updated_goods[] = ['goods_id'=>$gid,'img'=>$img]; }
        $idx++;
    }
}

// 更新盘ID=1的16格图片（仅更新存在的格子）
$res = $mysqli->query("SELECT slot_id, position FROM lottery_slot WHERE board_id = ".intval($BOARD_ID)." ORDER BY position ASC");
$updated_slots = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sid = intval($row['slot_id']);
        $pos = intval($row['position']);
        $img = $IMAGES[$pos % count($IMAGES)];
        $imgEsc = $mysqli->real_escape_string($img);
        $ok  = $mysqli->query("UPDATE lottery_slot SET img='".$imgEsc."', update_time=".$now." WHERE slot_id=".$sid);
        if ($ok) $updated_slots[] = ['slot_id'=>$sid,'position'=>$pos,'img'=>$img];
    }
}

$mysqli->close();

out(0, '真实PNG图片已设置：商品/SKU/格子', [
    'goods_updated' => $updated_goods,
    'slot_updated_count' => count($updated_slots),
    'images_used_count' => count($IMAGES),
    'thumbs' => $thumb_report,
]);
?>