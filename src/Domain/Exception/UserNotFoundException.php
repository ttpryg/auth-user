<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Domain\Exception;

class UserNotFoundException extends AuthUserException
{
    public static function byId(int|string $id): self
    {
        return new self("User with ID '{$id}' was not found.");
    }

    public static function byEmail(string $email): self
    {
        return new self("User with email '{$email}' was not found.");
    }

    public static function byUsername(string $username): self
    {
        return new self("User with username '{$username}' was not found.");
    }
}
