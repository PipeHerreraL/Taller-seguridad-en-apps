<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Class Logger
 * Proporciona un registro centralizado y seguro de peticiones HTTP.
 */
class Logger
{
    private static bool $logged = false;

    /**
     * Registra la petición actual en logs/app.log de forma segura.
     * Garantiza ejecutarse como máximo una vez por petición.
     */
    public static function logRequest(): void
    {
        if (self::$logged) {
            return;
        }
        self::$logged = true;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Prevenir Log Injection (reemplazar saltos de línea y tabuladores por espacios)
        $uriClean = preg_replace('/[\r\n\t]+/', ' ', $uri);
        $uriClean = filter_var($uriClean, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

        // Obtener el código de estado HTTP actual
        $status = http_response_code();
        if ($status === false || $status === 0) {
            $status = 200;
        }

        // Definir directorio y archivo de log en la raíz del proyecto
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $logLine = sprintf("[%s] %s - %s %s - Status: %d\n", $timestamp, $ip, $method, $uriClean, $status);

        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
