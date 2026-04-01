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

  public function addProduct(int $userId, int $productId, int $qty = 1): void {
    $stmt = $this->pdo->prepare("
      SELECT id, quantity
      FROM cart
      WHERE user_id = ? AND product_id = ?
      LIMIT 1
    ");
    $stmt->execute([$userId, $productId]);
    $row = $stmt->fetch();

    if ($row) {
      $newQty = (int)$row['quantity'] + $qty;
      $upd = $this->pdo->prepare("
        UPDATE cart
        SET quantity = ?
        WHERE id = ?
      ");
      $upd->execute([$newQty, $row['id']]);
      return;
    }

    $ins = $this->pdo->prepare("
      INSERT INTO cart (user_id, product_id, quantity)
      VALUES (?, ?, ?)
    ");
    $ins->execute([$userId, $productId, $qty]);
  }

  public function updateQuantity(int $userId, int $productId, int $qty): void {
    if ($qty <= 0) {
      $this->removeProduct($userId, $productId);
      return;
    }

    $stmt = $this->pdo->prepare("
      UPDATE cart
      SET quantity = ?
      WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$qty, $userId, $productId]);
  }

  public function removeProduct(int $userId, int $productId): void {
    $stmt = $this->pdo->prepare("
      DELETE FROM cart
      WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$userId, $productId]);
  }

  public function clear(int $userId): void {
    $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
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
      $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
      if ($qty > 0) {
        $this->addProduct($userId, (int)$productId, $qty);
      }
    }
  }
}