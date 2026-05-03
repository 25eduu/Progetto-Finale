<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$canUseWalletOnly = $walletBalance >= $total;
$canUseMixed      = $walletBalance > 0 && $walletBalance < $total;
?>

<h1 class="display-6 fw-semibold mb-2">Checkout</h1>
<p class="text-muted mb-4">Completa il tuo ordine scegliendo il metodo di pagamento che preferisci.</p>

<?php if (!empty($flash['error'])): ?>
  <div class="alert alert-danger rounded-4 shadow-sm border-0 checkout-alert mb-4">
    <?= htmlspecialchars($flash['error']) ?>
  </div>
<?php endif; ?>

<div class="row g-4 align-items-start">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div>
            <h2 class="h4 mb-1">Dati ordine</h2>
            <p class="text-muted mb-0 small">Inserisci i dati e scegli come vuoi pagare.</p>
          </div>
          <span class="badge text-bg-dark rounded-pill px-3 py-2">Checkout sicuro</span>
        </div>

        <form method="post" action="<?= BASE_URL ?>/index.php?r=checkout/process" id="checkoutForm">
          <?= CsrfHelper::field() ?>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-medium">Nome completo</label>
              <input type="text" name="name" class="form-control form-control-lg rounded-3"
                     placeholder="Mario Rossi"
                     value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Email</label>
              <input type="email" name="email" class="form-control form-control-lg rounded-3"
                     placeholder="nome@email.com"
                     value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" required>
            </div>
          </div>

          <hr class="my-4">

          <div class="mb-3">
            <label class="form-label fw-medium d-block mb-3">Metodo di pagamento</label>
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="card">
            <input type="hidden" id="checkoutTotalValue"  value="<?= htmlspecialchars((string)$total) ?>">
            <input type="hidden" id="checkoutWalletValue" value="<?= htmlspecialchars((string)$walletBalance) ?>">

            <div class="vstack gap-3">
              <button type="button" class="payment-option active" data-method="card">
                <div>
                  <div class="payment-option__title">Carta di credito o debito</div>
                  <div class="payment-option__desc">Verrai reindirizzato alla pagina sicura di Stripe</div>
                </div>
                <div class="payment-option__icon">💳</div>
              </button>

              <button type="button" class="payment-option" data-method="paypal">
                <div>
                  <div class="payment-option__title">PayPal</div>
                  <div class="payment-option__desc">Prossimamente disponibile</div>
                </div>
                <div class="payment-option__icon">🅿️</div>
              </button>

              <?php if ($canUseWalletOnly): ?>
                <button type="button" class="payment-option" data-method="wallet">
                  <div>
                    <div class="payment-option__title">Solo wallet</div>
                    <div class="payment-option__desc">
                      Saldo disponibile: € <?= number_format($walletBalance, 2, ',', '.') ?>
                    </div>
                  </div>
                  <div class="payment-option__icon">👛</div>
                </button>
              <?php endif; ?>

              <?php if ($canUseMixed): ?>
                <button type="button" class="payment-option" data-method="mixed">
                  <div>
                    <div class="payment-option__title">Wallet + carta</div>
                    <div class="payment-option__desc">
                      Scala prima il wallet (€ <?= number_format($walletBalance, 2, ',', '.') ?>), poi Stripe per il residuo
                    </div>
                  </div>
                  <div class="payment-option__icon">🔀</div>
                </button>
              <?php endif; ?>
            </div>
          </div>

          <div id="paymentPanels" class="mt-4">
            <div class="payment-panel active" data-panel="card">
              <div class="rounded-4 border p-4 bg-light-subtle">
                <div class="fw-semibold mb-1">Pagamento con carta</div>
                <div class="text-muted small">Dopo aver confermato verrai reindirizzato su Stripe per inserire i dati della carta in modo sicuro.</div>
              </div>
            </div>
            <div class="payment-panel" data-panel="paypal">
              <div class="rounded-4 border p-4 bg-light-subtle">
                <div class="fw-semibold mb-1">PayPal — prossimamente</div>
                <div class="text-muted small">L'integrazione PayPal è in arrivo. Usa un altro metodo per ora.</div>
              </div>
            </div>
            <?php if ($canUseWalletOnly): ?>
              <div class="payment-panel" data-panel="wallet">
                <div class="rounded-4 border p-4 bg-light-subtle">
                  <div class="fw-semibold mb-1">Pagamento con wallet</div>
                  <div class="text-muted small mb-2">L'importo totale verrà scalato dal saldo del tuo account.</div>
                  <div class="fw-bold">Saldo disponibile: € <?= number_format($walletBalance, 2, ',', '.') ?></div>
                </div>
              </div>
            <?php endif; ?>
            <?php if ($canUseMixed): ?>
              <div class="payment-panel" data-panel="mixed">
                <div class="rounded-4 border p-4 bg-light-subtle">
                  <div class="fw-semibold mb-1">Wallet + carta</div>
                  <div class="text-muted small">Useremo prima il saldo wallet, poi Stripe per il resto.</div>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-4">
            <label class="form-label fw-medium">Note ordine <span class="text-muted fw-normal">(facoltative)</span></label>
            <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Istruzioni per la spedizione…"></textarea>
          </div>

          <div class="d-grid gap-2 mt-4">
            <button class="btn btn-dark btn-lg rounded-3" id="checkoutSubmitBtn" type="submit">
              Vai al pagamento sicuro
            </button>
            <a href="<?= BASE_URL ?>/index.php?r=cart/index" class="btn btn-outline-dark rounded-3">
              Torna al carrello
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Riepilogo -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 sticky-lg-top checkout-summary-card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="h4 mb-0">Riepilogo</h2>
          <span class="text-muted small"><?= count($items) ?> articolo/i</span>
        </div>
        <div class="vstack gap-3">
          <?php foreach ($items as $item): ?>
            <div class="d-flex justify-content-between align-items-start gap-3">
              <div>
                <div class="fw-semibold small"><?= htmlspecialchars($item['name']) ?></div>
                <div class="text-muted small">Qnt: <?= (int)$item['quantity'] ?></div>
              </div>
              <div class="fw-semibold small text-end">
                € <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <hr class="my-4">
        <div class="d-flex justify-content-between mb-2 text-muted">
          <span>Subtotale</span><span>€ <?= number_format($total, 2, ',', '.') ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2 text-muted">
          <span>Spedizione</span><span class="text-success">Gratuita</span>
        </div>
        <div id="checkoutWalletSummary" class="d-none">
          <div class="d-flex justify-content-between mb-2 text-success">
            <span>Wallet usato</span><span id="checkoutWalletUsed">- € 0,00</span>
          </div>
          <div class="d-flex justify-content-between mb-2 text-muted">
            <span>Resto con carta</span><span id="checkoutCardRemaining">€ 0,00</span>
          </div>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
          <span class="fw-semibold">Totale</span>
          <span class="fs-5 fw-bold" id="checkoutDisplayedTotal">€ <?= number_format($total, 2, ',', '.') ?></span>
        </div>
        <?php if ($walletBalance > 0): ?>
          <div class="alert alert-success mt-4 mb-0 rounded-4 small">
            Hai <strong>€ <?= number_format($walletBalance, 2, ',', '.') ?></strong> nel tuo wallet.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>