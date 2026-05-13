<?php
declare(strict_types=1);

class ProductImportService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function importFromCsv(string $csvContent, ?string $zipPath = null): array
    {
        $errors = [];
        $successCount = 0;

        // Load categories
        $categories = [];
        $stmt = $this->pdo->query('SELECT id, name FROM categories');
        foreach ($stmt->fetchAll() as $row) {
            $categories[strtolower($row['name'])] = (int)$row['id'];
        }

        // Parse CSV
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (!is_array($lines) || count($lines) < 2) {
            $errors[] = "Il file CSV deve contenere almeno l'intestazione e una riga di dati";
            return ['errors' => $errors, 'success' => $successCount];
        }

        $header = array_map('trim', str_getcsv($lines[0]));
        $requiredHeaders = ['category', 'name', 'description', 'price', 'stock', 'image_filename'];
        $specHeaders = array_filter($header, fn($column) => str_starts_with($column, 'spec_'));
        $unknownHeaders = array_filter($header, fn($column) => !in_array($column, $requiredHeaders, true) && !str_starts_with($column, 'spec_'));

        if (!empty(array_diff($requiredHeaders, $header))) {
            $errors[] = "Intestazioni CSV mancanti. Richieste: " . implode(', ', $requiredHeaders) . ".";
            return ['errors' => $errors, 'success' => $successCount];
        }

        if (!empty($unknownHeaders)) {
            $errors[] = "Intestazioni CSV non riconosciute: " . implode(', ', $unknownHeaders) . ". Usa solo i campi obbligatori e spec_*.";
            return ['errors' => $errors, 'success' => $successCount];
        }

        $imageMap = [];
        $tempDir = null;
        if ($zipPath !== null && file_exists($zipPath)) {
            $tempDir = sys_get_temp_dir() . '/import_' . uniqid();
            mkdir($tempDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($tempDir);
                $zip->close();

                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($files as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $name = basename($file->getFilename());
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        $imageMap[$name] = $file->getPathname();
                    }
                }
            } else {
                $errors[] = 'Impossibile aprire l\'archivio ZIP delle immagini.';
                return ['errors' => $errors, 'success' => $successCount];
            }
        }

        $this->pdo->beginTransaction();

        try {
            for ($i = 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) {
                    continue;
                }

                $data = str_getcsv($line);
                if (count($data) !== count($header)) {
                    $errors[] = "Riga " . ($i + 1) . ": numero di colonne non corretto";
                    continue;
                }

                $row = array_combine($header, array_map('trim', $data));
                $categoryName = $row['category'];
                $name = $row['name'];
                $description = $row['description'];
                $price = $row['price'];
                $stock = $row['stock'];
                $imageFilename = $row['image_filename'];

                // Validate data
                $validationErrors = $this->validateProductData($name, $description, $price, $stock, $categoryName, $categories);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, array_map(fn($err) => "Riga " . ($i + 1) . ": $err", $validationErrors));
                    continue;
                }

                $imagePath = $imageFilename;
                if ($imageFilename !== '' && isset($imageMap[$imageFilename])) {
                    $imagePath = $this->saveImageFromZip($imageMap[$imageFilename], $imageFilename);
                    if ($imagePath === null) {
                        $errors[] = "Riga " . ($i + 1) . ": impossibile salvare l'immagine $imageFilename";
                        continue;
                    }
                }

                $productId = $this->insertProduct($name, $description, (float)$price, (int)$stock, $categories[strtolower($categoryName)], $imagePath);
                $this->insertSpecs($productId, $row, $specHeaders);
                $successCount++;
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $errors[] = "Errore durante l'importazione: " . $e->getMessage();
        } finally {
            if ($tempDir !== null && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }

        return ['errors' => $errors, 'success' => $successCount];
    }

    private function validateProductData(string $name, string $description, string $price, string $stock, string $categoryName, array $categories): array
    {
        $errors = [];

        if (empty(trim($name))) {
            $errors[] = "nome obbligatorio";
        }

        if (empty(trim($description))) {
            $errors[] = "descrizione obbligatoria";
        }

        if (!is_numeric($price) || (float)$price <= 0) {
            $errors[] = "prezzo deve essere un numero positivo";
        }

        if (!is_numeric($stock) || (int)$stock < 0) {
            $errors[] = "stock deve essere un numero non negativo";
        }

        if (empty(trim($categoryName)) || !isset($categories[strtolower($categoryName)])) {
            $errors[] = "categoria non valida";
        }

        return $errors;
    }

    private function insertProduct(string $name, string $description, float $price, int $stock, int $categoryId, ?string $imageFilename): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (category_id, name, description, price, stock, image_path, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$categoryId, $name, $description, $price, $stock, $imageFilename]);
        return (int)$this->pdo->lastInsertId();
    }

    private function insertSpecs(int $productId, array $row, array $specHeaders): void
    {
        if (empty($specHeaders)) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO product_specs (product_id, spec_key, spec_value) VALUES (?, ?, ?)");
        foreach ($specHeaders as $header) {
            $value = trim($row[$header] ?? '');
            if ($value === '') {
                continue;
            }

            $stmt->execute([$productId, substr($header, 5), $value]);
        }
    }

    private function saveImageFromZip(string $sourcePath, string $originalName): ?string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest = __DIR__ . '/../../public/assets/images/' . $filename;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

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
}