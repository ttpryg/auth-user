<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\TokenRepositoryInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Events\PasswordResetRequestedEvent;
use Ttpryg\AuthUser\Exceptions\TokenInvalidException;
use Ttpryg\AuthUser\Exceptions\UserNotFoundException;
use Ttpryg\AuthUser\Services\PasswordResetService;

class PasswordResetServiceTest extends TestCase
{
    // POSITIVE CASE: Request Reset Token
    public function test_successful_reset_token_request(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $user = new User('user@example.com', 'hash', 'user', isActive: true, metadata: [], id: 1);
        $userRepo->method('findByEmail')->with('user@example.com')->willReturn($user);

        $tokenRepo->expects($this->once())
            ->method('revokeAllUserTokens')
            ->with(1, 'password_reset');

        $tokenRepo->expects($this->once())
            ->method('createToken')
            ->willReturn('generated_token_string');

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));

        $passwordResetService = new PasswordResetService($userRepo, $tokenRepo, $hasher, eventDispatcher: $dispatcher);
        $token = $passwordResetService->requestResetToken('user@example.com');

        $this->assertEquals('generated_token_string', $token);
    }

    // NEGATIVE CASE: Request Token for Non-Existent Email
    public function test_request_reset_token_fails_on_unknown_email(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $userRepo->method('findByEmail')->with('unknown@example.com')->willReturn(value: null);

        $this->expectException(UserNotFoundException::class);

        $passwordResetService = new PasswordResetService($userRepo, $tokenRepo, $hasher);
        $passwordResetService->requestResetToken('unknown@example.com');
    }

    // POSITIVE CASE: Reset Password
    public function test_successful_password_reset(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $tokenObj = (object) ['user_id' => 1, 'token' => 'valid_token'];
        $tokenRepo->method('verifyToken')->with('valid_token', 'password_reset')->willReturn($tokenObj);

        $user = new User('user@example.com', 'old_hash', 'user', isActive: true, metadata: [], id: 1);
        $userRepo->method('findById')->with(1)->willReturn($user);

        $hasher->method('hash')->with('BrandNewPass123')->willReturn('new_hash');
        $userRepo->method('update')->willReturn(value: true);

        $tokenRepo->expects($this->once())
            ->method('revokeToken')
            ->with('valid_token');

        $passwordResetService = new PasswordResetService($userRepo, $tokenRepo, $hasher);
        $result = $passwordResetService->resetPassword('valid_token', 'BrandNewPass123');

        $this->assertTrue($result);
        $this->assertEquals('new_hash', $user->getPasswordHash());
    }

    // NEGATIVE CASE: Reset Password with Expired or Invalid Token
    public function test_reset_password_fails_on_invalid_token(): void
    {
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $tokenRepo = $this->createMock(TokenRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $tokenRepo->method('verifyToken')->with('expired_token', 'password_reset')->willReturn(value: null);

        $this->expectException(TokenInvalidException::class);

        $passwordResetService = new PasswordResetService($userRepo, $tokenRepo, $hasher);
        $passwordResetService->resetPassword('expired_token', 'BrandNewPass123');
    }
}
