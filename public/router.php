<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Router for PHP built-in server with project-root docroot.
// | Ensures /install.php and root-level static assets resolve correctly.
// | Adds mapping for /app/... view public assets when docroot is /public.
// +----------------------------------------------------------------------

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
$fullPath = $docRoot . $path;
$projectRoot = dirname(__DIR__);

// Serve existing files directly from current document root
if ($path !== '/' && $docRoot && is_file($fullPath)) {
    return false; // Let the built-in server handle static file
}

// NOTE: Keep .html routes as-is to avoid redirect loops with framework-level redirects.
// Previous normalization removed to maintain compatibility with pages like /shop/login/login.html.

// Handle Vite dev client requests to avoid ThinkPHP routing errors
if (preg_match('#^/@vite/#i', $path)) {
    header('Content-Type: application/javascript');
    echo "// Stubbed Vite dev client for production\n";
    echo "export {};\n";
    return true;
}

// API路径兼容重写：将 /index.php/api/device/probability/update → /index.php/api/device/probabilityUpdate
if (preg_match('#^/index\.php/api/device/probability/update#i', $path)) {
    $rewritten = preg_replace('#^/index\.php/api/device/probability/update#i', '/index.php/api/device/probabilityUpdate', $path);
    $_SERVER['REQUEST_URI'] = $rewritten;
    $path = $rewritten;
}

// Defer framework front controller to final fallback after static mappings.

// NEW: Alias /static/layui/** -> /static/ext/layui/** for compatibility
if (preg_match('#^/static/layui/(.+)$#i', $path)) {
    $alias = '/static/ext/layui/' . preg_replace('#^/static/layui/#i', '', $path);
    $fsPath = __DIR__ . $alias; // __DIR__ is public directory
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'css': header('Content-Type: text/css'); break;
            case 'js': header('Content-Type: application/javascript'); break;
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'woff': header('Content-Type: font/woff'); break;
            case 'woff2': header('Content-Type: font/woff2'); break;
            case 'ttf': header('Content-Type: font/ttf'); break;
            case 'eot': header('Content-Type: application/vnd.ms-fontobject'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        readfile($fsPath);
        return true;
    }
}

// NEW: Generic static asset mapper for /static/** to strengthen dev-server compatibility
if (preg_match('#^/static/(.+)$#i', $path)) {
    $fsPath = __DIR__ . $path; // serve from public/static
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'css': header('Content-Type: text/css'); break;
            case 'js': header('Content-Type: application/javascript'); break;
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'woff': header('Content-Type: font/woff'); break;
            case 'woff2': header('Content-Type: font/woff2'); break;
            case 'ttf': header('Content-Type: font/ttf'); break;
            case 'eot': header('Content-Type: application/vnd.ms-fontobject'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        readfile($fsPath);
        return true;
    }
}

// Map /app/{module}/view/public/{type}/... to project filesystem
if (preg_match('#^/app/(admin|shop)/view/public/(css|js|img|fonts)/(.+)$#i', $path)) {
    $fsPath = $projectRoot . $path;
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'css': header('Content-Type: text/css'); break;
            case 'js': header('Content-Type: application/javascript'); break;
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'woff': header('Content-Type: font/woff'); break;
            case 'woff2': header('Content-Type: font/woff2'); break;
            case 'ttf': header('Content-Type: font/ttf'); break;
            case 'eot': header('Content-Type: application/vnd.ms-fontobject'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        $length = @filesize($fsPath);
        if ($length !== false) { header('Content-Length: ' . $length); }
        readfile($fsPath);
        return true;
    }
}

// Alias mapping: /{module}/view/public/... -> /app/{module}/view/public/...
if (preg_match('#^/(admin|shop)/view/public/(css|js|img|fonts)/(.+)$#i', $path)) {
    $fsPath = $projectRoot . '/app/' . preg_replace('#^/(admin|shop)/#i', '$1/', $path);
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'css': header('Content-Type: text/css'); break;
            case 'js': header('Content-Type: application/javascript'); break;
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'woff': header('Content-Type: font/woff'); break;
            case 'woff2': header('Content-Type: font/woff2'); break;
            case 'ttf': header('Content-Type: font/ttf'); break;
            case 'eot': header('Content-Type: application/vnd.ms-fontobject'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        $length = @filesize($fsPath);
        if ($length !== false) { header('Content-Length: ' . $length); }
        readfile($fsPath);
        return true;
    }
}

// Map /upload/** from project root and provide fallback for missing defaults
if (preg_match('#^/upload/(.+)$#i', $path)) {
    $fsPath = $projectRoot . $path;
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        readfile($fsPath);
        return true;
    }
    // Fallback: default no_winning image
    if (preg_match('#^/upload/uniapp/game/no_winning\.png$#i', $path)) {
        $fallback = $projectRoot . '/addon/turntable/icon.png';
        if (is_file($fallback)) {
            header('Content-Type: image/png');
            readfile($fallback);
            return true;
        }
    }
}

// NEW: Map /addon/** static assets from project root (icons, images)
if (preg_match('#^/addon/(.+)$#i', $path)) {
    $fsPath = $projectRoot . $path;
    if (is_file($fsPath)) {
        $ext = strtolower(pathinfo($fsPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png': header('Content-Type: image/png'); break;
            case 'jpg':
            case 'jpeg': header('Content-Type: image/jpeg'); break;
            case 'gif': header('Content-Type: image/gif'); break;
            case 'svg': header('Content-Type: image/svg+xml'); break;
            case 'webp': header('Content-Type: image/webp'); break;
            case 'css': header('Content-Type: text/css'); break;
            case 'js': header('Content-Type: application/javascript'); break;
            default: header('Content-Type: application/octet-stream'); break;
        }
        readfile($fsPath);
        return true;
    }
}

// Allow /install.php even if docroot is set to public
$installEntry = $projectRoot . DIRECTORY_SEPARATOR . 'install.php';
if ($path === '/install.php' && is_file($installEntry)) {
    require $installEntry;
    return true;
}

// Fallback to framework front controller
require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
