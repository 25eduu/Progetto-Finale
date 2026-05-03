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
        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/register">
          <?= CsrfHelper::field() ?>
          <div class="mb-3">
            <label class="form-label fw-medium">Nome completo</label>
            <input type="text" name="full_name" class="form-control form-control-lg rounded-3" placeholder="Mario Rossi" required autocomplete="name">
          </div>
          <div class="mb-3">
            <label class="form-label fw-medium">Email</label>
            <input type="email" name="email" class="form-control form-control-lg rounded-3" placeholder="nome@email.com" required autocomplete="email">
          </div>
          <div class="mb-4">
            <label class="form-label fw-medium">Password</label>
            <input type="password" name="password" class="form-control form-control-lg rounded-3" placeholder="Minimo 8 caratteri" required minlength="8" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-dark btn-lg w-100 rounded-3">Crea account</button>
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