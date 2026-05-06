<?php
// ── Configurazione generale ───────────────────────────────────────────────────

define('BASE_URL', '/Progetto-Finale/public');

$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
define('GOOGLE_CLIENT_ID', $env['GOOGLE_CLIENT_ID'] ?? '');

// Carica configurazioni servizi (lette una sola volta)
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/stripe.php';
