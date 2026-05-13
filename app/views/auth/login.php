<?php require_once __DIR__ . '/../../helpers/security/CsrfHelper.php'; ?>
<div class="row justify-content-center py-4">
  <div class="col-lg-5 col-md-7">
    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['success'])): ?>
      <div class="alert alert-success rounded-3 mb-4"><?= htmlspecialchars($flash['success']) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h3 fw-semibold mb-1">Accedi</h1>
        <p class="text-muted mb-4">Bentornato in TechShop.</p>

        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/login"
              id="loginForm" novalidate>
          <?= CsrfHelper::field() ?>

          <!-- Email -->
          <div class="mb-3">
            <label for="loginEmail" class="form-label fw-medium">Email</label>
            <input type="email" id="loginEmail" name="email"
                   class="form-control form-control-lg rounded-3"
                   placeholder="nome@email.com"
                   required autocomplete="email">
            <div class="invalid-feedback">Inserisci un indirizzo email valido.</div>
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label for="loginPassword" class="form-label fw-medium">Password</label>
            <div class="input-group">
              <input type="password" id="loginPassword" name="password"
                     class="form-control form-control-lg rounded-start-3"
                     placeholder="••••••••"
                     required autocomplete="current-password">
              <button type="button" class="btn btn-outline-secondary rounded-end-3"
                      id="toggleLoginPw" tabindex="-1" aria-label="Mostra password">
                <svg id="loginEyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="invalid-feedback">Inserisci la password.</div>
          </div>

          <!-- Remember me -->
          <div class="mb-4 form-check">
            <input type="checkbox" name="remember_me" id="rememberMe"
                   class="form-check-input" value="1">
            <label class="form-check-label text-muted small" for="rememberMe">
              Ricordami per 30 giorni
            </label>
          </div>

          <!-- Errori JS sommario -->
          <div id="loginJsErrors" class="alert alert-warning rounded-3 small d-none mb-3"></div>

          <button type="submit" class="btn btn-dark btn-lg w-100 rounded-3">Accedi</button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/index.php?r=auth/registerForm" class="text-muted small">
            Non hai un account? <strong>Registrati</strong>
          </a>
        </div>

        <div class="d-flex align-items-center gap-3 my-4">
          <hr class="flex-grow-1 m-0"><span class="text-muted small">oppure</span><hr class="flex-grow-1 m-0">
        </div>
        <div class="d-flex justify-content-center">
          <div id="googleLoginBtn"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('loginForm');
  const emailInput= document.getElementById('loginEmail');
  const pwInput   = document.getElementById('loginPassword');
  const toggleBtn = document.getElementById('toggleLoginPw');
  const eyeIcon   = document.getElementById('loginEyeIcon');
  const jsErrors  = document.getElementById('loginJsErrors');

  // ── Mostra/nascondi password ───────────────────────────────────────────────
  toggleBtn.addEventListener('click', () => {
    const isText = pwInput.type === 'text';
    pwInput.type = isText ? 'password' : 'text';
    eyeIcon.innerHTML = isText
      ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
      : '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  });

  // ── Validazione live blur ─────────────────────────────────────────────────
  function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

  emailInput.addEventListener('blur', () => {
    if (emailInput.value.trim() === '') return; // non mostrare errore se vuoto prima del submit
    const ok = validateEmail(emailInput.value.trim());
    emailInput.classList.toggle('is-valid',   ok);
    emailInput.classList.toggle('is-invalid', !ok);
  });

  emailInput.addEventListener('input', () => {
    if (emailInput.classList.contains('is-invalid')) {
      const ok = validateEmail(emailInput.value.trim());
      if (ok) {
        emailInput.classList.replace('is-invalid', 'is-valid');
        jsErrors.classList.add('d-none');
      }
    }
  });

  pwInput.addEventListener('input', () => {
    if (pwInput.classList.contains('is-invalid') && pwInput.value.length > 0) {
      pwInput.classList.replace('is-invalid', 'is-valid');
    }
  });

  // ── Validazione al submit ─────────────────────────────────────────────────
  form.addEventListener('submit', (e) => {
    const errs = [];

    if (!validateEmail(emailInput.value.trim())) {
      emailInput.classList.add('is-invalid');
      emailInput.classList.remove('is-valid');
      errs.push('Inserisci un indirizzo email valido.');
    }

    if (pwInput.value.length === 0) {
      pwInput.classList.add('is-invalid');
      pwInput.classList.remove('is-valid');
      errs.push('Inserisci la password.');
    }

    if (errs.length > 0) {
      e.preventDefault();
      jsErrors.innerHTML = '<strong>Correggi i seguenti errori:</strong><ul class="mb-0 mt-1">'
        + errs.map(err => `<li>${err}</li>`).join('')
        + '</ul>';
      jsErrors.classList.remove('d-none');
    } else {
      jsErrors.classList.add('d-none');
    }
  });
});
</script>
