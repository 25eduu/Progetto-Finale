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
        $statusCode = 500;
        $message    = 'Si è verificato un errore. Riprova più tardi.';

        if (APP_ENV === 'development') {
            $message = $exception->getMessage();
            $trace   = $exception->getTraceAsString();
            error_log("Exception: $message\n$trace");
        } else {
            error_log('Exception: ' . $exception->getMessage() . '\nTrace: ' . $exception->getTraceAsString());
        }

        http_response_code($statusCode);
        
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        } else {
            require __DIR__ . '/../../views/errors/500.php';
        }
        exit;
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        error_log("Error [$errno] $errstr in $errfile:$errline");

        if (APP_ENV === 'production') {
            return true;
        }

        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
