<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
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

    // ─── Helpers privati ─────────────────────────────────────────────────

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
            $cartModel = new Cart($this->pdo);
            return $cartModel->getItemsByUserId($userId);
        }

        $items        = [];
        $productModel = new Product($this->pdo);

        foreach ($_SESSION['cart'] ?? [] as $id => $item) {
            $product = $productModel->findById((int)$id);
            if (!$product) {
                continue;
            }
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

    private function getWalletBalance(?int $userId): float
    {
        if (!$userId) {
            return 0.0;
        }
        $stmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    private function validateCartStock(array $items): void
    {
        if (empty($items)) {
            throw new RuntimeException('Il carrello è vuoto.');
        }

        foreach ($items as $item) {
            if ((int)$item['quantity'] <= 0) {
                throw new RuntimeException('Quantità non valida per: ' . $item['name']);
            }
            if ((int)$item['stock'] <= 0) {
                throw new RuntimeException('Prodotto esaurito: ' . $item['name']);
            }
            if ((int)$item['quantity'] > (int)$item['stock']) {
                throw new RuntimeException(
                    'Stock insufficiente per "' . $item['name'] . '". Disponibili: ' . $item['stock']
                );
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
        $stmt = $this->pdo->prepare("
            UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
        ");
        foreach ($items as $item) {
            $stmt->execute([(int)$item['quantity'], (int)$item['product_id'], (int)$item['quantity']]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Stock insufficiente per: ' . $item['name']);
            }
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

    private function updateSessionWalletBalance(float $newBalance): void
    {
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['wallet_balance'] = max(0, $newBalance);
        }
    }

    private function getAppUrl(): string
    {
        $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . BASE_URL;
    }

    private function completeOrder(int $orderId): void
    {
        $this->pdo->beginTransaction();

        try {
            // Lock riga per evitare doppia elaborazione (idempotenza)
            $stmt = $this->pdo->prepare("
                SELECT id, status, user_id, customer_email, customer_name, total_amount
                FROM orders
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $this->pdo->rollBack();
                error_log('Complete order: ordine #' . $orderId . ' non trovato');
                return;
            }

            // Idempotenza: già elaborato
            if ($order['status'] === 'paid') {
                $this->pdo->commit();
                return;
            }

            // 1. Aggiorna stato ordine
            $this->pdo->prepare("
                UPDATE orders
                SET status = 'paid', payment_status = 'paid'
                WHERE id = ?
            ")->execute([$orderId]);

            // 2. Decrementa stock per ogni prodotto dell'ordine
            $items = $this->pdo->prepare("
                SELECT product_id, quantity FROM order_items WHERE order_id = ?
            ");
            $items->execute([$orderId]);

            $stmtStock = $this->pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?
            ");

            foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $stmtStock->execute([
                    (int)$item['quantity'],
                    (int)$item['product_id'],
                    (int)$item['quantity'],
                ]);

                if ($stmtStock->rowCount() === 0) {
                    // Stock esaurito durante il pagamento — logga ma non rollbackare
                    error_log(sprintf(
                        'Complete order: stock insufficiente per prodotto #%d (ordine #%d)',
                        $item['product_id'],
                        $orderId
                    ));
                }
            }

            // 3. Svuota carrello
            if (!empty($order['user_id'])) {
                $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?")
                    ->execute([(int)$order['user_id']]);
            } else {
                unset($_SESSION['cart']);
            }

            $this->pdo->commit();

            // 4. Email di conferma (fuori transazione)
            try {
                (new MailService())->sendOrderConfirmation(
                    $order['customer_email'],
                    $order['customer_name'],
                    $orderId,
                    (float)$order['total_amount']
                );
            } catch (Throwable $e) {
                error_log('Complete order: errore invio email conferma ordine #' . $orderId . ': ' . $e->getMessage());
            }

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('Complete order: errore completeOrder #' . $orderId . ': ' . $e->getMessage());
        }
    }

    // ─── View ────────────────────────────────────────────────────────────

    public function walletRecharge(): void
    {
        $userId = $this->getUserId();
        if (!$userId) {
            header('Location: ' . BASE_URL . '/index.php?r=auth/loginForm');
            exit;
        }

        $amount = (float)($_GET['amount'] ?? 0);
        if ($amount < 10 || $amount > 500) {
            Flash::error('Importo non valido. Scegli tra €10 e €500.', BASE_URL . '/index.php?r=account/dashboard');
        }

        $stmt = $this->pdo->prepare("SELECT email, wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Flash::error('Utente non trovato.', BASE_URL . '/index.php?r=account/dashboard');
        }

        try {
            $env       = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
            $secretKey = $env['STRIPE_SECRET_KEY'] ?? '';

            if ($secretKey === '') {
                throw new RuntimeException('STRIPE_SECRET_KEY mancante');
            }

            \Stripe\Stripe::setApiKey($secretKey);

            $appUrl  = $this->getAppUrl();
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'customer_email'       => $user['email'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => ['name' => 'Ricarica Wallet TechShop'],
                        'unit_amount'  => (int)round($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $appUrl . '/index.php?r=checkout/walletSuccess&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $appUrl . '/index.php?r=account/dashboard',
                'metadata'    => [
                    'wallet_recharge' => 'true',
                    'user_id'         => (string)$userId,
                    'amount'          => (string)$amount,
                ],
            ]);

            $_SESSION['last_wallet_amount'] = $amount;

            header('Location: ' . $session->url);
            exit;

        } catch (Throwable $e) {
            Flash::error('Errore Stripe: ' . $e->getMessage(), BASE_URL . '/index.php?r=account/dashboard');
        }
    }

    public function walletSuccess(): void
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
            $env       = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
            $secretKey = $env['STRIPE_SECRET_KEY'] ?? '';
            \Stripe\Stripe::setApiKey($secretKey);

            $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);

            if ($session->payment_status === 'paid') {
                $this->pdo->beginTransaction();
                try {
                    $this->pdo->prepare("
                        UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
                    ")->execute([$amount, $userId]);

                    $this->pdo->prepare("
                        INSERT INTO wallet_logs (user_id, amount, description, created_at)
                        VALUES (?, ?, ?, NOW())
                    ")->execute([$userId, $amount, 'Ricarica via Stripe']);

                    $this->pdo->commit();

                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['wallet_balance'] = (float)($_SESSION['user']['wallet_balance'] ?? 0) + $amount;
                    }

                    Flash::success('Wallet ricaricato di € ' . number_format($amount, 2, ',', '.'), BASE_URL . '/index.php?r=account/dashboard');
                } catch (Throwable $e) {
                    $this->pdo->rollBack();
                    throw $e;
                }
            }

            header('Location: ' . BASE_URL . '/index.php?r=account/dashboard');
            exit;
        } catch (Throwable $e) {
            Flash::error('Errore elaborazione pagamento: ' . $e->getMessage(), BASE_URL . '/index.php?r=account/dashboard');
        }
    }

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
        $walletBalance = $this->getWalletBalance($userId);
        $total         = $this->getTotal($items);
        $flash         = Flash::get();

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function success(): void
    {
        $orderId    = $_SESSION['last_order_id']    ?? null;
        $orderEmail = $_SESSION['last_order_email'] ?? null;

        // Conferma da Stripe (sessione_id in query string)
        if (!empty($_GET['session_id'])) {
            $orderModel = new Order($this->pdo);
            $order      = $orderModel->findByStripeSessionId($_GET['session_id']);
            if ($order) {
                $orderId    = $order['id'];
                $orderEmail = $order['customer_email'];
                if ($order['status'] !== 'paid' && $order['payment_status'] !== 'paid') {
                    $this->completeOrder($orderId);
                }
            }
        }

        // Fallback usando l'ordine salvato nella sessione
        if (!$orderId && !empty($_SESSION['last_order_id'])) {
            $orderId    = $_SESSION['last_order_id'];
            $orderEmail = $_SESSION['last_order_email'] ?? $orderEmail;
            $orderModel = new Order($this->pdo);
            $order      = $orderModel->findById($orderId);
            if ($order && $order['status'] !== 'paid' && $order['payment_status'] !== 'paid') {
                $this->completeOrder($orderId);
            }
        }

        unset($_SESSION['last_order_id'], $_SESSION['last_order_email']);

        if (!$orderId) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/checkout/success.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    // ─── Process ─────────────────────────────────────────────────────────

    public function process(): void
    {
        CsrfHelper::validate();

        $items = $this->getCartItems();
        if (empty($items)) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        $userId        = $this->getUserId();
        $total         = $this->getTotal($items);
        $walletBalance = $this->getWalletBalance($userId);

        $name                = trim($_POST['name']           ?? '');
        $email               = trim($_POST['email']          ?? '');
        $paymentMethodInput  = trim($_POST['payment_method'] ?? 'card');
        $notes               = trim($_POST['notes']          ?? '');

        $redirectError = BASE_URL . '/index.php?r=checkout/index';

        if ($name === '' || $email === '') {
            Flash::error('Nome ed email sono obbligatori.', $redirectError);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::error('Indirizzo email non valido.', $redirectError);
        }

        try {
            $this->validateCartStock($items);
        } catch (Throwable $e) {
            Flash::error($e->getMessage(), $redirectError);
        }

        $orderModel = new Order($this->pdo);
        $noteValue  = $notes !== '' ? $notes : null;

        // ── SOLO WALLET ──────────────────────────────────────────────────
        if ($paymentMethodInput === 'wallet') {
            if (!$userId) {
                Flash::error('Devi essere loggato per usare il wallet.', $redirectError);
            }
            if ($walletBalance < $total) {
                Flash::error('Saldo wallet insufficiente.', $redirectError);
            }

            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id'            => $userId,
                    'customer_name'      => $name,
                    'customer_email'     => $email,
                    'total_amount'       => $total,
                    'status'             => 'paid',
                    'payment_method'     => 'wallet',
                    'wallet_amount_paid' => $total,
                    'stripe_amount_paid' => 0.00,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id'  => null,
                    'paypal_order_id'    => null,
                    'payment_status'     => 'paid',
                    'notes'              => $noteValue,
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
                    throw new RuntimeException('Saldo wallet insufficiente.');
                }

                $this->pdo->prepare("
                    INSERT INTO wallet_logs (user_id, amount, description, created_at)
                    VALUES (?, ?, ?, NOW())
                ")->execute([$userId, -$total, 'Pagamento ordine #' . $orderId]);

                $this->clearCart($userId);
                $this->pdo->commit();

                $this->updateSessionWalletBalance($walletBalance - $total);

                try {
                    (new MailService())->sendOrderConfirmation($email, $name, $orderId, $total);
                } catch (Throwable $e) {
                    error_log('Errore invio email ordine wallet #' . $orderId . ': ' . $e->getMessage());
                }

                $_SESSION['last_order_id']    = $orderId;
                $_SESSION['last_order_email'] = $email;

                header('Location: ' . BASE_URL . '/index.php?r=checkout/success');
                exit;

            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Flash::error('Errore checkout: ' . $e->getMessage(), $redirectError);
            }
        }

        // ── SOLO CARTA ───────────────────────────────────────────────────
        if ($paymentMethodInput === 'card') {
            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id'            => $userId,
                    'customer_name'      => $name,
                    'customer_email'     => $email,
                    'total_amount'       => $total,
                    'status'             => 'created',
                    'payment_method'     => 'stripe',
                    'wallet_amount_paid' => 0.00,
                    'stripe_amount_paid' => $total,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id'  => null,
                    'paypal_order_id'    => null,
                    'payment_status'     => 'pending',
                    'notes'              => $noteValue,
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

        // ── WALLET + CARTA ───────────────────────────────────────────────
        if ($paymentMethodInput === 'mixed') {
            if (!$userId) {
                Flash::error('Devi essere loggato per usare il wallet.', $redirectError);
            }
            if ($walletBalance <= 0) {
                Flash::error('Non hai saldo wallet disponibile.', $redirectError);
            }

            $walletAmountPaid = min($walletBalance, $total);
            $stripeAmountPaid = max(0, $total - $walletAmountPaid);

            if ($stripeAmountPaid <= 0) {
                Flash::error('Con il saldo disponibile puoi coprire l\'intero importo. Usa "Solo wallet".', $redirectError);
            }

            $this->pdo->beginTransaction();
            try {
                $orderId = $orderModel->create([
                    'user_id'            => $userId,
                    'customer_name'      => $name,
                    'customer_email'     => $email,
                    'total_amount'       => $total,
                    'status'             => 'created',
                    'payment_method'     => 'mixed',
                    'wallet_amount_paid' => $walletAmountPaid,
                    'stripe_amount_paid' => $stripeAmountPaid,
                    'paypal_amount_paid' => 0.00,
                    'stripe_session_id'  => null,
                    'paypal_order_id'    => null,
                    'payment_status'     => 'pending',
                    'notes'              => $noteValue,
                ]);

                $this->createOrderItems($orderModel, $orderId, $items);

                $stmtWallet = $this->pdo->prepare("
                    UPDATE users
                    SET wallet_balance = wallet_balance - ?
                    WHERE id = ? AND wallet_balance >= ?
                ");
                $stmtWallet->execute([$walletAmountPaid, $userId, $walletAmountPaid]);

                if ($stmtWallet->rowCount() === 0) {
                    throw new RuntimeException('Saldo wallet insufficiente.');
                }

                $this->pdo->prepare("
                    INSERT INTO wallet_logs (user_id, amount, description, created_at)
                    VALUES (?, ?, ?, NOW())
                ")->execute([$userId, -$walletAmountPaid, 'Prenotazione wallet ordine #' . $orderId]);

                $this->pdo->commit();
                $_SESSION['last_order_id']    = $orderId;
                $_SESSION['last_order_email'] = $email;
                $this->updateSessionWalletBalance($walletBalance - $walletAmountPaid);

            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Flash::error('Errore creazione ordine misto: ' . $e->getMessage(), $redirectError);
            }

            $this->redirectToStripe($orderId, $stripeAmountPaid, $email, 'Saldo residuo ordine #' . $orderId, $orderModel);
        }

        // ── PAYPAL ───────────────────────────────────────────────────────
        if ($paymentMethodInput === 'paypal') {
            Flash::error('PayPal non è ancora disponibile. Scegli un altro metodo di pagamento.', $redirectError);
        }

        Flash::error('Metodo di pagamento non valido.', $redirectError);
    }

    // ─── Redirect Stripe ────────────────────────────────────────────────

    private function redirectToStripe(int $orderId, float $amount, string $email, string $productName, Order $orderModel): never
    {
        $redirectError = BASE_URL . '/index.php?r=checkout/index';

        try {
            $env       = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
            $secretKey = $env['STRIPE_SECRET_KEY'] ?? '';

            if ($secretKey === '') {
                throw new RuntimeException('STRIPE_SECRET_KEY mancante nel file .env');
            }

            \Stripe\Stripe::setApiKey($secretKey);

            $appUrl  = $this->getAppUrl();
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'customer_email'       => $email,
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => 'eur',
                        'product_data' => ['name' => $productName],
                        'unit_amount'  => (int)round($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $appUrl . '/index.php?r=checkout/success&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $appUrl . '/index.php?r=checkout/index',
                'metadata'    => ['order_id' => (string)$orderId],
            ]);

            $orderModel->updateStripeSessionId($orderId, $session->id);

            header('Location: ' . $session->url);
            exit;

        } catch (Throwable $e) {
            Flash::error('Errore Stripe: ' . $e->getMessage(), $redirectError);
        }
    }
}