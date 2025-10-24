<?php
// Router for PHP built-in server compatible with PHP 7.x
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$docroot = __DIR__ . '/public';
$docrootReal = realpath($docroot);
$target = realpath($docroot . $uri);

// If the requested file exists under public, let server handle it
if ($uri !== '/' && $target && $docrootReal && strpos($target, $docrootReal) === 0 && is_file($target)) {
    return false; // serve the static file
}

// Delegate to public/router.php for advanced asset aliases and front controller
require $docroot . '/router.php';