<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Services;

use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Exceptions\InvalidCredentialsException;
use Ttpryg\AuthUser\Exceptions\UserInactiveException;

class AuthenticationService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher
    ) {}

    public function authenticate(string $identity, string $plainPassword): User
    {
        // Find user by email or username
        $user = str_contains($identity, '@')
            ? $this->userRepository->findByEmail($identity)
            : $this->userRepository->findByUsername($identity);

        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            throw new InvalidCredentialsException;
        }

        // Verify password
        if (! $this->passwordHasher->verify($plainPassword, $user->getPasswordHash())) {
            throw new InvalidCredentialsException;
        }

        // Check active status
        if (! $user->isActive()) {
            throw new UserInactiveException;
        }

        // Rehash password if algorithm/cost changed
        if ($this->passwordHasher->needsRehash($user->getPasswordHash())) {
            $user->setPasswordHash($this->passwordHasher->hash($plainPassword));
            $this->userRepository->update($user);
        }

        return $user;
    }
}
