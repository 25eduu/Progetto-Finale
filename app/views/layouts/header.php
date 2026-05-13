<?php
$cartCount = 0;
if (isset($_SESSION['user_id']) || isset($_SESSION['user']['id'])) {
    $currentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id']);
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            require_once __DIR__ . '/../../models/repositories/Cart.php';
            $cartCount = (new Cart($pdo))->countItems($currentUserId);
        } catch (Throwable) { $cartCount = 0; }
    }
} else {
    foreach (($_SESSION['cart'] ?? []) as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

$headerWalletBalance = 0.0;
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $headerWalletBalance = (float)($stmt->fetchColumn() ?: 0);
}

$currentRoute = $_GET['r'] ?? '';
$isAdmin      = str_starts_with($currentRoute, 'admin');
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TechShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
  <?php if ($isAdmin): ?>
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
  <?php endif; ?>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">TechShop</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">

      <!-- ── Barra ricerca live ─────────────────────────────────────────── -->
      <div class="mx-lg-3 my-2 my-lg-0 flex-lg-grow-1 position-relative" id="navSearchWrapper">
        <div class="input-group">
          <input
            type="search"
            id="navSearchInput"
            class="form-control rounded-start-3"
            placeholder="Cerca prodotti…"
            autocomplete="off"
            aria-label="Cerca prodotti">
          <a href="<?= BASE_URL ?>/index.php?r=products/search"
             id="navSearchBtn"
             class="btn rounded-end-3 px-3"
             aria-label="Cerca prodotti">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7" />
              <path d="M21 21l-4.35-4.35" />
            </svg>
          </a>
        </div>

        <!-- Dropdown risultati live -->
        <div id="navSearchDropdown"
             class="position-absolute w-100 bg-white border rounded-3 shadow-lg mt-1 d-none"
             style="z-index:1050; top:100%">
        </div>
      </div>

      <!-- ── Link navbar ─────────────────────────────────────────────────── -->
      <ul class="navbar-nav align-items-lg-center gap-1 ms-lg-2">

        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=products/index">Prodotti</a>
        </li>

        <li class="nav-item">
          <button type="button"
                  class="btn nav-link position-relative border-0 bg-transparent"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#miniCartCanvas">
            Carrello
            <span class="badge bg-dark ms-1 <?= $cartCount > 0 ? '' : 'd-none' ?>" id="cartBadge">
              <?= $cartCount ?>
            </span>
          </button>
        </li>

        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <a class="nav-link fw-semibold text-success" href="<?= BASE_URL ?>/index.php?r=account/dashboard">
              € <?= number_format($headerWalletBalance, 2, ',', '.') ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=account/profile">
              Ciao, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Utente') ?>
            </a>
          </li>
          <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link text-danger fw-semibold" href="<?= BASE_URL ?>/index.php?r=adminDashboard/index">Admin</a>
            </li>
          <?php endif; ?>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/index.php?r=auth/loginForm">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-dark btn-sm rounded-pill px-3 ms-1" href="<?= BASE_URL ?>/index.php?r=auth/registerForm">Registrati</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<!-- Mini-cart offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="miniCartCanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Carrello</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body" id="miniCartContent">
    <div class="text-muted">Caricamento...</div>
  </div>
</div>

<main class="container my-4">