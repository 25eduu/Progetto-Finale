<?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; ?>
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
        <form method="post" action="<?= BASE_URL ?>/index.php?r=auth/login">
          <?= CsrfHelper::field() ?>
          <div class="mb-3">
            <label class="form-label fw-medium">Email</label>
            <input type="email" name="email" class="form-control form-control-lg rounded-3" placeholder="nome@email.com" required autocomplete="email">
          </div>
          <div class="mb-4">
            <label class="form-label fw-medium">Password</label>
            <input type="password" name="password" class="form-control form-control-lg rounded-3" placeholder="••••••••" required autocomplete="current-password">
          </div>
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