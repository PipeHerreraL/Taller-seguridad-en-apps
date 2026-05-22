/**
 * main.js
 * Scripts del lado del cliente:
 *  - Toggle de visibilidad de contraseña
 *  - Indicador de fortaleza de contraseña
 *  - Validación inmediata de campos (client-side)
 *  - Auto-dismiss de alertas
 */
document.addEventListener('DOMContentLoaded', () => {
  /* ── 1. Toggle de contraseña ─────────────────────────── */
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      if (!target) return;
      const isText = target.type === 'text';
      target.type = isText ? 'password' : 'text';
      btn.textContent = isText ? '👁️' : '🙈';
    });
  });
  /* ── 2. Indicador de fortaleza de contraseña ─────────── */
  const passwordInput = document.getElementById('password');
  const strengthFill = document.getElementById('strength-fill');
  const strengthText = document.getElementById('strength-text');
  if (passwordInput && strengthFill && strengthText) {
    passwordInput.addEventListener('input', () => {
      const val = passwordInput.value;
      const score = calcPasswordStrength(val);
      const levels = [
        { label: '', color: 'transparent', width: '0%' },
        { label: 'Muy débil', color: '#ef4444', width: '20%' },
        { label: 'Débil', color: '#f97316', width: '40%' },
        { label: 'Media', color: '#f59e0b', width: '60%' },
        { label: 'Fuerte', color: '#10b981', width: '80%' },
        { label: 'Muy fuerte', color: '#06b6d4', width: '100%' },
      ];
      const level = levels[score];
      strengthFill.style.width = level.width;
      strengthFill.style.background = level.color;
      strengthText.textContent = val.length ? `Fortaleza: ${level.label}` : '';
      strengthText.style.color = level.color;
    });
  }
  /**
   * Calcula la fortaleza de una contraseña en escala 0-5.
   * @param {string} pwd
   * @returns {number}
   */
  function calcPasswordStrength(pwd) {
    if (!pwd) return 0;
    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 12) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    return Math.min(score, 5);
  }
  /* ── 3. Validación client-side de campos ─────────────── */
  const form = document.getElementById('registro-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      let valid = true;
      clearErrors(form);
      // Nombre y apellido: sin caracteres especiales
      ['nombre', 'apellido'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (!el.value.trim()) {
          showError(el, 'Este campo es obligatorio.');
          valid = false;
        } else if (/[<>"'\/\\]/.test(el.value)) {
          showError(el, 'Contiene caracteres no permitidos.');
          valid = false;
        }
      });
      // Email
      const emailEl = document.getElementById('email');
      if (emailEl && !isValidEmail(emailEl.value)) {
        showError(emailEl, 'Ingresa un correo válido.');
        valid = false;
      }
      // Contraseña mínimo 8 caracteres
      const passEl = document.getElementById('password');
      if (passEl && passEl.value.length < 8) {
        showError(passEl, 'La contraseña debe tener al menos 8 caracteres.');
        valid = false;
      }
      // Teléfono: solo dígitos
      const telEl = document.getElementById('telefono');
      if (telEl && !/^\+?[0-9]{7,15}$/.test(telEl.value.trim())) {
        showError(telEl, 'Ingresa un número de teléfono válido (7-15 dígitos).');
        valid = false;
      }
      // Fecha de nacimiento: no futura
      const fechaEl = document.getElementById('fecha_nacimiento');
      if (fechaEl && fechaEl.value) {
        const dob = new Date(fechaEl.value);
        if (dob > new Date()) {
          showError(fechaEl, 'La fecha no puede ser futura.');
          valid = false;
        }
      }
      if (!valid) e.preventDefault();
    });
  }
  /**
   * Muestra un mensaje de error debajo de un campo.
   */
  function showError(el, msg) {
    el.classList.add('is-invalid');
    const feedback = el.closest('.form-group')?.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.textContent = '⚠ ' + msg;
      feedback.style.display = 'flex';
    }
  }
  /**
   * Limpia todos los errores client-side del formulario.
   */
  function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => {
      el.textContent = '';
      el.style.display = 'none';
    });
  }
  /**
   * Valida formato de email.
   */
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
  /* ── 4. Auto-dismiss de alertas ──────────────────────── */
  document.querySelectorAll('.alert[data-autodismiss]').forEach(alert => {
    const delay = parseInt(alert.dataset.autodismiss, 10) || 5000;
    setTimeout(() => {
      alert.style.transition = 'opacity .5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, delay);
  });
});
