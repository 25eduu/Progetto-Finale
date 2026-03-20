<h1 class="display-6 fw-semibold mb-4">Carrello</h1>

<?php if (empty($items)): ?>
  <div class="alert alert-info">
    Il tuo carrello è vuoto.
  </div>

  <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-dark">
    Vai al catalogo
  </a>
<?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <?php foreach ($items as $item): ?>
        <div class="card mb-3">
          <div class="card-body">
            <div class="row align-items-center g-3">
              <div class="col-3 col-md-2">
                <div class="ratio ratio-1x1 bg-light rounded">
                  <img class="img-fluid object-fit-contain p-2"
                       src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($item['image_path'] ?? 'images/placeholder.png') ?>"
                       alt="<?= htmlspecialchars($item['name']) ?>">
                </div>
              </div>

              <div class="col-9 col-md-4">
                <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                <div class="text-muted small">
                  € <?= number_format((float)$item['price'], 2, ',', '.') ?>
                </div>
              </div>

              <div class="col-6 col-md-3">
                <div class="d-flex align-items-center gap-2">
                  <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/update" class="m-0">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <input type="hidden" name="quantity" value="<?= max(0, (int)$item['quantity'] - 1) ?>">
                    <button type="submit" class="btn btn-outline-dark btn-sm">−</button>
                  </form>

                  <span class="px-2 fw-semibold"><?= (int)$item['quantity'] ?></span>

                  <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/update" class="m-0">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <input type="hidden" name="quantity" value="<?= min((int)$item['stock'], (int)$item['quantity'] + 1) ?>">
                    <button type="submit" class="btn btn-outline-dark btn-sm">+</button>
                  </form>
                </div>
              </div>

              <div class="col-3 col-md-2 text-md-end">
                <div class="fw-semibold">
                  € <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?>
                </div>
              </div>

              <div class="col-3 col-md-1 text-md-end">
                <form method="post" action="<?= BASE_URL ?>/index.php?r=cart/remove" class="m-0">
                  <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">X</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h2 class="h5 mb-3">Riepilogo ordine</h2>

          <div class="d-flex justify-content-between mb-2">
            <span>Totale</span>
            <strong>€ <?= number_format($total, 2, ',', '.') ?></strong>
          </div>

          <a href="<?= BASE_URL ?>/index.php?r=checkout/index" class="btn btn-dark w-100 mt-3">
            Procedi al checkout
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>