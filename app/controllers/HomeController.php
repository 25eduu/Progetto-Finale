<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';

class HomeController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function index(): void {
    $productModel = new Product($this->pdo);
    $products = $productModel->getLatest(8); // 8 prodotti in vetrina

    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/home/index.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }
}