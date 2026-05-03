<?php
    define('BASE_URL', '/Progetto-Finale/public');

    $env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
    define('GOOGLE_CLIENT_ID', $env['GOOGLE_CLIENT_ID'] ?? '');