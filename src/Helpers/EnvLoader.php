<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Clase EnvLoader
 * Carga variables de entorno desde un archivo .env en $_ENV, $_SERVER y putenv.
 */
class EnvLoader
{
    /**
     * Carga el archivo .env especificado.
     *
     * @param string $path Ruta absoluta al archivo .env
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar comentarios
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Dividir en clave y valor por el primer signo '='
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Eliminar comillas alrededor del valor si existen
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Registrar la variable de entorno
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
