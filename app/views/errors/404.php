<?php $pdo = $pdo ?? null; require __DIR__ . '/../layouts/header.php'; ?>
<div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height:55vh">
  <div class="display-1 fw-bold text-muted mb-3">404</div>
  <h1 class="h3 mb-2">Pagina non trovata</h1>
  <p class="text-muted mb-4">La pagina che cerchi non esiste o è stata spostata.</p>
  <a href="<?= BASE_URL ?>/index.php" class="btn btn-dark rounded-pill px-4">Torna alla home</a>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
