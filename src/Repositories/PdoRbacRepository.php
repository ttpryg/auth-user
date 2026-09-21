<?php

namespace Ttpryg\AuthUser\Repositories;

use DateTimeImmutable;
use PDO;
use Ttpryg\AuthUser\Contracts\RbacRepositoryInterface;
use Ttpryg\AuthUser\Entities\Permission;
use Ttpryg\AuthUser\Entities\Role;

class PdoRbacRepository implements RbacRepositoryInterface
{
    public function __construct(private readonly PDO $pdo, private readonly string $rolesTable = 'roles', private readonly string $permissionsTable = 'permissions', private readonly string $userRolesTable = 'user_roles', private readonly string $rolePermissionsTable = 'role_permissions') {}

    public function createRole(Role $role): Role
    {
        $sql = "INSERT INTO {$this->rolesTable} (name, label, description, created_at) 
                VALUES (:name, :label, :description, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $role->getName(),
            'label' => $role->getLabel(),
            'description' => $role->getDescription(),
            'created_at' => $role->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);

        $role->setId($this->pdo->lastInsertId());

        return $role;
    }

    public function findRoleByName(string $name): ?Role
    {
        $sql = "SELECT * FROM {$this->rolesTable} WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToRoleEntity($data) : null;
    }

    public function findRoleById(int|string $id): ?Role
    {
        $sql = "SELECT * FROM {$this->rolesTable} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToRoleEntity($data) : null;
    }

    public function getAllRoles(): array
    {
        $sql = "SELECT * FROM {$this->rolesTable} ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToRoleEntity($data);
        }

        return $results;
    }

    public function createPermission(Permission $permission): Permission
    {
        $sql = "INSERT INTO {$this->permissionsTable} (name, label, description, created_at) 
                VALUES (:name, :label, :description, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $permission->getName(),
            'label' => $permission->getLabel(),
            'description' => $permission->getDescription(),
            'created_at' => $permission->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);

        $permission->setId($this->pdo->lastInsertId());

        return $permission;
    }

    public function findPermissionByName(string $name): ?Permission
    {
        $sql = "SELECT * FROM {$this->permissionsTable} WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToPermissionEntity($data) : null;
    }

    public function findPermissionById(int|string $id): ?Permission
    {
        $sql = "SELECT * FROM {$this->permissionsTable} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToPermissionEntity($data) : null;
    }

    public function getAllPermissions(): array
    {
        $sql = "SELECT * FROM {$this->permissionsTable} ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToPermissionEntity($data);
        }

        return $results;
    }

    public function assignRoleToUser(int|string $userId, int|string $roleId): bool
    {
        $ignoreKeyword = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
            ? 'INSERT OR IGNORE'
            : 'INSERT IGNORE';

        $sql = "{$ignoreKeyword} INTO {$this->userRolesTable} (user_id, role_id) VALUES (:user_id, :role_id)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }

    public function removeRoleFromUser(int|string $userId, int|string $roleId): bool
    {
        $sql = "DELETE FROM {$this->userRolesTable} WHERE user_id = :user_id AND role_id = :role_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }

    public function getUserRoles(int|string $userId): array
    {
        $sql = "SELECT r.* FROM {$this->rolesTable} r
                INNER JOIN {$this->userRolesTable} ur ON r.id = ur.role_id
                WHERE ur.user_id = :user_id ORDER BY r.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToRoleEntity($data);
        }

        return $results;
    }

    public function assignPermissionToRole(int|string $roleId, int|string $permissionId): bool
    {
        $ignoreKeyword = ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite')
            ? 'INSERT OR IGNORE'
            : 'INSERT IGNORE';

        $sql = "{$ignoreKeyword} INTO {$this->rolePermissionsTable} (role_id, permission_id) VALUES (:role_id, :permission_id)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    public function removePermissionFromRole(int|string $roleId, int|string $permissionId): bool
    {
        $sql = "DELETE FROM {$this->rolePermissionsTable} WHERE role_id = :role_id AND permission_id = :permission_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    public function getRolePermissions(int|string $roleId): array
    {
        $sql = "SELECT p.* FROM {$this->permissionsTable} p
                INNER JOIN {$this->rolePermissionsTable} rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id ORDER BY p.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToPermissionEntity($data);
        }

        return $results;
    }

    public function getUserPermissions(int|string $userId): array
    {
        $sql = "SELECT DISTINCT p.* FROM {$this->permissionsTable} p
                INNER JOIN {$this->rolePermissionsTable} rp ON p.id = rp.permission_id
                INNER JOIN {$this->userRolesTable} ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = :user_id ORDER BY p.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToPermissionEntity($data);
        }

        return $results;
    }

    private function mapToRoleEntity(array $data): Role
    {
        return new Role(
            name: $data['name'],
            label: $data['label'],
            description: $data['description'] ?? null,
            id: $data['id'],
            createdAt: ! empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null
        );
    }

    private function mapToPermissionEntity(array $data): Permission
    {
        return new Permission(
            name: $data['name'],
            label: $data['label'],
            description: $data['description'] ?? null,
            id: $data['id'],
            createdAt: ! empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null
        );
    }
}
