<div class="py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5 text-center">
          <div
            class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle"
            style="width: 90px; height: 90px;"
          >
            <span class="fs-1 text-success">✓</span>
          </div>

          <h1 class="display-6 fw-semibold mb-3">Ordine confermato</h1>
          <p class="text-muted mb-4">
            Grazie per il tuo acquisto. Il tuo ordine è stato registrato correttamente.
          </p>

          <?php if (!empty($orderId)): ?>
            <div class="alert alert-light border rounded-4 text-start mx-auto mb-4" style="max-width: 520px;">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Numero ordine</span>
                <strong>#<?= (int)$orderId ?></strong>
              </div>

              <?php if (!empty($orderEmail)): ?>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Email</span>
                  <strong><?= htmlspecialchars($orderEmail) ?></strong>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-dark btn-lg rounded-3 px-4">
              Continua lo shopping
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-dark btn-lg rounded-3 px-4">
              Torna alla home
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>