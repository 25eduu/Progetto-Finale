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
        return isset($_SESSION['user_id'])
            ? (int)$_SESSION['user_id']
            : (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    }

    private function getProductOrNull(int $productId): ?array {
        return (new Product($this->pdo))->findById($productId);
    }

    private function getCurrentQuantity(int $productId): int {
        $userId = $this->getUserId();

        if ($userId) {
            foreach ((new Cart($this->pdo))->getItemsByUserId($userId) as $item) {
                if ((int)$item['product_id'] === $productId) {
                    return (int)$item['quantity'];
                }
            }
            return 0;
        }

        return (int)($_SESSION['cart'][$productId]['quantity'] ?? 0);
    }

    private function getCartItems(): array {
        $userId = $this->getUserId();

        if ($userId) {
            return (new Cart($this->pdo))->getItemsByUserId($userId);
        }

        $items        = [];
        $productModel = new Product($this->pdo);

        foreach ($_SESSION['cart'] ?? [] as $productId => $item) {
            $product = $productModel->findById((int)$productId);
            if (!$product) continue;

            $items[] = [
                'product_id' => (int)$product['id'],
                'quantity'   => (int)$item['quantity'],
                'name'       => $product['name'],
                'price'      => $product['price'],
                'stock'      => $product['stock'],
                'image_path' => $product['image_path'],
            ];
        }

        return $items;
    }

    private function getCartCount(): int {
        $userId = $this->getUserId();

        if ($userId) {
            return (new Cart($this->pdo))->countItems($userId);
        }

        return array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
    }

    private function getCartTotal(array $items): float {
        return array_reduce($items, fn($c, $i) => $c + (float)$i['price'] * (int)$i['quantity'], 0.0);
    }

    private function renderMiniCartHtml(): string {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        ob_start();
        require __DIR__ . '/../views/cart/_mini_cart.php';
        return ob_get_clean();
    }

    private function jsonResponse(bool $success, ?string $message = null): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'      => $success,
            'message'      => $message,
            'cartCount'    => $this->getCartCount(),
            'miniCartHtml' => $this->renderMiniCartHtml(),
        ]);
        exit;
    }

    // ─── Pagine ───────────────────────────────────────────────────────────

    public function index(): void {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/cart/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function sidebar(): void {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        require __DIR__ . '/../views/cart/_mini_cart.php';
    }

    // ─── Azioni POST standard ─────────────────────────────────────────────

    public function add(): void {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?r=products/index');
            exit;
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->addProduct($userId, $productId, 1);
            } catch (RuntimeException $e) {
                // Gestione graceful: torna al carrello con messaggio
                $_SESSION['flash_error'] = $e->getMessage();
            }
        } else {
            $product = $this->getProductOrNull($productId);
            if (!$product) {
                header('Location: ' . BASE_URL . '/index.php?r=products/index');
                exit;
            }
            $currentQty = $this->getCurrentQuantity($productId);
            if ($currentQty + 1 > (int)$product['stock']) {
                $_SESSION['flash_error'] = 'Stock insufficiente per questo prodotto.';
            } else {
                $_SESSION['cart'][$productId]['quantity'] = $currentQty + 1;
            }
        }

        header('Location: ' . BASE_URL . '/index.php?r=cart/index');
        exit;
    }

    public function update(): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        if ($productId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->updateQuantity($userId, $productId, $quantity);
            } catch (RuntimeException $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        } else {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $product = $this->getProductOrNull($productId);
                if ($product && $quantity <= (int)$product['stock']) {
                    $_SESSION['cart'][$productId] = ['quantity' => $quantity];
                }
            }
        }

        header('Location: ' . BASE_URL . '/index.php?r=cart/index');
        exit;
    }

    public function remove(): void {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?r=cart/index');
            exit;
        }

        $userId = $this->getUserId();

        if ($userId) {
            (new Cart($this->pdo))->removeProduct($userId, $productId);
        } else {
            unset($_SESSION['cart'][$productId]);
        }

        header('Location: ' . BASE_URL . '/index.php?r=cart/index');
        exit;
    }

    // ─── Azioni AJAX ─────────────────────────────────────────────────────

    public function addAjax(): void {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->addProduct($userId, $productId, 1);
                $this->jsonResponse(true, 'Prodotto aggiunto al carrello.');
            } catch (RuntimeException $e) {
                $this->jsonResponse(false, $e->getMessage());
            }
        } else {
            $product = $this->getProductOrNull($productId);
            if (!$product) {
                $this->jsonResponse(false, 'Prodotto non trovato.');
            }
            $currentQty = $this->getCurrentQuantity($productId);
            if ($currentQty + 1 > (int)$product['stock']) {
                $this->jsonResponse(false, 'Hai raggiunto la quantità massima disponibile.');
            }
            $_SESSION['cart'][$productId]['quantity'] = $currentQty + 1;
            $this->jsonResponse(true, 'Prodotto aggiunto al carrello.');
        }
    }

    public function updateAjax(): void {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->updateQuantity($userId, $productId, $quantity);
                $this->jsonResponse(true, 'Carrello aggiornato.');
            } catch (RuntimeException $e) {
                $this->jsonResponse(false, $e->getMessage());
            }
        } else {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $product = $this->getProductOrNull($productId);
                if (!$product) {
                    $this->jsonResponse(false, 'Prodotto non trovato.');
                }
                if ($quantity > (int)$product['stock']) {
                    $this->jsonResponse(false, 'Quantità superiore allo stock disponibile.');
                }
                $_SESSION['cart'][$productId] = ['quantity' => $quantity];
            }
            $this->jsonResponse(true, 'Carrello aggiornato.');
        }
    }

    public function removeAjax(): void {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            (new Cart($this->pdo))->removeProduct($userId, $productId);
        } else {
            unset($_SESSION['cart'][$productId]);
        }

        $this->jsonResponse(true, 'Prodotto rimosso dal carrello.');
    }
}
