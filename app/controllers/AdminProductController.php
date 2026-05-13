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

        $csvPath = $_FILES['csv']['tmp_name'];
        $zipPath = $_FILES['images_zip']['tmp_name'] ?? null;
        $hasZip  = !empty($_FILES['images_zip']['name']) && is_uploaded_file($zipPath);

        $categories = [];
        $stmt = $this->pdo->query('SELECT id, name FROM categories');
        foreach ($stmt->fetchAll() as $row) {
            $categories[strtolower($row['name'])] = (int)$row['id'];
        }

        if (empty($categories)) {
            Flash::error('Nessuna categoria trovata. Aggiungi le categorie prima di importare.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $tempImages = [];
        $extractDir = null;
        if ($hasZip) {
            $extractDir = sys_get_temp_dir() . '/import_' . uniqid();
            mkdir($extractDir, 0755, true);
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                Flash::error('Impossibile aprire l’archivio ZIP.', BASE_URL . '/index.php?r=adminProduct/index');
            }
            $zip->extractTo($extractDir);
            $zip->close();
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($extractDir));
            foreach ($files as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $name = basename($file->getFilename());
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $tempImages[$name] = $file->getPathname();
                }
            }
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            Flash::error('Impossibile leggere il CSV.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            Flash::error('CSV vuoto o non valido.', BASE_URL . '/index.php?r=adminProduct/index');
        }

        $headers = array_map('trim', $headers);
        $required = ['category', 'name', 'description', 'price', 'stock', 'image_filename'];
        $missing = array_diff($required, $headers);
        if (!empty($missing)) {
            fclose($handle);
            Flash::error('Intestazioni mancanti nel CSV: ' . implode(', ', $missing), BASE_URL . '/index.php?r=adminProduct/index');
        }

        $mapping = array_flip($headers);
        $insertStmt = $this->pdo->prepare("INSERT INTO products (category_id, name, description, price, stock, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $imported = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $failed++;
                continue;
            }

            $row = array_map('trim', $row);
            $categoryName = strtolower($row[$mapping['category']] ?? '');
            $name = $row[$mapping['name']] ?? '';
            $description = $row[$mapping['description']] ?? '';
            $price = (float)str_replace(',', '.', $row[$mapping['price']] ?? '0');
            $stock = (int)($row[$mapping['stock']] ?? '0');
            $imageName = $row[$mapping['image_filename']] ?? '';

            if (!ValidationHelper::notEmpty($name) || !ValidationHelper::positiveFloat($price) || !isset($categories[$categoryName])) {
                $failed++;
                continue;
            }

            $imagePath = null;
            if ($imageName !== '' && isset($tempImages[$imageName])) {
                $imagePath = $this->saveImportedImage($tempImages[$imageName], $imageName);
                if ($imagePath === null) {
                    $failed++;
                    continue;
                }
            }

            $insertStmt->execute([$categories[$categoryName], $name, $description, $price, $stock, $imagePath]);
            $imported++;
        }

        fclose($handle);
        if ($extractDir !== null) {
            $this->deleteDirectory($extractDir);
        }

        Flash::success("Import completato. Prodotti importati: {$imported}. Falliti: {$failed}.", BASE_URL . '/index.php?r=adminProduct/index');
    }

    private function saveImportedImage(string $sourcePath, string $originalName): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = mime_content_type($sourcePath); // ← usa il path reale, non il nome
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        $ext = match($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        };
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest = __DIR__ . '/../../public/assets/images/' . $filename;
        if (!copy($sourcePath, $dest)) {
            return null;
        }

        return 'images/' . $filename;
    }

    private function deleteDirectory(string $dir): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($dir);
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
