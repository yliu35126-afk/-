<?php
// PHP 内置服务器路由重写：除静态资源外，全部交由 public/index.php 处理
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
// 静态资源直接返回（适配常见扩展名）
if (preg_match('/\.(?:png|jpg|jpeg|gif|svg|css|js|ico|woff|woff2|ttf|map)$/i', $uri)) {
    return false;
}
// 统一入口
require __DIR__ . '/public/index.php';