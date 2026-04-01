<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh;">
  <div style="max-width: 420px; width: 100%;">

    <h1 class="text-center mb-3">Verifica a due fattori</h1>

    <p class="text-muted text-center">
      Codice inviato a<br>
      <strong><?= htmlspecialchars($_SESSION['pending_2fa_email'] ?? '') ?></strong>
    </p>

    <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/verify2fa" class="card p-4 shadow-sm">

      <div class="mb-3 text-center">
        <label class="form-label">Codice OTP</label>
        <input
          type="text"
          class="form-control text-center fs-4"
          name="otp_code"
          maxlength="6"
          required>
      </div>

      <button class="btn btn-dark w-100 mb-2">
        Verifica
      </button>

      <a href="<?= BASE_URL ?>/index.php?r=auth/resend2fa" class="btn btn-outline-secondary w-100">
        Invia di nuovo il codice
      </a>

    </form>
  </div>
</div>