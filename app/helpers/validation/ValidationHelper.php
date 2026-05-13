<?php
declare(strict_types=1);

class ValidationHelper
{
    public static function notEmpty(string $value): bool
    {
        return trim($value) !== '';
    }

    /**
     * Valida email con FILTER_VALIDATE_EMAIL
     */
    public static function email(string $value): bool
    {
        return (bool)filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida password robusta: minimo 8 caratteri, almeno una maiuscola, 
     * almeno una minuscola, almeno un numero, almeno un carattere speciale
     */
    public static function password(string $value, int $minLength = 8): bool
    {
        if (strlen($value) < $minLength) {
            return false;
        }

        // Controlla complessità: maiuscola, minuscola, numero, carattere speciale
        $hasUpper   = preg_match('/[A-Z]/', $value);
        $hasLower   = preg_match('/[a-z]/', $value);
        $hasDigit   = preg_match('/\d/', $value);
        $hasSpecial = preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/', $value);

        return $hasUpper && $hasLower && $hasDigit && $hasSpecial;
    }

    /**
     * Valida un numero positivo (float), gestisce parsing di decimali
     */
    public static function positiveFloat(float|string $value): bool
    {
        $value = (string)$value;
        $value = str_replace(',', '.', $value);
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return is_float($float) && $float > 0;
    }

    /**
     * Valida un numero positivo intero
     */
    public static function positiveInt(int|string $value): bool
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return is_int($int) && $int > 0;
    }

    /**
     * Valida numero in range inclusivo
     */
    public static function between(float|int $value, float|int $min, float|int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Confronta due stringhe in modo sicuro (timing attack safe)
     */
    public static function matches(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    public static function maxLength(string $value, int $max): bool
    {
        return strlen($value) <= $max;
    }

    public static function minLength(string $value, int $min): bool
    {
        return strlen($value) >= $min;
    }

    /**
     * Valida estensione file da whitelist
     */
    public static function fileExtension(string $filename, array $allowed = []): bool
    {
        if (empty($allowed)) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowed, true);
    }

    /**
     * Valida MIME type da whitelist
     */
    public static function mimeType(string $mimeType, array $allowed = []): bool
    {
        if (empty($allowed)) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        }

        return in_array($mimeType, $allowed, true);
    }
}
