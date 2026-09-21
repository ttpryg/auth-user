<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Contracts;

use DateTimeInterface;

interface AuthenticatableInterface
{
    public function getAuthIdentifier(): int|string;

    public function getUsername(): ?string;

    public function getEmail(): string;

    public function getPasswordHash(): string;

    public function isActive(): bool;

    public function getMetadata(): array;

    public function getRoles(): array;

    public function hasRole(string|array $roles): bool;

    public function getPermissions(): array;

    public function hasPermission(string $permission): bool;

    public function getCreatedAt(): ?DateTimeInterface;

    public function getUpdatedAt(): ?DateTimeInterface;

    public function getDeletedAt(): ?DateTimeInterface;
}
