<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Application\Service\EmailVerificationService;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Domain\Entity\User;
use Ttpryg\AuthUser\Domain\Event\UserEmailVerifiedEvent;
use Ttpryg\AuthUser\Domain\Exception\TokenInvalidException;
use Ttpryg\AuthUser\Domain\Exception\UserNotFoundException;

class EmailVerificationServiceTest extends TestCase
{
    public function test_create_verification_token(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $user = new User('verify@example.com', 'hash', 'verify', id: 5);
        $userRepo->method('findById')->with(5)->willReturn($user);

        $tokenRepo->expects($this->once())
            ->method('revokeAllUserTokens')
            ->with(5, 'email_verification');

        $tokenRepo->expects($this->once())
            ->method('createToken')
            ->with(5, 'email_verification', 86400)
            ->willReturn('raw_verify_token');

        $emailVerificationService = new EmailVerificationService($userRepo, $tokenRepo, eventDispatcher: $dispatcher);
        $token = $emailVerificationService->createVerificationToken(5);

        $this->assertEquals('raw_verify_token', $token);
    }

    public function test_create_verification_token_fails_on_unknown_user(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);

        $userRepo->method('findById')->with(999)->willReturn(value: null);

        $this->expectException(UserNotFoundException::class);

        $emailVerificationService = new EmailVerificationService($userRepo, $tokenRepo);
        $emailVerificationService->createVerificationToken(999);
    }

    public function test_verify_email_success(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $tokenObj = (object) ['user_id' => 5, 'token' => 'valid_token'];
        $tokenRepo->method('verifyToken')->with('valid_token', 'email_verification')->willReturn($tokenObj);

        $user = new User('verify@example.com', 'hash', 'verify', id: 5);
        $userRepo->method('findById')->with(5)->willReturn($user);

        $userRepo->expects($this->once())
            ->method('update')
            ->with($this->callback(function (User $user): bool {
                $meta = $user->getMetadata();

                return isset($meta['email_verified_at']);
            }));

        $tokenRepo->expects($this->once())
            ->method('revokeToken')
            ->with('valid_token');

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserEmailVerifiedEvent::class));

        $emailVerificationService = new EmailVerificationService($userRepo, $tokenRepo, eventDispatcher: $dispatcher);
        $updatedUser = $emailVerificationService->verifyEmail('valid_token');

        $this->assertArrayHasKey('email_verified_at', $updatedUser->getMetadata());
    }

    public function test_verify_email_fails_on_invalid_token(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);

        $tokenRepo->method('verifyToken')->willReturn(value: null);

        $this->expectException(TokenInvalidException::class);

        $emailVerificationService = new EmailVerificationService($userRepo, $tokenRepo);
        $emailVerificationService->verifyEmail('invalid_token');
    }
}
