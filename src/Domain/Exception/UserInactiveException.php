<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Domain\Exception;

class UserInactiveException extends AuthUserException
{
    public function __construct(string $message = 'The user account is deactivated.')
    {
        parent::__construct($message);
    }
}
