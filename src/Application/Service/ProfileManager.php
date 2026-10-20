<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Application\Service;

use InvalidArgumentException;
use Ttpryg\AuthUser\Application\Config\AuthConfig;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Domain\Event\PasswordChangedEvent;
use Ttpryg\AuthUser\Domain\Event\UserStatusChangedEvent;
use Ttpryg\AuthUser\Domain\Exception\InvalidCredentialsException;
use Ttpryg\AuthUser\Domain\Exception\UserAlreadyExistsException;
use Ttpryg\AuthUser\Domain\Exception\UserNotFoundException;

class ProfileManager
{
    private readonly AuthConfig $authConfig;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?TokenRepositoryInterface $tokenRepository = null,
        ?AuthConfig $authConfig = null
    ) {
        $this->authConfig = $authConfig ?? new AuthConfig;
    }

    public function updateProfile(
        int|string $userId,
        ?string $email = null,
        ?string $username = null,
        ?array $metadata = null
    ): bool {
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserNotFoundException::byId($userId);
        }

        if ($email !== null && $email !== $user->getEmail()) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException("Invalid email format: {$email}");
            }

            $existing = $this->userRepository->findByEmail($email, includeTrashed: true);
            if ($existing instanceof \Ttpryg\AuthUser\Domain\Entity\User && (string) $existing->getId() !== (string) $userId) {
                throw UserAlreadyExistsException::forEmail($email);
            }
            $user->setEmail($email);
        }

        if ($username !== null && $username !== $user->getUsername()) {
            $existing = $this->userRepository->findByUsername($username, includeTrashed: true);
            if ($existing instanceof \Ttpryg\AuthUser\Domain\Entity\User && (string) $existing->getId() !== (string) $userId) {
                throw UserAlreadyExistsException::forUsername($username);
            }
            $user->setUsername($username);
        }

        if ($metadata !== null) {
            $user->setMetadata(array_merge($user->getMetadata(), $metadata));
        }

        return $this->userRepository->update($user);
    }

    public function changePassword(int|string $userId, string $currentPassword, string $newPassword): bool
    {
        if (strlen($newPassword) < $this->authConfig->minPasswordLength) {
            throw new InvalidArgumentException("Password must be at least {$this->authConfig->minPasswordLength} characters long.");
        }

        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserNotFoundException::byId($userId);
        }

        if (! $this->passwordHasher->verify($currentPassword, $user->getPasswordHash())) {
            throw new InvalidCredentialsException('Current password does not match.');
        }

        $user->setPasswordHash($this->passwordHasher->hash($newPassword));
        $success = $this->userRepository->update($user);

        if ($success) {
            // Invalidate existing sessions/tokens upon password change
            $this->tokenRepository?->revokeAllUserTokens($userId);
            $this->eventDispatcher?->dispatch(new PasswordChangedEvent($user));
        }

        return $success;
    }

    public function toggleStatus(int|string $userId, bool $isActive): bool
    {
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserNotFoundException::byId($userId);
        }

        $previous = $user->isActive();
        if ($previous === $isActive) {
            return true;
        }

        $user->setIsActive($isActive);
        $result = $this->userRepository->update($user);

        if ($result) {
            $this->eventDispatcher?->dispatch(new UserStatusChangedEvent($user, $previous, $isActive));
        }

        return $result;
    }

    public function deleteAccount(int|string $userId, bool $softDelete = true): bool
    {
        $user = $this->userRepository->findById($userId, includeTrashed: true);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserNotFoundException::byId($userId);
        }

        return $this->userRepository->delete($userId, $softDelete);
    }
}
