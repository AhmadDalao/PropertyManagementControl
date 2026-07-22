<?php

namespace App\Modules\Leases\Data;

use App\Models\Lease;
use App\Models\User;

final readonly class LeaseDetailData
{
    public function __construct(
        public Lease $lease,
        public User $actor,
        public bool $adminMode,
    ) {}
}
