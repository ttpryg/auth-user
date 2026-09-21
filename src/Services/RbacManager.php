<?php

namespace Ttpryg\AuthUser\Services;

use Ttpryg\AuthUser\Contracts\RbacRepositoryInterface;
use Ttpryg\AuthUser\Entities\Permission;
use Ttpryg\AuthUser\Entities\Role;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Exceptions\PermissionNotFoundException;
use Ttpryg\AuthUser\Exceptions\RoleNotFoundException;
use Ttpryg\AuthUser\Exceptions\UnauthorizedException;

class RbacManager
{
    public function __construct(
        private readonly RbacRepositoryInterface $rbacRepository
    ) {}

    public function createRole(string $name, string $label, ?string $description = null): Role
    {
        $existing = $this->rbacRepository->findRoleByName($name);
        if ($existing instanceof \Ttpryg\AuthUser\Entities\Role) {
            return $existing;
        }

        return $this->rbacRepository->createRole(new Role($name, $label, $description));
    }

    public function createPermission(string $name, string $label, ?string $description = null): Permission
    {
        $existing = $this->rbacRepository->findPermissionByName($name);
        if ($existing instanceof \Ttpryg\AuthUser\Entities\Permission) {
            return $existing;
        }

        return $this->rbacRepository->createPermission(new Permission($name, $label, $description));
    }

    public function assignRoleToUser(int|string $userId, string|int|Role $role): bool
    {
        $roleId = $role instanceof Role ? $role->getId() : null;
        if ($roleId === null) {
            $found = is_numeric($role)
                ? $this->rbacRepository->findRoleById($role)
                : $this->rbacRepository->findRoleByName((string) $role);

            if (! $found instanceof \Ttpryg\AuthUser\Entities\Role) {
                throw RoleNotFoundException::byName((string) $role);
            }
            $roleId = $found->getId();
        }

        return $this->rbacRepository->assignRoleToUser($userId, $roleId);
    }

    public function removeRoleFromUser(int|string $userId, string|int|Role $role): bool
    {
        $roleId = $role instanceof Role ? $role->getId() : null;
        if ($roleId === null) {
            $found = is_numeric($role)
                ? $this->rbacRepository->findRoleById($role)
                : $this->rbacRepository->findRoleByName((string) $role);

            if (! $found instanceof \Ttpryg\AuthUser\Entities\Role) {
                throw RoleNotFoundException::byName((string) $role);
            }
            $roleId = $found->getId();
        }

        return $this->rbacRepository->removeRoleFromUser($userId, $roleId);
    }

    public function assignPermissionToRole(string|int|Role $role, string|int|Permission $permission): bool
    {
        $roleId = $role instanceof Role ? $role->getId() : null;
        if ($roleId === null) {
            $found = is_numeric($role)
                ? $this->rbacRepository->findRoleById($role)
                : $this->rbacRepository->findRoleByName((string) $role);

            if (! $found instanceof \Ttpryg\AuthUser\Entities\Role) {
                throw RoleNotFoundException::byName((string) $role);
            }
            $roleId = $found->getId();
        }

        $permId = $permission instanceof Permission ? $permission->getId() : null;
        if ($permId === null) {
            $found = is_numeric($permission)
                ? $this->rbacRepository->findPermissionById($permission)
                : $this->rbacRepository->findPermissionByName((string) $permission);

            if (! $found instanceof \Ttpryg\AuthUser\Entities\Permission) {
                throw PermissionNotFoundException::byName((string) $permission);
            }
            $permId = $found->getId();
        }

        return $this->rbacRepository->assignPermissionToRole($roleId, $permId);
    }

    public function loadUserWithRbac(User $user): User
    {
        if ($user->getId() === null) {
            return $user;
        }

        $roles = $this->rbacRepository->getUserRoles($user->getId());
        $permissions = $this->rbacRepository->getUserPermissions($user->getId());

        $user->setRoles($roles);
        $user->setPermissions($permissions);

        return $user;
    }

    public function authorizeRole(User $user, string|array $requiredRole): void
    {
        if (! $user->hasRole($requiredRole)) {
            throw UnauthorizedException::missingRole($requiredRole);
        }
    }

    public function authorizePermission(User $user, string $requiredPermission): void
    {
        if (! $user->hasPermission($requiredPermission)) {
            throw UnauthorizedException::missingPermission($requiredPermission);
        }
    }
}
