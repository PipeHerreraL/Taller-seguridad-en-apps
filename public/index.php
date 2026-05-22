<?php
declare(strict_types=1);

/**
* public/index.php — Front Controller de la aplicación.
* Punto único de entrada para todas las peticiones dinámicas.
*/

require_once __DIR__ . '/../autoload.php';

// Obtener la ruta limpia de la URL
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Mapear rutas amigables a las vistas y controladores ubicados fuera del document root
$routes = [
    '/'          => __DIR__ . '/../views/index.php',
    '/index'     => __DIR__ . '/../views/index.php',
    '/pacientes' => __DIR__ . '/../views/pacientes.php',
    '/register'  => __DIR__ . '/../controllers/RegisterController.php',
    '/delete'    => __DIR__ . '/../controllers/DeleteController.php'
];

if (isset($routes[$uri])) {
    include $routes[$uri];
    exit;
}

// 404 - Página no encontrada
http_response_code(404);
echo '<h1>404 — Página no encontrada</h1>';
exit;
