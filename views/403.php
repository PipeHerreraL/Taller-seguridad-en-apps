<?php
declare(strict_types=1);
/**
 * views/403.php — Vista: Página de error 403 (Acceso Prohibido).
 */
require_once __DIR__.'/../autoload.php';

$title = 'Acceso Prohibido — ClinicaApp';
$description = 'No tienes permisos para acceder a este recurso.';
$activeTab = '';
include __DIR__.'/templates/header.php';
?>
<main class="container">
  <div class="error-container">
    <div class="error-card">
      <div class="error-code" style="background: linear-gradient(135deg, #f87171, #ef4444); -webkit-background-clip: text; background-clip: text;">403</div>
      <h1 class="error-title">Acceso Prohibido</h1>
      <p class="error-message">
        No tienes permisos para acceder a este recurso.
      </p>
      <a href="index" class="btn btn-primary" id="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 6px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
        Regresar al Inicio
      </a>
    </div>
  </div>
</main>

<?php
include __DIR__.'/templates/footer.php';
?>
