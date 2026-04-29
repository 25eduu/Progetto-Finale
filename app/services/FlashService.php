<?php
declare(strict_types=1);

class FlashService {
    /**
     * Imposta un messaggio flash nella sessione
     */
    public static function set(string $message, string $type = 'danger'): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = [
            'message' => $message,
            'type'    => $type // danger, success, warning, info (classi Bootstrap)
        ];
    }

    /**
     * Ritorna il messaggio e lo cancella dalla sessione
     */
    public static function get(): ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}