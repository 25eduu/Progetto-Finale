<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        AuthMiddleware::requireAdmin();
    }

    // ─── Dashboard ────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $stats = $this->pdo->query("
            SELECT
                (SELECT COUNT(*)                        FROM users)                         AS total_users,
                (SELECT COUNT(*)                        FROM orders)                        AS total_orders,
                (SELECT COALESCE(SUM(total_amount), 0)  FROM orders WHERE status = 'paid') AS revenue,
                (SELECT COUNT(*)                        FROM products WHERE stock = 0)      AS out_of_stock,
                (SELECT COUNT(*)                        FROM orders  WHERE DATE(created_at) = CURDATE()) AS orders_today
        ")->fetch(PDO::FETCH_ASSOC);

        // Ultimi 10 ordini
        $recentOrders = $this->pdo->query("
            SELECT o.id, o.customer_name, o.customer_email,
                   o.total_amount, o.status, o.payment_method, o.created_at
            FROM orders o
            ORDER BY o.id DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Revenue ultimi 7 giorni (per grafico)
        $revenueChart = $this->pdo->query("
            SELECT DATE(created_at) AS day,
                   COALESCE(SUM(total_amount), 0) AS total
            FROM orders
            WHERE status = 'paid'
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/dashboard.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    // ─── Prodotti ─────────────────────────────────────────────────────────

    public function products(): void
    {
        $products = (new Product($this->pdo))->getAll();
        $flash    = Flash::get();
        $pdo      = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/products.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function updateStock(): void
    {
        CsrfHelper::validate();

        $id    = (int)($_POST['product_id'] ?? 0);
        $stock = (int)($_POST['stock'] ?? -1);

        if ($id <= 0 || $stock < 0) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=admin/products');
        }

        $stmt = $this->pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$stock, $id]);

        Flash::success('Stock aggiornato con successo.', BASE_URL . '/index.php?r=admin/products');
    }

    public function createProduct(): void
    {
        CsrfHelper::validate();

        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price']    ?? 0);
        $stock       = (int)($_POST['stock']      ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0 || $categoryId <= 0) {
            Flash::error('Compila tutti i campi obbligatori.', BASE_URL . '/index.php?r=admin/products');
        }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->uploadImage($_FILES['image']);
            if ($imagePath === null) {
                Flash::error('Formato immagine non valido. Usa JPG, PNG o WebP.', BASE_URL . '/index.php?r=admin/products');
            }
        }

        $this->pdo->prepare("
            INSERT INTO products (category_id, name, description, price, stock, image_path, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$categoryId, $name, $description, $price, $stock, $imagePath]);

        Flash::success('Prodotto creato con successo.', BASE_URL . '/index.php?r=admin/products');
    }

    public function deleteProduct(): void
    {
        CsrfHelper::validate();

        $id = (int)($_POST['product_id'] ?? 0);
        if ($id <= 0) {
            Flash::error('Prodotto non valido.', BASE_URL . '/index.php?r=admin/products');
        }

        $this->pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

        Flash::success('Prodotto eliminato.', BASE_URL . '/index.php?r=admin/products');
    }

    // ─── Ordini ───────────────────────────────────────────────────────────

    public function orders(): void
    {
        $status = $_GET['status'] ?? '';
        $params = [];
        $where  = '';

        if (in_array($status, ['pending_payment', 'paid', 'shipped', 'completed', 'cancelled'], true)) {
            $where    = 'WHERE o.status = ?';
            $params[] = $status;
        }

        $orders = $this->pdo->prepare("
            SELECT o.id, o.customer_name, o.customer_email,
                   o.total_amount, o.status, o.payment_method,
                   o.payment_status, o.created_at
            FROM orders o
            {$where}
            ORDER BY o.id DESC
            LIMIT 50
        ");
        $orders->execute($params);
        $orders = $orders->fetchAll(PDO::FETCH_ASSOC);

        $flash = Flash::get();
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/orders.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function updateOrderStatus(): void
    {
        CsrfHelper::validate();

        $orderId   = (int)($_POST['order_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        $allowed = ['paid', 'shipped', 'completed', 'cancelled'];
        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=admin/orders');
        }

        $this->pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$newStatus, $orderId]);

        Flash::success("Ordine #{$orderId} aggiornato a \"{$newStatus}\".", BASE_URL . '/index.php?r=admin/orders');
    }

    // ─── Utenti ───────────────────────────────────────────────────────────

    public function users(): void
    {
        $users = $this->pdo->query("
            SELECT id, full_name, email, role, wallet_balance,
                   auth_provider, created_at
            FROM users
            ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

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

        if ($userId <= 0 || $amount <= 0) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=admin/users');
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("
                UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
            ")->execute([$amount, $userId]);

            $this->pdo->prepare("
                INSERT INTO wallet_logs (user_id, amount, description, created_at)
                VALUES (?, ?, ?, NOW())
            ")->execute([$userId, $amount, $note]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            Flash::error('Errore durante la ricarica: ' . $e->getMessage(), BASE_URL . '/index.php?r=admin/users');
        }

        Flash::success(sprintf('Aggiunti € %.2f al wallet dell\'utente #%d.', $amount, $userId), BASE_URL . '/index.php?r=admin/users');
    }

    // ─── Helpers privati ─────────────────────────────────────────────────

    private function uploadImage(array $file): ?string
    {
        $allowed   = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType  = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            return null;
        }

        $ext      = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = __DIR__ . '/../../public/assets/images/' . $filename;

        move_uploaded_file($file['tmp_name'], $dest);

        return 'images/' . $filename;
    }
}