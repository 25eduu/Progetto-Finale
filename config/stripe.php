<?php
// ── Configurazione Stripe ─────────────────────────────────────────────────────
$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);

define('STRIPE_SECRET_KEY',     $env['STRIPE_SECRET_KEY']     ?? '');
define('STRIPE_WEBHOOK_SECRET', $env['STRIPE_WEBHOOK_SECRET'] ?? '');
