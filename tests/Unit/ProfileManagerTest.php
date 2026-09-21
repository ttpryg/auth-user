<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Events\UserStatusChangedEvent;
use Ttpryg\AuthUser\Exceptions\InvalidCredentialsException;
use Ttpryg\AuthUser\Exceptions\UserAlreadyExistsException;
use Ttpryg\AuthUser\Exceptions\UserNotFoundException;
use Ttpryg\AuthUser\Services\ProfileManager;

class ProfileManagerTest extends TestCase
{
    // POSITIVE CASE: Update Profile
    public function test_successful_profile_update(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('old@example.com', 'hash', 'oldname', isActive: true, metadata: [], id: 1);
        $repo->method('findById')->with(1)->willReturn($user);
        $repo->method('findByEmail')->with('new@example.com')->willReturn(value: null);
        $repo->method('update')->willReturn(value: true);

        $profileManager = new ProfileManager($repo, $hasher);
        $result = $profileManager->updateProfile(1, email: 'new@example.com');

        $this->assertTrue($result);
        $this->assertEquals('new@example.com', $user->getEmail());
    }

    // NEGATIVE CASE: Update Email already taken by another user
    public function test_update_profile_fails_when_email_taken(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user1 = new User('user1@example.com', 'hash', 'user1', isActive: true, metadata: [], id: 1);
        $user2 = new User('taken@example.com', 'hash', 'user2', isActive: true, metadata: [], id: 2);

        $repo->method('findById')->with(1)->willReturn($user1);
        $repo->method('findByEmail')->with('taken@example.com')->willReturn($user2);

        $this->expectException(UserAlreadyExistsException::class);

        $profileManager = new ProfileManager($repo, $hasher);
        $profileManager->updateProfile(1, email: 'taken@example.com');
    }

    // POSITIVE CASE: Change Password
    public function test_successful_password_change(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('user@example.com', 'old_hash', 'user', isActive: true, metadata: [], id: 1);
        $repo->method('findById')->with(1)->willReturn($user);

        $hasher->method('verify')->with('OldPassword123', 'old_hash')->willReturn(value: true);
        $hasher->method('hash')->with('NewPassword123')->willReturn('new_hash');
        $repo->method('update')->willReturn(value: true);

        $profileManager = new ProfileManager($repo, $hasher);
        $result = $profileManager->changePassword(1, 'OldPassword123', 'NewPassword123');

        $this->assertTrue($result);
        $this->assertEquals('new_hash', $user->getPasswordHash());
    }

    // NEGATIVE CASE: Change Password with Wrong Current Password
    public function test_change_password_fails_on_wrong_current_password(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $user = new User('user@example.com', 'old_hash', 'user', isActive: true, metadata: [], id: 1);
        $repo->method('findById')->with(1)->willReturn($user);

        $hasher->method('verify')->with('WrongOldPassword', 'old_hash')->willReturn(value: false);

        $this->expectException(InvalidCredentialsException::class);

        $profileManager = new ProfileManager($repo, $hasher);
        $profileManager->changePassword(1, 'WrongOldPassword', 'NewPassword123');
    }

    // POSITIVE CASE: Toggle Status and Dispatch Event
    public function test_toggle_status_dispatches_event(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $user = new User('user@example.com', 'hash', 'user', isActive: true, metadata: [], id: 1);
        $repo->method('findById')->with(1)->willReturn($user);
        $repo->method('update')->willReturn(value: true);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserStatusChangedEvent::class));

        $profileManager = new ProfileManager($repo, $hasher, $dispatcher);
        $result = $profileManager->toggleStatus(1, isActive: false);

        $this->assertTrue($result);
        $this->assertFalse($user->isActive());
    }

    // NEGATIVE CASE: User Not Found
    public function test_operations_fail_on_non_existent_user(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repo->method('findById')->with(999)->willReturn(value: null);

        $this->expectException(UserNotFoundException::class);

        $profileManager = new ProfileManager($repo, $hasher);
        $profileManager->updateProfile(999, email: 'any@example.com');
    }
}
