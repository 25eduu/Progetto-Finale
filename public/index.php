<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

$route = $_GET['r'] ?? 'home/index';
$route = trim($route, '/');
[$controllerName, $action] = array_pad(explode('/', $route, 2), 2, 'index');

$controllerClass = ucfirst($controllerName) . 'Controller';
$controllerFile  = __DIR__ . '/../app/controllers/' . $controllerClass . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    echo "Controller non trovato: " . htmlspecialchars($controllerClass);
    exit;
}

require_once $controllerFile;

$controller = new $controllerClass($pdo);

if (!method_exists($controller, $action)) {
    http_response_code(404);
    echo "Azione non trovata: " . htmlspecialchars($action);
    exit;
}

$controller->$action();