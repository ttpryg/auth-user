<?php

namespace Ttpryg\AuthUser\Repositories;

use DateTimeImmutable;
use PDO;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly PDO $pdo, private readonly string $table = 'users') {}

    public function findById(int|string $id, bool $includeTrashed = false): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        if (! $includeTrashed) {
            $sql .= ' AND deleted_at IS NULL';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByEmail(string $email, bool $includeTrashed = false): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        if (! $includeTrashed) {
            $sql .= ' AND deleted_at IS NULL';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByUsername(string $username, bool $includeTrashed = false): ?User
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username";
        if (! $includeTrashed) {
            $sql .= ' AND deleted_at IS NULL';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $username]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToEntity($data) : null;
    }

    public function save(User $user): User
    {
        $sql = "INSERT INTO {$this->table} (username, email, password_hash, is_active, metadata, created_at, updated_at) 
                VALUES (:username, :email, :password_hash, :is_active, :metadata, :created_at, :updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'is_active' => $user->isActive() ? 1 : 0,
            'metadata' => json_encode($user->getMetadata()),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);

        $id = $this->pdo->lastInsertId();
        $user->setId($id);

        return $user;
    }

    public function update(User $user): bool
    {
        $sql = "UPDATE {$this->table} 
                SET username = :username, 
                    email = :email, 
                    password_hash = :password_hash, 
                    is_active = :is_active, 
                    metadata = :metadata, 
                    updated_at = :updated_at 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'is_active' => $user->isActive() ? 1 : 0,
            'metadata' => json_encode($user->getMetadata()),
            'updated_at' => (new DateTimeImmutable)->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int|string $id, bool $softDelete = true): bool
    {
        if ($softDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                'id' => $id,
                'deleted_at' => (new DateTimeImmutable)->format('Y-m-d H:i:s'),
            ]);
        }

        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    public function restore(int|string $id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    private function mapToEntity(array $data): User
    {
        $metadata = [];
        if (! empty($data['metadata'])) {
            $decoded = json_decode($data['metadata'], associative: true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        return new User(
            email: $data['email'],
            passwordHash: $data['password_hash'],
            username: $data['username'] ?? null,
            isActive: (bool) $data['is_active'],
            metadata: $metadata,
            id: $data['id'],
            createdAt: ! empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null,
            updatedAt: ! empty($data['updated_at']) ? new DateTimeImmutable($data['updated_at']) : null,
            deletedAt: ! empty($data['deleted_at']) ? new DateTimeImmutable($data['deleted_at']) : null
        );
    }
}
