<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Questo script va eseguito da riga di comando.\n";
    exit(1);
}

$csvFile = $argv[1] ?? null;
$imageSourceDir = $argv[2] ?? null;

if (!$csvFile || !$imageSourceDir) {
    echo "Uso: php scripts/import_products.php <file.csv> <cartella_immagini>\n";
    echo "Esempio: php scripts/import_products.php products.csv import_images\n";
    exit(1);
}

$csvPath = __DIR__ . DIRECTORY_SEPARATOR . $csvFile;
$imageDir = __DIR__ . DIRECTORY_SEPARATOR . $imageSourceDir;
$targetDir = __DIR__ . '/../public/assets/images';

if (!is_readable($csvPath) || !is_dir($imageDir)) {
    echo "Errore: file CSV non leggibile o cartella immagini non valida.\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

/** @var PDO $pdo */

$categories = [];
$stmt = $pdo->query('SELECT id, name FROM categories');
foreach ($stmt->fetchAll() as $row) {
    $categories[strtolower($row['name'])] = (int)$row['id'];
}

if (empty($categories)) {
    echo "Nessuna categoria trovata. Aggiungi prima le categorie.\n";
    exit(1);
}

$handle = fopen($csvPath, 'r');
$headers = fgetcsv($handle);
if ($headers === false) {
    echo "CSV vuoto o non valido.\n";
    exit(1);
}

$headers = array_map('trim', $headers);
$expected = ['category', 'name', 'description', 'price', 'stock', 'image_filename'];
$missing = array_diff($expected, $headers);
$specHeaders = array_filter($headers, fn($column) => str_starts_with($column, 'spec_'));
$unknownHeaders = array_filter($headers, fn($column) => !in_array($column, $expected, true) && !str_starts_with($column, 'spec_'));

if (!empty($missing)) {
    echo "Intestazioni mancanti nel CSV: " . implode(', ', $missing) . "\n";
    exit(1);
}

if (!empty($unknownHeaders)) {
    echo "Intestazioni non riconosciute nel CSV: " . implode(', ', $unknownHeaders) . "\n";
    exit(1);
}

$insertStmt = $pdo->prepare(
    'INSERT INTO products (category_id, name, description, price, stock, image_path, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
);
$insertSpecStmt = $pdo->prepare(
    'INSERT INTO product_specs (product_id, spec_key, spec_value) VALUES (?, ?, ?)'
);

$imported = 0;
$failed   = 0;

while (($row = fgetcsv($handle)) !== false) {
    $data = array_combine($headers, $row);
    if ($data === false) {
        $failed++;
        continue;
    }

    $categoryName = strtolower(trim($data['category']));
    $name = trim($data['name']);
    $description = trim($data['description']);
    $price = (float) str_replace(',', '.', trim($data['price']));
    $stock = (int) trim($data['stock']);
    $imageName = trim($data['image_filename']);

    if (empty($name) || $price <= 0 || $stock < 0 || !isset($categories[$categoryName])) {
        $failed++;
        continue;
    }

    $imagePath = null;
    if ($imageName !== '') {
        $sourceFile = $imageDir . DIRECTORY_SEPARATOR . $imageName;
        if (is_file($sourceFile) && is_readable($sourceFile)) {
            $ext = pathinfo($sourceFile, PATHINFO_EXTENSION);
            $targetName = uniqid('prod_', true) . '.' . strtolower($ext);
            $targetFile = $targetDir . DIRECTORY_SEPARATOR . $targetName;
            if (!copy($sourceFile, $targetFile)) {
                $failed++;
                continue;
            }
            $imagePath = 'images/' . $targetName;
        } else {
            echo "Immagine non trovata: {$imageName} (salto riga)\n";
            $failed++;
            continue;
        }
    }

    $insertStmt->execute([$categories[$categoryName], $name, $description, $price, $stock, $imagePath]);
    $productId = (int)$pdo->lastInsertId();

    foreach ($specHeaders as $header) {
        $value = trim($data[$header] ?? '');
        if ($value === '') {
            continue;
        }
        $insertSpecStmt->execute([$productId, substr($header, 5), $value]);
    }

    $imported++;
}

fclose($handle);

echo "Import completato. Prodotti importati: {$imported}. Falliti: {$failed}.\n";
