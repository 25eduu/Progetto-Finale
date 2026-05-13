<?php
/**
 * Ricerca prodotti — tecnica Postback
 *
 * Il form invia a se stesso tramite $_SERVER['PHP_SELF'].
 * Filtraggio, validazione e risultati gestiti nella stessa pagina
 * prima dell'output HTML.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';

// ── Stato iniziale ────────────────────────────────────────────────────────────

$results    = null;
$errors     = [];
$totalFound = 0;

$values = [
    'query'       => '',
    'category_id' => '',
    'price_min'   => '',
    'price_max'   => '',
    'in_stock'    => false,
    'sort'        => 'newest',
];

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$searchRequest = $_SERVER['REQUEST_METHOD'] === 'POST'
    || isset($_GET['query'])
    || isset($_GET['category_id'])
    || isset($_GET['price_min'])
    || isset($_GET['price_max'])
    || isset($_GET['in_stock'])
    || isset($_GET['sort']);

// ── Elaborazione POST / GET (postback e filtri tramite URL) ──────────────────

if ($searchRequest) {

    $values['query']       = trim($request['query']       ?? '');
    $values['category_id'] = trim($request['category_id'] ?? '');
    $values['price_min']   = trim($request['price_min']   ?? '');
    $values['price_max']   = trim($request['price_max']   ?? '');
    $values['in_stock']    = isset($request['in_stock']);
    $values['sort']        = $request['sort'] ?? 'newest';

    $priceMin = $values['price_min'] !== '' ? (float)$values['price_min'] : null;
    $priceMax = $values['price_max'] !== '' ? (float)$values['price_max'] : null;

    // ── Validazione PHP lato server ───────────────────────────────────────────

    if ($values['query'] === '' && $values['category_id'] === ''
        && $priceMin === null && $priceMax === null && !$values['in_stock']) {
        $errors['query'] = 'Inserisci almeno una parola chiave o seleziona un filtro.';
    }

    if ($priceMin !== null && $priceMin < 0) {
        $errors['price_min'] = 'Il prezzo minimo non può essere negativo.';
    }

    if ($priceMax !== null && $priceMax < 0) {
        $errors['price_max'] = 'Il prezzo massimo non può essere negativo.';
    }

    if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
        $errors['price_range'] = 'Il prezzo minimo non può essere maggiore del massimo.';
    }

    $allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc'];
    if (!in_array($values['sort'], $allowedSorts, true)) {
        $values['sort'] = 'newest';
    }

    // ── Query DB con prepared statements ─────────────────────────────────────

    if (empty($errors)) {
        $conditions = [];
        $params     = [];

        if ($values['query'] !== '') {
            $conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $params[]     = '%' . $values['query'] . '%';
            $params[]     = '%' . $values['query'] . '%';
        }

        if ($values['category_id'] !== '' && ValidationHelper::positiveInt((int)$values['category_id'])) {
            $conditions[] = 'p.category_id = ?';
            $params[]     = (int)$values['category_id'];
        }

        if ($priceMin !== null) {
            $conditions[] = 'p.price >= ?';
            $params[]     = $priceMin;
        }

        if ($priceMax !== null) {
            $conditions[] = 'p.price <= ?';
            $params[]     = $priceMax;
        }

        if ($values['in_stock']) {
            $conditions[] = 'p.stock > 0';
        }

        $where   = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $orderBy = match($values['sort']) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name_asc'   => 'p.name ASC',
            default      => 'p.id DESC',
        };

        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, p.stock, p.image_path, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            {$where}
            ORDER BY {$orderBy}
        ");
        $stmt->execute($params);
        $results    = $stmt->fetchAll();
        $totalFound = count($results);
    }
}
?>

<!--
  POSTBACK: il form invia a $_SERVER['PHP_SELF'] — stessa pagina PHP.
  L'elaborazione avviene nel blocco PHP in cima a questo file.
-->
<form
  method="post"
  action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?r=products/search"
  id="searchForm"
  novalidate>

  <!-- ── Barra di ricerca principale ──────────────────────────────────────── -->
  <h1 class="display-6 fw-bold mb-3">Cerca prodotti</h1>

  <div class="input-group input-group-lg shadow-sm mb-2">
    <input
      type="search"
      id="searchQuery"
      name="query"
      class="form-control rounded-start-3 border-end-0 <?= !empty($errors['query']) ? 'is-invalid' : '' ?>"
      value="<?= htmlspecialchars($values['query']) ?>"
      placeholder="Cerca per nome o descrizione… es. iPhone, MacBook, USB-C"
      autocomplete="off"
      autofocus>
    <button type="submit" class="btn btn-dark px-4 rounded-end-3 d-flex align-items-center gap-2">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true">
        <circle cx="11" cy="11" r="7" />
        <path d="M21 21l-4.35-4.35" />
      </svg>
      Cerca
    </button>
  </div>

  <?php if (!empty($errors['query'])): ?>
    <div class="text-danger small mb-2"><?= htmlspecialchars($errors['query']) ?></div>
  <?php endif; ?>

  <div id="jsSearchError" class="alert alert-warning rounded-3 small mb-2 d-none"></div>

  <!-- ── Filtri avanzati in una riga ───────────────────────────────────────── -->
  <div class="card border-0 bg-light rounded-4 mb-4">
    <div class="card-body p-3">
      <div class="row g-2 align-items-end">

        <div class="col-6 col-md-3">
          <label class="form-label fw-medium small mb-1">Categoria</label>
          <select id="searchCategory" name="category_id" class="form-select form-select-sm rounded-3">
            <option value="">Tutte le categorie</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= $values['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-3 col-md-2">
          <label class="form-label fw-medium small mb-1">Prezzo min (€)</label>
          <input type="number" name="price_min" id="priceMin"
                 class="form-control form-control-sm rounded-3 <?= !empty($errors['price_min']) ? 'is-invalid' : '' ?>"
                 value="<?= htmlspecialchars($values['price_min']) ?>"
                 placeholder="0" min="0" step="0.01">
        </div>

        <div class="col-3 col-md-2">
          <label class="form-label fw-medium small mb-1">Prezzo max (€)</label>
          <input type="number" name="price_max" id="priceMax"
                 class="form-control form-control-sm rounded-3 <?= !empty($errors['price_max']) ? 'is-invalid' : '' ?>"
                 value="<?= htmlspecialchars($values['price_max']) ?>"
                 placeholder="9999" min="0" step="0.01">
          <?php if (!empty($errors['price_range'])): ?>
            <div class="text-danger" style="font-size:.75rem"><?= htmlspecialchars($errors['price_range']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label fw-medium small mb-1">Ordina per</label>
          <select name="sort" id="searchSort" class="form-select form-select-sm rounded-3">
            <option value="newest"     <?= $values['sort'] === 'newest'     ? 'selected' : '' ?>>Più recenti</option>
            <option value="price_asc"  <?= $values['sort'] === 'price_asc'  ? 'selected' : '' ?>>Prezzo ↑</option>
            <option value="price_desc" <?= $values['sort'] === 'price_desc' ? 'selected' : '' ?>>Prezzo ↓</option>
            <option value="name_asc"   <?= $values['sort'] === 'name_asc'   ? 'selected' : '' ?>>Nome A–Z</option>
          </select>
        </div>

        <div class="col-6 col-md-2 d-flex align-items-center gap-3 pt-1">
          <div class="form-check mb-0">
            <input type="checkbox" id="inStock" name="in_stock" class="form-check-input"
                   value="1" <?= $values['in_stock'] ? 'checked' : '' ?>>
            <label class="form-check-label small" for="inStock">Disponibili</label>
          </div>
          <a href="<?= BASE_URL ?>/index.php?r=products/search"
             class="btn btn-outline-secondary btn-sm rounded-3 ms-auto">Reset</a>
        </div>

      </div>
    </div>
  </div>

</form>

<!-- ── Risultati ──────────────────────────────────────────────────────────── -->

<?php if ($results === null): ?>
  <div class="text-center py-5 text-muted">
    <div class="fs-1 mb-2">🔍</div>
    <p>Usa la barra in alto per cercare i prodotti che ti interessano.</p>
    <a href="<?= BASE_URL ?>/index.php?r=products/index" class="btn btn-outline-dark rounded-pill px-4 mt-1">
      Sfoglia tutto il catalogo →
    </a>
  </div>

<?php elseif (empty($results)): ?>
  <div class="text-center py-5 text-muted">
    <div class="fs-1 mb-2">😔</div>
    <p class="fw-semibold mb-1">Nessun prodotto trovato</p>
    <p class="small">Prova con termini diversi o rimuovi qualche filtro.</p>
    <a href="<?= BASE_URL ?>/index.php?r=products/search" class="btn btn-outline-dark rounded-pill px-4 mt-1">
      Nuova ricerca
    </a>
  </div>

<?php else: ?>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">
      <strong><?= $totalFound ?></strong>
      prodott<?= $totalFound === 1 ? 'o' : 'i' ?> trovat<?= $totalFound === 1 ? 'o' : 'i' ?>
      <?php if ($values['query'] !== ''): ?>
        per "<strong><?= htmlspecialchars($values['query']) ?></strong>"
      <?php endif; ?>
    </span>
  </div>

  <div class="row g-3">
    <?php foreach ($results as $p): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a class="text-decoration-none text-dark"
           href="<?= BASE_URL ?>/index.php?r=products/show&id=<?= (int)$p['id'] ?>">
          <div class="card product-card h-100">
            <div class="ratio ratio-1x1 bg-light">
              <img class="card-img-top object-fit-contain p-3"
                   src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($p['image_path'] ?? 'images/placeholder.png') ?>"
                   alt="<?= htmlspecialchars($p['name']) ?>"
                   loading="lazy">
            </div>
            <div class="card-body">
              <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($p['category_name']) ?></div>
              <div class="mt-1 fw-semibold">€ <?= number_format((float)$p['price'], 2, ',', '.') ?></div>
              <?php if ((int)$p['stock'] === 0): ?>
                <span class="badge text-bg-danger mt-1">Esaurito</span>
              <?php else: ?>
                <span class="badge text-bg-success mt-1">Disponibile</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<!-- ── Validazione JS lato client ─────────────────────────────────────────── -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form        = document.getElementById('searchForm');
  const queryInput  = document.getElementById('searchQuery');
  const categorySel = document.getElementById('searchCategory');
  const priceMin    = document.getElementById('priceMin');
  const priceMax    = document.getElementById('priceMax');
  const inStock     = document.getElementById('inStock');
  const jsError     = document.getElementById('jsSearchError');

  // Validazione live prezzo
  function checkPriceRange() {
    const min     = parseFloat(priceMin.value);
    const max     = parseFloat(priceMax.value);
    const invalid = priceMin.value && priceMax.value && min > max;
    priceMin.classList.toggle('is-invalid', invalid);
    priceMax.classList.toggle('is-invalid', invalid);
    return !invalid;
  }

  priceMin.addEventListener('input', checkPriceRange);
  priceMax.addEventListener('input', checkPriceRange);

  // Nasconde errore JS quando l'utente interagisce
  [queryInput, categorySel, priceMin, priceMax, inStock].forEach(el => {
    el.addEventListener('input',  () => jsError.classList.add('d-none'));
    el.addEventListener('change', () => jsError.classList.add('d-none'));
  });

  // Validazione al submit
  form.addEventListener('submit', (e) => {
    const hasQuery    = queryInput.value.trim() !== '';
    const hasCategory = categorySel.value !== '';
    const hasPrice    = priceMin.value !== '' || priceMax.value !== '';
    const hasStock    = inStock.checked;

    if (!hasQuery && !hasCategory && !hasPrice && !hasStock) {
      e.preventDefault();
      jsError.textContent = 'Inserisci almeno una parola chiave o seleziona un filtro.';
      jsError.classList.remove('d-none');
      queryInput.focus();
      return;
    }

    if (!checkPriceRange()) {
      e.preventDefault();
      jsError.textContent = 'Il prezzo minimo non può essere maggiore del massimo.';
      jsError.classList.remove('d-none');
    }
  });
});
</script>
