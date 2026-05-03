<?php
declare(strict_types=1);

class CsrfHelper
{
    private const TOKEN_KEY  = '_csrf_token';
    private const TIME_KEY   = '_csrf_token_time';
    private const TOKEN_TTL  = 3600;

    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
            $_SESSION[self::TIME_KEY]  = time();
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">',
            htmlspecialchars(self::generate(), ENT_QUOTES)
        );
    }

    public static function validate(): void
    {
        $token  = $_POST['_csrf_token'] ?? '';
        $stored = $_SESSION[self::TOKEN_KEY] ?? '';
        $time   = (int)($_SESSION[self::TIME_KEY] ?? 0);

        $valid = $stored !== ''
            && hash_equals($stored, $token)
            && (time() - $time) <= self::TOKEN_TTL;

        unset($_SESSION[self::TOKEN_KEY], $_SESSION[self::TIME_KEY]);

        if (!$valid) {
            http_response_code(419);
            Flash::error(
                'Sessione scaduta. Ricarica la pagina e riprova.',
                BASE_URL . '/index.php'
            );
        }
    }
}
