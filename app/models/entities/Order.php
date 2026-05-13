<?php
declare(strict_types=1);

class Order
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (
                user_id,
                customer_name,
                customer_email,
                total_amount,
                status,
                payment_method,
                wallet_amount_paid,
                stripe_amount_paid,
                paypal_amount_paid,
                stripe_session_id,
                paypal_order_id,
                payment_status,
                notes,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $data['user_id'] ?? null,
            $data['customer_name'],
            $data['customer_email'],
            $data['total_amount'],
            $data['status'],
            $data['payment_method'],
            $data['wallet_amount_paid'] ?? 0.00,
            $data['stripe_amount_paid'] ?? 0.00,
            $data['paypal_amount_paid'] ?? 0.00,
            $data['stripe_session_id'] ?? null,
            $data['paypal_order_id'] ?? null,
            $data['payment_status'],
            $data['notes'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addItem(int $orderId, int $productId, int $quantity, float $unitPrice): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$orderId, $productId, $quantity, $unitPrice]);
    }

    public function updateStripeSessionId(int $orderId, string $stripeSessionId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE orders
            SET stripe_session_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$stripeSessionId, $orderId]);
    }

    public function markPaid(int $orderId, ?string $paymentIntentId = null): void
    {
        if ($paymentIntentId !== null) {
            $stmt = $this->pdo->prepare("
                UPDATE orders
                SET status = 'paid',
                    payment_status = 'paid',
                    stripe_payment_intent_id = ?,
                    paid_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$paymentIntentId, $orderId]);
            return;
        }

        $stmt = $this->pdo->prepare("
            UPDATE orders
            SET status = 'paid',
                payment_status = 'paid',
                paid_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
    }

    public function findById(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM orders
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByStripeSessionId(string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM orders
            WHERE stripe_session_id = ?
            LIMIT 1
        ");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}