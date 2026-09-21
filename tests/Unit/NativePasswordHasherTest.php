<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Security\NativePasswordHasher;

class NativePasswordHasherTest extends TestCase
{
    private NativePasswordHasher $nativePasswordHasher;

    protected function setUp(): void
    {
        $this->nativePasswordHasher = new NativePasswordHasher;
    }

    public function test_hash_and_verify(): void
    {
        $password = 'Secret123!';
        $hash = $this->nativePasswordHasher->hash($password);

        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue($this->nativePasswordHasher->verify($password, $hash));
        $this->assertFalse($this->nativePasswordHasher->verify('WrongPassword', $hash));
    }

    public function test_needs_rehash(): void
    {
        $password = 'Secret123!';
        $hash = $this->nativePasswordHasher->hash($password);

        $this->assertFalse($this->nativePasswordHasher->needsRehash($hash));
    }
}
