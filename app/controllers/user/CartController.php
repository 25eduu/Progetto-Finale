<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/entities/Product.php';
require_once __DIR__ . '/../../models/repositories/Cart.php';
require_once __DIR__ . '/../../controllers/BaseController.php';

class CartController extends BaseController
{
    private function getProductOrNull(int $productId): ?array
    {
        return (new Product($this->pdo))->findById($productId);
    }

    private function getCurrentQuantity(int $productId): int
    {
        $userId = $this->getUserId();

        if ($userId) {
            foreach ($this->getCartItems() as $item) {
                if ((int)$item['product_id'] === $productId) {
                    return (int)$item['quantity'];
                }
            }
            return 0;
        }

        return (int)($_SESSION['cart'][$productId]['quantity'] ?? 0);
    }

    private function renderMiniCartHtml(): string
    {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        ob_start();
        require __DIR__ . '/../views/cart/_mini_cart.php';
        return ob_get_clean();
    }

    private function jsonCartResponse(bool $success, ?string $message = null): void
    {
        $this->jsonResponse([
            'success'      => $success,
            'message'      => $message,
            'cartCount'    => $this->getCartCount(),
            'miniCartHtml' => $this->renderMiniCartHtml(),
        ]);
    }

    public function index(): void
    {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        $pdo   = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/cart/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function sidebar(): void
    {
        $items = $this->getCartItems();
        $total = $this->getCartTotal($items);
        require __DIR__ . '/../views/cart/_mini_cart.php';
    }

    public function add(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->redirect(BASE_URL . '/index.php?r=products/index');
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->addProduct($userId, $productId, 1);
            } catch (RuntimeException $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        } else {
            $product = $this->getProductOrNull($productId);
            if (!$product) {
                $this->redirect(BASE_URL . '/index.php?r=products/index');
            }
            $currentQty = $this->getCurrentQuantity($productId);
            if ($currentQty + 1 > (int)$product['stock']) {
                $_SESSION['flash_error'] = 'Stock insufficiente per questo prodotto.';
            } else {
                $_SESSION['cart'][$productId]['quantity'] = $currentQty + 1;
            }
        }

        $this->redirect(BASE_URL . '/index.php?r=cart/index');
    }

    public function update(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        if ($productId <= 0) {
            $this->redirect(BASE_URL . '/index.php?r=cart/index');
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

        $this->redirect(BASE_URL . '/index.php?r=cart/index');
    }

    public function remove(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->redirect(BASE_URL . '/index.php?r=cart/index');
        }

        $userId = $this->getUserId();

        if ($userId) {
            (new Cart($this->pdo))->removeProduct($userId, $productId);
        } else {
            unset($_SESSION['cart'][$productId]);
        }

        $this->redirect(BASE_URL . '/index.php?r=cart/index');
    }

    public function addAjax(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonCartResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->addProduct($userId, $productId, 1);
                $this->jsonCartResponse(true, 'Prodotto aggiunto al carrello.');
            } catch (RuntimeException $e) {
                $this->jsonCartResponse(false, $e->getMessage());
            }
        } else {
            $product = $this->getProductOrNull($productId);
            if (!$product) {
                $this->jsonCartResponse(false, 'Prodotto non trovato.');
            }
            $currentQty = $this->getCurrentQuantity($productId);
            if ($currentQty + 1 > (int)$product['stock']) {
                $this->jsonCartResponse(false, 'Hai raggiunto la quantità massima disponibile.');
            }
            $_SESSION['cart'][$productId]['quantity'] = $currentQty + 1;
            $this->jsonCartResponse(true, 'Prodotto aggiunto al carrello.');
        }
    }

    public function updateAjax(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        if ($productId <= 0) {
            $this->jsonCartResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            try {
                (new Cart($this->pdo))->updateQuantity($userId, $productId, $quantity);
                $this->jsonCartResponse(true, 'Carrello aggiornato.');
            } catch (RuntimeException $e) {
                $this->jsonCartResponse(false, $e->getMessage());
            }
        } else {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $product = $this->getProductOrNull($productId);
                if (!$product) {
                    $this->jsonCartResponse(false, 'Prodotto non trovato.');
                }
                if ($quantity > (int)$product['stock']) {
                    $this->jsonCartResponse(false, 'Quantità superiore allo stock disponibile.');
                }
                $_SESSION['cart'][$productId] = ['quantity' => $quantity];
            }
            $this->jsonCartResponse(true, 'Carrello aggiornato.');
        }
    }

    public function removeAjax(): void
    {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonCartResponse(false, 'Prodotto non valido.');
        }

        $userId = $this->getUserId();

        if ($userId) {
            (new Cart($this->pdo))->removeProduct($userId, $productId);
        } else {
            unset($_SESSION['cart'][$productId]);
        }

        $this->jsonCartResponse(true, 'Prodotto rimosso dal carrello.');
    }
}
