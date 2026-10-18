<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Exceptions;

class UserAlreadyExistsException extends AuthUserException
{
    public static function forEmail(string $email): self
    {
        return new self("User with email '{$email}' already exists.");
    }

    public static function forUsername(string $username): self
    {
        return new self("User with username '{$username}' already exists.");
    }
}
