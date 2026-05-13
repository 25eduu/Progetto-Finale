<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h1 class="display-6 fw-bold mb-0">Prodotti</h1>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/index.php?r=adminDashboard/index" class="btn btn-outline-secondary rounded-pill px-4">
      ← Dashboard
    </a>
    <button class="btn btn-outline-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#importProductsModal">
      Importa CSV
    </button>
    <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
      + Nuovo prodotto
    </button>
  </div>
</div>

<?php if (!empty($flash['success'])): ?>
  <div class="alert alert-success rounded-3 mb-4"><?= htmlspecialchars($flash['success']) ?></div>
<?php endif; ?>
<?php if (!empty($flash['error'])): ?>
  <div class="alert alert-danger rounded-3 mb-4"><?= htmlspecialchars($flash['error']) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">#</th><th>Prodotto</th><th>Categoria</th>
            <th class="text-end">Prezzo</th><th class="text-center">Stock</th><th class="text-center pe-4">Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td class="ps-4 text-muted small"><?= (int)$p['id'] ?></td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-light d-flex align-items-center justify-content-center flex-shrink-0"
                       style="width:44px;height:44px;overflow:hidden">
                    <img src="<?= BASE_URL ?>/assets/<?= htmlspecialchars($p['image_path'] ?? 'images/placeholder.png') ?>"
                         class="img-fluid" style="max-height:40px;object-fit:contain">
                  </div>
                  <div class="fw-medium"><?= htmlspecialchars($p['name']) ?></div>
                </div>
              </td>
              <td><span class="badge text-bg-secondary rounded-pill"><?= htmlspecialchars($p['category_name']) ?></span></td>
              <td class="text-end">
                <input type="number" name="price" form="updateProductForm-<?= (int)$p['id'] ?>"
                       value="<?= number_format((float)$p['price'], 2, '.', '') ?>" step="0.01" min="0.00"
                       class="form-control form-control-sm text-end rounded-3" style="max-width:120px">
              </td>
              <td class="text-center">
                <input type="number" name="stock" form="updateProductForm-<?= (int)$p['id'] ?>"
                       value="<?= (int)$p['stock'] ?>" min="0"
                       class="form-control form-control-sm rounded-3 text-center" style="width:70px">
              </td>
              <td class="text-center pe-4">
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                  <form id="updateProductForm-<?= (int)$p['id'] ?>" method="post" action="<?= BASE_URL ?>/index.php?r=adminProduct/update"
                        class="d-inline-flex gap-1 align-items-center">
                    <?= CsrfHelper::field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-outline-dark btn-sm rounded-3">Salva</button>
                  </form>
                  <form method="post" action="<?= BASE_URL ?>/index.php?r=adminProduct/delete"
                        onsubmit="return confirm('Eliminare ' + <?= json_encode($p['name']) ?> + '?')">
                    <?= CsrfHelper::field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3">Elimina</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal nuovo prodotto -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 px-4 pt-4">
        <h2 class="h5 fw-semibold mb-0">Nuovo prodotto</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <form method="post" action="<?= BASE_URL ?>/index.php?r=adminProduct/create" enctype="multipart/form-data">
          <?= CsrfHelper::field() ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-medium">Nome prodotto *</label>
              <input type="text" name="name" class="form-control rounded-3" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-medium">Categoria *</label>
              <select name="category_id" class="form-select rounded-3" required>
                <option value="">Seleziona…</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Prezzo (€) *</label>
              <input type="number" name="price" class="form-control rounded-3" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Stock iniziale</label>
              <input type="number" name="stock" class="form-control rounded-3" min="0" value="0">
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Descrizione</label>
              <textarea name="description" class="form-control rounded-3" rows="3"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-medium">Immagine (JPG, PNG, WebP)</label>
              <input type="file" name="image" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
          <div class="d-flex gap-2 justify-content-end mt-4">
            <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Annulla</button>
            <button type="submit" class="btn btn-dark rounded-3 px-4">Crea prodotto</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal import prodotti -->
<div class="modal fade" id="importProductsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 px-4 pt-4">
        <h2 class="h5 fw-semibold mb-0">Importa prodotti</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <p class="text-muted">Carica un file CSV con i prodotti e un archivio ZIP con le immagini. I file immagine devono essere nominati come specificato nella colonna <code>image_filename</code>.</p>
        <form method="post" action="<?= BASE_URL ?>/index.php?r=adminProduct/importCsv" enctype="multipart/form-data">
          <?= CsrfHelper::field() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-medium">File CSV *</label>
              <input type="file" name="csv" class="form-control rounded-3" accept=".csv" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-medium">Archivio immagini (ZIP)</label>
              <input type="file" name="images_zip" class="form-control rounded-3" accept=".zip">
            </div>
          </div>
          <div class="mt-3 small text-muted">
            Intestazioni CSV richieste: <code>category,name,description,price,stock,image_filename</code>.
          </div>
          <div class="d-flex gap-2 justify-content-end mt-4">
            <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Annulla</button>
            <button type="submit" class="btn btn-dark rounded-3 px-4">Importa</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
