<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Order.php';

class CheckoutController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function getUserId(): ?int
    {
        if (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        if (isset($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }

        return null;
    }

    private function redirectWithError(string $message): void
    {
        $_SESSION['checkout_error'] = $message;
        header('Location: ' . BASE_URL . '/index.php?r=checkout/index');
        exit;
    }

    private function getCartItems(): array
    {
        $userId = $this->getUserId();

        if ($userId) {
            $cartModel = new Cart($this->pdo);
            return $cartModel->getItemsByUserId($userId);
        }

        $items = [];
        $productModel = new Product($this->pdo);

        foreach ($_SESSION['cart'] ?? [] as $id => $item) {
            $product = $productModel->findById((int)$id);
            if (!$product) {
                continue;
            }

            $qty = max(1, (int)($item['quantity'] ?? 1));

            $items[] = [
                'product_id' => (int)$product['id'],
                'quantity'   => $qty,
                'price'      => (float)$product['price'],
                'name'       => $product['name'],
                'stock'      => (int)$product['stock'],
            ];
        }

        return $items;
    }

    private function getTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float)$item['price'] * (int)$item['quantity'];
        }

        return $total;
    }

    private function getWalletBalance(?int $userId): float
    {
        if (!$userId) {
            return 0.0;
        }

        $stmt = $this->pdo->prepare("
            SELECT wallet_balance
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);

        return (float)($stmt->fetchColumn() ?: 0);
    }

    private function validateCartStock(array $items): void
    {
        if (empty($items)) {
            throw new RuntimeException('Il carrello è vuoto.');
        }

        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $stock = (int)$item['stock'];

            if ($qty <= 0) {
                throw new RuntimeException('Quantità non valida per il prodotto: ' . $item['name']);
            }

            if ($stock <= 0) {
                throw new RuntimeException('Prodotto esaurito: ' . $item['name']);
            }

            if ($qty > $stock) {
                throw new RuntimeException(
                    'Stock insufficiente per "' . $item['name'] . '". Disponibili: ' . $stock
                );
            }
        }
    }

    private function getAppUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);

        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . BASE_URL;
    }

    private function createOrderItems(Order $orderModel, int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $orderModel->addItem(
                $orderId,
                (int)$item['product_id'],
                (int)$item['quantity'],
                (float)$item['price']
            );
        }
    }

    private function decreaseStock(array $items): void
    {
        $stmtStock = $this->pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND stock >= ?
        ");

        foreach ($items as $item) {
            $stmtStock->execute([
                (int)$item['quantity'],
                (int)$item['product_id'],
                (int)$item['quantity']
            ]);

            if ($stmtStock->rowCount() === 0) {
                throw new RuntimeException('Stock insufficiente per il prodotto: ' . $item['name']);
            }
        }
    }

    private function clearCart(?int $userId): void
    {
        if ($userId) {
            $cartModel = new Cart($this->pdo);
            $cartModel->clear($userId);
        } else {
            unset($_SESSION['cart']);
        }
    }

    private function updateSessionWalletBalance(float $newBalance): void
    {
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['wallet_balance'] = max(0, $newBalance);
        }
    }

    public function index(): void
    {
        $items = $this->getCartItems();
        $total = $this->getTotal($items);

        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        try {
            $this->validateCartStock($items);
        } catch (Throwable $e) {
            $this->redirectWithError($e->getMessage());
        }

        $userId = $this->getUserId();
        $walletBalance = $this->getWalletBalance($userId);

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function process(): void
    {
        $items = $this->getCartItems();

        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        $userId = $this->getUserId();
        $total = $this->getTotal($items);

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $paymentMethodInput = trim($_POST['payment_method'] ?? 'card');
        $notes = trim($_POST['notes'] ?? '');

        if ($name === '' || $email === '') {
            $this->redirectWithError('Nome ed email obbligatori');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Email non valida');
        }

        try {
            $this->validateCartStock($items);
        } catch (Throwable $e) {
            $this->redirectWithError($e->getMessage());
        }

        $walletBalance = $this->getWalletBalance($userId);
        $orderModel = new Order($this->pdo);

        // SOLO WALLET
        if ($paymentMethodInput === 'wallet') {
            if (!$userId) {
                $this->redirectWithError('Devi essere loggato per usare il wallet');
            }

            if ($walletBalance < $total) {
                $this->redirectWithError('Saldo wallet insufficiente');
            }

            $this->pdo->beginTransaction();

            try {
                $orderId = $orderModel->create([
                    'user_id' => $userId,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'total_amount' => $total,
                    'status' => 'paid',
                    'payment_method' => 'wallet',
                    'wallet_amount_paid' => $total,
                    'stripe_amount_paid' => 0.00,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id' => null,
                    'paypal_order_id' => null,
                    'payment_status' => 'paid',
                    'notes' => $notes !== '' ? $notes : null,
                ]);

                $this->createOrderItems($orderModel, $orderId, $items);
                $this->decreaseStock($items);

                $stmtWallet = $this->pdo->prepare("
                    UPDATE users
                    SET wallet_balance = wallet_balance - ?
                    WHERE id = ? AND wallet_balance >= ?
                ");
                $stmtWallet->execute([$total, $userId, $total]);

                if ($stmtWallet->rowCount() === 0) {
                    throw new RuntimeException('Saldo wallet insufficiente');
                }

                $stmtLog = $this->pdo->prepare("
                    INSERT INTO wallet_logs (user_id, amount, description, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtLog->execute([
                    $userId,
                    -$total,
                    'Pagamento ordine #' . $orderId
                ]);

                $this->clearCart($userId);
                $this->pdo->commit();

                $this->updateSessionWalletBalance($walletBalance - $total);

                try {
                    $mailService = new MailService();
                    $mailService->sendOrderConfirmation($email, $name, $orderId, $total);
                } catch (Throwable $e) {
                    // non bloccare l'ordine
                }

                $_SESSION['last_order_id'] = $orderId;
                $_SESSION['last_order_email'] = $email;

                header('Location: ' . BASE_URL . '/index.php?r=checkout/success');
                exit;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $this->redirectWithError('Errore checkout: ' . $e->getMessage());
            }
        }

        // CARTA SOLO STRIPE
        if ($paymentMethodInput === 'card') {
            try {
                $this->pdo->beginTransaction();

                $orderId = $orderModel->create([
                    'user_id' => $userId,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'total_amount' => $total,
                    'status' => 'pending_payment',
                    'payment_method' => 'stripe',
                    'wallet_amount_paid' => 0.00,
                    'stripe_amount_paid' => $total,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id' => null,
                    'paypal_order_id' => null,
                    'payment_status' => 'pending',
                    'notes' => $notes !== '' ? $notes : null,
                ]);

                $this->createOrderItems($orderModel, $orderId, $items);
                $this->pdo->commit();
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $this->redirectWithError('Errore creazione ordine: ' . $e->getMessage());
            }

            try {
                $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
                $secretKey = $env['STRIPE_SECRET_KEY'] ?? '';

                if ($secretKey === '') {
                    throw new RuntimeException('STRIPE_SECRET_KEY mancante nel file .env');
                }

                \Stripe\Stripe::setApiKey($secretKey);

                $appUrl = $this->getAppUrl();

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'mode' => 'payment',
                    'customer_email' => $email,
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Ordine #' . $orderId,
                            ],
                            'unit_amount' => (int) round($total * 100),
                        ],
                        'quantity' => 1,
                    ]],
                    'success_url' => $appUrl . '/index.php?r=checkout/success&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $appUrl . '/index.php?r=checkout/index',
                    'metadata' => [
                        'order_id' => (string)$orderId,
                    ],
                ]);

                $orderModel->updateStripeSessionId($orderId, $session->id);

                header('Location: ' . $session->url);
                exit;
            } catch (Throwable $e) {
                $this->redirectWithError('Errore Stripe: ' . $e->getMessage());
            }
        }

        // WALLET + CARTA
        if ($paymentMethodInput === 'mixed') {
            if (!$userId) {
                $this->redirectWithError('Devi essere loggato per usare il wallet');
            }

            if ($walletBalance <= 0) {
                $this->redirectWithError('Non hai saldo wallet disponibile');
            }

            $walletAmountPaid = min($walletBalance, $total);
            $stripeAmountPaid = max(0, $total - $walletAmountPaid);

            if ($stripeAmountPaid <= 0) {
                $this->redirectWithError('Per usare wallet + carta deve esserci un residuo da pagare');
            }

            try {
                $this->pdo->beginTransaction();

                $orderId = $orderModel->create([
                    'user_id' => $userId,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'total_amount' => $total,
                    'status' => 'pending_payment',
                    'payment_method' => 'mixed',
                    'wallet_amount_paid' => $walletAmountPaid,
                    'stripe_amount_paid' => $stripeAmountPaid,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id' => null,
                    'paypal_order_id' => null,
                    'payment_status' => 'pending',
                    'notes' => $notes !== '' ? $notes : null,
                ]);

                $this->createOrderItems($orderModel, $orderId, $items);

                $stmtWallet = $this->pdo->prepare("
                    UPDATE users
                    SET wallet_balance = wallet_balance - ?
                    WHERE id = ? AND wallet_balance >= ?
                ");
                $stmtWallet->execute([$walletAmountPaid, $userId, $walletAmountPaid]);

                if ($stmtWallet->rowCount() === 0) {
                    throw new RuntimeException('Saldo wallet insufficiente');
                }

                $stmtLog = $this->pdo->prepare("
                    INSERT INTO wallet_logs (user_id, amount, description, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmtLog->execute([
                    $userId,
                    -$walletAmountPaid,
                    'Prenotazione wallet ordine #' . $orderId
                ]);

                $this->pdo->commit();
                $this->updateSessionWalletBalance($walletBalance - $walletAmountPaid);
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $this->redirectWithError('Errore creazione ordine misto: ' . $e->getMessage());
            }

            try {
                $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
                $secretKey = $env['STRIPE_SECRET_KEY'] ?? '';

                if ($secretKey === '') {
                    throw new RuntimeException('STRIPE_SECRET_KEY mancante nel file .env');
                }

                \Stripe\Stripe::setApiKey($secretKey);

                $appUrl = $this->getAppUrl();

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'mode' => 'payment',
                    'customer_email' => $email,
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Saldo residuo ordine #' . $orderId,
                            ],
                            'unit_amount' => (int) round($stripeAmountPaid * 100),
                        ],
                        'quantity' => 1,
                    ]],
                    'success_url' => $appUrl . '/index.php?r=checkout/success&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $appUrl . '/index.php?r=checkout/index',
                    'metadata' => [
                        'order_id' => (string)$orderId,
                    ],
                ]);

                $orderModel->updateStripeSessionId($orderId, $session->id);

                header('Location: ' . $session->url);
                exit;
            } catch (Throwable $e) {
                $this->redirectWithError('Errore Stripe: ' . $e->getMessage());
            }
        }

        // PAYPAL ANCORA NON IMPLEMENTATO
        if ($paymentMethodInput === 'paypal') {
            $this->redirectWithError('PayPal non è ancora implementato');
        }

        $this->redirectWithError('Metodo di pagamento non valido');
    }

    public function success(): void
    {
        $orderId = $_SESSION['last_order_id'] ?? null;
        $orderEmail = $_SESSION['last_order_email'] ?? null;

        if (!$orderId && empty($_GET['session_id'])) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/success.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}