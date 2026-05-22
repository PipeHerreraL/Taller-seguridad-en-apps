<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$publicDir = __DIR__ . '/public';
$staticFile = $publicDir . $uri;

// Serve existing static files (css, js, images, etc.) from public/
if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile)) {
    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
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
    
    // Obtener metadatos del archivo para la validación de caché
    $mtime = filemtime($staticFile);
    $etag = md5($staticFile . $mtime); // Rápido y único para desarrollo
    
    // Configurar cabeceras de caché del cliente
    header("Content-Type: $contentType");
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header("ETag: \"$etag\"");
    header('Cache-Control: no-cache, must-revalidate'); // Revalidar siempre con el servidor (ETag)
    
    // Comprobar si el navegador ya tiene la versión actual en caché
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

// Rewrite extensionless URL to PHP file if it exists in public/
if (file_exists($publicDir . $uri . '.php')) {
    include_once $publicDir . $uri . '.php';
    exit;
}

// Default fallback for root
if ($uri === '/') {
    include_once $publicDir . '/index.php';
    exit;
}

// 404 fallback
http_response_code(404);
echo '<h1>404 — Página no encontrada</h1>';
