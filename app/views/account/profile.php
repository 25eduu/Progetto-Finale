<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$fullName = $user['full_name'] ?? 'Utente';
$email    = $user['email']     ?? '';
$provider = $user['auth_provider'] ?? 'local';
?>

<section class="py-4">
  <div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="display-6 fw-bold mb-1">Il tuo profilo</h1>
        <p class="text-muted mb-0">Modifica le tue informazioni personali.</p>
      </div>
      <a href="<?= BASE_URL ?>/index.php?r=account/dashboard" class="btn btn-outline-dark rounded-pill px-4">
        ← Dashboard
      </a>
    </div>

    <?php if (!empty($flash['success'])): ?>
      <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">
        <?= htmlspecialchars($flash['success']) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
        <?= htmlspecialchars($flash['error']) ?>
      </div>
    <?php endif; ?>

    <div class="row g-4">

      <!-- Cambio nome -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-semibold mb-1">Informazioni personali</h2>
            <p class="text-muted small mb-4">Aggiorna il tuo nome visualizzato.</p>

            <form method="post" action="<?= BASE_URL ?>/index.php?r=account/updateProfile">
              <?= CsrfHelper::field() ?>

              <div class="mb-3">
                <label class="form-label fw-medium">Nome completo</label>
                <input type="text"
                       name="full_name"
                       class="form-control form-control-lg rounded-3"
                       value="<?= htmlspecialchars($fullName) ?>"
                       required
                       minlength="2"
                       maxlength="120">
              </div>

              <div class="mb-4">
                <label class="form-label fw-medium">Email</label>
                <input type="email"
                       class="form-control form-control-lg rounded-3 bg-light"
                       value="<?= htmlspecialchars($email) ?>"
                       disabled>
                <div class="form-text">L'email non può essere modificata.</div>
              </div>

              <button type="submit" class="btn btn-dark rounded-3 px-4">
                Salva modifiche
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Cambio password -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-semibold mb-1">Sicurezza</h2>

            <?php if ($provider === 'google'): ?>
              <p class="text-muted small mb-0">
                Il tuo account è collegato a Google. La password viene gestita direttamente da Google.
              </p>
            <?php else: ?>
              <p class="text-muted small mb-4">Scegli una password sicura di almeno 8 caratteri.</p>

              <form method="post" action="<?= BASE_URL ?>/index.php?r=account/updatePassword">
                <?= CsrfHelper::field() ?>

                <div class="mb-3">
                  <label class="form-label fw-medium">Password attuale</label>
                  <input type="password"
                         name="current_password"
                         class="form-control form-control-lg rounded-3"
                         placeholder="••••••••"
                         required
                         autocomplete="current-password">
                </div>

                <div class="mb-3">
                  <label class="form-label fw-medium">Nuova password</label>
                  <input type="password"
                         name="new_password"
                         id="newPassword"
                         class="form-control form-control-lg rounded-3"
                         placeholder="Minimo 8 caratteri"
                         required
                         minlength="8"
                         autocomplete="new-password">
                </div>

                <div class="mb-4">
                  <label class="form-label fw-medium">Conferma nuova password</label>
                  <input type="password"
                         name="confirm_password"
                         id="confirmPassword"
                         class="form-control form-control-lg rounded-3"
                         placeholder="Ripeti la nuova password"
                         required
                         autocomplete="new-password">
                  <div class="form-text text-danger d-none" id="passwordMismatch">
                    Le password non coincidono.
                  </div>
                </div>

                <button type="submit" class="btn btn-dark rounded-3 px-4" id="changePasswordBtn">
                  Cambia password
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const newPw      = document.getElementById('newPassword');
  const confirmPw  = document.getElementById('confirmPassword');
  const mismatch   = document.getElementById('passwordMismatch');
  const submitBtn  = document.getElementById('changePasswordBtn');

  if (!newPw || !confirmPw) return;

  function checkMatch() {
    const different = confirmPw.value && newPw.value !== confirmPw.value;
    mismatch.classList.toggle('d-none', !different);
    submitBtn.disabled = different;
  }

  newPw.addEventListener('input', checkMatch);
  confirmPw.addEventListener('input', checkMatch);
});
</script>
