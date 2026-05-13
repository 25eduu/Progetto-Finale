<?php
declare(strict_types=1);
    class ErrorHandler
    {
        public static function register(): void
        {
            set_exception_handler([self::class, 'handleException']);
            set_error_handler([self::class, 'handleError']);

            register_shutdown_function([self::class, 'handleFatal']);
        }

        public static function handleFatal(): void
        {
            $error = error_get_last();

            if (!$error) return;

            http_response_code(500);

            $isDev = defined('APP_ENV') && APP_ENV === 'development';

            if ($isDev) {
                echo "<h1>FATAL ERROR</h1>";
                echo "<pre>";
                print_r($error);
                echo "</pre>";
                return;
            }

            $view = require __DIR__ . '/../../views/errors/500.php';

            if (file_exists($view)) {
                require $view;
            } else {
                echo "500 Internal Server Error";
            }

            exit;
        }

        public static function handleException(Throwable $exception): void
        {
            $isDev = defined('APP_ENV') && APP_ENV === 'development';

            http_response_code(500);

            // LOG sempre (anche in produzione)
            error_log(
                "[EXCEPTION] " .
                $exception->getMessage() .
                " in " . $exception->getFile() .
                ":" . $exception->getLine()
            );

            // Se richiesta API JSON
            $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($wantsJson) {
                header('Content-Type: application/json');

                echo json_encode([
                    'error' => $isDev
                        ? $exception->getMessage()
                        : 'Internal Server Error'
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }

            // DEV → mostra tutto
            if ($isDev) {
                echo "<h1>Exception</h1>";
                echo "<pre>";
                echo $exception->getMessage() . PHP_EOL . PHP_EOL;
                echo $exception->getFile() . ":" . $exception->getLine() . PHP_EOL . PHP_EOL;
                echo $exception->getTraceAsString();
                echo "</pre>";
                exit;
            }

            // PROD → pagina pulita
            $view = require __DIR__ . '/../../views/errors/500.php';

            if (file_exists($view)) {
                require $view;
            } else {
                echo "Internal Server Error";
            }

            exit;
        }

        public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
        {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            error_log("Error [$errno] $errstr in $errfile:$errline");

            if (defined('APP_ENV') && APP_ENV === 'production') {
                return true;
            }

            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
    }
