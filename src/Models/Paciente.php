<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Modelo Paciente
 * Realiza las operaciones CRUD contra la base de datos usando sentencias preparadas.
 */
class Paciente
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        // Permite inyectar la conexión para unit testing (SQLite en memoria)
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Inserta un nuevo paciente utilizando sentencias preparadas para mitigar SQL Injection.
     */
    public function crear(array $data): bool
    {
        $sql = 'INSERT INTO pacientes 
                    (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones) 
                VALUES 
                    (:nombre, :apellido, :email, :password_hash, :fecha_nacimiento, :telefono, :tipo_sangre, :genero, :observaciones)';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'           => $data['nombre'],
            ':apellido'         => $data['apellido'],
            ':email'            => $data['email'],
            ':password_hash'    => $data['password_hash'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':telefono'         => $data['telefono'],
            ':tipo_sangre'      => $data['tipo_sangre'],
            ':genero'           => $data['genero'],
            ':observaciones'    => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Retorna todos los pacientes ordenados por fecha de creación.
     */
    public function obtenerTodos(): array
    {
        $sql = 'SELECT * FROM pacientes ORDER BY created_at DESC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un paciente por su correo electrónico usando sentencias preparadas.
     */
    public function buscarPorEmail(string $email): array|false
    {
        $sql = 'SELECT * FROM pacientes WHERE email = :email';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un paciente por su ID usando sentencias preparadas.
     */
    public function eliminar(int $id): bool
    {
        $sql = 'DELETE FROM pacientes WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cuenta el total de pacientes en la base de datos.
     */
    public function contarTodos(): int
    {
        $sql = 'SELECT COUNT(*) FROM pacientes';
        $stmt = $this->db->query($sql);
        return (int)$stmt->fetchColumn();
    }
}