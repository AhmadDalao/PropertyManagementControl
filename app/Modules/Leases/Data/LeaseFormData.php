<?php

namespace App\Modules\Leases\Data;

use App\Models\Lease;
use App\Models\User;

final readonly class LeaseFormData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<int, array{value:int,label:string}>  $portfolios
     * @param  array<int, array{value:int,label:string}>  $tenants
     * @param  array<int, array{value:int,label:string}>  $assets
     */
    public function __construct(
        public User $actor,
        public ?Lease $lease,
        public array $defaults,
        public ?int $portfolioId,
        public array $portfolios,
        public array $tenants,
        public array $assets,
    ) {}
}
