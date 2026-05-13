<?php
// ── Configurazione Mail ───────────────────────────────────────────────────────
$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);

define('MAIL_FROM_EMAIL',  $env['MAIL_FROM_EMAIL']  ?? $env['MAIL_FROM']     ?? '');
define('MAIL_FROM_NAME',   $env['MAIL_FROM_NAME']   ?? 'TechShop');
define('MAIL_HOST',        $env['MAIL_HOST']         ?? 'smtp-relay.brevo.com');
define('MAIL_PORT',    (int)($env['MAIL_PORT']       ?? 587));
define('MAIL_ENCRYPTION',  $env['MAIL_ENCRYPTION']  ?? 'tls');
define('MAIL_USERNAME',    $env['SMTP_USER']         ?? $env['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD',    $env['SMTP_PASS']         ?? $env['MAIL_PASSWORD'] ?? '');
