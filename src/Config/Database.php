<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

/**
 * Clase Database
 * Gestiona la conexión a MySQL usando PDO con el patrón Singleton.
 * PDO::ATTR_EMULATE_PREPARES = false garantiza consultas preparadas reales
 * (prevención de SQL Injection a nivel de driver).
 */
class Database
{
    private static ?Database $instance = null;

    private PDO $connection;

    // Parámetros de conexión cargados desde variables de entorno
    private string $host;

    private string $dbName;

    private string $username;

    private string $password;

    private string $charset;

    /**
     * Constructor privado: crea la conexión PDO con opciones de seguridad.
     *
     * @throws PDOException Si la conexión falla.
     */
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'clinica_db';
        $this->username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
        $this->charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: null;

        $hostStr = $this->host;
        if (! empty($port)) {
            $hostStr .= ";port={$port}";
        }

        $dsn = "mysql:host={$hostStr};dbname={$this->dbName};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Consultas preparadas reales (anti SQL Injection)
        ];

        $this->connection = new PDO($dsn, $this->username, $this->password, $options);
    }

    /**
     * Retorna la única instancia de Database (Singleton).
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Expone el objeto PDO para ejecutar consultas.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Previene clonación y deserialización
    private function __clone() {}

    public function __wakeup(): void {}
}
