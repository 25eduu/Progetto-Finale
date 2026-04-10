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

    $this->pdo->beginTransaction();

    $stmt = $this->pdo->prepare("
      SELECT status FROM orders WHERE id = ? FOR UPDATE
    ");
    $stmt->execute([$orderId]);

    if ($stmt->fetchColumn() === 'paid') {
      $this->pdo->commit();
      return;
    }

    $this->pdo->prepare("
      UPDATE orders
      SET status = 'paid', payment_status = 'paid'
      WHERE id = ?
    ")->execute([$orderId]);

    $this->pdo->commit();
  }
}