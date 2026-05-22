<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If request matches a file inside public/, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Rewrite extensionless URL to PHP file if it exists
if (file_exists(__DIR__ . '/public' . $uri . '.php')) {
    include_once __DIR__ . '/public' . $uri . '.php';
    exit;
}

// Default fallback for root
if ($uri === '/') {
    include_once __DIR__ . '/public/index.php';
    exit;
}

return false;
