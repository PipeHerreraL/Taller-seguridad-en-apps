<?php
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__ . '/public';
$staticFile = $publicDir . $uri;

// 1. Serve existing static files (css, js, images, fonts, etc.) from public/
if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile)) {
    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
    
    // Security check: Never serve raw PHP files statically (prevents source code disclosure)
    if ($ext === 'php') {
        http_response_code(403);
        echo '<h1>403 — Acceso prohibido</h1>';
        exit;
    }

    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'json'  => 'application/json',
        'pdf'   => 'application/pdf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
    ];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    
    // Get file metadata for cache validation (ETag / Last-Modified)
    $mtime = filemtime($staticFile);
    $etag = md5($staticFile . $mtime);
    
    header("Content-Type: $contentType");
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header("ETag: \"$etag\"");
    header('Cache-Control: no-cache, must-revalidate');
    
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === "\"$etag\"") {
        http_response_code(304);
        exit;
    }
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
        http_response_code(304);
        exit;
    }
    
    readfile($staticFile);
    exit;
}

// 2. All other requests go to the Front Controller public/index.php
include_once $publicDir . '/index.php';
exit;
