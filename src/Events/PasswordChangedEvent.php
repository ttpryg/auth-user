<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Events;

use Ttpryg\AuthUser\Entities\User;

class PasswordChangedEvent
{
    public function __construct(
        public readonly User $user
    ) {}
}
