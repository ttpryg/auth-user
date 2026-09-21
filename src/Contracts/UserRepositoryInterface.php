<?php

declare(strict_types=1);

namespace Ttpryg\AuthUser\Contracts;

use Ttpryg\AuthUser\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int|string $id, bool $includeTrashed = false): ?User;

    public function findByEmail(string $email, bool $includeTrashed = false): ?User;

    public function findByUsername(string $username, bool $includeTrashed = false): ?User;

    public function save(User $user): User;

    public function update(User $user): bool;

    public function delete(int|string $id, bool $softDelete = true): bool;

    public function restore(int|string $id): bool;
}
