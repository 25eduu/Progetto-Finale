<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/repositories/Cart.php';
require_once __DIR__ . '/../models/entities/Product.php';
require_once __DIR__ . '/../models/entities/User.php';
require_once __DIR__ . '/../models/entities/Order.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/email/MailService.php';

class CheckoutService
{
    private PDO $pdo;
    private OrderService $orderService;
    private MailService $mailService;
    private Product $productModel;
    private User $userModel;
    private Cart $cartRepository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->orderService = new OrderService($pdo);
        $this->mailService = new MailService();
        $this->productModel = new Product($pdo);
        $this->userModel = new User($pdo);
        $this->cartRepository = new Cart($pdo);
    }

    public function getCartItems(?int $userId): array
    {
        if ($userId) {
            return $this->cartRepository->getItemsByUserId($userId);
        }

        $items = [];
        foreach ($_SESSION['cart'] ?? [] as $id => $item) {
            $product = $this->productModel->findById((int)$id);
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

    public function getTotal(array $items): float
    {
        return array_reduce($items, fn($carry, $item) => $carry + ((float)$item['price'] * (int)$item['quantity']), 0.0);
    }

    public function getWalletBalance(int $userId): float
    {
        return $this->userModel->getWalletBalance($userId);
    }

    public function processWalletOrder(int $userId, string $name, string $email, ?string $notes, array $items, float $total): int
    {
        if ($userId === null) {
            throw new RuntimeException('Devi essere loggato per usare il wallet.');
        }

        $walletBalance = $this->getWalletBalance($userId);
        if ($walletBalance < $total) {
            throw new RuntimeException('Saldo wallet insufficiente.');
        }

        $this->pdo->beginTransaction();

        try {
            $orderId = $this->orderService->createOrder([
                'user_id' => $userId,
                'customer_name' => $name,
                'customer_email' => $email,
                'total_amount' => $total,
                'status' => 'paid',
                'payment_method' => 'wallet',
                'wallet_amount_paid' => $total,
                'stripe_amount_paid' => 0.00,
                'paypal_amount_paid' => 0.00,
                'payment_status' => 'paid',
                'notes' => $notes,
            ]);

            $this->orderService->addItems($orderId, $items);
            $this->orderService->decreaseStock($items);

            if (!$this->userModel->subtractWalletBalance($userId, $total)) {
                throw new RuntimeException('Saldo wallet insufficiente.');
            }

            $this->pdo->prepare(
                'INSERT INTO wallet_logs (user_id, amount, description, created_at) VALUES (?, ?, ?, NOW())'
            )->execute([$userId, -$total, 'Pagamento ordine #' . $orderId]);

            $this->orderService->clearCart($userId);
            $this->pdo->commit();

            try {
                $this->mailService->sendOrderConfirmation($email, $name, $orderId, $total, $items);
            } catch (Throwable $e) {
                error_log('Email ordine wallet #' . $orderId . ': ' . $e->getMessage());
            }

            return $orderId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function processCardOrder(?int $userId, string $name, string $email, ?string $notes, array $items, float $total): int
    {
        $this->pdo->beginTransaction();

        try {
            $orderId = $this->orderService->createOrder([
                'user_id' => $userId,
                'customer_name' => $name,
                'customer_email' => $email,
                'total_amount' => $total,
                'status' => 'created',
                'payment_method' => 'stripe',
                'wallet_amount_paid' => 0.00,
                'stripe_amount_paid' => $total,
                'paypal_amount_paid' => 0.00,
                'payment_status' => 'pending',
                'notes' => $notes,
            ]);

            $this->orderService->addItems($orderId, $items);
            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function processMixedOrder(int $userId, string $name, string $email, ?string $notes, array $items, float $total, float $walletBalance): array
    {
        if ($userId === null) {
            throw new RuntimeException('Devi essere loggato per usare il wallet.');
        }

        if ($walletBalance <= 0) {
            throw new RuntimeException('Non hai saldo wallet disponibile.');
        }

        $walletPaid = min($walletBalance, $total);
        $stripePaid = max(0.0, $total - $walletPaid);

        if ($stripePaid <= 0) {
            throw new RuntimeException('Usa "Solo wallet" — il saldo copre l\'intero importo.');
        }

        $this->pdo->beginTransaction();

        try {
            $orderId = $this->orderService->createOrder([
                'user_id' => $userId,
                'customer_name' => $name,
                'customer_email' => $email,
                'total_amount' => $total,
                'status' => 'created',
                'payment_method' => 'mixed',
                'wallet_amount_paid' => $walletPaid,
                'stripe_amount_paid' => $stripePaid,
                'paypal_amount_paid' => 0.00,
                'payment_status' => 'pending',
                'notes' => $notes,
            ]);

            $this->orderService->addItems($orderId, $items);

            if (!$this->userModel->subtractWalletBalance($userId, $walletPaid)) {
                throw new RuntimeException('Saldo wallet insufficiente.');
            }

            $this->pdo->prepare(
                'INSERT INTO wallet_logs (user_id, amount, description, created_at) VALUES (?, ?, ?, NOW())'
            )->execute([$userId, -$walletPaid, 'Prenotazione wallet ordine #' . $orderId]);

            $this->pdo->commit();

            return [
                'order_id' => $orderId,
                'stripe_amount' => $stripePaid,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
