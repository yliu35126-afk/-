<?php
// 轻量脚本：清理菜单缓存并刷新 device_turntable 的后台菜单注册
// 仅影响“菜单显示”，不影响路由与功能

// 通过 Web 入口完成框架初始化，确保数据库与缓存可用
require __DIR__ . '/../index.php';
try {
    // 清理菜单相关缓存
    \think\facade\Cache::tag('menu')->clear();

    // 刷新指定插件的后台菜单（读取 addon/device_turntable/config/menu_admin.php）
    $menu = new \app\model\system\Menu();
    $menu->refreshMenu('admin', 'device_turntable');

    echo json_encode(['code' => 0, 'message' => 'ok']);
} catch (\Throwable $e) {
    echo json_encode(['code' => -1, 'message' => $e->getMessage()]);
}