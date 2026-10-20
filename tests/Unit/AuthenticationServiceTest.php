<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Application\Service\AuthenticationService;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Domain\Entity\User;
use Ttpryg\AuthUser\Domain\Exception\InvalidCredentialsException;
use Ttpryg\AuthUser\Domain\Exception\UserInactiveException;

class AuthenticationServiceTest extends TestCase
{
    // POSITIVE CASE
    public function test_successful_authentication(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('user@example.com', 'hashed_pass', 'username', isActive: true, metadata: [], id: 1);
        $repo->method('findByEmail')->with('user@example.com')->willReturn($user);

        $hasher->method('verify')->with('password123', 'hashed_pass')->willReturn(value: true);
        $hasher->method('needsRehash')->willReturn(value: false);

        $authenticationService = new AuthenticationService($repo, $hasher);
        $result = $authenticationService->authenticate('user@example.com', 'password123');

        $this->assertEquals($user, $result);
    }

    // NEGATIVE CASE: User Not Found
    public function test_authentication_fails_on_non_existent_user(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repo->method('findByEmail')->with('unknown@example.com')->willReturn(value: null);

        $this->expectException(InvalidCredentialsException::class);

        $authenticationService = new AuthenticationService($repo, $hasher);
        $authenticationService->authenticate('unknown@example.com', 'password123');
    }

    // NEGATIVE CASE: Invalid Password
    public function test_authentication_fails_on_wrong_password(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('user@example.com', 'hashed_pass', 'username', isActive: true, metadata: [], id: 1);
        $repo->method('findByEmail')->with('user@example.com')->willReturn($user);

        $hasher->method('verify')->with('wrong_pass', 'hashed_pass')->willReturn(value: false);

        $this->expectException(InvalidCredentialsException::class);

        $authenticationService = new AuthenticationService($repo, $hasher);
        $authenticationService->authenticate('user@example.com', 'wrong_pass');
    }

    // NEGATIVE CASE: Deactivated Account
    public function test_authentication_fails_on_inactive_user(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('user@example.com', 'hashed_pass', 'username', isActive: false, metadata: [], id: 1);
        $repo->method('findByEmail')->with('user@example.com')->willReturn($user);

        $hasher->method('verify')->with('password123', 'hashed_pass')->willReturn(value: true);

        $this->expectException(UserInactiveException::class);

        $authenticationService = new AuthenticationService($repo, $hasher);
        $authenticationService->authenticate('user@example.com', 'password123');
    }

    public function test_authentication_mitigates_timing_attack_when_user_not_found(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repo->method('findByEmail')->willReturn(value: null);

        // Expect verify to be called with dummy hash
        $hasher->expects($this->once())
            ->method('verify')
            ->with('password123', $this->stringStartsWith('$2y$10$'))
            ->willReturn(value: false);

        $this->expectException(InvalidCredentialsException::class);

        $authenticationService = new AuthenticationService($repo, $hasher);
        $authenticationService->authenticate('unknown@example.com', 'password123');
    }

    public function test_allow_inactive_login_via_config(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $dispatcher = $this->createMock(\Ttpryg\AuthUser\Contracts\EventDispatcherInterface::class);

        $user = new User('user@example.com', 'hashed_pass', 'username', isActive: false, metadata: [], id: 1);
        $repo->method('findByEmail')->with('user@example.com')->willReturn($user);

        $hasher->method('verify')->with('password123', 'hashed_pass')->willReturn(value: true);
        $hasher->method('needsRehash')->willReturn(value: false);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(\Ttpryg\AuthUser\Domain\Event\UserLoggedInEvent::class));

        $authConfig = new \Ttpryg\AuthUser\Application\Config\AuthConfig(allowInactiveLogin: true);
        $authenticationService = new AuthenticationService($repo, $hasher, authConfig: $authConfig, eventDispatcher: $dispatcher);
        $result = $authenticationService->authenticate('user@example.com', 'password123');

        $this->assertSame($user, $result);
    }
}
