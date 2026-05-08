<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Product.php';

class ProductsController {
  private PDO $pdo;

  public function __construct(PDO $pdo) {
    $this->pdo = $pdo;
  }

  public function index(): void {
    $productModel = new Product($this->pdo);
    $products = $productModel->getAll();

    $pdo = $this->pdo;
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

  /**
     * Pagina ricerca con postback ($_SERVER['PHP_SELF']).
     */
    public function search(): void
    {
        $pdo = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/products/search.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
    
  /**
     * Endpoint AJAX per ricerca live nella navbar.
     * Restituisce JSON con i primi 6 prodotti corrispondenti.
     */
    public function searchAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
 
        $query = trim($_GET['q'] ?? '');
 
        if (strlen($query) < 2) {
            echo json_encode(['results' => []]);
            exit;
        }
 
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.name, p.price, p.stock, p.image_path, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.name LIKE ? OR p.description LIKE ?
            ORDER BY p.name ASC
            LIMIT 6
        ");
        $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
        $products = $stmt->fetchAll();
 
        echo json_encode([
            'results' => array_map(fn($p) => [
                'id'            => (int)$p['id'],
                'name'          => $p['name'],
                'price'         => number_format((float)$p['price'], 2, ',', '.'),
                'category_name' => $p['category_name'],
                'image_path'    => $p['image_path'] ?? 'images/placeholder.png',
                'in_stock'      => (int)$p['stock'] > 0,
                'url'           => BASE_URL . '/index.php?r=products/show&id=' . (int)$p['id'],
            ], $products),
            'search_url' => BASE_URL . '/index.php?r=products/search',
        ]);
        exit;
    }
}