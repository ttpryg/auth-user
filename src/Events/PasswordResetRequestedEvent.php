<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Events;

use Ttpryg\AuthUser\Entities\User;

class PasswordResetRequestedEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {}
}
