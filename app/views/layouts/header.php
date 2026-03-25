<?php
$cartCount = 0;

if (isset($_SESSION['user_id']) || isset($_SESSION['user']['id'])) {
  $currentUserId = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : (int)$_SESSION['user']['id'];

  if (isset($pdo) && $pdo instanceof PDO) {
    try {
      require_once __DIR__ . '/../../models/Cart.php';
      $cartModelHeader = new Cart($pdo);
      $cartCount = $cartModelHeader->countItems($currentUserId);
    } catch (Throwable $e) {
      $cartCount = 0;
    }
  } else {
    foreach (($_SESSION['cart'] ?? []) as $item) {
      $cartCount += (int)$item['quantity'];
    }
  }
} else {
  foreach (($_SESSION['cart'] ?? []) as $item) {
    $cartCount += (int)$item['quantity'];
  }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TechShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">TechShop</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=products/index">Prodotti</a>
        </li>

        <li class="nav-item">
          <button type="button"
                  class="btn nav-link position-relative border-0 bg-transparent"
                  id="openMiniCartBtn"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#miniCartCanvas"
                  aria-controls="miniCartCanvas">
            Carrello
            <?php if ($cartCount > 0): ?>
              <span class="badge bg-dark ms-1" id="cartBadge"><?= $cartCount ?></span>
            <?php else: ?>
              <span class="badge bg-dark ms-1 d-none" id="cartBadge">0</span>
            <?php endif; ?>
          </button>
        </li>

        <?php if (!empty($_SESSION['user_id'])): ?>
        <li class="nav-item">
          <span class="nav-link">Ciao, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Utente') ?></span>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=auth/logout">Logout</a>
        </li>
      <?php else: ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=auth/loginForm">Login</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=auth/registerForm">Registrati</a>
        </li>
      <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end" tabindex="-1" id="miniCartCanvas" aria-labelledby="miniCartCanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="miniCartCanvasLabel">Carrello</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Chiudi"></button>
  </div>
  <div class="offcanvas-body" id="miniCartContent">
    <div class="text-muted">Caricamento carrello...</div>
  </div>
</div>

<main class="container my-4">