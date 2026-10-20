<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Domain\Event;

use Ttpryg\AuthUser\Domain\Entity\User;

class UserLoggedInEvent
{
    public function __construct(
        public readonly User $user
    ) {}
}
