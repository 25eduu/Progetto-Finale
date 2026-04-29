<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

class StripeWebhookController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function handle(): void {
        $env = parse_ini_file(__DIR__ . '/../../.env');
        $secret = $env['STRIPE_WEBHOOK_SECRET'];
        $payload = @file_get_contents('php://input');
        $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Exception $e) {
            http_response_code(400);
            exit;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;
            if ($orderId) {
                $this->markOrderPaid((int)$orderId);
            }
        }
        http_response_code(200);
    }

    private function markOrderPaid(int $orderId): void {
        try {
            $this->pdo->beginTransaction();

            // 1. Verifichiamo se l'ordine è già pagato per evitare doppie operazioni
            $stmt = $this->pdo->prepare("SELECT status, user_id FROM orders WHERE id = ? FOR UPDATE");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order || $order['status'] === 'paid') {
                $this->pdo->rollBack();
                return;
            }

            // 2. Aggiorna lo stato dell'ordine
            $this->pdo->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?")
                      ->execute([$orderId]);

            // 3. Decrementa lo STOCK dei prodotti
            $stmtItems = $this->pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$orderId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $updateStock = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($items as $item) {
                $updateStock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            }

            // 4. Svuota il carrello dell'utente
            if ($order['user_id']) {
                $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$order['user_id']]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Errore Webhook Order #$orderId: " . $e->getMessage());
        }
    }
}