<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$canUseWalletOnly = $walletBalance >= $total;
$canUseMixed      = $walletBalance > 0 && $walletBalance < $total;
?>

<div class="checkout-progress mb-5">
  <div class="checkout-step active" data-step="01">Dati ordine</div>
  <div class="checkout-step" data-step="02">Pagamento</div>
  <div class="checkout-step" data-step="03">Conferma</div>
</div>

<?php if (!empty($flash['error'])): ?>
  <div class="alert alert-danger rounded-4 shadow-sm border-0 checkout-alert mb-4">
    <?= htmlspecialchars($flash['error']) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/index.php?r=checkout/process" id="checkoutForm" novalidate>
  <?= CsrfHelper::field() ?>
  <input type="hidden" name="payment_method" id="paymentMethodInput" value="card">
  <input type="hidden" id="checkoutTotalValue"  value="<?= htmlspecialchars((string)$total) ?>">
  <input type="hidden" id="checkoutWalletValue" value="<?= htmlspecialchars((string)$walletBalance) ?>">

  <div class="row g-4 align-items-start">

    <!-- ══ COLONNA SINISTRA — dati e spedizione ══ -->
    <div class="col-lg-7">

      <!-- 1. Dati personali -->
      <div class="checkout-section mb-4">
        <div class="checkout-section__header">
          <span class="checkout-section__num">1</span>
          <div>
            <h2 class="checkout-section__title">Dati personali</h2>
            <p class="checkout-section__sub">Chi effettua l'ordine</p>
          </div>
        </div>
        <div class="checkout-section__body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="co-name">Nome completo *</label>
              <input type="text" id="co-name" name="name"
                     class="form-control form-control-lg rounded-3"
                     placeholder="Mario Rossi"
                     value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? '') ?>"
                     required autocomplete="name">
              <div class="invalid-feedback">Campo obbligatorio.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="co-email">Email *</label>
              <input type="email" id="co-email" name="email"
                     class="form-control form-control-lg rounded-3"
                     placeholder="mario@email.com"
                     value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>"
                     required autocomplete="email">
              <div class="invalid-feedback">Inserisci un'email valida.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="co-phone">Telefono</label>
              <input type="tel" id="co-phone" name="phone"
                     class="form-control form-control-lg rounded-3"
                     placeholder="+39 333 000 0000"
                     autocomplete="tel">
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Indirizzo di spedizione -->
      <div class="checkout-section mb-4">
        <div class="checkout-section__header">
          <span class="checkout-section__num">2</span>
          <div>
            <h2 class="checkout-section__title">Indirizzo di spedizione</h2>
            <p class="checkout-section__sub">Dove consegnare il tuo ordine</p>
          </div>
        </div>
        <div class="checkout-section__body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="co-address">Via e numero civico *</label>
              <input type="text" id="co-address" name="shipping_address"
                     class="form-control form-control-lg rounded-3"
                     placeholder="Via Roma, 42"
                     required autocomplete="street-address">
              <div class="invalid-feedback">Campo obbligatorio.</div>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="co-city">Città *</label>
              <input type="text" id="co-city" name="shipping_city"
                     class="form-control form-control-lg rounded-3"
                     placeholder="Milano"
                     required autocomplete="address-level2">
              <div class="invalid-feedback">Campo obbligatorio.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="co-zip">CAP *</label>
              <input type="text" id="co-zip" name="shipping_zip"
                     class="form-control form-control-lg rounded-3"
                     placeholder="20100"
                     maxlength="10" required autocomplete="postal-code">
              <div class="invalid-feedback">Campo obbligatorio.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="co-province">Provincia</label>
              <input type="text" id="co-province" name="shipping_province"
                     class="form-control form-control-lg rounded-3"
                     placeholder="MI"
                     maxlength="5" autocomplete="address-level1">
            </div>
            <div class="col-12">
              <label class="form-label" for="co-country">Paese *</label>
              <select id="co-country" name="shipping_country"
                      class="form-select form-select-lg rounded-3"
                      required autocomplete="country">
                <option value="IT" selected>🇮🇹 Italia</option>
                <option value="CH">🇨🇭 Svizzera</option>
                <option value="SM">🇸🇲 San Marino</option>
                <option value="DE">🇩🇪 Germania</option>
                <option value="FR">🇫🇷 Francia</option>
                <option value="ES">🇪🇸 Spagna</option>
                <option value="AT">🇦🇹 Austria</option>
                <option value="BE">🇧🇪 Belgio</option>
                <option value="NL">🇳🇱 Paesi Bassi</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Note ordine -->
      <div class="checkout-section">
        <div class="checkout-section__header">
          <span class="checkout-section__num">3</span>
          <div>
            <h2 class="checkout-section__title">Note aggiuntive</h2>
            <p class="checkout-section__sub">Istruzioni per la consegna (facoltativo)</p>
          </div>
        </div>
        <div class="checkout-section__body">
          <textarea name="notes" id="co-notes"
                    class="form-control rounded-3" rows="3"
                    placeholder="Es. Citofono al piano 2, lasciare al portiere…"></textarea>
        </div>
      </div>

    </div><!-- /col-lg-7 -->

    <!-- ══ COLONNA DESTRA — riepilogo + pagamento ══ -->
    <div class="col-lg-5">
      <div class="sticky-lg-top checkout-summary-card">

        <!-- Riepilogo ordine -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h2 class="h5 fw-bold mb-0">Riepilogo</h2>
              <span class="badge text-bg-secondary rounded-pill"><?= count($items) ?> articolo/i</span>
            </div>

            <div class="vstack gap-2 mb-3">
              <?php foreach ($items as $item): ?>
                <div class="d-flex align-items-center gap-3">
                  <div class="co-thumb">
                    <img src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($item['image_path'] ?? 'images/placeholder.png') ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>">
                  </div>
                  <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="text-muted" style="font-size:.75rem">Qnt: <?= (int)$item['quantity'] ?></div>
                  </div>
                  <div class="fw-semibold small text-nowrap">
                    € <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <hr class="my-3">

            <div class="d-flex justify-content-between text-muted small mb-1">
              <span>Subtotale</span>
              <span>€ <?= number_format($total, 2, ',', '.') ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-1">
              <span>Spedizione</span>
              <span class="text-success fw-medium">Gratuita</span>
            </div>

            <div id="checkoutWalletSummary" class="d-none">
              <div class="d-flex justify-content-between small text-success mb-1">
                <span>Wallet usato</span>
                <span id="checkoutWalletUsed">- € 0,00</span>
              </div>
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Resto con carta</span>
                <span id="checkoutCardRemaining">€ 0,00</span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
              <span class="fw-bold">Totale</span>
              <span class="fs-5 fw-bold" id="checkoutDisplayedTotal">
                € <?= number_format($total, 2, ',', '.') ?>
              </span>
            </div>

            <?php if ($walletBalance > 0): ?>
              <div class="alert alert-success mt-3 mb-0 rounded-3 py-2 small">
                💰 Hai <strong>€ <?= number_format($walletBalance, 2, ',', '.') ?></strong> nel wallet
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Metodo di pagamento -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Metodo di pagamento</h2>

            <div class="co-methods">

              <label class="co-method active" data-method="card">
                <input type="radio" name="_pm" value="card" checked hidden>
                <div class="co-method__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/><path d="M6 15h4"/>
                  </svg>
                </div>
                <div class="co-method__info">
                  <span class="co-method__name">Carta di credito / debito</span>
                  <span class="co-method__desc">Pagamento sicuro via Stripe</span>
                </div>
                <div class="co-method__check">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </label>

              <label class="co-method co-method--disabled" data-method="paypal">
                <input type="radio" name="_pm" value="paypal" disabled hidden>
                <div class="co-method__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 5h5a3 3 0 0 1 3 3c0 .8-.3 1.5-.8 2"/>
                    <path d="M7 19h5.5a3 3 0 0 0 3-3v-1.5"/><path d="M9 16.5h2"/>
                  </svg>
                </div>
                <div class="co-method__info">
                  <span class="co-method__name">PayPal</span>
                  <span class="co-method__desc">Prossimamente disponibile</span>
                </div>
                <span class="badge text-bg-warning rounded-pill ms-auto" style="font-size:.65rem">In arrivo</span>
              </label>

              <?php if ($canUseWalletOnly): ?>
              <label class="co-method" data-method="wallet">
                <input type="radio" name="_pm" value="wallet" hidden>
                <div class="co-method__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 8h16a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z"/>
                    <path d="M16 12h2"/>
                  </svg>
                </div>
                <div class="co-method__info">
                  <span class="co-method__name">Solo wallet</span>
                  <span class="co-method__desc">Saldo: € <?= number_format($walletBalance, 2, ',', '.') ?></span>
                </div>
                <div class="co-method__check">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </label>
              <?php endif; ?>

              <?php if ($canUseMixed): ?>
              <label class="co-method" data-method="mixed">
                <input type="radio" name="_pm" value="mixed" hidden>
                <div class="co-method__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 8h16a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z"/>
                    <path d="M2 10h20"/><path d="M16 14h2"/>
                  </svg>
                </div>
                <div class="co-method__info">
                  <span class="co-method__name">Wallet + carta</span>
                  <span class="co-method__desc">
                    Scala € <?= number_format($walletBalance, 2, ',', '.') ?> dal wallet, il resto con Stripe
                  </span>
                </div>
                <div class="co-method__check">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </label>
              <?php endif; ?>

            </div>

            <div id="co-method-detail" class="co-method-detail mt-3"></div>

          </div>
        </div>

        <!-- CTA -->
        <div class="d-grid gap-2">
          <button type="submit" id="checkoutSubmitBtn"
                  class="btn btn-dark btn-lg rounded-3 py-3 fw-semibold">
            Vai al pagamento sicuro
          </button>
          <p class="text-center text-muted mb-0" style="font-size:.72rem; letter-spacing:.02em">
            🔒 Trasmissione crittografata · Protezione 3D Secure
          </p>
          <a href="<?= BASE_URL ?>/index.php?r=cart/index" class="btn btn-outline-dark rounded-3">
            ← Torna al carrello
          </a>
        </div>

      </div>
    </div><!-- /col-lg-5 -->

  </div>
</form>