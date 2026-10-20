<?php

namespace Ttpryg\AuthUser\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Infrastructure\Persistence\Pdo\PdoTokenRepository;

class PdoTokenRepositoryTest extends TestCase
{
    private PDO $pdo;

    private PdoTokenRepository $pdoTokenRepository;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE user_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type VARCHAR(50) NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->pdoTokenRepository = new PdoTokenRepository($this->pdo);
    }

    public function test_create_and_verify_token(): void
    {
        $rawToken = $this->pdoTokenRepository->createToken(1, 'password_reset', 3600);
        $this->assertNotEmpty($rawToken);

        // Ensure token in database is hashed (SHA-256, 64 hex chars) and NOT plaintext
        $stmt = $this->pdo->query('SELECT token FROM user_tokens LIMIT 1');
        $storedToken = $stmt->fetchColumn();
        $this->assertEquals(hash('sha256', $rawToken), $storedToken);
        $this->assertNotEquals($rawToken, $storedToken);

        // Verify with raw token
        $verified = $this->pdoTokenRepository->verifyToken($rawToken, 'password_reset');
        $this->assertNotNull($verified);
        $this->assertEquals(1, $verified->user_id);
        $this->assertEquals('password_reset', $verified->type);
    }

    public function test_verify_returns_null_on_wrong_type_or_token(): void
    {
        $rawToken = $this->pdoTokenRepository->createToken(1, 'password_reset', 3600);

        $this->assertNull($this->pdoTokenRepository->verifyToken('wrong_token', 'password_reset'));
        $this->assertNull($this->pdoTokenRepository->verifyToken($rawToken, 'email_verification'));
    }

    public function test_verify_returns_null_when_expired(): void
    {
        // TTL of -10 seconds (already expired)
        $rawToken = $this->pdoTokenRepository->createToken(1, 'password_reset', -10);

        $this->assertNull($this->pdoTokenRepository->verifyToken($rawToken, 'password_reset'));
    }

    public function test_revoke_token(): void
    {
        $rawToken = $this->pdoTokenRepository->createToken(1, 'password_reset', 3600);
        $this->assertNotNull($this->pdoTokenRepository->verifyToken($rawToken, 'password_reset'));

        $revoked = $this->pdoTokenRepository->revokeToken($rawToken);
        $this->assertTrue($revoked);
        $this->assertNull($this->pdoTokenRepository->verifyToken($rawToken, 'password_reset'));
    }

    public function test_revoke_all_user_tokens(): void
    {
        $token1 = $this->pdoTokenRepository->createToken(1, 'password_reset', 3600);
        $token2 = $this->pdoTokenRepository->createToken(1, 'email_verification', 3600);
        $token3 = $this->pdoTokenRepository->createToken(2, 'password_reset', 3600);

        // Revoke only password_reset for user 1
        $this->pdoTokenRepository->revokeAllUserTokens(1, 'password_reset');
        $this->assertNull($this->pdoTokenRepository->verifyToken($token1, 'password_reset'));
        $this->assertNotNull($this->pdoTokenRepository->verifyToken($token2, 'email_verification'));
        $this->assertNotNull($this->pdoTokenRepository->verifyToken($token3, 'password_reset'));

        // Revoke all for user 1
        $this->pdoTokenRepository->revokeAllUserTokens(1);
        $this->assertNull($this->pdoTokenRepository->verifyToken($token2, 'email_verification'));
        $this->assertNotNull($this->pdoTokenRepository->verifyToken($token3, 'password_reset'));
    }
}
