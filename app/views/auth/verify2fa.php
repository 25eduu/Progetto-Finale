<?php require_once __DIR__ . '/../../helpers/security/CsrfHelper.php'; ?>
<div class="d-flex justify-content-center align-items-center" style="min-height:70vh">
  <div style="max-width:520px;width:100%">
    <?php if (!empty($flash['error'])): ?>
      <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-dark mb-3" style="width:56px;height:56px">
          <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h1 class="h3 fw-semibold mb-1">Verifica identità</h1>
        <p class="text-muted mb-0">Codice inviato a<br><strong><?= htmlspecialchars($_SESSION['pending_2fa_email'] ?? '') ?></strong></p>
      </div>
      <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/verify2fa" class="js-otp-form">
        <?= CsrfHelper::field() ?>
        <div class="mb-4 text-center">
          <label class="form-label fw-medium d-block mb-3">Codice a 6 cifre</label>
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
        <button class="btn btn-dark btn-lg w-100 rounded-3 mb-2">Verifica</button>
        <a href="<?= BASE_URL ?>/index.php?r=auth/resend2fa" class="btn btn-outline-secondary w-100 rounded-3">Invia di nuovo il codice</a>
      </form>
      <p class="text-muted small text-center mt-3 mb-0">
        Il codice scade dopo 10 minuti.
        <a href="<?= BASE_URL ?>/index.php?r=auth/loginForm">Torna al login</a>
      </p>
    </div>
  </div>
</div>