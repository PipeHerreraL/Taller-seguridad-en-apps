<?php
declare(strict_types=1);
/**
 * views/404.php — Vista: Página de error 404.
 */
require_once __DIR__.'/../autoload.php';

$title = 'Página no encontrada — ClinicaApp';
$description = 'La página solicitada no existe.';
$activeTab = '';
include __DIR__.'/templates/header.php';
?>
<main class="container">
  <div class="error-container">
    <div class="error-card">
      <div class="error-code">404</div>
      <h1 class="error-title">Página no encontrada</h1>
      <p class="error-message">
        Lo sentimos, el recurso que estás buscando no existe o ha sido movido a una nueva dirección.
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
