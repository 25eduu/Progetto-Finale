<?php
declare(strict_types=1);

class AccountController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  private function requireLogin(): int {
    $userId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

    if ($userId <= 0) {
      header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
      exit;
    }

    return $userId;
  }

  public function dashboard(): void {
    $userId = $this->requireLogin();

    $stmtUser = $this->pdo->prepare("SELECT id, full_name, email, wallet_balance, created_at FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      unset($_SESSION['user_id'], $_SESSION['user']);
      header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
      exit;
    }

    $stmtStats = $this->pdo->prepare("
      SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_spent
      FROM orders
      WHERE user_id = ?
    ");
    $stmtStats->execute([$userId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [
      'total_orders' => 0,
      'total_spent' => 0,
    ];

    $stmtOrders = $this->pdo->prepare("
      SELECT 
        id,
        total_amount,
        status,
        payment_method,
        payment_status,
        wallet_amount_paid,
        stripe_amount_paid,
        paypal_amount_paid,
        created_at
      FROM orders
      WHERE user_id = ?
      ORDER BY id DESC
      LIMIT 8
    ");
    $stmtOrders->execute([$userId]);
    $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

    $stmtWalletLogs = $this->pdo->prepare("
      SELECT amount, description, created_at
      FROM wallet_logs
      WHERE user_id = ?
      ORDER BY id DESC
      LIMIT 10
    ");
    $stmtWalletLogs->execute([$userId]);
    $walletLogs = $stmtWalletLogs->fetchAll(PDO::FETCH_ASSOC);

    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/account/dashboard.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }
}