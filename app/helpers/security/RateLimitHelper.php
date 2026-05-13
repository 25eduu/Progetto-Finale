<?php
declare(strict_types=1);

class RateLimitHelper
{
    private const STORAGE_KEY_PREFIX = '_rate_limit_';
    private const DEFAULT_LIMIT       = 5;
    private const DEFAULT_WINDOW      = 300; // 5 minuti

    /**
     * Verifica se un'azione è stata superata il limite.
     * 
     * @param string $action Es: "login_attempt", "api_call"
     * @param int $limit Numero massimo di tentativi
     * @param int $windowSeconds Finestra temporale in secondi
     * @return bool true se è sotto il limite, false se superato
     */
    public static function isAllowed(string $action, int $limit = self::DEFAULT_LIMIT, int $windowSeconds = self::DEFAULT_WINDOW): bool
    {
        $identifier = self::getIdentifier();
        $key        = self::STORAGE_KEY_PREFIX . $action . '_' . $identifier;

        $attempts = (int)($_SESSION[$key] ?? 0);
        $timestamp = (int)($_SESSION[$key . '_time'] ?? 0);
        $now       = time();

        // Reset se la finestra è scaduta
        if ($now - $timestamp > $windowSeconds) {
            $_SESSION[$key]           = 0;
            $_SESSION[$key . '_time'] = $now;
            return true;
        }

        // Incrementa tentativo
        $_SESSION[$key]++;
        if (!isset($_SESSION[$key . '_time'])) {
            $_SESSION[$key . '_time'] = $now;
        }

        return $_SESSION[$key] <= $limit;
    }

    /**
     * Resetta il contatore per un'azione
     */
    public static function reset(string $action): void
    {
        $identifier = self::getIdentifier();
        $key        = self::STORAGE_KEY_PREFIX . $action . '_' . $identifier;
        
        unset($_SESSION[$key], $_SESSION[$key . '_time']);
    }

    /**
     * Ottiene il numero di tentativiremaining per un'azione
     */
    public static function getRemaining(string $action, int $limit = self::DEFAULT_LIMIT): int
    {
        $identifier = self::getIdentifier();
        $key        = self::STORAGE_KEY_PREFIX . $action . '_' . $identifier;
        $attempts   = (int)($_SESSION[$key] ?? 0);
        
        return max(0, $limit - $attempts);
    }

    /**
     * Ottiene un identificatore unico per l'utente/IP
     */
    private static function getIdentifier(): string
    {
        if (!empty($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return 'ip_' . md5($ip);
    }
}
