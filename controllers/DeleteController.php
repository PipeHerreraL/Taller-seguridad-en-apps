<?php

declare(strict_types=1);

/**
 * controllers/DeleteController.php
 * Controlador de eliminación: valida el ID como entero positivo
 * y ejecuta DELETE con sentencia preparada.
 */

require_once __DIR__ . '/../autoload.php';

use App\Models\Paciente;

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pacientes');
    exit;
}

// Filtrar el ID como entero positivo — rechaza strings maliciosos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    $_SESSION['errors'] = ['id' => 'ID de paciente no válido.'];
    header('Location: pacientes');
    exit;
}

try {
    $model = new Paciente();
    $model->eliminar($id);
    $_SESSION['success'] = 'Paciente eliminado correctamente.';
} catch (\PDOException $e) {
    error_log('[DeleteController] ' . $e->getMessage());
    $_SESSION['errors'] = ['db' => 'No se pudo eliminar el paciente.'];
}

header('Location: pacientes');
exit;