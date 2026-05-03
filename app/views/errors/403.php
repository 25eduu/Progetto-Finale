<?php $pdo = $pdo ?? null; require __DIR__ . '/../layouts/header.php'; ?>
<div class="d-flex flex-column align-items-center justify-content-center text-center" style="min-height:55vh">
  <div class="display-1 fw-bold text-muted mb-3">403</div>
  <h1 class="h3 mb-2">Accesso negato</h1>
  <p class="text-muted mb-4">Non hai i permessi per visualizzare questa pagina.</p>
  <a href="<?= BASE_URL ?>/index.php" class="btn btn-dark rounded-pill px-4">Torna alla home</a>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
