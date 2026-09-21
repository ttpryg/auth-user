<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Contracts;

interface TokenRepositoryInterface
{
    public function createToken(int|string $userId, string $type, int $ttlSeconds = 3600): string;

    public function verifyToken(string $token, string $type): ?object;

    public function revokeToken(string $token): bool;

    public function revokeAllUserTokens(int|string $userId, ?string $type = null): bool;
}
