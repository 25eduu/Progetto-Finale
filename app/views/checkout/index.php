<h1 class="display-6 fw-semibold mb-4">Checkout</h1>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h2 class="h5 mb-3">Dati ordine</h2>

        <form method="post" action="<?= BASE_URL ?>/index.php?r=checkout/process">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Metodo di pagamento</label>
            <select name="payment_method" class="form-select" required>
              <option value="card">Carta</option>
              <option value="paypal">PayPal</option>
              <option value="wallet">Solo wallet</option>
              <option value="mixed">Wallet + carta</option>
            </select>
          </div>

          <?php if ($walletBalance > 0): ?>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWallet">
              <label class="form-check-label" for="useWallet">
                Usa saldo wallet disponibile (€ <?= number_format($walletBalance, 2, ',', '.') ?>)
              </label>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Note ordine</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>

          <button class="btn btn-dark w-100">
            Conferma ordine
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h2 class="h5 mb-3">Riepilogo</h2>

        <?php foreach ($items as $item): ?>
          <div class="d-flex justify-content-between small mb-2">
            <span><?= htmlspecialchars($item['name']) ?> x<?= (int)$item['quantity'] ?></span>
            <span>€ <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?></span>
          </div>
        <?php endforeach; ?>

        <hr>

        <div class="d-flex justify-content-between mb-2">
          <span>Totale</span>
          <strong>€ <?= number_format($total, 2, ',', '.') ?></strong>
        </div>

        <?php if ($walletBalance > 0): ?>
          <div class="d-flex justify-content-between small text-muted">
            <span>Saldo wallet disponibile</span>
            <span>€ <?= number_format($walletBalance, 2, ',', '.') ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>