<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../helpers/ui/Flash.php';
require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';
require_once __DIR__ . '/../../models/entities/User.php';
require_once __DIR__ . '/../../services/payment/StripeService.php';

class WalletController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getUserId(): ?int
    {
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    }

    private function getAppUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }

    private function logWallet(int $userId, float $amount, string $description): void
    {
        $this->pdo->prepare("
            INSERT INTO wallet_logs (user_id, amount, description, created_at)
            VALUES (?, ?, ?, NOW())
        ")->execute([$userId, $amount, $description]);
    }

    // ── Avvia ricarica tramite Stripe ─────────────────────────────────────────

    public function recharge(): void
    {
        $userId = $this->getUserId();
        if (!$userId) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $amount = (float)($_GET['amount'] ?? 0);
        if (!ValidationHelper::between($amount, 10, 500)) {
            Flash::error('Importo non valido. Scegli tra €10 e €500.', BASE_URL . '/index.php?r=account/dashboard');
        }

        $user = (new User($this->pdo))->findById($userId);
        if (!$user) {
            Flash::error('Utente non trovato.', BASE_URL . '/index.php?r=account/dashboard');
        }

        try {
            $stripe  = new StripeService();
            $session = $stripe->createWalletSession($userId, $amount, $user['email'], $this->getAppUrl());
            $_SESSION['last_wallet_amount'] = $amount;
            header('Location: ' . $session->url);
            exit;
        } catch (Throwable $e) {
            Flash::error('Errore Stripe: ' . $e->getMessage(), BASE_URL . '/index.php?r=account/dashboard');
        }
    }

    // ── Callback successo pagamento wallet ────────────────────────────────────

    public function success(): void
    {
        $userId = $this->getUserId();
        if (!$userId) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $amount = $_SESSION['last_wallet_amount'] ?? 0;
        unset($_SESSION['last_wallet_amount']);

        if (empty($_GET['session_id']) || $amount <= 0) {
            Flash::error('Sessione non valida.', BASE_URL . '/index.php?r=account/dashboard');
        }

        try {
            $stripe  = new StripeService();
            $session = $stripe->retrieveSession($_GET['session_id']);

            if ($session->payment_status === 'paid') {
                $this->pdo->beginTransaction();
                try {
                    (new User($this->pdo))->addWalletBalance($userId, $amount);
                    $this->logWallet($userId, $amount, 'Ricarica via Stripe');
                    $this->pdo->commit();

                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['wallet_balance'] =
                            (float)($_SESSION['user']['wallet_balance'] ?? 0) + $amount;
                    }

                    Flash::success(
                        'Wallet ricaricato di € ' . number_format($amount, 2, ',', '.'),
                        BASE_URL . '/index.php?r=account/dashboard'
                    );
                } catch (Throwable $e) {
                    $this->pdo->rollBack();
                    throw $e;
                }
            }

            header('Location: ' . BASE_URL . '/index.php?r=account/dashboard');
            exit;
        } catch (Throwable $e) {
            Flash::error('Errore pagamento: ' . $e->getMessage(), BASE_URL . '/index.php?r=account/dashboard');
        }
    }
}
