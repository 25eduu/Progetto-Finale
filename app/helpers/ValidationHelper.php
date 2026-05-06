<?php
declare(strict_types=1);

class ValidationHelper
{
    public static function notEmpty(string $value): bool
    {
        return trim($value) !== '';
    }

    public static function email(string $value): bool
    {
        return (bool)filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    }

    public static function password(string $value, int $minLength = 8): bool
    {
        return strlen($value) >= $minLength;
    }

    public static function positiveFloat(float $value): bool
    {
        return $value > 0;
    }

    public static function positiveInt(int $value): bool
    {
        return $value > 0;
    }

    public static function between(float $value, float $min, float $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    public static function matches(string $a, string $b): bool
    {
        return $a === $b;
    }

    public static function maxLength(string $value, int $max): bool
    {
        return strlen($value) <= $max;
    }

    public static function minLength(string $value, int $min): bool
    {
        return strlen($value) >= $min;
    }
}
