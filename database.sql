-- ============================================================
--  Script SQL — Sistema de Registro de Pacientes
--  Motor: MySQL / MariaDB (XAMPP)
--  Autor: Taller de Seguridad en Apps — 2026
-- ============================================================

-- Crea la base de datos si no existe y la selecciona
CREATE DATABASE IF NOT EXISTS clinica_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE clinica_db;

-- ============================================================
--  Tabla: pacientes
--  Almacena la información registrada a través del formulario.
-- ============================================================
CREATE TABLE IF NOT EXISTS pacientes (
    id               INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    apellido         VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,           -- Almacena el hash bcrypt, NUNCA la contraseña en texto plano
    fecha_nacimiento DATE         NOT NULL,
    telefono         VARCHAR(20)  NOT NULL,
    tipo_sangre      ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    genero           ENUM('Masculino','Femenino','Otro','Prefiero no decir') NOT NULL,
    observaciones    TEXT,                            -- Campo opcional para notas
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
