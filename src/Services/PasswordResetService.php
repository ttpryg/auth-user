<?php

namespace Ttpryg\AuthUser\Services;

use Ttpryg\AuthUser\Config\AuthConfig;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Events\PasswordResetRequestedEvent;
use Ttpryg\AuthUser\Exceptions\TokenInvalidException;
use Ttpryg\AuthUser\Exceptions\UserNotFoundException;

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

    public function requestResetToken(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
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
        $tokenObj = $this->tokenRepository->verifyToken($token, self::TOKEN_TYPE);
        if (! $tokenObj) {
            throw new TokenInvalidException;
        }

        $user = $this->userRepository->findById($tokenObj->user_id);
        if (! $user instanceof \Ttpryg\AuthUser\Entities\User) {
            throw UserNotFoundException::byId($tokenObj->user_id);
        }

        // Update password
        $user->setPasswordHash($this->passwordHasher->hash($newPassword));
        $success = $this->userRepository->update($user);

        if ($success) {
            // Revoke reset tokens
            $this->tokenRepository->revokeToken($token);
        }

        return $success;
    }
}
