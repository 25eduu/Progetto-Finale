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

    $stmtUser = $this->pdo->prepare("SELECT id, full_name, email, wallet_balance, role, created_at FROM users WHERE id = ? LIMIT 1");
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

  public function rechargeWallet(): void
  {
    CsrfHelper::validate();
    
    $userId = $this->requireLogin();
    $amount = (float)($_POST['amount'] ?? 0);

    if ($amount <= 0) {
      Flash::error('Importo non valido.', BASE_URL . '/index.php?r=account/dashboard');
    }

    $this->pdo->beginTransaction();
    try {
      $stmt = $this->pdo->prepare("
        UPDATE users
        SET wallet_balance = wallet_balance + ?
        WHERE id = ?
      ");
      $stmt->execute([$amount, $userId]);

      $this->pdo->prepare("
        INSERT INTO wallet_logs (user_id, amount, description, created_at)
        VALUES (?, ?, ?, NOW())
      ")->execute([$userId, $amount, 'Ricarica manuale']);

      $this->pdo->commit();

      // Aggiorna sessione
      if (isset($_SESSION['user'])) {
        $_SESSION['user']['wallet_balance'] = (float)($_SESSION['user']['wallet_balance'] ?? 0) + $amount;
      }

      Flash::success('Wallet ricaricato con successo di € ' . number_format($amount, 2, ',', '.'), BASE_URL . '/index.php?r=account/dashboard');
    } catch (Throwable $e) {
      $this->pdo->rollBack();
      Flash::error('Errore durante la ricarica: ' . $e->getMessage(), BASE_URL . '/index.php?r=account/dashboard');
    }
  }
  public function profile(): void
  {
      $userId = $this->requireLogin();
 
      $stmt = $this->pdo->prepare("SELECT id, full_name, email, auth_provider FROM users WHERE id = ? LIMIT 1");
      $stmt->execute([$userId]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
      if (!$user) {
          header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
          exit;
      }
 
      $flash = Flash::get();
      $pdo   = $this->pdo;
      require __DIR__ . '/../views/layouts/header.php';
      require __DIR__ . '/../views/account/profile.php';
      require __DIR__ . '/../views/layouts/footer.php';
  }
 
  public function updateProfile(): void
  {
      CsrfHelper::validate();
      $userId = $this->requireLogin();
 
      $fullName = trim($_POST['full_name'] ?? '');
 
      if (strlen($fullName) < 2) {
          Flash::error('Il nome deve contenere almeno 2 caratteri.', BASE_URL . '/index.php?r=account/profile');
      }
 
      if (strlen($fullName) > 120) {
          Flash::error('Il nome non può superare i 120 caratteri.', BASE_URL . '/index.php?r=account/profile');
      }
 
      $this->pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")
          ->execute([$fullName, $userId]);
 
      // Aggiorna sessione
      if (isset($_SESSION['user'])) {
          $_SESSION['user']['full_name'] = $fullName;
      }
 
      Flash::success('Nome aggiornato con successo.', BASE_URL . '/index.php?r=account/profile');
  }
 
  public function updatePassword(): void
  {
      CsrfHelper::validate();
      $userId = $this->requireLogin();
 
      $currentPassword = $_POST['current_password'] ?? '';
      $newPassword     = $_POST['new_password']     ?? '';
      $confirmPassword = $_POST['confirm_password'] ?? '';
 
      if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
          Flash::error('Compila tutti i campi.', BASE_URL . '/index.php?r=account/profile');
      }
 
      if (strlen($newPassword) < 8) {
          Flash::error('La nuova password deve essere di almeno 8 caratteri.', BASE_URL . '/index.php?r=account/profile');
      }
 
      if ($newPassword !== $confirmPassword) {
          Flash::error('Le password non coincidono.', BASE_URL . '/index.php?r=account/profile');
      }
 
      // Recupera password attuale e auth_provider
      $stmt = $this->pdo->prepare("SELECT password, auth_provider FROM users WHERE id = ? LIMIT 1");
      $stmt->execute([$userId]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
      if (!$user || $user['auth_provider'] === 'google') {
          Flash::error('Operazione non consentita per questo account.', BASE_URL . '/index.php?r=account/profile');
      }
 
      if (empty($user['password']) || !password_verify($currentPassword, $user['password'])) {
          Flash::error('La password attuale non è corretta.', BASE_URL . '/index.php?r=account/profile');
      }
 
      if (password_verify($newPassword, $user['password'])) {
          Flash::error('La nuova password deve essere diversa da quella attuale.', BASE_URL . '/index.php?r=account/profile');
      }
 
      $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
 
      $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
          ->execute([$newHash, $userId]);
 
      Flash::success('Password aggiornata con successo.', BASE_URL . '/index.php?r=account/profile');
  }
}