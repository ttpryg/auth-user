<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Exceptions\InvalidCredentialsException;
use Ttpryg\AuthUser\Exceptions\UserInactiveException;
use Ttpryg\AuthUser\Services\AuthenticationService;

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
}
