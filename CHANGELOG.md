# Changelog

All notable changes to `ttpryg/auth-user` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-10-18

### Added

- Root `ExceptionInterface` marker interface (`Ttpryg\AuthUser\Exceptions\ExceptionInterface`)
  that extends `Throwable` and is implemented by `AuthUserException` (and, through
  inheritance, all specific auth exceptions), so consumers can catch all
  library-specific errors with a single type.
- `.editorconfig` for consistent editor settings across the project.

### Changed

- Added `declare(strict_types=1)` to all source files that were missing it.

## [1.2.0] - 2026-10-16

### Added

- `EmailVerificationService` and email-verification flow.
- Domain events: `UserLoggedInEvent`, `PasswordChangedEvent`, and `UserEmailVerifiedEvent`.
- Integration tests for `PdoTokenRepository`.

### Changed

- Hardened `AuthenticationService`, `RegistrationService`, `ProfileManager`, and
  `PasswordResetService` for production-readiness.
- Improved `PdoTokenRepository` and `PdoRbacRepository` durability and portability.
- Added the event dispatcher integration to the Dockerfile and Composer setup.

## [1.1.1] - 2026-09-21

### Changed

- Added a Rector configuration for code quality and type-declaration checks.

## [1.1.0] - 2026-09-18

### Added

- Role-Based Access Control: `Role` and `Permission` entities, `RbacManager`, and
  `RbacRepositoryInterface` (with a PDO implementation).

## [1.0.1] - 2026-09-18

### Fixed

- Token verification query now compares against the current time.

## [1.0.0] - 2026-09-17

### Added

- Initial release: `AuthenticationService`, `RegistrationService`, `PasswordResetService`,
  and `ProfileManager`.
- `NativePasswordHasher` (`PasswordHasherInterface`), PDO repositories for users, and
  domain events (`UserRegisteredEvent`, `PasswordResetRequestedEvent`, `UserStatusChangedEvent`).
- Unit tests for authentication, password reset, and profile management.
