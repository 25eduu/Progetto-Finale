<?php
$miniCount = 0;
foreach ($items as $item) {
  $miniCount += (int)$item['quantity'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h5 mb-0">Il tuo carrello</h2>
  <span class="text-muted small"><?= $miniCount ?> prodotto/i</span>
</div>

<?php if (empty($items)): ?>
  <div class="alert alert-light border">
    Il carrello è vuoto.
  </div>

  <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-dark w-100" data-close-cart="1">
    Vai al catalogo
  </a>
<?php else: ?>
  <div class="mini-cart-items">
    <?php foreach ($items as $item): ?>
      <div class="border rounded-3 p-2 mb-3">
        <div class="d-flex gap-3">
          <div class="mini-cart-thumb bg-light rounded d-flex align-items-center justify-content-center">
            <img class="img-fluid object-fit-contain p-2"
                 src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($item['image_path'] ?? 'images/placeholder.png') ?>"
                 alt="<?= htmlspecialchars($item['name']) ?>">
          </div>

          <div class="flex-grow-1">
            <div class="fw-semibold small"><?= htmlspecialchars($item['name']) ?></div>
            <div class="text-muted small mb-2">€ <?= number_format((float)$item['price'], 2, ',', '.') ?></div>

            <div class="d-flex align-items-center justify-content-between gap-2">
              <div class="btn-group btn-group-sm" role="group">
                <button type="button"
                        class="btn btn-outline-dark js-cart-decrease"
                        data-product-id="<?= (int)$item['product_id'] ?>"
                        data-quantity="<?= (int)$item['quantity'] ?>">−</button>

                <span class="btn btn-light disabled"><?= (int)$item['quantity'] ?></span>

                <button type="button"
                        class="btn btn-outline-dark js-cart-increase"
                        data-product-id="<?= (int)$item['product_id'] ?>"
                        data-quantity="<?= (int)$item['quantity'] ?>"
                        data-stock="<?= (int)$item['stock'] ?>">+</button>
              </div>

              <button type="button"
                      class="btn btn-sm btn-outline-danger js-cart-remove"
                      data-product-id="<?= (int)$item['product_id'] ?>">X</button>
            </div>

            <div class="text-end fw-semibold mt-2 small">
              € <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="border-top pt-3 mt-3">
    <div class="d-flex justify-content-between mb-3">
      <span>Totale</span>
      <strong>€ <?= number_format($total, 2, ',', '.') ?></strong>
    </div>

    <div class="d-grid gap-2">
      <a href="<?= BASE_URL ?>/index.php?r=cart/index" class="btn btn-outline-dark" data-close-cart="1">
        Vai al carrello
      </a>
      <a href="<?= BASE_URL ?>/index.php?r=checkout/index" class="btn btn-dark" data-close-cart="1">
        Procedi al checkout
      </a>
    </div>
  </div>
<?php endif; ?>