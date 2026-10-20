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

Run the SQL migrations from `database/migrations/` via `ttpryg/database-migrator`.

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
