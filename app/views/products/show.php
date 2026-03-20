<a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-outline-secondary mb-3">
  ← Torna al catalogo
</a>

<div class="row g-4">
  <div class="col-md-5">
    <div class="bg-light rounded-4 p-3">
      <img class="img-fluid object-fit-contain w-100"
           src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($product['image_path'] ?? 'images/placeholder.png') ?>"
           alt="<?= htmlspecialchars($product['name']) ?>">
    </div>
  </div>

  <div class="col-md-7">
    <div class="d-flex align-items-center gap-2">
      <span class="badge text-bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
      <?php if ((int)$product['stock'] > 0): ?>
        <span class="badge text-bg-success">Disponibile</span>
      <?php else: ?>
        <span class="badge text-bg-danger">Esaurito</span>
      <?php endif; ?>
    </div>

    <h1 class="mt-2 fw-semibold"><?= htmlspecialchars($product['name']) ?></h1>
    <div class="fs-4 mb-3">€ <?= number_format((float)$product['price'], 2, ',', '.') ?></div>

    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

    <?php if (!empty($specs)): ?>
      <h2 class="h5 mt-4">Specifiche tecniche</h2>
      <div class="table-responsive">
        <table class="table table-sm">
          <tbody>
            <?php foreach ($specs as $s): ?>
              <tr>
                <th class="w-25"><?= htmlspecialchars($s['spec_key']) ?></th>
                <td><?= htmlspecialchars($s['spec_value']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <form class="mt-2 js-add-to-cart-form" data-product-id="<?= (int)$product['id'] ?>">
  <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
  <button type="submit" class="btn btn-dark btn-lg">
    Aggiungi al carrello
  </button>
</form>

<div id="toastArea" class="mt-3"></div>
  </div>
</div>

<?php if (!empty($related)): ?>
  <section class="mt-5">
    <h2 class="h4 fw-semibold mb-3">Prodotti correlati</h2>

    <div class="row g-3">
      <?php foreach ($related as $r): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$r['id'] ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
              <div class="ratio ratio-1x1 bg-light">
                <img class="card-img-top object-fit-contain p-3"
                     src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($r['image_path'] ?? 'images/placeholder.png') ?>"
                     alt="<?= htmlspecialchars($r['name']) ?>">
              </div>
              <div class="card-body">
                <div class="fw-semibold"><?= htmlspecialchars($r['name']) ?></div>
                <div class="text-muted small">
                  € <?= number_format((float)$r['price'], 2, ',', '.') ?>
                </div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
<?php if (!empty($accessories)): ?>
  <section class="mt-5">
    <h2 class="h4 fw-semibold mb-3">Spesso acquistati insieme</h2>

    <div class="row g-3">
      <?php foreach ($accessories as $a): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$a['id'] ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
              <div class="ratio ratio-1x1 bg-light">
                <img class="card-img-top object-fit-contain p-3"
                     src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($a['image_path'] ?? 'images/placeholder.png') ?>"
                     alt="<?= htmlspecialchars($a['name']) ?>">
              </div>
              <div class="card-body">
                <div class="fw-semibold"><?= htmlspecialchars($a['name']) ?></div>
                <div class="text-muted small">
                  € <?= number_format((float)$a['price'], 2, ',', '.') ?>
                </div>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>