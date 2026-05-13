<?php
declare(strict_types=1);

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
        if (!$this->isAuthenticated()) {
            return [];
        }

        $cart = new Cart($this->pdo);
        return $cart->getItems((int)$this->getUserId());
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
        if (!$this->isAuthenticated()) {
            return 0;
        }

        $cart = new Cart($this->pdo);
        return $cart->getCount((int)$this->getUserId());
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
