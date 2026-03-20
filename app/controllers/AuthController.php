<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Cart.php';

class AuthController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  private function mergeSessionCartToDatabase(int $userId): void {
    if (empty($_SESSION['cart'])) {
      return;
    }

    $cartModel = new Cart($this->pdo);
    $cartModel->mergeSessionCart($userId, $_SESSION['cart']);
    unset($_SESSION['cart']);
  }

  private function loginUser(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'email' => $user['email'],
      'full_name' => $user['full_name'],
      'role' => $user['role'],
    ];

    $this->mergeSessionCartToDatabase((int)$user['id']);
  }

  public function loginForm(): void {
    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/auth/login.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function registerForm(): void {
    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/auth/register.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function login(): void {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
      die('Credenziali non valide');
    }

    $this->loginUser($user);

    header('Location: ' . BASE_URL . '/index.php');
    exit;
  }

  public function register(): void {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
      die('Compila tutti i campi');
    }

    $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      die('Email già registrata');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $this->pdo->prepare("
      INSERT INTO users (email, password, full_name, wallet_balance, role, auth_provider, created_at)
      VALUES (?, ?, ?, 0.00, 'user', 'local', NOW())
    ");
    $stmt->execute([$email, $hash, $fullName]);

    $userId = (int)$this->pdo->lastInsertId();

    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $this->loginUser($user);

    header('Location: ' . BASE_URL . '/index.php');
    exit;
  }

  public function logout(): void {
    unset($_SESSION['user_id'], $_SESSION['user']);
    header('Location: ' . BASE_URL . '/index.php');
    exit;
  }

  public function googleCallback(): void {
    $credential = $_POST['credential'] ?? '';
    if ($credential === '') {
      die('Token Google mancante');
    }

    $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
    $clientId = $env['GOOGLE_CLIENT_ID'] ?? '';

    if ($clientId === '') {
      die('GOOGLE_CLIENT_ID mancante nel .env');
    }

    $tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
    $response = @file_get_contents($tokenInfoUrl);

    if ($response === false) {
      die('Impossibile verificare il token Google');
    }

    $payload = json_decode($response, true);

    if (!$payload || ($payload['aud'] ?? '') !== $clientId) {
      die('Token Google non valido');
    }

    $googleId = $payload['sub'] ?? '';
    $email = $payload['email'] ?? '';
    $fullName = $payload['name'] ?? 'Utente Google';
    $emailVerified = ($payload['email_verified'] ?? 'false') === 'true';

    if ($googleId === '' || $email === '') {
      die('Dati Google incompleti');
    }

    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
      if (empty($user['google_id'])) {
        $upd = $this->pdo->prepare("
          UPDATE users
          SET google_id = ?, auth_provider = 'google'
          WHERE id = ?
        ");
        $upd->execute([$googleId, $user['id']]);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
      }
    } else {
      $verifiedAt = $emailVerified ? date('Y-m-d H:i:s') : null;

      $ins = $this->pdo->prepare("
        INSERT INTO users (
          email, password, full_name, wallet_balance, role,
          google_id, auth_provider, email_verified_at, created_at
        ) VALUES (?, NULL, ?, 0.00, 'user', ?, 'google', ?, NOW())
      ");
      $ins->execute([$email, $fullName, $googleId, $verifiedAt]);

      $userId = (int)$this->pdo->lastInsertId();

      $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
      $stmt->execute([$userId]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $this->loginUser($user);

    header('Location: ' . BASE_URL . '/index.php');
    exit;
  }
}