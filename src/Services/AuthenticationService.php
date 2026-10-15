<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Services;

use Ttpryg\AuthUser\Config\AuthConfig;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Events\UserLoggedInEvent;
use Ttpryg\AuthUser\Exceptions\InvalidCredentialsException;
use Ttpryg\AuthUser\Exceptions\UserInactiveException;

class AuthenticationService
{
    private const DUMMY_HASH = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUUWXYZ012345';

    private readonly AuthConfig $authConfig;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        ?AuthConfig $authConfig = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->authConfig = $authConfig ?? new AuthConfig;
    }

    public function authenticate(string $identity, string $plainPassword): User
    {
        // Find user by email or username
        $user = str_contains($identity, '@')
            ? $this->userRepository->findByEmail($identity)
            : $this->userRepository->findByUsername($identity);

        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            // Mitigate timing attack by performing a dummy hash verification
            $this->passwordHasher->verify($plainPassword, self::DUMMY_HASH);
            throw new InvalidCredentialsException;
        }

        // Verify password
        if (! $this->passwordHasher->verify($plainPassword, $user->getPasswordHash())) {
            throw new InvalidCredentialsException;
        }

        // Check active status
        if (! $this->authConfig->allowInactiveLogin && ! $user->isActive()) {
            throw new UserInactiveException;
        }

        // Rehash password if algorithm/cost changed
        if ($this->passwordHasher->needsRehash($user->getPasswordHash())) {
            $user->setPasswordHash($this->passwordHasher->hash($plainPassword));
            $this->userRepository->update($user);
        }

        $this->eventDispatcher?->dispatch(new UserLoggedInEvent($user));

        return $user;
    }
}
