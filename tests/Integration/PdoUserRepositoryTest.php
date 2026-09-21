<?php

namespace Ttpryg\AuthUser\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Repositories\PdoUserRepository;

class PdoUserRepositoryTest extends TestCase
{
    private PDO $pdo;

    private PdoUserRepository $pdoUserRepository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create in-memory SQLite schema
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                metadata TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL
            )
        ');

        $this->pdoUserRepository = new PdoUserRepository($this->pdo);
    }

    public function test_save_and_find_user(): void
    {
        $user = new User(
            email: 'john@example.com',
            passwordHash: 'secret_hash',
            username: 'john',
            metadata: ['theme' => 'dark']
        );

        $savedUser = $this->pdoUserRepository->save($user);

        $this->assertNotNull($savedUser->getId());

        $foundById = $this->pdoUserRepository->findById($savedUser->getId());
        $this->assertNotNull($foundById);
        $this->assertEquals('john@example.com', $foundById->getEmail());
        $this->assertEquals('john', $foundById->getUsername());
        $this->assertEquals(['theme' => 'dark'], $foundById->getMetadata());

        $foundByEmail = $this->pdoUserRepository->findByEmail('john@example.com');
        $this->assertNotNull($foundByEmail);
        $this->assertEquals($savedUser->getId(), $foundByEmail->getId());

        $foundByUsername = $this->pdoUserRepository->findByUsername('john');
        $this->assertNotNull($foundByUsername);
        $this->assertEquals($savedUser->getId(), $foundByUsername->getId());
    }

    public function test_soft_delete_and_restore(): void
    {
        $user = new User('jane@example.com', 'hash', 'jane');
        $savedUser = $this->pdoUserRepository->save($user);
        $id = $savedUser->getId();

        // Soft delete
        $this->pdoUserRepository->delete($id, softDelete: true);

        $this->assertNull($this->pdoUserRepository->findById($id, includeTrashed: false));
        $this->assertNotNull($this->pdoUserRepository->findById($id, includeTrashed: true));

        // Restore
        $this->pdoUserRepository->restore($id);
        $this->assertNotNull($this->pdoUserRepository->findById($id, includeTrashed: false));
    }
}
