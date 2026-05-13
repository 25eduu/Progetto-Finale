<?php
declare(strict_types=1);

class OrderService
{
    private PDO $pdo;
    private Order $orderModel;
    private MailService $mailService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->orderModel = new Order($pdo);
        $this->mailService = new MailService();
    }

    public function validateCartStock(array $items): void
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
                throw new RuntimeException('Stock insufficiente per "' . $item['name'] . '". Disponibili: ' . $item['stock']);
            }
        }
    }

    public function createOrder(array $data): int
    {
        return $this->orderModel->create($data);
    }

    public function addItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $this->orderModel->addItem(
                $orderId,
                (int)$item['product_id'], 
                (int)$item['quantity'], 
                (float)$item['price']
            );
        }
    }

    public function decreaseStock(array $items): void
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($items as $item) {
            $stmt->execute([(int)$item['quantity'], (int)$item['product_id'], (int)$item['quantity']]);
            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Stock insufficiente per: ' . $item['name']);
            }
        }
    }

    public function clearCart(?int $userId): void
    {
        if ($userId) {
            (new Cart($this->pdo))->clear($userId);
        } else {
            unset($_SESSION['cart']);
        }
    }

    public function completeOrder(int $orderId): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("SELECT id, status, user_id, customer_email, customer_name, total_amount FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $this->pdo->rollBack();
                return;
            }

            if ($order['status'] === 'paid') {
                $this->pdo->commit();
                return;
            }

            $this->pdo->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?")->execute([$orderId]);

            $itemsStmt = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$orderId]);

            $stmtStock = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($itemsStmt->fetchAll() as $item) {
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
                $this->mailService->sendOrderConfirmation(
                    $order['customer_email'],
                    $order['customer_name'],
                    $orderId,
                    (float)$order['total_amount']
                );
            } catch (Throwable $e) {
                error_log("completeOrder: errore email ordine #$orderId: " . $e->getMessage());
            }
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log("completeOrder: errore #$orderId: " . $e->getMessage());
            throw $e;
        }
    }
}
