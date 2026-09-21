<?php

namespace Ttpryg\AuthUser\Services;

use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Events\UserStatusChangedEvent;
use Ttpryg\AuthUser\Exceptions\InvalidCredentialsException;
use Ttpryg\AuthUser\Exceptions\UserAlreadyExistsException;
use Ttpryg\AuthUser\Exceptions\UserNotFoundException;

class ProfileManager
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    public function updateProfile(
        int|string $userId,
        ?string $email = null,
        ?string $username = null,
        ?array $metadata = null
    ): bool {
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            throw UserNotFoundException::byId($userId);
        }

        if ($email !== null && $email !== $user->getEmail()) {
            $existing = $this->userRepository->findByEmail($email);
            if ($existing instanceof \Ttpryg\AuthUser\Entities\User && $existing->getId() !== $userId) {
                throw UserAlreadyExistsException::forEmail($email);
            }
            $user->setEmail($email);
        }

        if ($username !== null && $username !== $user->getUsername()) {
            $existing = $this->userRepository->findByUsername($username);
            if ($existing instanceof \Ttpryg\AuthUser\Entities\User && $existing->getId() !== $userId) {
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
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            throw UserNotFoundException::byId($userId);
        }

        if (! $this->passwordHasher->verify($currentPassword, $user->getPasswordHash())) {
            throw new InvalidCredentialsException('Current password does not match.');
        }

        $user->setPasswordHash($this->passwordHasher->hash($newPassword));

        return $this->userRepository->update($user);
    }

    public function toggleStatus(int|string $userId, bool $isActive): bool
    {
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
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
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            throw UserNotFoundException::byId($userId);
        }

        return $this->userRepository->delete($userId, $softDelete);
    }
}
