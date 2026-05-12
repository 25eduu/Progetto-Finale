<?php
// Raggruppa prodotti per categoria
$byCategory = [];
foreach ($products as $p) {
    $byCategory[$p['category_name']][] = $p;
}
$allCategories = array_keys($byCategory);
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="display-6 fw-bold mb-1" style="letter-spacing:-.03em">Catalogo</h1>
    <p class="text-muted mb-0" style="font-size:.875rem"><?= count($products) ?> prodotti disponibili</p>
  </div>
  <a href="<?= BASE_URL ?>/index.php?r=products/search"
     class="btn btn-outline-dark rounded-pill px-4">
    🔍 Ricerca avanzata
  </a>
</div>

<!-- Filtri categoria pill -->
<?php if (count($allCategories) > 1): ?>
  <div class="d-flex gap-2 flex-wrap mb-4" id="catFilters">
    <button class="btn btn-sm btn-dark rounded-pill px-3 cat-filter active" data-cat="all">Tutti</button>
    <?php foreach ($allCategories as $cat): ?>
      <button class="btn btn-sm btn-outline-dark rounded-pill px-3 cat-filter" data-cat="<?= htmlspecialchars($cat) ?>">
        <?= htmlspecialchars($cat) ?>
      </button>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Griglia -->
<div class="row g-3" id="productGrid">
  <?php foreach ($products as $p): ?>
    <div class="col-6 col-md-4 col-lg-3 product-col" data-cat="<?= htmlspecialchars($p['category_name']) ?>">
      <a class="text-decoration-none text-dark" href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$p['id'] ?>">
        <div class="card product-card h-100">
          <div class="position-relative" style="aspect-ratio:1;background:var(--bg);overflow:hidden">
            <img class="w-100 h-100 p-3"
                 style="object-fit:contain;transition:transform .3s ease"
                 src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($p['image_path'] ?? 'images/placeholder.png') ?>"
                 alt="<?= htmlspecialchars($p['name']) ?>"
                 loading="lazy">
            <?php if ((int)$p['stock'] === 0): ?>
              <span class="badge text-bg-danger position-absolute top-0 start-0 m-2">Esaurito</span>
            <?php elseif ((int)$p['stock'] <= 3): ?>
              <span class="badge text-bg-warning position-absolute top-0 start-0 m-2">Ultimi pezzi</span>
            <?php endif; ?>
          </div>
          <div class="card-body p-3">
            <div class="badge text-bg-secondary mb-1"><?= htmlspecialchars($p['category_name']) ?></div>
            <div class="fw-semibold" style="font-size:.875rem;letter-spacing:-.01em;line-height:1.3;margin-bottom:.35rem">
              <?= htmlspecialchars($p['name']) ?>
            </div>
            <div class="fw-bold" style="font-size:1rem;letter-spacing:-.02em">
              € <?= number_format((float)$p['price'], 2, ',', '.') ?>
            </div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
  <div class="text-center py-5 text-muted">
    <div class="fs-1 mb-3">📦</div>
    <p>Nessun prodotto disponibile al momento.</p>
  </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const filters = document.querySelectorAll('.cat-filter');
  const cols    = document.querySelectorAll('.product-col');
  if (!filters.length) return;

  filters.forEach(btn => {
    btn.addEventListener('click', () => {
      filters.forEach(b => {
        b.classList.remove('btn-dark', 'active');
        b.classList.add('btn-outline-dark');
      });
      btn.classList.add('btn-dark', 'active');
      btn.classList.remove('btn-outline-dark');

      const cat = btn.dataset.cat;
      cols.forEach(col => {
        const show = cat === 'all' || col.dataset.cat === cat;
        col.style.display = show ? '' : 'none';
      });
    });
  });

  // Hover zoom immagine
  cols.forEach(col => {
    const img = col.querySelector('img');
    if (!img) return;
    col.addEventListener('mouseenter', () => img.style.transform = 'scale(1.06)');
    col.addEventListener('mouseleave', () => img.style.transform = 'scale(1)');
  });
});
</script>
