<?php
declare(strict_types=1);

class Flash
{
    public static function error(string $message, string $redirectUrl): never
    {
        $_SESSION['flash_error'] = $message;
        header('Location: ' . $redirectUrl);
        exit;
    }

    public static function success(string $message, string $redirectUrl): never
    {
        $_SESSION['flash_success'] = $message;
        header('Location: ' . $redirectUrl);
        exit;
    }

    public static function get(): array
    {
        $data = [
            'error'   => $_SESSION['flash_error']   ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ];
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        return $data;
    }
}
