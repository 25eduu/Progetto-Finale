<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/User.php';

class AdminUserController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        AuthMiddleware::requireAdmin();
    }

    public function index(): void
    {
        $users = (new User($this->pdo))->getAll();
        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/users.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function addWallet(): void
    {
        CsrfHelper::validate();

        $userId = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $note   = trim($_POST['note'] ?? 'Ricarica manuale da admin');

        if (!ValidationHelper::positiveInt($userId) || !ValidationHelper::positiveFloat($amount)) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=adminUser/index');
        }

        $this->pdo->beginTransaction();
        try {
            (new User($this->pdo))->addWalletBalance($userId, $amount);

            $this->pdo->prepare("
                INSERT INTO wallet_logs (user_id, amount, description, created_at)
                VALUES (?, ?, ?, NOW())
            ")->execute([$userId, $amount, $note]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            Flash::error('Errore: ' . $e->getMessage(), BASE_URL . '/index.php?r=adminUser/index');
        }

        Flash::success(
            sprintf('Aggiunti € %.2f al wallet dell\'utente #%d.', $amount, $userId),
            BASE_URL . '/index.php?r=adminUser/index'
        );
    }
}
