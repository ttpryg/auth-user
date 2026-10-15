<?php

namespace Ttpryg\AuthUser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Entities\Permission;
use Ttpryg\AuthUser\Entities\Role;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Exceptions\UnauthorizedException;
use Ttpryg\AuthUser\Services\RbacManager;

class UserRbacTest extends TestCase
{
    // POSITIVE CASE: Check user roles and permissions
    public function test_user_has_role_and_permission(): void
    {
        $roleAdmin = new Role('admin', 'Administrator');
        $roleEditor = new Role('editor', 'Editor');

        $permCreate = new Permission('user:create', 'Create User');
        $permDelete = new Permission('user:delete', 'Delete User');

        $user = new User(
            email: 'admin@example.com',
            passwordHash: 'hash',
            roles: [$roleAdmin, $roleEditor],
            permissions: [$permCreate, $permDelete]
        );

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertTrue($user->hasRole(['superadmin', 'admin']));
        $this->assertFalse($user->hasRole('customer'));

        $this->assertTrue($user->hasPermission('user:create'));
        $this->assertTrue($user->hasPermission('user:delete'));
        $this->assertFalse($user->hasPermission('system:shutdown'));
    }

    // NEGATIVE CASE: Authorization throws UnauthorizedException when role is missing
    public function test_authorize_role_fails_on_missing_role(): void
    {
        $user = new User('customer@example.com', 'hash', roles: [new Role('customer', 'Customer')]);
        $rbacManager = new RbacManager($this->createMock(\Ttpryg\AuthUser\Contracts\RbacRepositoryInterface::class));

        $this->expectException(UnauthorizedException::class);
        $rbacManager->authorizeRole($user, 'admin');
    }

    // NEGATIVE CASE: Authorization throws UnauthorizedException when permission is missing
    public function test_authorize_permission_fails_on_missing_permission(): void
    {
        $user = new User('customer@example.com', 'hash', permissions: [new Permission('post:read', 'Read Post')]);
        $rbacManager = new RbacManager($this->createMock(\Ttpryg\AuthUser\Contracts\RbacRepositoryInterface::class));

        $this->expectException(UnauthorizedException::class);
        $rbacManager->authorizePermission($user, 'post:delete');
    }

    public function test_wildcard_and_superadmin_permissions(): void
    {
        // Wildcard prefix: user:*
        $userWildcard = new User('wildcard@example.com', 'hash', permissions: [new Permission('user:*', 'All User Permissions')]);
        $this->assertTrue($userWildcard->hasPermission('user:create'));
        $this->assertTrue($userWildcard->hasPermission('user:delete'));
        $this->assertFalse($userWildcard->hasPermission('post:delete'));

        // Superadmin: *
        $superadmin = new User('super@example.com', 'hash', permissions: [new Permission('*', 'All Permissions')]);
        $this->assertTrue($superadmin->hasPermission('user:create'));
        $this->assertTrue($superadmin->hasPermission('anything:do_whatever'));
    }
}
