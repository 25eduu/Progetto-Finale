<?php
declare(strict_types=1);

    class ErrorHandler
    {
        public static function register(): void
        {
            set_exception_handler([self::class, 'handleException']);
            set_error_handler([self::class, 'handleError']);
        }

        public static function handleException(Throwable $exception): void
        {
            http_response_code(500);

            echo '<pre>';
            echo 'ERRORE: ' . $exception->getMessage() . PHP_EOL . PHP_EOL;
            echo 'FILE: ' . $exception->getFile() . PHP_EOL;
            echo 'LINEA: ' . $exception->getLine() . PHP_EOL . PHP_EOL;
            echo $exception->getTraceAsString();
            echo '</pre>';

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
