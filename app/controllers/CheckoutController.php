<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../services/StripeService.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/User.php';

class CheckoutController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Helpers privati ──────────────────────────────────────────────────────

    private function getUserId(): ?int
    {
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    }

    private function getCartItems(): array
    {
        $userId = $this->getUserId();

        if ($userId) {
            return (new Cart($this->pdo))->getItemsByUserId($userId);
        }

        $items        = [];
        $productModel = new Product($this->pdo);

        foreach ($_SESSION['cart'] ?? [] as $id => $item) {
            $product = $productModel->findById((int)$id);
            if (!$product) continue;
            $items[] = [
                'product_id' => (int)$product['id'],
                'quantity'   => max(1, (int)($item['quantity'] ?? 1)),
                'price'      => (float)$product['price'],
                'name'       => $product['name'],
                'stock'      => (int)$product['stock'],
            ];
        }

        return $items;
    }

    private function getTotal(array $items): float
    {
        return array_reduce($items, fn($c, $i) => $c + (float)$i['price'] * (int)$i['quantity'], 0.0);
    }

    private function validateCartStock(array $items): void
    {
        if (empty($items)) throw new RuntimeException('Il carrello è vuoto.');

        foreach ($items as $item) {
            if ((int)$item['quantity'] <= 0) throw new RuntimeException('Quantità non valida per: ' . $item['name']);
            if ((int)$item['stock'] <= 0)    throw new RuntimeException('Prodotto esaurito: ' . $item['name']);
            if ((int)$item['quantity'] > (int)$item['stock']) {
                throw new RuntimeException('Stock insufficiente per "' . $item['name'] . '". Disponibili: ' . $item['stock']);
            }
        }
    }

    private function createOrderItems(Order $orderModel, int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $orderModel->addItem($orderId, (int)$item['product_id'], (int)$item['quantity'], (float)$item['price']);
        }
    }

    private function decreaseStock(array $items): void
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        foreach ($items as $item) {
            $stmt->execute([(int)$item['quantity'], (int)$item['product_id'], (int)$item['quantity']]);
            if ($stmt->rowCount() === 0) throw new RuntimeException('Stock insufficiente per: ' . $item['name']);
        }
    }

    private function clearCart(?int $userId): void
    {
        if ($userId) {
            (new Cart($this->pdo))->clear($userId);
        } else {
            unset($_SESSION['cart']);
        }
    }

    private function getAppUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }

    private function completeOrder(int $orderId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, status, user_id, customer_email, customer_name, total_amount
                FROM orders WHERE id = ? FOR UPDATE
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) { $this->pdo->rollBack(); return; }
            if ($order['status'] === 'paid') { $this->pdo->commit(); return; }

            $this->pdo->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?")
                ->execute([$orderId]);

            $items = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$orderId]);

            $stmtStock = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($items->fetchAll() as $item) {
                $stmtStock->execute([(int)$item['quantity'], (int)$item['product_id'], (int)$item['quantity']]);
                if ($stmtStock->rowCount() === 0) {
                    error_log("completeOrder: stock insufficiente prodotto #{$item['product_id']} ordine #$orderId");
                }
            }

            if (!empty($order['user_id'])) {
                $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([(int)$order['user_id']]);
            } else {
                unset($_SESSION['cart']);
            }

            $this->pdo->commit();

            try {
                (new MailService())->sendOrderConfirmation(
                    $order['customer_email'], $order['customer_name'], $orderId, (float)$order['total_amount']
                );
            } catch (Throwable $e) {
                error_log("completeOrder: errore email ordine #$orderId: " . $e->getMessage());
            }

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log("completeOrder: errore #$orderId: " . $e->getMessage());
        }
    }

    // ─── Pagina checkout ──────────────────────────────────────────────────────

    public function index(): void
    {
        $items = $this->getCartItems();

        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        try {
            $this->validateCartStock($items);
        } catch (Throwable $e) {
            Flash::error($e->getMessage(), BASE_URL . '/index.php?r=cart/index');
        }

        $userId        = $this->getUserId();
        $walletBalance = (new User($this->pdo))->getWalletBalance($userId ?? 0);
        $total         = $this->getTotal($items);
        $flash         = Flash::get();
        $pdo           = $this->pdo;

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function success(): void
    {
        $orderId    = $_SESSION['last_order_id']    ?? null;
        $orderEmail = $_SESSION['last_order_email'] ?? null;

        if (!empty($_GET['session_id'])) {
            $order = (new Order($this->pdo))->findByStripeSessionId($_GET['session_id']);
            if ($order) {
                $orderId    = $order['id'];
                $orderEmail = $order['customer_email'];
                if ($order['status'] !== 'paid') $this->completeOrder($orderId);
            }
        }

        if (!$orderId && !empty($_SESSION['last_order_id'])) {
            $orderId    = $_SESSION['last_order_id'];
            $orderEmail = $_SESSION['last_order_email'] ?? $orderEmail;
            $order      = (new Order($this->pdo))->findById($orderId);
            if ($order && $order['status'] !== 'paid') $this->completeOrder($orderId);
        }

        unset($_SESSION['last_order_id'], $_SESSION['last_order_email']);

        if (!$orderId) { header('Location: ' . BASE_URL . '/index.php'); exit; }

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/success.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    // ─── Process ordine ───────────────────────────────────────────────────────

    public function process(): void
    {
        CsrfHelper::validate();

        $items = $this->getCartItems();
        if (empty($items)) { header('Location: ' . BASE_URL . '/index.php?r=cart/index'); exit; }

        $userId        = $this->getUserId();
        $total         = $this->getTotal($items);
        $userModel     = new User($this->pdo);
        $walletBalance = $userModel->getWalletBalance($userId ?? 0);

        $name          = trim($_POST['name']           ?? '');
        $email         = trim($_POST['email']          ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'card');
        $notes         = trim($_POST['notes']          ?? '');
        $noteValue     = $notes !== '' ? $notes : null;
        $redirectError = BASE_URL . '/index.php?r=checkout/index';

        if (!ValidationHelper::notEmpty($name) || !ValidationHelper::notEmpty($email)) {
            Flash::error('Nome ed email sono obbligatori.', $redirectError);
        }

        if (!ValidationHelper::email($email)) {
            Flash::error('Indirizzo email non valido.', $redirectError);
        }

        try { $this->validateCartStock($items); }
        catch (Throwable $e) { Flash::error($e->getMessage(), $redirectError); }

        $orderModel = new Order($this->pdo);

        // ── SOLO WALLET ──────────────────────────────────────────────────────
        if ($paymentMethod === 'wallet') {
            if (!$userId) Flash::error('Devi essere loggato per usare il wallet.', $redirectError);
            if ($walletBalance < $total) Flash::error('Saldo wallet insufficiente.', $redirectError);

            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id' => $userId, 'customer_name' => $name, 'customer_email' => $email,
                    'total_amount' => $total, 'status' => 'paid', 'payment_method' => 'wallet',
                    'wallet_amount_paid' => $total, 'stripe_amount_paid' => 0.00,
                    'paypal_amount_paid' => 0.00, 'payment_status' => 'paid', 'notes' => $noteValue,
                ]);
                $this->createOrderItems($orderModel, $orderId, $items);
                $this->decreaseStock($items);

                if (!$userModel->subtractWalletBalance($userId, $total)) {
                    throw new RuntimeException('Saldo wallet insufficiente.');
                }

                $this->pdo->prepare("INSERT INTO wallet_logs (user_id, amount, description, created_at) VALUES (?,?,?,NOW())")
                    ->execute([$userId, -$total, 'Pagamento ordine #' . $orderId]);

                $this->clearCart($userId);
                $this->pdo->commit();

                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['wallet_balance'] = max(0, $walletBalance - $total);
                }

                try { (new MailService())->sendOrderConfirmation($email, $name, $orderId, $total); }
                catch (Throwable $e) { error_log("Email ordine wallet #$orderId: " . $e->getMessage()); }

                $_SESSION['last_order_id']    = $orderId;
                $_SESSION['last_order_email'] = $email;
                header('Location: ' . BASE_URL . '/index.php?r=checkout/success');
                exit;

            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Flash::error('Errore checkout: ' . $e->getMessage(), $redirectError);
            }
        }

        // ── SOLO CARTA ───────────────────────────────────────────────────────
        if ($paymentMethod === 'card') {
            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id' => $userId, 'customer_name' => $name, 'customer_email' => $email,
                    'total_amount' => $total, 'status' => 'created', 'payment_method' => 'stripe',
                    'wallet_amount_paid' => 0.00, 'stripe_amount_paid' => $total,
                    'paypal_amount_paid' => 0.00, 'payment_status' => 'pending', 'notes' => $noteValue,
                ]);
                $this->createOrderItems($orderModel, $orderId, $items);
                $this->pdo->commit();
                $_SESSION['last_order_id']    = $orderId;
                $_SESSION['last_order_email'] = $email;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Flash::error('Errore creazione ordine: ' . $e->getMessage(), $redirectError);
            }
            $this->redirectToStripe($orderId, $total, $email, 'Ordine #' . $orderId, $orderModel);
        }

        // ── WALLET + CARTA ───────────────────────────────────────────────────
        if ($paymentMethod === 'mixed') {
            if (!$userId) Flash::error('Devi essere loggato per usare il wallet.', $redirectError);
            if ($walletBalance <= 0) Flash::error('Non hai saldo wallet disponibile.', $redirectError);

            $walletPaid  = min($walletBalance, $total);
            $stripePaid  = max(0, $total - $walletPaid);

            if ($stripePaid <= 0) Flash::error('Usa "Solo wallet" — il saldo copre l\'intero importo.', $redirectError);

            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id' => $userId, 'customer_name' => $name, 'customer_email' => $email,
                    'total_amount' => $total, 'status' => 'created', 'payment_method' => 'mixed',
                    'wallet_amount_paid' => $walletPaid, 'stripe_amount_paid' => $stripePaid,
                    'paypal_amount_paid' => 0.00, 'payment_status' => 'pending', 'notes' => $noteValue,
                ]);
                $this->createOrderItems($orderModel, $orderId, $items);

                if (!$userModel->subtractWalletBalance($userId, $walletPaid)) {
                    throw new RuntimeException('Saldo wallet insufficiente.');
                }

                $this->pdo->prepare("INSERT INTO wallet_logs (user_id, amount, description, created_at) VALUES (?,?,?,NOW())")
                    ->execute([$userId, -$walletPaid, 'Prenotazione wallet ordine #' . $orderId]);

                $this->pdo->commit();
                $_SESSION['last_order_id']    = $orderId;
                $_SESSION['last_order_email'] = $email;

                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['wallet_balance'] = max(0, $walletBalance - $walletPaid);
                }
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Flash::error('Errore ordine misto: ' . $e->getMessage(), $redirectError);
            }
            $this->redirectToStripe($orderId, $stripePaid, $email, 'Saldo residuo ordine #' . $orderId, $orderModel);
        }

        if ($paymentMethod === 'paypal') {
            Flash::error('PayPal non è ancora disponibile. Scegli un altro metodo.', $redirectError);
        }

        Flash::error('Metodo di pagamento non valido.', $redirectError);
    }

    // ─── Redirect a Stripe ────────────────────────────────────────────────────

    private function redirectToStripe(int $orderId, float $amount, string $email, string $productName, Order $orderModel): never
    {
        $redirectError = BASE_URL . '/index.php?r=checkout/index';
        try {
            $stripe  = new StripeService();
            $session = $stripe->createOrderSession($orderId, $amount, $email, $productName, $this->getAppUrl());
            $orderModel->updateStripeSessionId($orderId, $session->id);
            header('Location: ' . $session->url);
            exit;
        } catch (Throwable $e) {
            Flash::error('Errore Stripe: ' . $e->getMessage(), $redirectError);
        }
    }
}
