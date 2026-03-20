<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CheckoutController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  private function getUserId(): ?int {
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($_SESSION['user']['id'])) return (int)$_SESSION['user']['id'];
    return null;
  }

  private function getCartItems(): array {
    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      return $cartModel->getItemsByUserId($userId);
    }

    $items = [];
    $productModel = new Product($this->pdo);

    foreach ($_SESSION['cart'] ?? [] as $id => $item) {
      $product = $productModel->findById((int)$id);
      if (!$product) continue;

      $items[] = [
        'product_id' => (int)$product['id'],
        'quantity'   => (int)$item['quantity'],
        'price'      => (float)$product['price'],
        'name'       => $product['name'],
        'stock'      => (int)$product['stock'],
      ];
    }

    return $items;
  }

  private function getTotal(array $items): float {
    $total = 0.0;
    foreach ($items as $i) {
      $total += (float)$i['price'] * (int)$i['quantity'];
    }
    return $total;
  }

  private function getWalletBalance(?int $userId): float {
    if (!$userId) return 0.0;

    $stmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return (float)($stmt->fetchColumn() ?: 0);
  }

  public function index(): void {
    $items = $this->getCartItems();
    $total = $this->getTotal($items);

    if (empty($items)) {
      header('Location: ' . BASE_URL . '/index.php?r=cart/index');
      exit;
    }

    $userId = $this->getUserId();
    $walletBalance = $this->getWalletBalance($userId);

    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/checkout/index.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function process(): void {
    $items = $this->getCartItems();
    if (empty($items)) {
      header('Location: ' . BASE_URL . '/index.php?r=cart/index');
      exit;
    }

    $userId = $this->getUserId();
    $total = $this->getTotal($items);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $paymentMethodInput = $_POST['payment_method'] ?? 'card';
    $useWallet = isset($_POST['use_wallet']) && $_POST['use_wallet'] === '1';
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '' || $email === '') {
      die('Nome ed email obbligatori');
    }

    $walletBalance = $this->getWalletBalance($userId);
    $walletAmountPaid = 0.00;
    $stripeAmountPaid = 0.00;
    $paypalAmountPaid = 0.00;
    $paymentMethod = $paymentMethodInput;
    $paymentStatus = 'pending';
    $status = 'created';

    if ($paymentMethodInput === 'wallet') {
      if ($walletBalance < $total) {
        die('Saldo wallet insufficiente');
      }
      $walletAmountPaid = $total;
      $paymentStatus = 'paid';
    } elseif ($paymentMethodInput === 'card') {
      if ($useWallet && $walletBalance > 0) {
        $walletAmountPaid = min($walletBalance, $total);
        $stripeAmountPaid = $total - $walletAmountPaid;
        $paymentMethod = $walletAmountPaid > 0 ? 'mixed' : 'card';
      } else {
        $stripeAmountPaid = $total;
      }
    } elseif ($paymentMethodInput === 'paypal') {
      if ($useWallet && $walletBalance > 0) {
        $walletAmountPaid = min($walletBalance, $total);
        $paypalAmountPaid = $total - $walletAmountPaid;
        $paymentMethod = $walletAmountPaid > 0 ? 'mixed' : 'paypal';
      } else {
        $paypalAmountPaid = $total;
      }
    } elseif ($paymentMethodInput === 'mixed') {
      $walletAmountPaid = min($walletBalance, $total);
      $stripeAmountPaid = $total - $walletAmountPaid;
      $paymentMethod = 'mixed';
    } else {
      die('Metodo di pagamento non valido');
    }

    $this->pdo->beginTransaction();

    try {
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, NOW())
      ");

      $stmt->execute([
        $userId,
        $name,
        $email,
        $total,
        $status,
        $paymentMethod,
        $walletAmountPaid,
        $stripeAmountPaid,
        $paypalAmountPaid,
        $paymentStatus,
        $notes !== '' ? $notes : null
      ]);

      $orderId = (int)$this->pdo->lastInsertId();

      $stmtItem = $this->pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
      ");

      foreach ($items as $i) {
        $stmtItem->execute([
          $orderId,
          $i['product_id'],
          $i['quantity'],
          $i['price']
        ]);

        $stmtStock = $this->pdo->prepare("
          UPDATE products
          SET stock = stock - ?
          WHERE id = ? AND stock >= ?
        ");
        $stmtStock->execute([
          $i['quantity'],
          $i['product_id'],
          $i['quantity']
        ]);
      }

      if ($walletAmountPaid > 0 && $userId) {
        $stmtWallet = $this->pdo->prepare("
          UPDATE users
          SET wallet_balance = wallet_balance - ?
          WHERE id = ?
        ");
        $stmtWallet->execute([$walletAmountPaid, $userId]);

        $stmtLog = $this->pdo->prepare("
          INSERT INTO wallet_logs (user_id, amount, description, created_at)
          VALUES (?, ?, ?, NOW())
        ");
        $stmtLog->execute([
          $userId,
          -$walletAmountPaid,
          'Pagamento ordine #' . $orderId
        ]);
      }

      if ($userId) {
        $cartModel = new Cart($this->pdo);
        $cartModel->clear($userId);
      } else {
        unset($_SESSION['cart']);
      }

      $this->pdo->commit();

      $_SESSION['last_order_id'] = $orderId;
      $_SESSION['last_order_email'] = $email;

      header('Location: ' . BASE_URL . '/index.php?r=checkout/success');
      exit;

    } catch (Throwable $e) {
      $this->pdo->rollBack();
      die('Errore checkout: ' . $e->getMessage());
    }
  }

  public function success(): void {
    $orderId = $_SESSION['last_order_id'] ?? null;
    $orderEmail = $_SESSION['last_order_email'] ?? null;

    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/checkout/success.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }
}