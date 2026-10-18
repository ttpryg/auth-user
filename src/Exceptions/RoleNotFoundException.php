<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Exceptions;

class RoleNotFoundException extends AuthUserException
{
    public static function byName(string $name): self
    {
        return new self("Role with name '{$name}' was not found.");
    }

    public static function byId(int|string $id): self
    {
        return new self("Role with ID '{$id}' was not found.");
    }
}
