<?php
declare(strict_types=1);

class Product {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function getLatest(int $limit = 8): array {
    $stmt = $this->pdo->prepare(
      "SELECT id, name, price, stock, image_path
       FROM products
       ORDER BY id DESC
       LIMIT :lim"
    );
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function getAll(): array {
    $stmt = $this->pdo->prepare(
      "SELECT p.id, p.name, p.price, p.stock, p.image_path, c.name AS category_name
       FROM products p
       JOIN categories c ON c.id = p.category_id
       ORDER BY p.id DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function findById(int $id): ?array {
    $stmt = $this->pdo->prepare(
      "SELECT p.*, c.name AS category_name
       FROM products p
       JOIN categories c ON c.id = p.category_id
       WHERE p.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
  }

  public function getSpecs(int $productId): array {
    $stmt = $this->pdo->prepare(
      "SELECT spec_key, spec_value
       FROM product_specs
       WHERE product_id = ?
       ORDER BY id ASC"
    );
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
  }
  public function getRelated($categoryId, $excludeId)
  {
      $stmt = $this->pdo->prepare("
          SELECT * FROM products
          WHERE category_id = ? AND id != ?
          LIMIT 4
      ");
      $stmt->execute([$categoryId, $excludeId]);
      return $stmt->fetchAll();
  }
  public function getAccessories(int $productId): array
{
    $stmt = $this->pdo->prepare("
        SELECT p.id, p.name, p.price, p.image_path
        FROM related_products rp
        JOIN products p ON p.id = rp.related_product_id
        WHERE rp.product_id = ?
        ORDER BY rp.id ASC
    ");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}
}
