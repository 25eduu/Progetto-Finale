<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/ui/Flash.php';
require_once __DIR__ . '/../../helpers/security/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';
require_once __DIR__ . '/../../models/entities/User.php';

class AccountController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function requireLogin(): int
    {
        $userId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        return $userId;
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $userId    = $this->requireLogin();
        $userModel = new User($this->pdo);
        $user      = $userModel->findById($userId);

        if (!$user) {
            unset($_SESSION['user_id'], $_SESSION['user']);
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $stmtStats = $this->pdo->prepare("
            SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_spent
            FROM orders WHERE user_id = ?
        ");
        $stmtStats->execute([$userId]);
        $stats = $stmtStats->fetch() ?: ['total_orders' => 0, 'total_spent' => 0];

        $stmtOrders = $this->pdo->prepare("
            SELECT id, total_amount, status, payment_method, payment_status,
                   wallet_amount_paid, stripe_amount_paid, paypal_amount_paid, created_at
            FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 8
        ");
        $stmtOrders->execute([$userId]);
        $orders = $stmtOrders->fetchAll();

        $stmtWalletLogs = $this->pdo->prepare("
            SELECT amount, description, created_at
            FROM wallet_logs WHERE user_id = ? ORDER BY id DESC LIMIT 10
        ");
        $stmtWalletLogs->execute([$userId]);
        $walletLogs = $stmtWalletLogs->fetchAll();

        $pdo = $this->pdo;
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/account/dashboard.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    // ─── Profilo ──────────────────────────────────────────────────────────────

    public function profile(): void
    {
        $userId = $this->requireLogin();
        $user   = (new User($this->pdo))->findById($userId);

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
        $userId   = $this->requireLogin();
        $fullName = trim($_POST['full_name'] ?? '');

        if (!ValidationHelper::minLength($fullName, 2)) {
            Flash::error('Il nome deve contenere almeno 2 caratteri.', BASE_URL . '/index.php?r=account/profile');
        }

        if (!ValidationHelper::maxLength($fullName, 120)) {
            Flash::error('Il nome non può superare i 120 caratteri.', BASE_URL . '/index.php?r=account/profile');
        }

        (new User($this->pdo))->updateName($userId, $fullName);

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

        if (!ValidationHelper::notEmpty($currentPassword) || !ValidationHelper::notEmpty($newPassword) || !ValidationHelper::notEmpty($confirmPassword)) {
            Flash::error('Compila tutti i campi.', BASE_URL . '/index.php?r=account/profile');
        }

        if (!ValidationHelper::password($newPassword)) {
            Flash::error('La nuova password deve essere di almeno 8 caratteri.', BASE_URL . '/index.php?r=account/profile');
        }

        if (!ValidationHelper::matches($newPassword, $confirmPassword)) {
            Flash::error('Le password non coincidono.', BASE_URL . '/index.php?r=account/profile');
        }

        $userModel = new User($this->pdo);
        $user      = $userModel->findById($userId);

        if (!$user || $user['auth_provider'] === 'google') {
            Flash::error('Operazione non consentita per questo account.', BASE_URL . '/index.php?r=account/profile');
        }

        if (empty($user['password']) || !password_verify($currentPassword, $user['password'])) {
            Flash::error('La password attuale non è corretta.', BASE_URL . '/index.php?r=account/profile');
        }

        if (password_verify($newPassword, $user['password'])) {
            Flash::error('La nuova password deve essere diversa da quella attuale.', BASE_URL . '/index.php?r=account/profile');
        }

        $userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        Flash::success('Password aggiornata con successo.', BASE_URL . '/index.php?r=account/profile');
    }
}
