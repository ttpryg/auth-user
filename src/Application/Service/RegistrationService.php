<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Application\Service;

use InvalidArgumentException;
use PDOException;
use Ttpryg\AuthUser\Application\Config\AuthConfig;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Domain\Entity\User;
use Ttpryg\AuthUser\Domain\Event\UserRegisteredEvent;
use Ttpryg\AuthUser\Domain\Exception\UserAlreadyExistsException;

class RegistrationService
{
    private readonly AuthConfig $authConfig;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        ?AuthConfig $authConfig = null
    ) {
        $this->authConfig = $authConfig ?? new AuthConfig;
    }

    public function register(
        string $email,
        string $plainPassword,
        ?string $username = null,
        array $metadata = [],
        bool $isActive = true
    ): User {
        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$email}");
        }

        // Validate password minimum length
        if (strlen($plainPassword) < $this->authConfig->minPasswordLength) {
            throw new InvalidArgumentException("Password must be at least {$this->authConfig->minPasswordLength} characters long.");
        }

        // Validate email uniqueness (including trashed accounts to avoid DB constraint crash)
        if ($this->userRepository->findByEmail($email, includeTrashed: true) instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserAlreadyExistsException::forEmail($email);
        }

        // Validate username uniqueness if provided and required
        if ($username !== null && $this->authConfig->requireUniqueUsername) {
            if ($this->userRepository->findByUsername($username, includeTrashed: true) instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
                throw UserAlreadyExistsException::forUsername($username);
            }
        }

        // Hash password
        $passwordHash = $this->passwordHasher->hash($plainPassword);

        // Create User entity
        $user = new User(
            email: $email,
            passwordHash: $passwordHash,
            username: $username,
            isActive: $isActive,
            metadata: $metadata
        );

        // Save to database with race condition handling
        try {
            $savedUser = $this->userRepository->save($user);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                throw UserAlreadyExistsException::forEmail($email);
            }
            throw $e;
        }

        // Dispatch Event
        $this->eventDispatcher?->dispatch(new UserRegisteredEvent($savedUser));

        return $savedUser;
    }
}
