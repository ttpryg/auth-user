<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Domain\Exception;

class InvalidCredentialsException extends AuthUserException
{
    public function __construct(string $message = 'Invalid email/username or password.')
    {
        parent::__construct($message);
    }
}
