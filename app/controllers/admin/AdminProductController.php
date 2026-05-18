<?php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../helpers/ui/Flash.php';
require_once __DIR__ . '/../../helpers/security/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/validation/ValidationHelper.php';
require_once __DIR__ . '/../../models/entities/Product.php';
require_once __DIR__ . '/../../services/ProductImportService.php';

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
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/admin/products.php';
        require __DIR__ . '/../../views/layouts/footer.php';
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
    
    public function update(): void
    {
        CsrfHelper::validate();

        $id    = (int)($_POST['product_id'] ?? 0);
        $price = (float)str_replace(',', '.', $_POST['price'] ?? '0');
        $stock = (int)($_POST['stock'] ?? -1);

        if (!ValidationHelper::positiveInt($id) || !ValidationHelper::positiveFloat($price) || $stock < 0) {
            Flash::error('Dati non validi.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $this->pdo->prepare("UPDATE products SET price = ?, stock = ? WHERE id = ?")
            ->execute([$price, $stock, $id]);

        Flash::success('Prodotto aggiornato.', BASE_URL . '/index.php?r=adminProduct/index');
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

    public function importCsv(): void
    {
        CsrfHelper::validate();

        if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            Flash::error('Seleziona un file CSV da importare.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $csvContent = file_get_contents($_FILES['csv']['tmp_name']);
        if ($csvContent === false) {
            Flash::error('Impossibile leggere il file CSV.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $zipPath = $_FILES['images_zip']['tmp_name'] ?? null;
        $importService = new ProductImportService($this->pdo);
        $result = $importService->importFromCsv($csvContent, $zipPath);

        if (!empty($result['errors'])) {
            $errorMessage = 'Errori durante l\'importazione:<br>' . implode('<br>', $result['errors']);
            Flash::error($errorMessage, BASE_URL . '/index.php?r=adminProduct/index');
        } else {
            Flash::success("Import completato. Prodotti importati: {$result['success']}.", BASE_URL . '/index.php?r=adminProduct/index');
        }
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

    /**
     * Upload immagine con validazione robusta MIME type
     * @return string|null path relativo dell'immagine o null se non valida
     */
    private function uploadImage(array $file): ?string
    {
        // Whitelist di MIME type permessi
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        
        // Max dimensione: 5MB
        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            error_log('File upload troppo grande: ' . $file['size']);
            return null;
        }

        // Valida MIME type con finfo (più sicuro di mime_content_type)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed, true)) {
            error_log('MIME type non permesso: ' . $mimeType);
            return null;
        }

        // Valida l'estensione del file originale
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!ValidationHelper::fileExtension($file['name'], ['jpg', 'jpeg', 'png', 'webp'])) {
            error_log('Estensione file non permessa: ' . $originalExt);
            return null;
        }

        // Mappa MIME type a estensione
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };

        if ($ext === null) {
            return null;
        }

        // Genera filename sicuro e unico
        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = __DIR__ . '/../../../public/assets/images/' . $filename;

        // Verifica che la directory esista
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        // Sposta il file uploaded
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            error_log('Fallito lo spostamento del file: ' . $filename);
            return null;
        }

        return 'images/' . $filename;
    }
}
