<?php

namespace Ttpryg\AuthUser\Repositories;

use DateTimeImmutable;
use PDO;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;

class PdoTokenRepository implements TokenRepositoryInterface
{
    public function __construct(private readonly PDO $pdo, private readonly string $table = 'user_tokens') {}

    public function createToken(int|string $userId, string $type, int $ttlSeconds = 3600): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable)->modify("+{$ttlSeconds} seconds");

        $sql = "INSERT INTO {$this->table} (user_id, type, token, expires_at) 
                VALUES (:user_id, :type, :token, :expires_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function verifyToken(string $token, string $type): ?object
    {
        $now = (new \DateTimeImmutable)->format('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE token = :token AND type = :type AND expires_at > :now";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'token' => $token,
            'type' => $type,
            'now' => $now,
        ]);

        $data = $stmt->fetch(PDO::FETCH_OBJ);

        return $data ?: null;
    }

    public function revokeToken(string $token): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['token' => $token]);
    }

    public function revokeAllUserTokens(int|string $userId, ?string $type = null): bool
    {
        if ($type !== null) {
            $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND type = :type";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute(['user_id' => $userId, 'type' => $type]);
        }

        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['user_id' => $userId]);
    }
}
