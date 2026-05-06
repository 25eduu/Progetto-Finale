<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Flash.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../models/Product.php';

class AdminProductController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        AuthMiddleware::requireAdmin();
    }

    public function index(): void
    {
        $products = (new Product($this->pdo))->getAll();
        $flash    = Flash::get();
        $pdo      = $this->pdo;
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/admin/products.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    public function updateStock(): void
    {
        CsrfHelper::validate();

        $id    = (int)($_POST['product_id'] ?? 0);
        $stock = (int)($_POST['stock']      ?? -1);

        if (!ValidationHelper::positiveInt($id) || $stock < 0) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $this->pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")
            ->execute([$stock, $id]);

        Flash::success('Stock aggiornato.', BASE_URL . '/index.php?r=adminProduct/index');
    }

    public function create(): void
    {
        CsrfHelper::validate();

        $name        = trim($_POST['name']         ?? '');
        $description = trim($_POST['description']  ?? '');
        $price       = (float)($_POST['price']     ?? 0);
        $stock       = (int)($_POST['stock']       ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if (!ValidationHelper::notEmpty($name) || !ValidationHelper::positiveFloat($price) || !ValidationHelper::positiveInt($categoryId)) {
            Flash::error('Compila tutti i campi obbligatori.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->uploadImage($_FILES['image']);
            if ($imagePath === null) {
                Flash::error('Formato immagine non valido. Usa JPG, PNG o WebP.', BASE_URL . '/index.php?r=adminProduct/index');
            }
        }

        $this->pdo->prepare("
            INSERT INTO products (category_id, name, description, price, stock, image_path, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ")->execute([$categoryId, $name, $description, $price, $stock, $imagePath]);

        Flash::success('Prodotto creato.', BASE_URL . '/index.php?r=adminProduct/index');
    }

    public function delete(): void
    {
        CsrfHelper::validate();

        $id = (int)($_POST['product_id'] ?? 0);
        if (!ValidationHelper::positiveInt($id)) {
            Flash::error('Prodotto non valido.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $this->pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

        Flash::success('Prodotto eliminato.', BASE_URL . '/index.php?r=adminProduct/index');
    }

    private function uploadImage(array $file): ?string
    {
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            return null;
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = __DIR__ . '/../../public/assets/images/' . $filename;
        move_uploaded_file($file['tmp_name'], $dest);

        return 'images/' . $filename;
    }
}
