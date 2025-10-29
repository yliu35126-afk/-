<?php
// 轻量脚本：清理菜单缓存并刷新 device_turntable 的商家端菜单注册
// 仅影响“菜单显示/授权”，不影响路由与功能

require __DIR__ . '/../index.php';
try {
    \think\facade\Cache::tag('menu')->clear();

    $menu = new \app\model\system\Menu();
    // 刷新店铺端菜单（读取 addon/device_turntable/config/menu_shop.php）
    $menu->refreshMenu('shop', 'device_turntable');

    echo json_encode(['code' => 0, 'message' => 'ok']);
} catch (\Throwable $e) {
    echo json_encode(['code' => -1, 'message' => $e->getMessage()]);
}