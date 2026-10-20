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
use Ttpryg\AuthUser\Domain\Event\PasswordResetRequestedEvent;
use Ttpryg\AuthUser\Domain\Exception\TokenInvalidException;
use Ttpryg\AuthUser\Domain\Exception\UserNotFoundException;

class PasswordResetService
{
    private const TOKEN_TYPE = 'password_reset';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenRepositoryInterface $tokenRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private ?AuthConfig $authConfig = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->authConfig = $authConfig ?? new AuthConfig;
    }

    public function requestResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            if ($this->authConfig->preventUserEnumeration) {
                return null;
            }

            throw UserNotFoundException::byEmail($email);
        }

        // Revoke previous reset tokens
        $this->tokenRepository->revokeAllUserTokens($user->getId(), self::TOKEN_TYPE);

        // Generate token
        $token = $this->tokenRepository->createToken(
            $user->getId(),
            self::TOKEN_TYPE,
            $this->authConfig->passwordResetTokenTtl
        );

        // Dispatch Event
        $this->eventDispatcher?->dispatch(new PasswordResetRequestedEvent($user, $token));

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        if (strlen($newPassword) < $this->authConfig->minPasswordLength) {
            throw new InvalidArgumentException("Password must be at least {$this->authConfig->minPasswordLength} characters long.");
        }

        $tokenObj = $this->tokenRepository->verifyToken($token, self::TOKEN_TYPE);
        if (! $tokenObj) {
            throw new TokenInvalidException;
        }

        $user = $this->userRepository->findById($tokenObj->user_id);
        if (! $user instanceof \Ttpryg\AuthUser\Domain\Entity\User) {
            throw UserNotFoundException::byId($tokenObj->user_id);
        }

        // Update password
        $user->setPasswordHash($this->passwordHasher->hash($newPassword));
        $success = $this->userRepository->update($user);

        if ($success) {
            // Revoke reset token
            $this->tokenRepository->revokeToken($token);
            $this->eventDispatcher?->dispatch(new PasswordChangedEvent($user));
        }

        return $success;
    }
}
