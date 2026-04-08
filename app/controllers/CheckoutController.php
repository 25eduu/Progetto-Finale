<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/MailService.php';
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

  private function redirectWithError(string $message): void {
    $_SESSION['checkout_error'] = $message;
    header('Location: ' . BASE_URL . '/index.php?r=checkout/index');
    exit;
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

  private function validateCartStock(array $items): void {
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

  public function index(): void {
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
    $walletAmountPaid = 0.00;
    $stripeAmountPaid = 0.00;
    $paypalAmountPaid = 0.00;
    $paymentMethod = $paymentMethodInput;
    $paymentStatus = 'pending';
    $status = 'created';

    if ($paymentMethodInput === 'wallet') {
      if (!$userId) {
        $this->redirectWithError('Devi essere loggato per usare il wallet');
      }

      if ($walletBalance < $total) {
        $this->redirectWithError('Saldo wallet insufficiente');
      }

      $walletAmountPaid = $total;
      $paymentStatus = 'paid';
      $status = 'paid';
    } elseif ($paymentMethodInput === 'card') {
      $stripeAmountPaid = $total;
    } elseif ($paymentMethodInput === 'paypal') {
      $paypalAmountPaid = $total;
    } elseif ($paymentMethodInput === 'mixed') {
      if (!$userId) {
        $this->redirectWithError('Devi essere loggato per usare il wallet');
      }

      if ($walletBalance <= 0) {
        $this->redirectWithError('Non hai saldo wallet disponibile');
      }

      $walletAmountPaid = min($walletBalance, $total);
      $stripeAmountPaid = $total - $walletAmountPaid;
      $paymentMethod = 'mixed';
    } else {
      $this->redirectWithError('Metodo di pagamento non valido');
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

      $stmtStock = $this->pdo->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
      ");

      foreach ($items as $i) {
        $stmtItem->execute([
          $orderId,
          (int)$i['product_id'],
          (int)$i['quantity'],
          (float)$i['price']
        ]);

        $stmtStock->execute([
          (int)$i['quantity'],
          (int)$i['product_id'],
          (int)$i['quantity']
        ]);

        if ($stmtStock->rowCount() === 0) {
          throw new RuntimeException('Stock insufficiente per il prodotto: ' . $i['name']);
        }
      }

      if ($walletAmountPaid > 0) {
        if (!$userId) {
          throw new RuntimeException('Utente non valido per addebito wallet');
        }

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
          sprintf('Pagamento ordine #%d', $orderId)
        ]);
      }

      if ($userId) {
        $cartModel = new Cart($this->pdo);
        $cartModel->clear($userId);
      } else {
        unset($_SESSION['cart']);
      }

      $this->pdo->commit();

      try {
        $mailService = new MailService();
        $mailService->sendOrderConfirmation($email, $name, $orderId, $total);
      } catch (Throwable $e) {
        // Non bloccare il checkout se l'email fallisce
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

  public function success(): void {
    $orderId = $_SESSION['last_order_id'] ?? null;
    $orderEmail = $_SESSION['last_order_email'] ?? null;

    if (!$orderId) {
      header('Location: ' . BASE_URL . '/index.php');
      exit;
    }

    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/checkout/success.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }
}