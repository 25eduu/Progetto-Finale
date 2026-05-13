<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    echo "Questo script va eseguito da riga di comando.\n";
    exit(1);
}

$csvFile = $argv[1] ?? null;

if (!$csvFile) {
    echo "Uso: php scripts/import_specs.php <file.csv>\n";
    echo "Esempio: php scripts/import_specs.php specs.csv\n";
    exit(1);
}

$csvPath = __DIR__ . DIRECTORY_SEPARATOR . $csvFile;

if (!is_readable($csvPath)) {
    echo "Errore: file CSV non leggibile.\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

/** @var PDO $pdo */

// Recupera tutti i prodotti
$productMap = [];

$stmt = $pdo->query('SELECT id, name FROM products');

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
    $productMap[strtolower(trim($product['name']))] = (int)$product['id'];
}

if (empty($productMap)) {
    echo "Nessun prodotto trovato nel database.\n";
    exit(1);
}

// Apertura CSV
$handle = fopen($csvPath, 'r');

$headers = fgetcsv($handle);

if ($headers === false) {
    echo "CSV vuoto o non valido.\n";
    exit(1);
}

$headers = array_map('trim', $headers);

$expected = ['product_name', 'spec_key', 'spec_value'];

$missing = array_diff($expected, $headers);

if (!empty($missing)) {
    echo "Intestazioni mancanti nel CSV: " . implode(', ', $missing) . "\n";
    exit(1);
}

// Prepared statement
$insertStmt = $pdo->prepare(
    'INSERT INTO product_specs (product_id, spec_key, spec_value)
     VALUES (?, ?, ?)'
);

$imported = 0;
$failed = 0;

while (($row = fgetcsv($handle)) !== false) {

    $data = array_combine($headers, $row);

    if ($data === false) {
        $failed++;
        continue;
    }

    $productName = strtolower(trim($data['product_name']));
    $specKey = trim($data['spec_key']);
    $specValue = trim($data['spec_value']);

    // Validazioni
    if (
        empty($productName) ||
        empty($specKey) ||
        empty($specValue)
    ) {
        $failed++;
        continue;
    }

    // Controlla esistenza prodotto
    if (!isset($productMap[$productName])) {
        echo "Prodotto non trovato: {$data['product_name']} (salto riga)\n";
        $failed++;
        continue;
    }

    $productId = $productMap[$productName];

    // Inserimento
    try {

        $insertStmt->execute([
            $productId,
            $specKey,
            $specValue
        ]);

        $imported++;

    } catch (PDOException $e) {

        echo "Errore inserimento specifica per {$data['product_name']}: {$e->getMessage()}\n";

        $failed++;
    }
}

fclose($handle);

echo "Import completato.\n";
echo "Specifiche importate: {$imported}\n";
echo "Fallite: {$failed}\n";