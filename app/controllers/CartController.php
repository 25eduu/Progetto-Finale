<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  private function getUserId(): ?int {
    if (isset($_SESSION['user_id'])) {
      return (int)$_SESSION['user_id'];
    }

    if (isset($_SESSION['user']['id'])) {
      return (int)$_SESSION['user']['id'];
    }

    return null;
  }

  private function getCartItems(): array {
    $userId = $this->getUserId();
    $items = [];

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      return $cartModel->getItemsByUserId($userId);
    }

    $sessionCart = $_SESSION['cart'] ?? [];
    if (empty($sessionCart)) {
      return [];
    }

    $productModel = new Product($this->pdo);

    foreach ($sessionCart as $productId => $item) {
      $product = $productModel->findById((int)$productId);
      if (!$product) {
        continue;
      }

      $items[] = [
        'product_id' => (int)$product['id'],
        'quantity' => (int)$item['quantity'],
        'name' => $product['name'],
        'price' => $product['price'],
        'stock' => $product['stock'],
        'image_path' => $product['image_path'],
      ];
    }

    return $items;
  }

  private function getCartCount(): int {
    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      return $cartModel->countItems($userId);
    }

    $count = 0;
    foreach (($_SESSION['cart'] ?? []) as $item) {
      $count += (int)$item['quantity'];
    }
    return $count;
  }

  private function getCartTotal(array $items): float {
    $total = 0.0;
    foreach ($items as $item) {
      $total += (float)$item['price'] * (int)$item['quantity'];
    }
    return $total;
  }

  private function renderMiniCartHtml(): string {
    $items = $this->getCartItems();
    $total = $this->getCartTotal($items);

    ob_start();
    require __DIR__ . '/../views/cart/_mini_cart.php';
    return ob_get_clean();
  }

  private function jsonCartResponse(): void {
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
      'success' => true,
      'cartCount' => $this->getCartCount(),
      'miniCartHtml' => $this->renderMiniCartHtml(),
    ]);
    exit;
  }

  public function index(): void {
    $items = $this->getCartItems();
    $total = $this->getCartTotal($items);

    $pdo = $this->pdo;
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/cart/index.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function sidebar(): void {
    $items = $this->getCartItems();
    $total = $this->getCartTotal($items);
    require __DIR__ . '/../views/cart/_mini_cart.php';
  }

  public function add(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($productId <= 0) {
      header('Location: ' . BASE_URL . '/index.php?r=products/index');
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->addProduct($userId, $productId, 1);
    } else {
      if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
      }

      if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = ['quantity' => 0];
      }

      $_SESSION['cart'][$productId]['quantity']++;
    }

    header('Location: ' . BASE_URL . '/index.php?r=cart/index');
    exit;
  }

  public function addAjax(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($productId <= 0) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Prodotto non valido']);
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->addProduct($userId, $productId, 1);
    } else {
      if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
      }

      if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = ['quantity' => 0];
      }

      $_SESSION['cart'][$productId]['quantity']++;
    }

    $this->jsonCartResponse();
  }

  public function update(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($productId <= 0) {
      header('Location: ' . BASE_URL . '/index.php?r=cart/index');
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->updateQuantity($userId, $productId, $quantity);
    } else {
      if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
      } else {
        $_SESSION['cart'][$productId] = ['quantity' => $quantity];
      }
    }

    header('Location: ' . BASE_URL . '/index.php?r=cart/index');
    exit;
  }

  public function updateAjax(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    if ($productId <= 0) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Prodotto non valido']);
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->updateQuantity($userId, $productId, $quantity);
    } else {
      if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
      } else {
        $_SESSION['cart'][$productId] = ['quantity' => $quantity];
      }
    }

    $this->jsonCartResponse();
  }

  public function remove(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
      header('Location: ' . BASE_URL . '/index.php?r=cart/index');
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->removeProduct($userId, $productId);
    } else {
      unset($_SESSION['cart'][$productId]);
    }

    header('Location: ' . BASE_URL . '/index.php?r=cart/index');
    exit;
  }

  public function removeAjax(): void {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['success' => false, 'message' => 'Prodotto non valido']);
      exit;
    }

    $userId = $this->getUserId();

    if ($userId) {
      $cartModel = new Cart($this->pdo);
      $cartModel->removeProduct($userId, $productId);
    } else {
      unset($_SESSION['cart'][$productId]);
    }

    $this->jsonCartResponse();
  }
}