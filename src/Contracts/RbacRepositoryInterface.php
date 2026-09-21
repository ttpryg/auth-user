<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Contracts;

use Ttpryg\AuthUser\Entities\Permission;
use Ttpryg\AuthUser\Entities\Role;

interface RbacRepositoryInterface
{
    // Role Operations
    public function createRole(Role $role): Role;

    public function findRoleByName(string $name): ?Role;

    public function findRoleById(int|string $id): ?Role;

    public function getAllRoles(): array;

    // Permission Operations
    public function createPermission(Permission $permission): Permission;

    public function findPermissionByName(string $name): ?Permission;

    public function findPermissionById(int|string $id): ?Permission;

    public function getAllPermissions(): array;

    // User - Role Assignments
    public function assignRoleToUser(int|string $userId, int|string $roleId): bool;

    public function removeRoleFromUser(int|string $userId, int|string $roleId): bool;

    public function getUserRoles(int|string $userId): array;

    // Role - Permission Assignments
    public function assignPermissionToRole(int|string $roleId, int|string $permissionId): bool;

    public function removePermissionFromRole(int|string $roleId, int|string $permissionId): bool;

    public function getRolePermissions(int|string $roleId): array;

    // Aggregated User Permissions
    public function getUserPermissions(int|string $userId): array;
}
