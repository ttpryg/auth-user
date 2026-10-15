<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Ttpryg\AuthUser\Config\AuthConfig;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Events\UserEmailVerifiedEvent;
use Ttpryg\AuthUser\Exceptions\TokenInvalidException;
use Ttpryg\AuthUser\Exceptions\UserNotFoundException;

class EmailVerificationService
{
    private const TOKEN_TYPE = 'email_verification';

    private readonly AuthConfig $authConfig;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenRepositoryInterface $tokenRepository,
        ?AuthConfig $authConfig = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->authConfig = $authConfig ?? new AuthConfig;
    }

    public function createVerificationToken(int|string $userId): string
    {
        $user = $this->userRepository->findById($userId);
        if (! $user instanceof User) {
            throw UserNotFoundException::byId($userId);
        }

        // Revoke prior tokens
        $this->tokenRepository->revokeAllUserTokens($userId, self::TOKEN_TYPE);

        return $this->tokenRepository->createToken(
            $userId,
            self::TOKEN_TYPE,
            $this->authConfig->emailVerificationTokenTtl
        );
    }

    public function verifyEmail(string $token): User
    {
        $tokenObj = $this->tokenRepository->verifyToken($token, self::TOKEN_TYPE);
        if (! $tokenObj) {
            throw new TokenInvalidException;
        }

        $user = $this->userRepository->findById($tokenObj->user_id);
        if (! $user instanceof User) {
            throw UserNotFoundException::byId($tokenObj->user_id);
        }

        $metadata = $user->getMetadata();
        $metadata['email_verified_at'] = (new DateTimeImmutable)->format(DateTimeInterface::ATOM);
        $user->setMetadata($metadata);

        $this->userRepository->update($user);
        $this->tokenRepository->revokeToken($token);

        $this->eventDispatcher?->dispatch(new UserEmailVerifiedEvent($user));

        return $user;
    }
}
