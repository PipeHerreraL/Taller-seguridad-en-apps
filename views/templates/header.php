<?php
declare(strict_types=1);
/**
 * @var string $title Título de la página
 * @var string $description Descripción SEO
 * @var string $activeTab Pestaña activa ('registro', 'pacientes' o vacío)
 */
$activeTab = $activeTab ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'ClinicaApp') ?></title>
  <meta name="description" content="<?= htmlspecialchars($description ?? '') ?>">
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
    <a href="index"     class="nav-link <?= $activeTab === 'registro' ? 'active' : '' ?>" id="nav-registro">Registro</a>
    <a href="pacientes" class="nav-link <?= $activeTab === 'pacientes' ? 'active' : '' ?>" id="nav-pacientes">Pacientes</a>
  </div>
</nav>
