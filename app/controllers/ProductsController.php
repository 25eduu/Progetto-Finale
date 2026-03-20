<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';

class ProductsController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function index(): void {
    $model = new Product($this->pdo);
    $products = $model->getAll();

    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/products/index.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }

  public function show(): void {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      http_response_code(400);
      echo "ID non valido";
      return;
    }
  
    $model = new Product($this->pdo);
    $product = $model->findById($id);
  
    if (!$product) {
      http_response_code(404);
      echo "Prodotto non trovato";
      return;
    }
  
    $specs = $model->getSpecs($id);
    $related = $model->getRelated($product['category_id'], $product['id']);
    $accessories = $model->getAccessories($product['id']);
  
    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/products/show.php';
    require __DIR__ . '/../views/layouts/footer.php';
  }
}