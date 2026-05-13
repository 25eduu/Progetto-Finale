<?php
declare(strict_types=1);

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        return $stmt->fetch() ?: null;
    }

    public function findByGoogleIdOrEmail(string $googleId, string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$googleId, $email]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $this->pdo->prepare("
            INSERT INTO users
                (email, password, full_name, wallet_balance, role, google_id, auth_provider, email_verified_at, created_at)
            VALUES (?, ?, ?, 0.00, 'user', ?, ?, ?, NOW())
        ")->execute([
            $data['email'],
            $data['password']          ?? null,
            $data['full_name'],
            $data['google_id']         ?? null,
            $data['auth_provider']     ?? 'local',
            $data['email_verified_at'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateName(int $id, string $fullName): void
    {
        $this->pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")
            ->execute([$fullName, $id]);
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$hashedPassword, $id]);
    }

    public function updateGoogleId(int $id, string $googleId): void
    {
        $this->pdo->prepare("UPDATE users SET google_id = ?, auth_provider = 'google' WHERE id = ?")
            ->execute([$googleId, $id]);
    }

    public function getWalletBalance(int $id): float
    {
        $stmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return (float)($stmt->fetchColumn() ?: 0);
    }

    public function addWalletBalance(int $id, float $amount): void
    {
        $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")
            ->execute([$amount, $id]);
    }

    /**
     * Sottrae dal wallet solo se il saldo è sufficiente.
     * Ritorna true se l'operazione è riuscita.
     */
    public function subtractWalletBalance(int $id, float $amount): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET wallet_balance = wallet_balance - ?
            WHERE id = ? AND wallet_balance >= ?
        ");
        $stmt->execute([$amount, $id, $amount]);
        return $stmt->rowCount() > 0;
    }

    public function getAll(): array
    {
        return $this->pdo->query("
            SELECT id, full_name, email, role, wallet_balance, auth_provider, created_at
            FROM users ORDER BY id DESC
        ")->fetchAll();
    }
}
