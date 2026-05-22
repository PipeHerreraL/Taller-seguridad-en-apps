<?php
declare(strict_types=1);
/**
 * public/views/index.php — Vista: Página de registro de pacientes.
 */
require_once __DIR__ . '/../autoload.php';
session_start();
$success = $_SESSION['success'] ?? null;
$errors  = $_SESSION['errors']  ?? [];
$old     = $_SESSION['old']     ?? [];
unset($_SESSION['success'], $_SESSION['errors'], $_SESSION['old']);
session_write_close();
$tiposSangre = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$generosList = ['Masculino', 'Femenino', 'Otro', 'Prefiero no decir'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Pacientes — ClinicaApp</title>
  <meta name="description" content="Sistema de registro de pacientes con PHP + MySQL.">
  <meta name="theme-color" content="#0f0f1a">
  <link rel="preload" href="assets/fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/css/styles.css?v=1.0.7">
</head>
<body>
<nav class="navbar">
  <a href="index" class="navbar-brand" id="nav-brand">
    <div class="logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="white" style="width: 18px; height: 18px;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-3 3H15m-3 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
      </svg>
    </div>
    ClinicaApp
  </a>
  <button class="navbar-toggle" id="nav-toggle" aria-label="Abrir menú de navegación" aria-expanded="false">
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
    <span class="hamburger-line"></span>
  </button>
  <div class="navbar-nav" id="navbar-menu">
    <a href="index"     class="nav-link active" id="nav-registro">Registro</a>
    <a href="pacientes" class="nav-link"        id="nav-pacientes">Pacientes</a>
  </div>
</nav>
<main class="container">
  <div class="page-header">
    <h1>Registro de <span>Paciente</span></h1>
  </div>
  <?php if ($success): ?>
  <div class="alert alert-success" role="alert" data-autodismiss="6000" id="alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <span><?= $success ?></span>
  </div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" role="alert" id="alert-errors">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div>
      <strong>Corrige los siguientes errores:</strong>
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  <div class="card">
    <div class="card-header">
      <h2>Nuevo Paciente</h2>
      <p>Todos los campos marcados con * son obligatorios.</p>
    </div>
    <form id="registro-form" action="register" method="POST" novalidate>
      <div class="form-grid">
        <!-- Nombre -->
        <div class="form-group">
          <label class="form-label" for="nombre">Nombre *</label>
          <input type="text" id="nombre" name="nombre"
            class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>"
            placeholder="Ej. Juan" maxlength="100" required autocomplete="given-name"
            value="<?= htmlspecialchars($old['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <span class="invalid-feedback"><?= htmlspecialchars($errors['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Apellido -->
        <div class="form-group">
          <label class="form-label" for="apellido">Apellido *</label>
          <input type="text" id="apellido" name="apellido"
            class="form-control <?= isset($errors['apellido']) ? 'is-invalid' : '' ?>"
            placeholder="Ej. Pérez" maxlength="100" required autocomplete="family-name"
            value="<?= htmlspecialchars($old['apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <span class="invalid-feedback"><?= htmlspecialchars($errors['apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Email -->
        <div class="form-group">
          <label class="form-label" for="email">Correo electrónico *</label>
          <input type="email" id="email" name="email"
            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
            placeholder="paciente@ejemplo.com" maxlength="150" required autocomplete="email"
            value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <span class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Contraseña -->
        <div class="form-group">
          <label class="form-label" for="password">Contraseña *</label>
          <div class="input-group">
            <input type="password" id="password" name="password"
              class="form-control <?= isset($errors['contraseña']) ? 'is-invalid' : '' ?>"
              placeholder="Mín. 8 caracteres" minlength="8" required autocomplete="new-password">
            <button type="button" class="toggle-password" data-target="password" id="toggle-pwd" aria-label="Ver contraseña">
              <!-- Eye Icon (shown when password is hidden) -->
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              <!-- Eye-Slash Icon (shown when password is visible, hidden by default) -->
              <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle; display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
            </button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
          <div class="strength-text" id="strength-text"></div>
          <span class="invalid-feedback"><?= htmlspecialchars($errors['contraseña'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Fecha de nacimiento -->
        <div class="form-group">
          <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento *</label>
          <div class="input-group">
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
              class="form-control <?= isset($errors['fecha_nacimiento']) ? 'is-invalid' : '' ?>"
              max="<?= date('Y-m-d') ?>" required
              value="<?= htmlspecialchars($old['fecha_nac'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="calendar-trigger" id="btn-calendar" aria-label="Abrir calendario">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 18px; height: 18px; vertical-align: middle;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
              </svg>
            </button>
          </div>
          <span class="invalid-feedback"><?= htmlspecialchars($errors['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Teléfono -->
        <div class="form-group">
          <label class="form-label" for="telefono">Teléfono *</label>
          <input type="tel" id="telefono" name="telefono"
            class="form-control <?= isset($errors['teléfono']) ? 'is-invalid' : '' ?>"
            placeholder="+573001234567" pattern="^\+?[0-9]{7,15}$" maxlength="16" required autocomplete="tel"
            value="<?= htmlspecialchars($old['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <span class="invalid-feedback"><?= htmlspecialchars($errors['teléfono'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Tipo de sangre -->
        <div class="form-group">
          <label class="form-label" for="tipo_sangre">Tipo de sangre *</label>
          <select id="tipo_sangre" name="tipo_sangre"
            class="form-control <?= isset($errors['tipo_sangre']) ? 'is-invalid' : '' ?>" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($tiposSangre as $ts): ?>
              <option value="<?= $ts ?>" <?= ($old['tipo_sangre'] ?? '') === $ts ? 'selected' : '' ?>><?= $ts ?></option>
            <?php endforeach; ?>
          </select>
          <span class="invalid-feedback"><?= htmlspecialchars($errors['tipo_sangre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Género -->
        <div class="form-group">
          <label class="form-label" for="genero">Género *</label>
          <select id="genero" name="genero"
            class="form-control <?= isset($errors['género']) ? 'is-invalid' : '' ?>" required>
            <option value="">— Selecciona —</option>
            <?php foreach ($generosList as $g): ?>
              <option value="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>"
                <?= ($old['genero'] ?? '') === $g ? 'selected' : '' ?>>
                <?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="invalid-feedback"><?= htmlspecialchars($errors['género'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <!-- Observaciones -->
        <div class="form-group full-width">
          <label class="form-label" for="observaciones">Observaciones</label>
          <textarea id="observaciones" name="observaciones" class="form-control"
            placeholder="Alergias, condiciones preexistentes… (opcional)" maxlength="1000"
          ><?= htmlspecialchars($old['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div><!-- /form-grid -->
      <button type="submit" class="btn btn-primary btn-block" id="btn-registrar">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 4px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6 6 0 0 1 6-6h1.5a6 6 0 0 1 6 6v.115" />
        </svg>
        Registrar Paciente
      </button>
    </form>
  </div>
</main>
<footer>
  <p>ClinicaApp &copy; <?= date('Y') ?> — Taller de Seguridad en Aplicaciones Web · PHP + MySQL</p>
 </footer>
<!-- defer: el script se descarga en paralelo y ejecuta tras parsear el HTML -->
<script src="assets/js/main.js?v=1.0.1" defer></script>
</body>
</html>
