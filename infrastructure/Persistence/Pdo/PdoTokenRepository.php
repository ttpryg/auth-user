<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Infrastructure\Persistence\Pdo;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;

class PdoTokenRepository implements TokenRepositoryInterface
{
    public function __construct(private readonly PDO $pdo, private readonly string $table = 'user_tokens') {}

    public function createToken(int|string $userId, string $type, int $ttlSeconds = 3600): string
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("+{$ttlSeconds} seconds");

        $sql = "INSERT INTO {$this->table} (user_id, type, token, expires_at) 
                VALUES (:user_id, :type, :token, :expires_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'token' => $hashedToken,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Verify a token and return its row data, or null if invalid or expired.
     *
     * @return array{user_id: int|string, token: string, type: string, expires_at: string}|null
     */
    public function verifyToken(string $token, string $type): ?object
    {
        $hashedToken = hash('sha256', $token);
        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE (token = :hashed_token OR token = :plain_token) AND type = :type AND expires_at > :now";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'hashed_token' => $hashedToken,
            'plain_token' => $token,
            'type' => $type,
            'now' => $now,
        ]);

        $data = $stmt->fetch(PDO::FETCH_OBJ);

        return $data ?: null;
    }

    public function revokeToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        $sql = "DELETE FROM {$this->table} WHERE token = :hashed_token OR token = :plain_token";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'hashed_token' => $hashedToken,
            'plain_token' => $token,
        ]);
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
