<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Events;

use Ttpryg\AuthUser\Entities\User;

class UserLoggedInEvent
{
    public function __construct(
        public readonly User $user
    ) {}
}
