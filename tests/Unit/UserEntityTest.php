<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Entities\User;

class UserEntityTest extends TestCase
{
    public function test_user_creation_and_getters(): void
    {
        $user = new User(
            email: 'user@example.com',
            passwordHash: 'hashed_password',
            username: 'testuser',
            isActive: true,
            metadata: ['role' => 'admin'],
            id: 1
        );

        $this->assertEquals(1, $user->getId());
        $this->assertEquals(1, $user->getAuthIdentifier());
        $this->assertEquals('user@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('hashed_password', $user->getPasswordHash());
        $this->assertTrue($user->isActive());
        $this->assertEquals(['role' => 'admin'], $user->getMetadata());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertNull($user->getDeletedAt());
    }

    public function test_user_to_array(): void
    {
        $user = new User(
            email: 'user@example.com',
            passwordHash: 'hashed_password',
            username: 'testuser',
            id: 42
        );

        $array = $user->toArray();

        $this->assertEquals(42, $array['id']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('user@example.com', $array['email']);
        $this->assertTrue($array['is_active']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertNull($array['deleted_at']);
    }
}
