<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTime;

/**
 * Clase Validator
 * Realiza validaciones y sanitizaciones de entrada para asegurar que los datos
 * son correctos y seguros.
 */
class Validator
{
    private array $errors = [];

    /**
     * Valida que un valor no esté vacío (ni compuesto solo de espacios).
     */
    public function required(string $value, string $field): void
    {
        if (trim($value) === '') {
            $this->errors[$field] = "El campo " . ucfirst($field) . " es obligatorio.";
        }
    }

    /**
     * Valida formato de correo electrónico.
     */
    public function email(string $email, string $field = 'email'): void
    {
        if (trim($email) === '') {
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El formato de correo electrónico no es válido.";
        }
    }

    /**
     * Valida longitud mínima de una cadena.
     */
    public function minLength(string $value, string $field, int $min): void
    {
        if (trim($value) === '') {
            return;
        }
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = "El campo " . ucfirst($field) . " debe tener al menos {$min} caracteres.";
        }
    }

    /**
     * Valida que un valor sea numérico.
     */
    public function numeric(string $value, string $field): void
    {
        if (trim($value) === '') {
            return;
        }
        if (!is_numeric($value)) {
            $this->errors[$field] = "El campo " . ucfirst($field) . " debe ser un número.";
        }
    }

    /**
     * Valida que una fecha sea correcta en formato Y-m-d y que no sea futura.
     */
    public function date(string $value, string $field): void
    {
        if (trim($value) === '') {
            return;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->errors[$field] = "El formato de fecha no es válido.";
            return;
        }

        $now = new DateTime();
        if ($d > $now) {
            $this->errors[$field] = "La fecha de nacimiento no puede ser futura.";
        }
    }

    /**
     * Valida formato de teléfono.
     */
    public function phone(string $value, string $field): void
    {
        if (trim($value) === '') {
            return;
        }
        if (!preg_match('/^\+?[0-9]{7,15}$/', $value)) {
            $this->errors[$field] = "El formato de teléfono no es válido.";
        }
    }

    /**
     * Valida que un valor pertenezca a una lista permitida.
     */
    public function inList(string $value, string $field, array $list): void
    {
        if (trim($value) === '') {
            return;
        }
        if (!in_array($value, $list, true)) {
            $this->errors[$field] = "El valor seleccionado para " . $field . " no es válido.";
        }
    }

    /**
     * Sanitiza una cadena eliminando espacios extras y convirtiendo caracteres especiales a HTML.
     */
    public static function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Obtiene los errores de validación.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Agrega un error manualmente.
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
}