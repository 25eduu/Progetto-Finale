<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/entities/Product.php';
require_once __DIR__ . '/../models/repositories/Cart.php';

class BaseController
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ottiene l'ID dell'utente dalla sessione, o null se non autenticato
     */
    protected function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Ottiene i dati utente dalla sessione
     */
    protected function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Verifica se l'utente è autenticato
     */
    protected function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Verifica se l'utente è admin
     */
    protected function isAdmin(): bool
    {
        return ($_SESSION['user']?? null)['role'] === 'admin';
    }

    /**
     * Ottiene i prodotti nel carrello dell'utente dal database
     */
    protected function getCartItems(): array
    {
        if ($this->isAuthenticated()) {
            $cart = new Cart($this->pdo);
            return $cart->getItemsByUserId((int)$this->getUserId());
        }

        return $this->getSessionCartItems();
    }

    protected function getSessionCartItems(): array
    {
        $items = [];

        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return [];
        }

        $productModel = new Product($this->pdo);

        foreach ($_SESSION['cart'] as $productId => $cartItem) {
            $product = $productModel->findById((int)$productId);
            if (!$product) {
                continue;
            }

            $items[] = [
                'product_id' => (int)$product['id'],
                'quantity'   => (int)($cartItem['quantity'] ?? 0),
                'name'       => $product['name'],
                'price'      => (float)$product['price'],
                'stock'      => (int)$product['stock'],
                'image_path' => $product['image_path'],
            ];
        }

        return $items;
    }

    /**
     * Calcola il totale del carrello
     */
    protected function getCartTotal(array $items = []): float
    {
        if (empty($items)) {
            $items = $this->getCartItems();
        }

        $total = 0;
        foreach ($items as $item) {
            $total += (float)$item['price'] * (int)$item['quantity'];
        }

        return round($total, 2);
    }

    /**
     * Ottiene il conteggio articoli nel carrello
     */
    protected function getCartCount(): int
    {
        if ($this->isAuthenticated()) {
            $cart = new Cart($this->pdo);
            return $cart->countItems((int)$this->getUserId());
        }

        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return 0;
        }

        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += (int)($item['quantity'] ?? 0);
        }

        return $count;
    }

    /**
     * Renderizza una vista con variabili
     */
    protected function render(string $viewPath, array $variables = []): void
    {
        extract($variables, EXTR_SKIP);
        require $viewPath;
    }

    /**
     * Restituisce risposta JSON
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirige l'utente
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
