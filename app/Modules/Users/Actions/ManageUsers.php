<?php

namespace App\Modules\Users\Actions;

use App\Models\User;

final class ManageUsers
{
    public function __construct(
        private readonly CreateUser $createUser,
        private readonly UpdateUser $updateUser,
        private readonly SuspendUser $suspendUser,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): User
    {
        return $this->createUser->execute($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $target, array $data): User
    {
        return $this->updateUser->execute($actor, $target, $data);
    }

    public function suspend(User $actor, User $target): ?string
    {
        return $this->suspendUser->execute($actor, $target);
    }
}
