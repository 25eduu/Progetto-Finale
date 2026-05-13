<?php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../helpers/ui/Flash.php';
require_once __DIR__ . '/../../helpers/security/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';

class AdminOrderController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        AuthMiddleware::requireAdmin();
    }

    public function index(): void
    {
        $status  = $_GET['status'] ?? '';
        $params  = [];
        $where   = '';

        $allowed = ['pending_payment', 'paid', 'shipped', 'completed', 'cancelled'];
        if (in_array($status, $allowed, true)) {
            $where    = 'WHERE o.status = ?';
            $params[] = $status;
        }

        $stmt = $this->pdo->prepare("
            SELECT o.id, o.customer_name, o.customer_email,
                   o.total_amount, o.status, o.payment_method,
                   o.payment_status, o.created_at
            FROM orders o {$where}
            ORDER BY o.id DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/admin/orders.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function updateStatus(): void
    {
        CsrfHelper::validate();

        $orderId   = (int)($_POST['order_id'] ?? 0);
        $newStatus = $_POST['status']         ?? '';

        $allowed = ['paid', 'shipped', 'completed', 'cancelled'];
        if (!ValidationHelper::positiveInt($orderId) || !in_array($newStatus, $allowed, true)) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=adminOrder/index');
        }

        $this->pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$newStatus, $orderId]);

        Flash::success("Ordine #{$orderId} aggiornato a \"{$newStatus}\".", BASE_URL . '/index.php?r=adminOrder/index');
    }
}
