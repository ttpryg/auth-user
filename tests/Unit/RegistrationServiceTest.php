<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Contracts\EventDispatcherInterface;
use Ttpryg\AuthUser\Contracts\PasswordHasherInterface;
use Ttpryg\AuthUser\Contracts\UserRepositoryInterface;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Events\UserRegisteredEvent;
use Ttpryg\AuthUser\Exceptions\UserAlreadyExistsException;
use Ttpryg\AuthUser\Services\RegistrationService;

class RegistrationServiceTest extends TestCase
{
    // POSITIVE CASE
    public function test_successful_registration(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $repo->method('findByEmail')->willReturn(value: null);
        $repo->method('findByUsername')->willReturn(value: null);

        $hasher->expects($this->once())
            ->method('hash')
            ->with('Secret123!')
            ->willReturn('hashed_secret');

        $repo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $user): \Ttpryg\AuthUser\Entities\User {
                $user->setId(10);

                return $user;
            });

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserRegisteredEvent::class));

        $registrationService = new RegistrationService($repo, $hasher, $dispatcher);
        $user = $registrationService->register('newuser@example.com', 'Secret123!', 'newuser');

        $this->assertEquals(10, $user->getId());
        $this->assertEquals('newuser@example.com', $user->getEmail());
        $this->assertEquals('newuser', $user->getUsername());
        $this->assertEquals('hashed_secret', $user->getPasswordHash());
    }

    // NEGATIVE CASE: Duplicate Email
    public function test_registration_throws_exception_if_email_exists(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $existingUser = new User('existing@example.com', 'hash', 'existing');
        $repo->method('findByEmail')->with('existing@example.com')->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);

        $registrationService = new RegistrationService($repo, $hasher);
        $registrationService->register('existing@example.com', 'Secret123!');
    }

    // NEGATIVE CASE: Duplicate Username
    public function test_registration_throws_exception_if_username_exists(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $repo->method('findByEmail')->willReturn(value: null);
        $existingUser = new User('other@example.com', 'hash', 'taken_username');
        $repo->method('findByUsername')->with('taken_username')->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);

        $registrationService = new RegistrationService($repo, $hasher);
        $registrationService->register('new@example.com', 'Secret123!', 'taken_username');
    }
}
