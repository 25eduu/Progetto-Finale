<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Privati ───────────────────────────────────────────────────────────

    private function mergeSessionCartToDatabase(int $userId): void
    {
        if (empty($_SESSION['cart'])) {
            return;
        }

        $cartModel = new Cart($this->pdo);
        $cartModel->mergeSessionCart($userId, $_SESSION['cart']);
        unset($_SESSION['cart']);
    }

    private function loginUser(array $user): void
    {
        // Prevenzione session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user']    = [
            'id'        => (int)$user['id'],
            'email'     => $user['email'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ];

        $this->mergeSessionCartToDatabase((int)$user['id']);
    }

    private function startTwoFactorLogin(array $user): void
    {
        $code      = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Invalida tutti i codici precedenti
        $this->pdo->prepare("
            UPDATE two_factor_codes
            SET is_used = 1
            WHERE user_id = ? AND is_used = 0
        ")->execute([(int)$user['id']]);

        $this->pdo->prepare("
            INSERT INTO two_factor_codes (user_id, otp_code, expires_at, is_used)
            VALUES (?, ?, ?, 0)
        ")->execute([(int)$user['id'], $code, $expiresAt]);

        try {
            $mailService = new MailService();
            $mailService->sendTwoFactorCode($user['email'], $user['full_name'], $code);
            
        } catch (Throwable $e) {
            echo "<h1>Debug SMTP:</h1><pre>" . $e->getMessage() . "</pre>"; exit;

            Flash::error(
                'Impossibile inviare il codice di verifica. Riprova tra qualche minuto.',
                BASE_URL . '/index.php?r=auth/loginForm'
            );
        }

        $_SESSION['pending_2fa_user_id'] = (int)$user['id'];
        $_SESSION['pending_2fa_email']   = $user['email'];
        $_SESSION['pending_2fa_expires'] = time() + 600;
        $_SESSION['otp_attempts']        = 0;

        header('Location: ' . BASE_URL . '/index.php?r=auth/verify2faForm');
        exit;
    }

    // ─── View ──────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/auth/login.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function registerForm(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/auth/register.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function verify2faForm(): void
    {
        if (empty($_SESSION['pending_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/auth/verify2fa.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    // ─── Azioni POST ───────────────────────────────────────────────────────

    public function login(): void
    {
        CsrfHelper::validate();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Flash::error('Email e password sono obbligatori.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Messaggio volutamente generico per non rivelare se l'email esiste
        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            Flash::error('Credenziali non valide.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $this->startTwoFactorLogin($user);
    }

    public function register(): void
    {
        CsrfHelper::validate();

        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '') {
            Flash::error('Compila tutti i campi.', BASE_URL . '/index.php?r=auth/registerForm');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::error('Email non valida.', BASE_URL . '/index.php?r=auth/registerForm');
        }

        if (strlen($password) < 8) {
            Flash::error('La password deve essere di almeno 8 caratteri.', BASE_URL . '/index.php?r=auth/registerForm');
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Flash::error('Questa email è già registrata.', BASE_URL . '/index.php?r=auth/registerForm');
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

        Flash::success('Benvenuto in TechShop, ' . $fullName . '!', BASE_URL . '/index.php');
    }

    public function logout(): void
    {
        session_destroy();
        session_start();
        session_regenerate_id(true);

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    public function verify2fa(): void
    {
        if (empty($_SESSION['pending_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        CsrfHelper::validate();

        if (time() > (int)($_SESSION['pending_2fa_expires'] ?? 0)) {
            unset(
                $_SESSION['pending_2fa_user_id'],
                $_SESSION['pending_2fa_email'],
                $_SESSION['pending_2fa_expires'],
                $_SESSION['otp_attempts']
            );
            Flash::error('Il codice è scaduto. Effettua nuovamente il login.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $otp = trim($_POST['otp_code'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            Flash::error('Inserisci un codice valido a 6 cifre.', BASE_URL . '/index.php?r=auth/verify2faForm');
        }

        $_SESSION['otp_attempts'] = (int)($_SESSION['otp_attempts'] ?? 0) + 1;

        if ($_SESSION['otp_attempts'] > 5) {
            unset(
                $_SESSION['pending_2fa_user_id'],
                $_SESSION['pending_2fa_email'],
                $_SESSION['pending_2fa_expires'],
                $_SESSION['otp_attempts']
            );
            Flash::error('Troppi tentativi errati. Effettua nuovamente il login.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM two_factor_codes
            WHERE user_id = ?
              AND otp_code = ?
              AND is_used  = 0
              AND expires_at >= NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([(int)$_SESSION['pending_2fa_user_id'], $otp]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            Flash::error('Codice non valido o scaduto.', BASE_URL . '/index.php?r=auth/verify2faForm');
        }

        $this->pdo->prepare("UPDATE two_factor_codes SET is_used = 1 WHERE id = ?")
            ->execute([(int)$row['id']]);

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['pending_2fa_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Flash::error('Utente non trovato.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        unset(
            $_SESSION['pending_2fa_user_id'],
            $_SESSION['pending_2fa_email'],
            $_SESSION['pending_2fa_expires'],
            $_SESSION['otp_attempts']
        );

        $this->loginUser($user);

        $intended = $_SESSION['intended_url'] ?? '';
        unset($_SESSION['intended_url']);

        header('Location: ' . ($intended ?: BASE_URL . '/index.php'));
        exit;
    }

    public function resend2fa(): void
    {
        if (empty($_SESSION['pending_2fa_user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['pending_2fa_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $this->startTwoFactorLogin($user);
    }

    public function googleCallback(): void
    {
        $credential = $_POST['credential'] ?? '';
        if ($credential === '') {
            Flash::error('Token Google mancante.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $env      = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
        $clientId = $env['GOOGLE_CLIENT_ID'] ?? '';

        if ($clientId === '') {
            Flash::error('Configurazione Google mancante.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $response     = @file_get_contents($tokenInfoUrl);

        if ($response === false) {
            Flash::error('Impossibile verificare il token Google. Riprova.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $payload = json_decode($response, true);

        if (!$payload || ($payload['aud'] ?? '') !== $clientId) {
            Flash::error('Token Google non valido.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $googleId      = $payload['sub']            ?? '';
        $email         = $payload['email']          ?? '';
        $fullName      = $payload['name']           ?? 'Utente Google';
        $emailVerified = ($payload['email_verified'] ?? 'false') === 'true';

        if ($googleId === '' || $email === '') {
            Flash::error('Dati Google incompleti.', BASE_URL . '/index.php?r=auth/loginForm');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$googleId, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (empty($user['google_id'])) {
                $this->pdo->prepare("
                    UPDATE users SET google_id = ?, auth_provider = 'google' WHERE id = ?
                ")->execute([$googleId, $user['id']]);

                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$user['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } else {
            $verifiedAt = $emailVerified ? date('Y-m-d H:i:s') : null;

            $this->pdo->prepare("
                INSERT INTO users
                    (email, password, full_name, wallet_balance, role, google_id, auth_provider, email_verified_at, created_at)
                VALUES (?, NULL, ?, 0.00, 'user', ?, 'google', ?, NOW())
            ")->execute([$email, $fullName, $googleId, $verifiedAt]);

            $userId = (int)$this->pdo->lastInsertId();
            $stmt   = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $this->loginUser($user);

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}