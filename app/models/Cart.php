<?php
declare(strict_types=1);

class Cart {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getItemsByUserId(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                c.product_id,
                c.quantity,
                p.name,
                p.price,
                p.stock,
                p.image_path
            FROM cart c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?
            ORDER BY c.id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Aggiunge un prodotto al carrello con verifica stock in transazione.
     * Usa LOCK IN SHARE MODE per leggere lo stock in modo condiviso:
     * impedisce modifiche concorrenti allo stock durante la verifica,
     * ma permette ad altre letture di procedere contemporaneamente.
     *
     * @throws RuntimeException se lo stock è insufficiente
     */
    public function addProduct(int $userId, int $productId, int $qty = 1): void {
        $this->pdo->beginTransaction();

        try {
            // Legge lo stock con LOCK IN SHARE MODE:
            // nessun altro può aggiornare questo prodotto finché
            // la transazione non è completata
            $stmtStock = $this->pdo->prepare("
                SELECT stock, name
                FROM products
                WHERE id = ?
                LOCK IN SHARE MODE
            ");
            $stmtStock->execute([$productId]);
            $product = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException('Prodotto non trovato.');
            }

            // Verifica quante unità ha già nel carrello
            $stmtCart = $this->pdo->prepare("
                SELECT id, quantity
                FROM cart
                WHERE user_id = ? AND product_id = ?
                LIMIT 1
            ");
            $stmtCart->execute([$userId, $productId]);
            $existing = $stmtCart->fetch(PDO::FETCH_ASSOC);

            $currentQty = $existing ? (int)$existing['quantity'] : 0;
            $newQty     = $currentQty + $qty;

            if ($newQty > (int)$product['stock']) {
                throw new RuntimeException(
                    'Stock insufficiente per "' . $product['name'] . '". ' .
                    'Disponibili: ' . $product['stock'] . ', nel carrello: ' . $currentQty . '.'
                );
            }

            // Aggiorna o inserisce nel carrello
            if ($existing) {
                $this->pdo->prepare("
                    UPDATE cart SET quantity = ? WHERE id = ?
                ")->execute([$newQty, $existing['id']]);
            } else {
                $this->pdo->prepare("
                    INSERT INTO cart (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ")->execute([$userId, $productId, $qty]);
            }

            $this->pdo->commit();

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateQuantity(int $userId, int $productId, int $qty): void {
        if ($qty <= 0) {
            $this->removeProduct($userId, $productId);
            return;
        }

        // Verifica stock anche in aggiornamento
        $this->pdo->beginTransaction();
        try {
            $stmtStock = $this->pdo->prepare("
                SELECT stock, name FROM products WHERE id = ? LOCK IN SHARE MODE
            ");
            $stmtStock->execute([$productId]);
            $product = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException('Prodotto non trovato.');
            }

            if ($qty > (int)$product['stock']) {
                throw new RuntimeException(
                    'Stock insufficiente per "' . $product['name'] . '". ' .
                    'Disponibili: ' . $product['stock'] . '.'
                );
            }

            $this->pdo->prepare("
                UPDATE cart SET quantity = ?
                WHERE user_id = ? AND product_id = ?
            ")->execute([$qty, $userId, $productId]);

            $this->pdo->commit();

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function removeProduct(int $userId, int $productId): void {
        $this->pdo->prepare("
            DELETE FROM cart WHERE user_id = ? AND product_id = ?
        ")->execute([$userId, $productId]);
    }

    public function clear(int $userId): void {
        $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?")
            ->execute([$userId]);
    }

    public function countItems(int $userId): int {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(quantity), 0)
            FROM cart
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function mergeSessionCart(int $userId, array $sessionCart): void {
        foreach ($sessionCart as $productId => $item) {
            $qty = (int)($item['quantity'] ?? 0);
            if ($qty > 0) {
                try {
                    $this->addProduct($userId, (int)$productId, $qty);
                } catch (RuntimeException $e) {
                    // Se lo stock non basta durante il merge, salta silenziosamente
                    error_log('mergeSessionCart: ' . $e->getMessage());
                }
            }
        }
    }
}
