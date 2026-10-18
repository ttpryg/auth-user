<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Exceptions;

class UnauthorizedException extends AuthUserException
{
    public static function missingRole(string|array $role): self
    {
        $roleStr = is_array($role) ? implode(', ', $role) : $role;

        return new self("User is unauthorized. Required role(s): {$roleStr}.");
    }

    public static function missingPermission(string $permission): self
    {
        return new self("User is unauthorized. Required permission: {$permission}.");
    }
}
