<?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; ?>
<div class="row justify-content-center py-4">
  <div class="col-lg-5 col-md-7">
    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h3 fw-semibold mb-1">Crea account</h1>
        <p class="text-muted mb-4">Unisciti a TechShop oggi.</p>

        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/register"
              id="registerForm" novalidate>
          <?= CsrfHelper::field() ?>

          <!-- Nome -->
          <div class="mb-3">
            <label for="regName" class="form-label fw-medium">Nome completo</label>
            <input type="text" id="regName" name="full_name"
                   class="form-control form-control-lg rounded-3"
                   placeholder="Mario Rossi"
                   required minlength="2" maxlength="120"
                   autocomplete="name">
            <div class="invalid-feedback">Inserisci il tuo nome (almeno 2 caratteri).</div>
            <div class="valid-feedback">Ottimo!</div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label for="regEmail" class="form-label fw-medium">Email</label>
            <input type="email" id="regEmail" name="email"
                   class="form-control form-control-lg rounded-3"
                   placeholder="nome@email.com"
                   required autocomplete="email">
            <div class="invalid-feedback">Inserisci un indirizzo email valido.</div>
            <div class="valid-feedback">Email valida!</div>
          </div>

          <!-- Password -->
          <div class="mb-2">
            <label for="regPassword" class="form-label fw-medium">Password</label>
            <div class="input-group">
              <input type="password" id="regPassword" name="password"
                     class="form-control form-control-lg rounded-start-3"
                     placeholder="Minimo 8 caratteri"
                     required minlength="8"
                     autocomplete="new-password">
              <button type="button" class="btn btn-outline-secondary rounded-end-3"
                      id="togglePassword" tabindex="-1" aria-label="Mostra password">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="invalid-feedback d-block d-none" id="pwFeedback"></div>
          </div>

          <!-- Barra forza password -->
          <div class="mb-4">
            <div class="d-flex gap-1 mb-1" id="strengthBars">
              <div class="flex-grow-1 rounded-pill" style="height:4px;background:#e2e8f0" id="bar1"></div>
              <div class="flex-grow-1 rounded-pill" style="height:4px;background:#e2e8f0" id="bar2"></div>
              <div class="flex-grow-1 rounded-pill" style="height:4px;background:#e2e8f0" id="bar3"></div>
              <div class="flex-grow-1 rounded-pill" style="height:4px;background:#e2e8f0" id="bar4"></div>
            </div>
            <div class="text-muted d-flex justify-content-between" style="font-size:.75rem">
              <span id="strengthLabel"></span>
              <span id="strengthHint" class="text-end"></span>
            </div>
          </div>

          <!-- Errori JS sommario -->
          <div id="regJsErrors" class="alert alert-warning rounded-3 small d-none mb-3"></div>

          <button type="submit" class="btn btn-dark btn-lg w-100 rounded-3">
            Crea account
          </button>
        </form>

        <div class="text-center mt-3">
          <a href="<?= BASE_URL ?>/index.php?r=auth/loginForm" class="text-muted small">
            Hai già un account? <strong>Accedi</strong>
          </a>
        </div>

        <div class="d-flex align-items-center gap-3 my-4">
          <hr class="flex-grow-1 m-0"><span class="text-muted small">oppure</span><hr class="flex-grow-1 m-0">
        </div>
        <div class="d-flex justify-content-center">
          <div id="googleRegisterBtn"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('registerForm');
  const nameInput = document.getElementById('regName');
  const emailInput= document.getElementById('regEmail');
  const pwInput   = document.getElementById('regPassword');
  const toggleBtn = document.getElementById('togglePassword');
  const eyeIcon   = document.getElementById('eyeIcon');
  const pwFeedback= document.getElementById('pwFeedback');
  const jsErrors  = document.getElementById('regJsErrors');
  const bars      = [1,2,3,4].map(i => document.getElementById('bar' + i));
  const strengthLabel = document.getElementById('strengthLabel');
  const strengthHint  = document.getElementById('strengthHint');

  // ── Mostra/nascondi password ───────────────────────────────────────────────
  toggleBtn.addEventListener('click', () => {
    const isText = pwInput.type === 'text';
    pwInput.type = isText ? 'password' : 'text';
    eyeIcon.innerHTML = isText
      ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
      : '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  });

  // ── Forza password ────────────────────────────────────────────────────────
  function getStrength(pw) {
    let score = 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/\d/.test(pw))   score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    return Math.min(4, score);
  }

  const colors  = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];
  const labels  = ['', 'Debole', 'Discreta', 'Buona', 'Ottima'];
  const hints   = [
    '',
    'Aggiungi lettere e numeri',
    'Aggiungi maiuscole o simboli',
    'Aggiungi un simbolo speciale',
    'Password sicura ✓',
  ];

  pwInput.addEventListener('input', () => {
    const pw    = pwInput.value;
    const score = pw.length === 0 ? 0 : getStrength(pw);

    bars.forEach((bar, i) => {
      bar.style.background = i < score ? colors[score] : '#e2e8f0';
    });

    strengthLabel.textContent = pw.length > 0 ? labels[score] : '';
    strengthLabel.style.color = colors[score] || '#64748b';
    strengthHint.textContent  = pw.length > 0 ? hints[score] : '';

    // Feedback inline
    if (pw.length === 0) {
      pwInput.classList.remove('is-valid', 'is-invalid');
      pwFeedback.classList.add('d-none');
    } else if (pw.length < 8) {
      pwInput.classList.add('is-invalid');
      pwInput.classList.remove('is-valid');
      pwFeedback.textContent = 'La password deve essere di almeno 8 caratteri.';
      pwFeedback.classList.remove('d-none');
    } else {
      pwInput.classList.add('is-valid');
      pwInput.classList.remove('is-invalid');
      pwFeedback.classList.add('d-none');
    }
  });

  // ── Validazione live blur ──────────────────────────────────────────────────
  function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

  nameInput.addEventListener('blur', () => {
    const ok = nameInput.value.trim().length >= 2;
    nameInput.classList.toggle('is-valid',   ok);
    nameInput.classList.toggle('is-invalid', !ok);
  });

  emailInput.addEventListener('blur', () => {
    const ok = validateEmail(emailInput.value.trim());
    emailInput.classList.toggle('is-valid',   ok);
    emailInput.classList.toggle('is-invalid', !ok);
  });

  // ── Validazione al submit ─────────────────────────────────────────────────
  form.addEventListener('submit', (e) => {
    const errs = [];

    if (nameInput.value.trim().length < 2) {
      nameInput.classList.add('is-invalid');
      errs.push('Il nome deve contenere almeno 2 caratteri.');
    }

    if (!validateEmail(emailInput.value.trim())) {
      emailInput.classList.add('is-invalid');
      errs.push('Inserisci un indirizzo email valido.');
    }

    if (pwInput.value.length < 8) {
      pwInput.classList.add('is-invalid');
      pwFeedback.textContent = 'La password deve essere di almeno 8 caratteri.';
      pwFeedback.classList.remove('d-none');
      errs.push('La password deve essere di almeno 8 caratteri.');
    }

    if (errs.length > 0) {
      e.preventDefault();
      jsErrors.innerHTML = '<strong>Correggi i seguenti errori:</strong><ul class="mb-0 mt-1">'
        + errs.map(err => `<li>${err}</li>`).join('')
        + '</ul>';
      jsErrors.classList.remove('d-none');
      jsErrors.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      jsErrors.classList.add('d-none');
    }
  });
});
</script>
