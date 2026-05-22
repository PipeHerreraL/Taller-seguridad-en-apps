<?php

declare(strict_types=1);

/**
 * autoload.php
 * Cargador de clases PSR-4 manual (sin Composer) para código de la app.
 * Composer tiene su propio autoload en vendor/autoload.php (solo para tests).
 */

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\Config\\'  => __DIR__ . '/src/Config/',
        'App\\Models\\'  => __DIR__ . '/src/Models/',
        'App\\Helpers\\' => __DIR__ . '/src/Helpers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Cargar variables de entorno desde el archivo .env
require_once __DIR__ . '/src/Helpers/EnvLoader.php';
\App\Helpers\EnvLoader::load(__DIR__ . '/.env');


// Cargar variables de entorno desde el archivo .env
require_once __DIR__ . '/src/Helpers/EnvLoader.php';
\App\Helpers\EnvLoader::load(__DIR__ . '/.env');

