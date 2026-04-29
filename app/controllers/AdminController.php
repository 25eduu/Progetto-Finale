<?php
declare(strict_types=1);

class AdminController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // Middleware di protezione
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: index.php?r=auth/loginForm');
            exit;
        }
    }

    public function dashboard(): void {
        // Statistiche rapide
        $totalOrders = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE status='paid'")->fetchColumn();
        $totalRevenue = $this->pdo->query("SELECT SUM(total_amount) FROM orders WHERE status='paid'")->fetchColumn();
        $lowStock = $this->pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();

        // Ultimi 5 ordini
        $stmt = $this->pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/dashboard.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}