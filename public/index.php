<?php
declare(strict_types=1);

// ── Ambiente ────────────────────────────────────────────────────────────────
// Cambia APP_ENV in 'production' sul server host
define('APP_ENV', getenv('APP_ENV') ?: 'development');

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors',     '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('log_errors',     '1');
    error_reporting(E_ALL);
}

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/Flash.php';
require_once __DIR__ . '/../app/helpers/CsrfHelper.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/Cart.php';
require_once __DIR__ . '/../app/services/MailService.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

// ── Auto-login da cookie "ricordami" ─────────────────────────────────────────
(new AuthController($pdo))->tryAutoLogin();

$route          = trim($_GET['r'] ?? 'home/index', '/');
$parts          = explode('/', $route, 2);
$controllerName = $parts[0];
$action         = $parts[1] ?? 'index';

if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $controllerName)
    || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $action)) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errors/404.php';
    exit;
}

$controllerClass = ucfirst($controllerName) . 'Controller';
$controllerFile  = __DIR__ . '/../app/controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errors/404.php';
    exit;
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errors/404.php';
    exit;
}

$controller = new $controllerClass($pdo);

if (!method_exists($controller, $action)) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errors/404.php';
    exit;
}

$controller->$action();
