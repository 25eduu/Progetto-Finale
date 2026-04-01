<h1 class="display-6 fw-semibold mb-3">Catalogo</h1>

<div class="row g-3">
  <?php foreach ($products as $p): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$p['id'] ?>">
        <div class="card product-card h-100">
          <div class="ratio ratio-1x1 bg-light">
            <img class="card-img-top object-fit-contain p-3"
                 src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($p['image_path'] ?? 'images/placeholder.png') ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>">
          </div>

          <div class="card-body">
            <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
            <div class="small text-muted"><?= htmlspecialchars($p['category_name']) ?></div>
            <div class="mt-1">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>