<?php
declare(strict_types=1);
/**
 * public/views/pacientes.php — Vista: Listado y gestión de pacientes registrados.
 */
require_once __DIR__ . '/../autoload.php';
use App\Models\Paciente;
session_start();
$success = $_SESSION['success'] ?? null;
$errors  = $_SESSION['errors']  ?? [];
unset($_SESSION['success'], $_SESSION['errors']);
session_write_close();
$pacientes     = [];
$dbError       = null;
try {
    $pacienteModel = new Paciente();
    $pacientes     = $pacienteModel->obtenerTodos();
} catch (\PDOException $e) {
    error_log('[pacientes.php] ' . $e->getMessage());
    $dbError = 'No se pudo conectar a la base de datos. Verifica que XAMPP esté activo.';
}
$total = count($pacientes);
// Calcular tipo de sangre más frecuente
$tiposCount = [];
foreach ($pacientes as $p) {
    $ts = $p['tipo_sangre'];
    $tiposCount[$ts] = ($tiposCount[$ts] ?? 0) + 1;
}
arsort($tiposCount);
$tipoComun = array_key_first($tiposCount) ?? '—';
$title = "Pacientes Registrados — ClinicaApp";
$description = "Lista completa de pacientes registrados en ClinicaApp.";
$activeTab = 'pacientes';
include __DIR__ . '/templates/header.php';
?>
<main class="container container-wide">
  <div class="page-header">
    <h1>Lista de <span>Pacientes</span></h1>
    <a href="index" class="btn btn-primary" id="btn-nuevo">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;">
        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6 6 0 0 1 6-6h1.5a6 6 0 0 1 6 6v.115" />
      </svg>
      Nuevo Paciente
    </a>
  </div>
  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.097-.207c.051-.107.131-.205.244-.275M13.75 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM2.25 18.72a9.094 9.094 0 0 1 3.741-.479 3 3 0 0 1-4.682-2.72m.94 3.198.097-.207c.051-.107.131-.205.244-.275M19.5 6.75a3 3 0 9.094 9.094 0 0 1-3 3M6.75 19.25a6.75 6.75 0 0 1 10.5 0" />
        </svg>
      </div>
      <div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-label">Pacientes registrados</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon cyan">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.25c-5.25 6-7.5 9.75-7.5 13.5a7.5 7.5 0 0 0 15 0c0-3.75-2.25-7.75-7.5-13.5Z" />
        </svg>
      </div>
      <div>
        <div class="stat-value"><?= htmlspecialchars($tipoComun, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="stat-label">RH más común</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
        </svg>
      </div>
      <div>
        <div class="stat-value">PDO</div>
        <div class="stat-label">Protección SQLi</div>
      </div>
    </div>
  </div>
  <!-- Flash messages -->
  <?php if ($success): ?>
  <div class="alert alert-success" role="alert" data-autodismiss="5000" id="alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
  <div class="alert alert-danger" role="alert" id="alert-errors">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div><?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?></div>
  </div>
  <?php endif; ?>
  <?php if ($dbError): ?>
  <div class="alert alert-danger" role="alert" id="alert-db">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
    </svg>
    <span><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <?php endif; ?>
  <!-- Tabla -->
  <div class="card" style="padding:0">
    <?php if (empty($pacientes) && !$dbError): ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto; opacity: 0.5;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
        </svg>
      </div>
      <p style="margin-top: 1rem;">No hay pacientes registrados todavía.</p>
      <a href="index" class="btn btn-accent" style="margin-top:1rem" id="btn-primero">Registrar primer paciente</a>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th class="sortable" data-sort-col="0">
              Nombre completo
              <span class="sort-indicator">
                <svg class="sort-icon-default" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-left: 4px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
              </span>
            </th>
            <th>Email</th>
            <th>Teléfono</th>
            <th class="sortable text-center" data-sort-col="3">
              Fecha nac.
              <span class="sort-indicator">
                <svg class="sort-icon-default" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-left: 4px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
              </span>
            </th>
            <th class="sortable text-center" data-sort-col="4">
              RH
              <span class="sort-indicator">
                <svg class="sort-icon-default" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-left: 4px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
              </span>
            </th>
            <th class="sortable text-center" data-sort-col="5">
              Género
              <span class="sort-indicator">
                <svg class="sort-icon-default" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-left: 4px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
              </span>
            </th>
            <th class="sortable text-center" data-sort-col="6">
              Registrado
              <span class="sort-indicator">
                <svg class="sort-icon-default" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 11px; height: 11px; display: inline-block; vertical-align: middle; margin-left: 4px; opacity: 0.3;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                </svg>
              </span>
            </th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pacientes as $p): ?>
          <tr>
            <td class="copyable" data-copy="<?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8') ?>" data-sort-value="<?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8') ?>" title="Clic para copiar">
              <strong><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8') ?></strong>
            </td>
            <td class="copyable cell-email" data-copy="<?= htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8') ?>" title="Clic para copiar">
              <?= htmlspecialchars($p['email'],           ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="copyable" data-copy="<?= htmlspecialchars($p['telefono'], ENT_QUOTES, 'UTF-8') ?>" title="Clic para copiar">
              <?= htmlspecialchars($p['telefono'],        ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="text-center" data-sort-value="<?= htmlspecialchars($p['fecha_nacimiento']) ?>"><?= date('d/m/Y', strtotime($p['fecha_nacimiento'])) ?></td>
            <td class="text-center" data-sort-value="<?= htmlspecialchars($p['tipo_sangre'], ENT_QUOTES, 'UTF-8') ?>"><span class="badge badge-blood"><?= htmlspecialchars($p['tipo_sangre'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td class="text-center" data-sort-value="<?= htmlspecialchars($p['genero'], ENT_QUOTES, 'UTF-8') ?>">
              <?php
                $genderLabel = htmlspecialchars($p['genero'], ENT_QUOTES, 'UTF-8');
                switch ($p['genero']) {
                    case 'Masculino':
                        echo '<span class="badge-gender badge-gender-masculino" title="Masculino">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle; margin-right: 2px;">
                            <circle cx="10" cy="14" r="4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 10l5-5m0 0h-4m4 0v4" />
                          </svg>Masculino
                        </span>';
                        break;
                    case 'Femenino':
                        echo '<span class="badge-gender badge-gender-femenino" title="Femenino">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle; margin-right: 2px;">
                            <circle cx="12" cy="9" r="4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 13v6m-2.5-3h5" />
                          </svg>Femenino
                        </span>';
                        break;
                    case 'Otro':
                        echo '<span class="badge-gender badge-gender-otro" title="Otro">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" style="width: 13px; height: 13px; display: inline-block; vertical-align: middle; margin-right: 2px;">
                            <circle cx="12" cy="12" r="3.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5v4.5m-2-2h4M14.5 9.5l4-4m0 0h-3m3 0v3M9.5 9.5l-4-4m0 0h3m-3 0v3" />
                          </svg>Otro
                        </span>';
                        break;
                    default:
                        echo '<span class="badge-gender badge-gender-none" title="Prefiero no decir">-</span>';
                        break;
                }
              ?>
            </td>
            <td class="text-center" data-sort-value="<?= htmlspecialchars($p['created_at']) ?>" style="color:var(--text-muted);"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td class="text-center">
              <div class="dropdown">
                <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false" title="Acciones">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                  </svg>
                </button>
                <div class="dropdown-menu">
                  <form action="delete" method="POST" style="display:block"
                        onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($p['nombre'] . ' ' . $p['apellido']), ENT_QUOTES, 'UTF-8') ?>?')">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="dropdown-item text-danger" id="btn-eliminar-<?= (int) $p['id'] ?>" title="Eliminar paciente" aria-label="Eliminar paciente">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="width: 15px; height: 15px; display: inline-block; vertical-align: middle;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                      </svg>
                      Eliminar
                    </button>
                  </form>
                </div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>
<?php
include __DIR__ . '/templates/footer.php';
?>
