<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh;">
  <div style="max-width: 520px; width: 100%;">

    <h1 class="text-center mb-3">Verifica a due fattori</h1>

    <p class="text-muted text-center mb-4">
      Codice inviato a<br>
      <strong><?= htmlspecialchars($_SESSION['pending_2fa_email'] ?? '') ?></strong>
    </p>

    <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/verify2fa" class="card p-4 shadow-sm js-otp-form">
      <div class="mb-3 text-center">
        <label class="form-label d-block mb-3">Codice OTP</label>

        <div class="otp-group d-flex justify-content-center gap-2">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric" autocomplete="one-time-code">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric">
          <input type="text" class="form-control otp-box" maxlength="1" inputmode="numeric">
        </div>

        <input type="hidden" name="otp_code" id="otp_code" required>
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