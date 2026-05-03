<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../services/MailService.php';

class StripeWebhookController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(): void
    {
        $env    = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
        $secret = $env['STRIPE_WEBHOOK_SECRET'] ?? '';

        if ($secret === '') {
            http_response_code(500);
            exit;
        }

        $payload = (string)file_get_contents('php://input');
        $sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Exception $e) {
            http_response_code(400);
            exit;
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            default                      => null,
        };

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    private function handleCheckoutCompleted(object $session): void
    {
        $orderId = isset($session->metadata->order_id)
            ? (int)$session->metadata->order_id
            : 0;

        if ($orderId <= 0) {
            error_log('Stripe webhook: order_id mancante nei metadata');
            return;
        }

        $this->markOrderPaid($orderId);
    }

    private function markOrderPaid(int $orderId): void
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
                error_log('Stripe webhook: ordine #' . $orderId . ' non trovato');
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
                        'Stripe webhook: stock insufficiente per prodotto #%d (ordine #%d)',
                        $item['product_id'],
                        $orderId
                    ));
                }
            }

            // 3. Svuota carrello utente (se loggato)
            if (!empty($order['user_id'])) {
                $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?")
                    ->execute([(int)$order['user_id']]);
            }

            $this->pdo->commit();

            // 4. Email di conferma (fuori transazione)
            try {
                $mailService = new MailService();
                $mailService->sendOrderConfirmation(
                    $order['customer_email'],
                    $order['customer_name'],
                    $orderId,
                    (float)$order['total_amount']
                );
            } catch (Throwable $e) {
                error_log('Stripe webhook: errore invio email conferma ordine #' . $orderId . ': ' . $e->getMessage());
            }

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('Stripe webhook: errore markOrderPaid #' . $orderId . ': ' . $e->getMessage());
        }
    }
}