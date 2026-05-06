<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Flash.php';

class AdminDashboardController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        AuthMiddleware::requireAdmin();
    }

    public function index(): void
    {
        $stats = $this->pdo->query("
            SELECT
                (SELECT COUNT(*)                       FROM users)                         AS total_users,
                (SELECT COUNT(*)                       FROM orders)                        AS total_orders,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'paid') AS revenue,
                (SELECT COUNT(*)                       FROM products WHERE stock = 0)      AS out_of_stock,
                (SELECT COUNT(*)                       FROM orders WHERE DATE(created_at) = CURDATE()) AS orders_today
        ")->fetch();

        $recentOrders = $this->pdo->query("
            SELECT o.id, o.customer_name, o.customer_email,
                   o.total_amount, o.status, o.payment_method, o.created_at
            FROM orders o ORDER BY o.id DESC LIMIT 10
        ")->fetchAll();

        $revenueChart = $this->pdo->query("
            SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount), 0) AS total
            FROM orders
            WHERE status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->fetchAll();

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/dashboard.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}
