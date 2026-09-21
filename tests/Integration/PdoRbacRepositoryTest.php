<?php

namespace Ttpryg\AuthUser\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuthUser\Entities\Role;
use Ttpryg\AuthUser\Entities\User;
use Ttpryg\AuthUser\Repositories\PdoRbacRepository;
use Ttpryg\AuthUser\Repositories\PdoUserRepository;
use Ttpryg\AuthUser\Services\RbacManager;

class PdoRbacRepositoryTest extends TestCase
{
    private PDO $pdo;

    private PdoRbacRepository $pdoRbacRepository;

    private PdoUserRepository $pdoUserRepository;

    private RbacManager $rbacManager;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQLite memory tables
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                metadata TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL DEFAULT NULL
            );

            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(100) NOT NULL,
                description TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE,
                label VARCHAR(150) NOT NULL,
                description TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                PRIMARY KEY (user_id, role_id)
            );

            CREATE TABLE role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            );
        ');

        $this->pdoRbacRepository = new PdoRbacRepository($this->pdo);
        $this->pdoUserRepository = new PdoUserRepository($this->pdo);
        $this->rbacManager = new RbacManager($this->pdoRbacRepository, $this->pdoUserRepository);
    }

    // POSITIVE CASE: Full RBAC Integration Flow
    public function test_full_rbac_flow(): void
    {
        // 1. Create User
        $user = new User('editor@example.com', 'hash', 'editor_john');
        $user = $this->pdoUserRepository->save($user);
        $userId = $user->getId();

        // 2. Create Roles
        $this->rbacManager->createRole('admin', 'Administrator');
        $roleEditor = $this->rbacManager->createRole('editor', 'Editor');

        // 3. Create Permissions
        $permission = $this->rbacManager->createPermission('post:create', 'Create Post');
        $permPublishPost = $this->rbacManager->createPermission('post:publish', 'Publish Post');

        // 4. Assign Permissions to Role
        $this->rbacManager->assignPermissionToRole($roleEditor, $permission);
        $this->rbacManager->assignPermissionToRole($roleEditor, $permPublishPost);

        // 5. Assign Role to User
        $this->rbacManager->assignRoleToUser($userId, $roleEditor);

        // 6. Load User with RBAC & Verify
        $loadedUser = $this->rbacManager->loadUserWithRbac($user);

        $this->assertTrue($loadedUser->hasRole('editor'));
        $this->assertFalse($loadedUser->hasRole('admin'));

        $this->assertTrue($loadedUser->hasPermission('post:create'));
        $this->assertTrue($loadedUser->hasPermission('post:publish'));
        $this->assertFalse($loadedUser->hasPermission('user:delete'));
    }
}
