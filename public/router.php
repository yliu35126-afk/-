<?php
// +----------------------------------------------------------------------
// | ThinkPHP Development Server Router
// +----------------------------------------------------------------------
// - Serve existing static files directly
// - Otherwise, bootstrap the app via index.php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    // Let the built-in server serve the requested static file
    return false;
}

// Fallback to framework entry
require __DIR__ . '/index.php';
