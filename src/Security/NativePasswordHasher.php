<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Security;

use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;

class NativePasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly string|int $algo = PASSWORD_BCRYPT, private readonly array $options = []) {}

    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, $this->algo, $this->options);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, $this->algo, $this->options);
    }
}
