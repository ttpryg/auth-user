<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Config;

class AuthConfig
{
    public function __construct(
        public readonly int $passwordResetTokenTtl = 3600, // 1 hour
        public readonly int $emailVerificationTokenTtl = 86400, // 24 hours
        public readonly bool $allowInactiveLogin = false,
        public readonly bool $requireUniqueUsername = true
    ) {}
}
