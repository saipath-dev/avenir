<?php
// php -S 127.0.0.1:8080 router.php  — serves the test copy the way Apache does at http://localhost/avenir/ (rewrite to public/)
$root = __DIR__ . '/tc/avenir/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$rel = preg_replace('#^/avenir#', '', $uri);
if ($rel !== '' && $rel !== '/' && is_file($root . $rel) && !str_ends_with($rel, '.php')) {
    // a real file under public/ (assets, uploads): serve it with its type
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $types = ['css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject', 'map' => 'application/json', 'html' => 'text/html', 'pdf' => 'application/pdf', 'webp' => 'image/webp'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($root . $rel));
    readfile($root . $rel);
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/avenir/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['PHP_SELF'] = '/avenir/index.php';
$_SERVER['PATH_INFO'] = $rel === '' ? '/' : $rel;
chdir($root);
require $root . '/index.php';
