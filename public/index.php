<?php
declare(strict_types=1);

/**
* public/index.php — Front Controller de la aplicación.
* Punto único de entrada para todas las peticiones dinámicas.
*/

require_once __DIR__ . '/../autoload.php';

// Registrar el logger para capturar cada petición al finalizar su ejecución (evita log injection)
register_shutdown_function([\App\Helpers\Logger::class, 'logRequest']);

// Obtener la ruta limpia de la URL
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Detectar y remover el subdirectorio base de la URL (útil si se ejecuta bajo XAMPP/Apache en una subcarpeta)
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
// Asegurar que comience con '/'
if ($uri === '' || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

// Mapear rutas amigables a las vistas y controladores ubicados fuera del document root
$routes = [
    '/'              => __DIR__ . '/../views/index.php',
    '/index'         => __DIR__ . '/../views/index.php',
    '/index.php'     => __DIR__ . '/../views/index.php',
    '/pacientes'     => __DIR__ . '/../views/pacientes.php',
    '/pacientes.php' => __DIR__ . '/../views/pacientes.php',
    '/register'      => __DIR__ . '/../controllers/RegisterController.php',
    '/delete'        => __DIR__ . '/../controllers/DeleteController.php',
    '/chat'          => __DIR__ . '/../controllers/ChatController.php'
];

if (isset($routes[$uri])) {
    include $routes[$uri];
    exit;
}

// 404 - Página no encontrada
http_response_code(404);
include_once __DIR__ . '/../views/404.php';
exit;
