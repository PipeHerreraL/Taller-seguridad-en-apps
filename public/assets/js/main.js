/**
 * main.js
 * Scripts del lado del cliente:
 *  - Toggle de visibilidad de contraseña
 *  - Activador de calendario (fecha de nacimiento)
 *  - Indicador de fortaleza de contraseña
 *  - Validación inmediata de campos (client-side)
 *  - Auto-dismiss de alertas
 */
document.addEventListener('DOMContentLoaded', () => {
  /* ── 1. Toggle de contraseña ────────────────────────── */
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      if (!target) return;
      const isText = target.type === 'text';
      target.type = isText ? 'password' : 'text';
      
      const eyeIcon = btn.querySelector('.icon-eye');
      const eyeSlashIcon = btn.querySelector('.icon-eye-slash');
      if (eyeIcon && eyeSlashIcon) {
        if (isText) {
          eyeIcon.style.display = 'inline-block';
          eyeSlashIcon.style.display = 'none';
          btn.setAttribute('aria-label', 'Ver contraseña');
        } else {
          eyeIcon.style.display = 'none';
          eyeSlashIcon.style.display = 'inline-block';
          btn.setAttribute('aria-label', 'Ocultar contraseña');
        }
      }
    });
  });

  /* ── 2. Activador de calendario ──────────────────────────── */
  document.querySelectorAll('.calendar-trigger').forEach(btn => {
    btn.addEventListener('click', () => {
      const inputId = btn.closest('.input-group')?.querySelector('input[type="date"]')?.id;
      const dateInput = inputId ? document.getElementById(inputId) : null;
      if (dateInput && typeof dateInput.showPicker === 'function') {
        dateInput.showPicker();
      } else if (dateInput) {
        dateInput.focus(); // fallback para navegadores sin showPicker
      }
    });
  });

  /* ── 3. Indicador de fortaleza de contraseña ─────────── */
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

  /* ── 5. Copiar al portapapeles ─────────────────────────── */
  document.querySelectorAll('.copyable').forEach(cell => {
    cell.addEventListener('click', () => {
      const textToCopy = cell.dataset.copy || cell.textContent.trim();
      navigator.clipboard.writeText(textToCopy).then(() => {
        showToast(`Copiado: "${textToCopy}"`);
      }).catch(err => {
        console.error('Error al copiar al portapapeles:', err);
      });
    });
  });

  function showToast(message) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span>📋</span> <span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('fade-out');
      setTimeout(() => {
        toast.remove();
        if (container.children.length === 0) container.remove();
      }, 250);
    }, 2500);
  }

  /* ── 6. Dropdown Actions ─────────────────────────────────── */
  document.querySelectorAll('.dropdown-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const parent = btn.closest('.dropdown');
      const cell = btn.closest('td');
      const row = btn.closest('tr');

      // Close other dropdowns
      document.querySelectorAll('.dropdown').forEach(d => {
        if (d !== parent) {
          d.classList.remove('open');
          d.classList.remove('dropdown-up');
          d.closest('td')?.classList.remove('dropdown-active');
          d.closest('tr')?.classList.remove('dropdown-active');
        }
      });

      const isOpen = parent.classList.toggle('open');
      if (isOpen) {
        cell?.classList.add('dropdown-active');
        row?.classList.add('dropdown-active');

        // Check if there is enough space below the dropdown
        const rect = parent.getBoundingClientRect();
        const wrapper = btn.closest('.table-wrapper');
        if (wrapper) {
          const wrapperRect = wrapper.getBoundingClientRect();
          const spaceBelow = wrapperRect.bottom - rect.bottom;
          if (spaceBelow < 110) {
            parent.classList.add('dropdown-up');
          } else {
            parent.classList.remove('dropdown-up');
          }
        }
      } else {
        parent.classList.remove('dropdown-up');
        cell?.classList.remove('dropdown-active');
        row?.classList.remove('dropdown-active');
      }
    });
  });

  // Stop propagation inside dropdown menu
  document.querySelectorAll('.dropdown-menu').forEach(menu => {
    menu.addEventListener('click', (e) => { e.stopPropagation(); });
  });

  // Close when clicking outside
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(d => {
      d.classList.remove('open');
      d.classList.remove('dropdown-up');
      d.closest('td')?.classList.remove('dropdown-active');
      d.closest('tr')?.classList.remove('dropdown-active');
    });
  });

  /* ── 7. Menú Hamburguesa Responsivo ─────────────────────────── */
  const navToggle = document.getElementById('nav-toggle');
  const navbarMenu = document.getElementById('navbar-menu');
  if (navToggle && navbarMenu) {
    navToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = navToggle.classList.toggle('open');
      navbarMenu.classList.toggle('open', isOpen);
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close when clicking links inside menu
    navbarMenu.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        navToggle.classList.remove('open');
        navbarMenu.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!navToggle.contains(e.target) && !navbarMenu.contains(e.target)) {
        navToggle.classList.remove('open');
        navbarMenu.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

});

