<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;

final class ManageLeases
{
    public function __construct(
        private readonly CreateLease $create,
        private readonly UpdateLease $update,
        private readonly TerminateLease $terminate,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Lease
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, Lease $lease, array $data): Lease
    {
        return $this->update->handle($actor, $lease, $data);
    }

    public function terminate(User $actor, Lease $lease): Lease
    {
        return $this->terminate->handle($actor, $lease);
    }
}
