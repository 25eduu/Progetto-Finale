<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h1 class="display-6 fw-bold mb-1" style="letter-spacing:-.03em">Il tuo carrello</h1>
    <?php if (!empty($items)): ?>
      <p class="text-muted mb-0" style="font-size:.875rem">
        <?= count($items) ?> articolo/i · Totale € <?= number_format($total, 2, ',', '.') ?>
      </p>
    <?php endif; ?>
  </div>
  <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-outline-dark btn-sm rounded-pill px-3">
    ← Continua gli acquisti
  </a>
</div>

<?php if (empty($items)): ?>
  <div class="text-center py-5">
    <div class="mb-4" style="font-size:3.5rem">🛒</div>
    <h2 class="h4 fw-bold mb-2">Il carrello è vuoto</h2>
    <p class="text-muted mb-4">Aggiungi qualcosa dal catalogo per iniziare.</p>
    <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-dark rounded-pill px-4">
      Vai al catalogo
    </a>
  </div>
<?php else: ?>
  <div class="row g-4">
    <!-- Lista prodotti -->
    <div class="col-lg-8">
      <div class="card" style="overflow:hidden">
        <?php foreach ($items as $i => $item): ?>
          <div class="p-3 <?= $i > 0 ? 'border-top' : '' ?>" style="border-color:var(--border)">
            <div class="d-flex gap-3 align-items-start">

              <!-- Immagine -->
              <a href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$item['product_id'] ?>"
                 class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                 style="width:80px;height:80px;background:var(--bg);overflow:hidden">
                <img src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($item['image_path'] ?? 'images/placeholder.png') ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>"
                     style="max-width:72px;max-height:72px;object-fit:contain;padding:.3rem">
              </a>

              <!-- Info -->
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                  <a href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$item['product_id'] ?>"
                     class="fw-semibold text-decoration-none text-dark"
                     style="font-size:.9375rem;letter-spacing:-.01em;line-height:1.3">
                    <?= htmlspecialchars($item['name']) ?>
                  </a>
                  <div class="fw-bold flex-shrink-0" style="font-size:.9375rem">
                    € <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?>
                  </div>
                </div>

                <div class="text-muted mb-2" style="font-size:.8125rem">
                  € <?= number_format((float)$item['price'], 2, ',', '.') ?> cad.
                  <?php if ((int)$item['stock'] <= 3 && (int)$item['stock'] > 0): ?>
                    · <span class="text-warning fw-medium">Solo <?= (int)$item['stock'] ?> disponibili</span>
                  <?php endif; ?>
                </div>

                <!-- Controlli quantità -->
                <div class="d-flex align-items-center gap-2">
                  <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/update" class="m-0">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <input type="hidden" name="quantity" value="<?= max(0, (int)$item['quantity'] - 1) ?>">
                    <button type="submit" class="btn btn-outline-dark btn-sm"
                            style="width:30px;height:30px;padding:0;line-height:1">−</button>
                  </form>

                  <span class="fw-semibold" style="min-width:24px;text-align:center">
                    <?= (int)$item['quantity'] ?>
                  </span>

                  <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/update" class="m-0">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <input type="hidden" name="quantity" value="<?= min((int)$item['stock'], (int)$item['quantity'] + 1) ?>">
                    <button type="submit" class="btn btn-outline-dark btn-sm"
                            style="width:30px;height:30px;padding:0;line-height:1"
                            <?= (int)$item['quantity'] >= (int)$item['stock'] ? 'disabled' : '' ?>>+</button>
                  </form>

                  <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/remove" class="m-0 ms-2">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <button type="submit" class="btn btn-sm" style="color:var(--text-muted);padding:.2rem .5rem">
                      🗑
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Riepilogo -->
    <div class="col-lg-4">
      <div class="card sticky-lg-top" style="top:90px">
        <div class="card-body p-4">
          <h2 class="h5 fw-bold mb-4" style="letter-spacing:-.02em">Riepilogo ordine</h2>

          <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
            <span class="text-muted">Subtotale (<?= count($items) ?> articoli)</span>
            <span>€ <?= number_format($total, 2, ',', '.') ?></span>
          </div>
          <div class="d-flex justify-content-between mb-3" style="font-size:.875rem">
            <span class="text-muted">Spedizione</span>
            <span class="text-success fw-medium">Gratuita</span>
          </div>

          <div class="border-top pt-3 mb-4" style="border-color:var(--border)">
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-semibold">Totale</span>
              <span class="fs-5 fw-bold" style="letter-spacing:-.02em">
                € <?= number_format($total, 2, ',', '.') ?>
              </span>
            </div>
          </div>

          <div class="d-grid gap-2">
            <a href="<?= BASE_URL ?>/index.php?r=checkout/index"
               class="btn btn-dark btn-lg rounded-3">
              Procedi al checkout →
            </a>
            <a href="<?= BASE_URL ?>/index.php?r=products/index"
               class="btn btn-outline-dark rounded-3">
              Continua gli acquisti
            </a>
          </div>

          <div class="mt-3 text-center text-muted" style="font-size:.75rem">
            🔒 Pagamento sicuro con Stripe
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
