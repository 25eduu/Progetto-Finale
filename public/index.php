<?php
declare(strict_types=1);

define('APP_ENV', getenv('APP_ENV') ?: 'development');

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', 1);
    ini_set('log_errors',     '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', 1);
    ini_set('log_errors',     '1');
    error_reporting(E_ALL);
}

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/security/ErrorHandler.php';
require_once __DIR__ . '/../app/helpers/ui/Flash.php';
require_once __DIR__ . '/../app/helpers/security/CsrfHelper.php';
require_once __DIR__ . '/../app/helpers/validation/ValidationHelper.php';
require_once __DIR__ . '/../app/helpers/security/RateLimitHelper.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/repositories/Cart.php';
require_once __DIR__ . '/../app/models/entities/User.php';
require_once __DIR__ . '/../app/services/email/MailService.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/auth/AuthController.php';

// Registra global error handler
ErrorHandler::register();

// ── Auto-login da cookie "ricordami" ──────────────────────────────────────────
(new AuthController($pdo))->tryAutoLogin();

// ── Router ────────────────────────────────────────────────────────────────────
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

// Mappa delle sottocartelle per controller
$controllerFolders = [
    'admin' => ['AdminDashboard', 'AdminProduct', 'AdminOrder', 'AdminUser'],
    'user' => ['Account', 'Cart', 'Checkout', 'Wallet'],
    'auth' => ['Auth'],
    'api' => ['StripeWebhook'],
    'public' => ['Home', 'Products']
];

$controllerFile = null;
$subfolder = '';

// Cerca il controller nelle sottocartelle
foreach ($controllerFolders as $folder => $controllers) {
    if (in_array($controllerClass, array_map(fn($c) => $c . 'Controller', $controllers))) {
        $controllerFile = __DIR__ . '/../app/controllers/' . $folder . '/' . $controllerClass . '.php';
        $subfolder = $folder;
        break;
    }
}

// Fallback alla cartella principale (per compatibilità)
if (!$controllerFile) {
    $controllerFile = __DIR__ . '/../app/controllers/' . $controllerClass . '.php';
}

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
