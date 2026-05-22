<?php
declare(strict_types=1);
?>
<footer>
  <p>ClinicaApp &copy; <?= date('Y') ?> — Taller de Seguridad en Aplicaciones Web · PHP + MySQL</p>
</footer>

<!-- Widget de Chat Flotante Inteligente (Asistente de IA) -->
<div id="clinica-chat-widget" class="chat-widget">
  <!-- Botón de apertura del chat -->
  <button id="chat-toggle-btn" class="chat-toggle-btn" aria-label="Abrir asistente virtual" title="¿Tienes dudas? ¡Pregúntale a la IA!">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="chat-icon-svg">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025 10.334 10.334 0 0 1-2.164-3.385C2.805 14.125 2.25 13.125 2.25 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
    </svg>
    <span class="chat-toggle-text">Asistente IA</span>
  </button>

  <!-- Contenedor del Chat (oculto inicialmente) -->
  <div id="chat-panel" class="chat-panel chat-hidden">
    <div class="chat-header">
      <div class="chat-header-info">
        <div class="chat-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="avatar-svg">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 21m0 0-.813-5.096M9 21h3.375c.621 0 1.125-.504 1.125-1.125V18m0-9.75v3.375c0 .621-.504 1.125-1.125 1.125H12M3 16.5V10.5m18 6v-6M5.25 12h13.5M12 5.25h.008v.008H12V5.25Zm0 2.25h.008v.008H12V7.5Zm0 2.25h.008v.008H12V9.75Z" />
          </svg>
        </div>
        <div>
          <h3>Asistente Clínico</h3>
          <span class="status-indicator">En línea (Solo Lectura)</span>
        </div>
      </div>
      <button id="chat-close-btn" class="chat-close-btn" aria-label="Cerrar chat">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Mensajes del chat -->
    <div id="chat-body" class="chat-body">
      <div class="chat-message bot">
        <p>¡Hola! Soy tu asistente virtual de seguridad clínica. Puedes preguntarme cosas como:</p>
        <ul>
          <li>¿Cuántos pacientes hay registrados?</li>
          <li>¿Cuántos se registraron hoy o este mes?</li>
          <li>¿Cuántos pacientes femeninos con RH O+ hay?</li>
          <li>¿Cuál es el promedio de edad o la distribución por género?</li>
          <li>¿Cuál es el correo o teléfono de Juan Pérez?</li>
        </ul>
        <p><small>Nota: Mi acceso es estrictamente de solo lectura por políticas de seguridad de datos.</small></p>
      </div>
    </div>

    <!-- Indicador de escritura animado -->
    <div id="chat-typing-indicator" class="chat-typing-indicator chat-hidden">
      <span></span>
      <span></span>
      <span></span>
    </div>

    <!-- Formulario de entrada -->
    <form id="chat-form" class="chat-form" autocomplete="off">
      <input type="text" id="chat-input" placeholder="Pregunta sobre los pacientes..." maxlength="150" required>
      <button type="submit" id="chat-send-btn" aria-label="Enviar pregunta">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
        </svg>
      </button>
    </form>
  </div>
</div>

<script src="assets/js/chat.js?v=1.0.0" defer></script>
<script src="assets/js/main.js?v=1.0.1" defer></script>
</body>
</html>
