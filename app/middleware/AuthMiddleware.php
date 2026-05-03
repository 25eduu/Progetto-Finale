<?php
declare(strict_types=1);

class AuthMiddleware
{
    public static function requireUser(): int
    {
        $userId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            Flash::error(
                'Devi effettuare il login per accedere a questa pagina.',
                BASE_URL . '/index.php?r=auth/loginForm'
            );
        }

        return $userId;
    }

    public static function requireAdmin(): int
    {
        $userId = self::requireUser();

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            $pdo = $GLOBALS['pdo'] ?? null;
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }

        return $userId;
    }

    public static function requireGuest(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
