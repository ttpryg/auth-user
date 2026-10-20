<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Domain\Event;

use Ttpryg\AuthUser\Domain\Entity\User;

class UserStatusChangedEvent
{
    public function __construct(
        public readonly User $user,
        public readonly bool $previousState,
        public readonly bool $newState
    ) {}
}
