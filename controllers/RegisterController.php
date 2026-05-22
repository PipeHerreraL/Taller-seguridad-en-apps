<?php

declare(strict_types=1);
/**
 * controllers/RegisterController.php
 * Controlador de registro: recibe POST del formulario,
 * aplica validación + sanitización + insert con PDO preparado.
 */
require_once __DIR__.'/../autoload.php';
use App\Helpers\Validator;
use App\Models\Paciente;

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index');
    exit;
}
// ── 1. Sanitizar entradas ────────────────────────────────────────────────────
$nombre = Validator::sanitize($_POST['nombre'] ?? '');
$apellido = Validator::sanitize($_POST['apellido'] ?? '');
$email = Validator::sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';  // sin sanitize antes de hash
$fecha_nac = Validator::sanitize($_POST['fecha_nacimiento'] ?? '');
$telefono = Validator::sanitize($_POST['telefono'] ?? '');
$tipo_sangre = Validator::sanitize($_POST['tipo_sangre'] ?? '');
$genero = Validator::sanitize($_POST['genero'] ?? '');
$observaciones = Validator::sanitize($_POST['observaciones'] ?? '');
// ── 2. Validar con Validator ─────────────────────────────────────────────
$v = new Validator;
$v->required($nombre, 'nombre');
$v->noSpecialChars($nombre, 'nombre');
$v->required($apellido, 'apellido');
$v->noSpecialChars($apellido, 'apellido');
$v->required($email, 'email');
$v->email($email, 'email');
$v->required($password, 'password');
$v->minLength($password, 'password', 8);
$v->required($fecha_nac, 'fecha_nacimiento');
$v->date($fecha_nac, 'fecha_nacimiento');
$v->required($telefono, 'telefono');
$v->phone($telefono, 'telefono');
$tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$v->required($tipo_sangre, 'tipo_sangre');
$v->inList($tipo_sangre, 'tipo_sangre', $tiposSangre);
$generosList = ['Masculino', 'Femenino', 'Otro', 'Prefiero no decir'];
$v->required($genero, 'genero');
$v->inList($genero, 'genero', $generosList);
if ($v->hasErrors()) {
    $_SESSION['errors'] = $v->getErrors();
    $_SESSION['old'] = compact('nombre', 'apellido', 'email', 'fecha_nac', 'telefono', 'tipo_sangre', 'genero', 'observaciones');
    header('Location: index');
    exit;
}
// ── 3. Verificar email duplicado ─────────────────────────────────────────────
try {
    $model = new Paciente;
    if ($model->buscarPorEmail($email)) {
        $_SESSION['errors'] = ['email' => 'Este correo electrónico ya está registrado.'];
        $_SESSION['old'] = compact('nombre', 'apellido', 'email', 'fecha_nac', 'telefono', 'tipo_sangre', 'genero', 'observaciones');
        header('Location: index');
        exit;
    }
    // ── 4. Hashear contraseña (bcrypt) ───────────────────────────────────────
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    // ── 5. Insertar con consulta preparada ───────────────────────────────────
    $model->crear([
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'password_hash' => $passwordHash,
        'fecha_nacimiento' => $fecha_nac,
        'telefono' => $telefono,
        'tipo_sangre' => $tipo_sangre,
        'genero' => $genero,
        'observaciones' => $observaciones,
    ]);
    $_SESSION['success'] = "Paciente <strong>{$nombre} {$apellido}</strong> registrado exitosamente.";
    header('Location: index');
    exit;
} catch (PDOException $e) {
    error_log('[RegisterController] '.$e->getMessage());
    $_SESSION['errors'] = ['db' => 'Error al guardar los datos. Inténtalo de nuevo.'];
    header('Location: index');
    exit;
}
