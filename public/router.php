<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Router for PHP built-in server with project-root docroot.
// | Ensures /install.php and root-level static assets resolve correctly.
// +----------------------------------------------------------------------

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', DIRECTORY_SEPARATOR);
$fullPath = $docRoot . $path;

// Serve existing files directly from current document root
if ($path !== '/' && $docRoot && is_file($fullPath)) {
    return false; // Let the built-in server handle static file
}

// Allow /install.php even if docroot is set to public
$installEntry = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'install.php';
if ($path === '/install.php' && is_file($installEntry)) {
    require $installEntry;
    return true;
}

// Fallback to framework front controller
require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
