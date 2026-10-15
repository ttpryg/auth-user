# AuthUser Library

`ttpryg/auth-user` is a framework-agnostic standalone PHP library for user management, authentication, token management, password resetting, and **Role-Based Access Control (RBAC)**.

## 🌟 Key Features

- **Framework Agnostic**: Works out of the box with any PHP application or framework (Vanilla PHP, Slim 4, Laravel, Symfony, CodeIgniter).
- **Production-Grade Security**:
  - **Hashed Token Storage**: Tokens are stored as SHA-256 hashes in the database to prevent exposure during data leaks.
  - **Timing Attack Mitigation**: Constant-time verification on login attempts even for non-existent users.
  - **Account Enumeration Protection**: Configurable option to prevent email discovery on password reset.
  - **Input & Password Validation**: Email format validation and customizable minimum password length.
  - **Session Invalidation**: Automatically revokes tokens upon password change or reset.
- **Full RBAC (Role-Based Access Control)**: Manage roles (`admin`, `editor`, `customer`) and permissions (`user:create`, `post:publish`) with support for wildcard (`user:*`) and superadmin (`*`) permissions.
- **Cross-Database Compatible**: Supports MySQL / MariaDB, PostgreSQL, and SQLite out of the box.
- **Email Verification & Password Reset**: Dedicated services for verification tokens and password reset lifecycle.
- **Domain Events**: Dispatches domain events (`UserRegisteredEvent`, `UserLoggedInEvent`, `UserStatusChangedEvent`, `PasswordResetRequestedEvent`, `PasswordChangedEvent`, `UserEmailVerifiedEvent`).

---

## 🗄️ Database Schema

Run the SQL script from `database/schema.sql` or use `DatabaseMigrator`:

```sql
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_user_login (email, is_active)
);

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

---

## 🚀 RBAC Usage Example

```php
use PDO;
use Ttpryg\AuthUser\Repositories\PdoUserRepository;
use Ttpryg\AuthUser\Repositories\PdoRbacRepository;
use Ttpryg\AuthUser\Services\RbacManager;

$pdo = new PDO("mysql:host=localhost;dbname=my_db", "root", "secret");
$userRepo = new PdoUserRepository($pdo);
$rbacRepo = new PdoRbacRepository($pdo);
$rbacManager = new RbacManager($rbacRepo, $userRepo);

// 1. Create Roles & Permissions
$adminRole = $rbacManager->createRole('admin', 'Administrator');
$deleteUserPerm = $rbacManager->createPermission('user:delete', 'Delete User Account');

// 2. Assign Permission to Role
$rbacManager->assignPermissionToRole($adminRole, $deleteUserPerm);

// 3. Assign Role to User
$user = $userRepo->findById(1);
$rbacManager->assignRoleToUser($user->getId(), $adminRole);

// 4. Load User RBAC & Check Access
$user = $rbacManager->loadUserWithRbac($user);

if ($user->hasRole('admin')) {
    echo "User is an Admin!";
}

if ($user->hasPermission('user:delete')) {
    echo "User can delete other accounts!";
}
```

---

## 📄 License
MIT License.
